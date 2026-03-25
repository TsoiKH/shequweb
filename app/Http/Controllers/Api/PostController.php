<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Resources\CommentResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Collection;
use App\Models\Notification;
use App\Models\User;
use App\Models\Tag;
use App\Models\SensitiveWord;
use Stevebauman\Location\Facades\Location;

class PostController extends Controller
{
    /**
     * 发布新帖子
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // 1. 基础验证
        $request->validate([
            'title'      => 'required|max:100',
            'content'    => 'required',
            'media_urls' => 'required|array|min:1|max:18',
            'tags'       => 'nullable|array',
            'city'       => 'nullable|string',
        ]);

        // --- 敏感词处理系统 (数据库驱动) ---
        $title = $request->title;
        $content = $request->content;
        
        // 使用缓存 1 小时，提高性能
        $sensitiveWords = Cache::remember('sensitive_words_list', 3600, function () {
            return SensitiveWord::all(['word', 'type']);
        });
        
        foreach ($sensitiveWords as $sw) {
            $word = $sw->word;
            
            if ($sw->type === 'block') {
                // A. 拦截模式：大小写不敏感匹配
                if (mb_stripos($title, $word) !== false || mb_stripos($content, $word) !== false) {
                    return response()->json(['code' => 422, 'msg' => "包含违规词: 【{$word}】"], 422);
                }
            } elseif ($sw->type === 'replace') {
                // B. 替换模式：大小写不敏感替换
                $replacement = str_repeat('*', mb_strlen($word));
                $title = str_ireplace($word, $replacement, $title);
                $content = str_ireplace($word, $replacement, $content);
            }
        }

        // --- IP 定位逻辑 ---
        $ip = $request->ip();
        // 生产环境务必移除测试 IP 逻辑
        $testIp = (app()->environment('local') && $ip === '127.0.0.1') ? '62.157.140.133' : $ip;
        $position = Location::get($testIp);

        // --- 媒体分类 ---
        $mediaUrls = $request->input('media_urls', []);
        if (empty($mediaUrls)) {
            $mediaType = 'text'; 
        } else {
            $firstFile = $mediaUrls[0];
            $mediaType = preg_match('/\.(mp4|mov|avi|wmv)$/i', $firstFile) ? 'video' : 'image';
        }

        // 2. 创建帖子
        $post = Post::create([
            'user_id'    => $user->id,
            'title'      => $title,
            'content'    => $content,
            'media_urls' => $request->media_urls,
            'media_type' => $mediaType,
            'country'    => $position ? $position->countryName : 'Germany',
            'city'       => $request->city ?: ($position ? $position->cityName : 'Unknown'),
            'ip_address' => $ip,
            'status'     => 1,
        ]);

        // --- 3. 智能标签增强逻辑 ---
        $tagsToSync = $request->tags ?? [];
        $contentToScan = $title . $content;
        
        // A. 自动从内容提取 #话题
        preg_match_all('/#([^#\s\x{FF03}]+)/u', $content, $matches);
        if (!empty($matches[1])) {
            $tagsToSync = array_merge($tagsToSync, $matches[1]);
        }

        // B. 如果用户没给标签，或为了增加聚合维度，匹配核心字典
        // 这些词就是你原来的“版块名”
        $coreDictionary = [
            '新闻', '找房', '租房', '招聘', '求职', '美食', '二手', 
            '留学', '新生', '交友', '心情', '攻略', '德国'
        ];
        foreach ($coreDictionary as $coreWord) {
            if (str_contains($contentToScan, $coreWord)) {
                $tagsToSync[] = $coreWord;
            }
        }

        // 4. 同步标签 (封装成 Unique 数组)
        if (!empty($tagsToSync)) {
            $tagsToSync = array_unique($tagsToSync);
            $tagIds = [];
            foreach ($tagsToSync as $tagName) {
                $tagName = mb_substr(trim($tagName), 0, 20); // 限制标签长度
                if (empty($tagName)) continue;

                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tag->increment('use_count');
                $tagIds[] = $tag->id;
            }
            $post->tags()->sync($tagIds);
            Cache::forget('hot_tags_list');
        }

        return response()->json([
            'code' => 200, 
            'msg'  => '发布成功', 
            'data' => new PostResource($post->load(['user', 'tags']))
        ]);
    }
    

    // 上传图片接口
    public function upload(Request $request)
    {
    
        $request->validate([
            'files'   => 'required|array',
            'files.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);
    
        $urls = [];
    
        // 2. 直接从请求中获取文件数组
        $files = $request->file('files');
    
        foreach ($files as $file) {
            if ($file->isValid()) {
                $path = $file->store('posts', 'public');
                $urls[] = asset('storage/' . $path);
            }
        }
    
        return response()->json([
            'code' => 200,
            'msg'  => '上传成功',
            'data' => [
                'urls' => $urls 
            ]
        ]);
    }

    public function index(Request $request)
    {
        $myId = $this->getAuthUserId();
    
        // 获取前端传来的当前城市 (用于推荐排序)
        $currentCity = $request->get('city'); 
    
        $query = Post::with([
                'user' => function($query) use ($myId) {
                    $query->withCount(['followers as is_followed' => function($q) use ($myId) {
                        $q->where('follows.user_id', $myId); 
                    }]);
                },
                'tags' 
            ])
            ->withCount([
                'likes as is_liked' => function($q) use ($myId) {
                    $q->where('user_id', $myId);
                },
                'collections as is_collected' => function($q) use ($myId) {
                    $q->where('user_id', $myId);
                }
            ])
            //作者自主状态必须是公开 (status = 1)
            ->where('status', 1);
    
        // --- 核心过滤排除系统封禁的内容 ---
        // 逻辑：如果 reports 表中存在该帖子的记录且 status 为 1，则不显示
        $query->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('reports')
              ->whereColumn('reports.post_id', 'posts.id')
              ->where('reports.status', 1);
        });
    
        // 标签硬筛选
        if ($request->filled('tag')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('name', $request->tag);
            });
        }
    
        // --- 核心排序逻辑开始 ---
    
        // A. 第一优先级：同城优先 (同城权重 0，非同城 1，ASC 排序让 0 在前)
        if ($currentCity) {
            $query->orderByRaw("CASE WHEN city = ? THEN 0 ELSE 1 END ASC", [$currentCity]);
        }
    
        // B. 第二优先级：全局热度 (点赞数从高到低)
        $query->orderBy('like_count', 'desc');
    
        // C. 第三优先级：新鲜度 (最新发布)
        $query->orderBy('created_at', 'desc');
    
        // --- 核心排序逻辑结束 ---
    
        $posts = $query->paginate(10);
    
        return PostResource::collection($posts);
    }

    /**
     * 切换点赞状态 (点赞/取消点赞)
     */
    public function toggleLike(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);
    
        // 使用事务保证 Like 记录和 Post 计数器的同步
        $result = DB::transaction(function () use ($user, $post) {
            $like = Like::where('user_id', $user->id)
                ->where('likeable_id', $post->id)
                ->where('likeable_type', Post::class)
                ->first();
    
            if ($like) {
                // 取消点赞
                $like->delete();
                $post->decrement('like_count');
                return ['status' => false, 'count' => $post->like_count];
            } else {
                // 点赞
                Like::create([
                    'user_id'       => $user->id,
                    'likeable_id'   => $post->id,
                    'likeable_type' => Post::class,
                ]);
                $post->increment('like_count');
    
                if ($post->user_id !== $user->id) {
                    Notification::create([
                        'user_id'   => $post->user_id,
                        'sender_id' => $user->id,
                        'type'      => 'like_post',
                        'post_id'   => $post->id,
                    ]);
                }
                return ['status' => true, 'count' => $post->like_count];
            }
        });
    
        return response()->json([
            'code' => 200,
            'msg'  => $result['status'] ? '点赞成功' : '取消点赞成功',
            'data' => [
                'is_liked'   => $result['status'],
                'like_count' => $result['count']
            ]
        ]);
    }



    /**
     * 获取帖子详情
     */
    public function show(Request $request, $id)
    {
        $myId = $this->getAuthUserId();
    
        // 1. 获取基础数据，同时使用 withCount 预加载评论总数
        $post = Post::with(['user', 'tags'])
            ->withCount('comments') 
            ->withUserStats($myId)
            ->find($id);
    
        if (!$post) {
            return response()->json(['code' => 404, 'msg' => '帖子不存在'], 404);
        }
    
        // 2. 状态检查逻辑 (保持你原有的逻辑)
        $isBanned = DB::table('reports')->where('post_id', $id)->where('status', 1)->exists();
        if ($isBanned) {
            return response()->json(['code' => 403, 'msg' => '该帖子因违规已被系统封禁'], 403);
        }
        if ($post->status !== 1 && $myId !== $post->user_id) {
            return response()->json(['code' => 403, 'msg' => '作者已将该帖子设为私密'], 403);
        }
    
        // 3. 阅读量防刷逻辑 (保持你原有的逻辑)
        $ip = $request->ip();
        $cacheKey = "post_viewed_{$id}_{$ip}";
        if (!Cache::has($cacheKey)) {
            $post->increment('view_count');
            Cache::put($cacheKey, true, now()->addHours(1));
        }

        /**
         * 4. 核心改动：获取评论区 (支持二级预览)
         * - 只选 parent_id 为空或 0 的顶级评论
         * - 预加载每个顶级评论的子评论预览 (replies)
         */
        $comments = Comment::with(['user', 'replies' => function($q) use ($myId) {
                // 子评论也需要加载用户信息，并且只取前 3 条作为预览
                $q->with('user')->orderBy('created_at', 'asc')->limit(3);
            }])
            ->withCount('replies') // 统计每条主评论下总共有多少条回复
            ->where('post_id', $id)
            ->where(function($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20); // 评论使用分页加载

        // 5. 组装返回
        // 我们将 PostResource 和 CommentResource 结合
        $resource = new PostResource($post);
        
        // 将分页后的评论注入到 Resource 的额外数据中
        return $resource->additional([
            'comments' => CommentResource::collection($comments)->response()->getData(true)
        ])->response()->setStatusCode(200);
    }

    /**
     * 获取我关注的人发的帖子
     */
    public function followedPosts(Request $request)
    {
        $user = $request->user();
        $myId = $user->id;
    
        $followingIds = $user->followings()->pluck('following_id');
    
        if ($followingIds->isEmpty()) {
            return PostResource::collection(Post::whereRaw('1=0')->paginate(10));
        }
    
        // 3. 查询帖子
        $posts = Post::with([
                'user' => function($query) use ($myId) {
                    $query->withCount(['followers as is_followed' => function($q) use ($myId) {
                        $q->where('user_id', $myId);
                    }]);
                },
                'tags'
            ])
            ->withUserStats($myId) 
            ->whereIn('user_id', $followingIds)
            ->where('status', 1)
            ->latest()
            ->paginate(10);
    
        return PostResource::collection($posts);
    }

    /**
     * 智能搜索接口 (融合小红书模式：动态词条 + 多维筛选)
     * Route: GET /api/posts/search
     */
    public function search(Request $request)
    {
        // 1. 获取并清洗参数
        $rawKeyword = $request->query('keyword');
        $keyword = trim($rawKeyword); // 核心：去除首尾空格，确保存储和查询一致
        $sort = $request->query('sort', 'latest');    // 排序模式
        $currentCity = $request->query('city');      // 当前定位
        $selectedTags = $request->query('tags', []); // 已选的次级标签 (数组)

        if (empty($keyword)) {
            return response()->json(['code' => 400, 'msg' => '请输入关键词']);
        }

        $myId = $this->getAuthUserId();

        // 2. 构建基础查询
        $query = Post::with(['user', 'tags'])
            ->withUserStats($myId)
            ->where('status', 1);

        // 3. 关键词匹配 (搜索标题、内容或关联标签)
        $query->where(function($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
            ->orWhere('content', 'like', "%{$keyword}%")
            ->orWhereHas('tags', function($t) use ($keyword) {
                $t->where('name', 'like', "%{$keyword}%");
            });
        });

        // 4. 次级标签多重筛选 (并集筛选)
        if (!empty($selectedTags) && is_array($selectedTags)) {
            foreach ($selectedTags as $tag) {
                $query->whereHas('tags', function($t) use ($tag) {
                    $t->where('name', $tag);
                });
            }
        }

        // 5. 灵活排序逻辑
        $sortConfig = [
            'latest'   => '最新',
            'hot'      => '最多人喜欢', 
            'view'     => '最多人看',   
            'location' => '附近'
        ];

        switch ($sort) {
            case 'hot': 
                $query->orderBy('like_count', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'view': 
                $query->orderBy('view_count', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'location': 
                if ($currentCity) {
                    $query->orderByRaw("CASE WHEN city = ? THEN 0 ELSE 1 END ASC", [$currentCity]);
                }
                $query->orderBy('created_at', 'desc');
                break;
            default: // latest
                $query->latest();
                break;
        }

        // 6. 执行分页
        $posts = $query->paginate(15);

        // 7. 动态生成次级筛选词 (在当前搜索结果中寻找高频标签)
        $postIds = $posts->pluck('id');
        $dynamicTags = [];
        if ($postIds->isNotEmpty()) {
            $dynamicTags = DB::table('post_tag')
                ->join('tags', 'post_tag.tag_id', '=', 'tags.id')
                ->whereIn('post_id', $postIds)
                ->where('tags.name', '!=', $keyword) // 排除掉主搜索词
                ->whereNotIn('tags.name', (array)$selectedTags) // 排除掉已选中的词
                ->select('tags.name', DB::raw('count(*) as total'))
                ->groupBy('tags.name')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->pluck('name');
        }

        // 8. 记录搜索历史 (仅针对已登录用户，且有结果时记录)
        if ($myId > 0 && $posts->total() > 0) {
            // 使用 updateOrInsert 避免重复记录，同时保持 count 累加
            $history = DB::table('search_histories')
                ->where('user_id', $myId)
                ->where('keyword', mb_substr($keyword, 0, 30))
                ->first();

            if ($history) {
                DB::table('search_histories')
                    ->where('id', $history->id)
                    ->update([
                        'count' => $history->count + 1,
                        'updated_at' => now()
                    ]);
            } else {
                DB::table('search_histories')->insert([
                    'user_id'    => $myId,
                    'keyword'    => mb_substr($keyword, 0, 30),
                    'count'      => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return response()->json([
            'code' => 200,
            'msg'  => '搜索成功',
            'data' => [
                'posts' => PostResource::collection($posts)->response()->getData(true),
                'menus' => [
                    'sort_options' => $sortConfig,
                    'dynamic_tags' => $dynamicTags
                ]
            ]
        ]);
    }


    public function destroy(Request $request, $id)
    {
        // 1. 查找帖子
        $post = Post::findOrFail($id);

        // 2. 权限校验：只有作者本人才能删除
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['code' => 403, 'msg' => '你没有权限删除此帖子']);
        }

        // 3. 核心逻辑：清理物理图片文件
        // 这里的 media_urls 会因为模型 casts 自动变成数组
        if (!empty($post->media_urls)) {
            foreach ($post->media_urls as $url) {
                $path = str_replace(asset('storage/'), '', $url);
                
                // 执行删除
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        // 4. 删除数据库记录（关联的点赞和评论建议在数据库设置级联删除，或者在此手动清理）
        $post->delete();

        return response()->json([
            'code' => 200,
            'msg'  => '帖子已成功删除，相关资源已清理'
        ]);
    }



    public function userPosts(Request $request, $userId)
    {
        $me = auth('sanctum')->user();
        $isMe = $me && $me->id == $userId;
    
        return $this->getPostList($request, function($query) use ($userId) {
            $query->where('user_id', $userId);
        }, $isMe);
    }

    public function toggleCollect(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        return DB::transaction(function () use ($user, $post) {
            $collection = Collection::where('user_id', $user->id)->where('post_id', $post->id)->first();

            if ($collection) {
                $collection->delete();
                $post->decrement('collect_count');
                $isCollected = false;
            } else {
                Collection::create(['user_id' => $user->id, 'post_id' => $post->id]);
                $post->increment('collect_count');
                $isCollected = true;

                if ($post->user_id !== $user->id) {
                    Notification::create([
                        'user_id'   => $post->user_id,
                        'sender_id' => $user->id,
                        'type'      => 'collect', 
                        'post_id'   => $post->id,
                    ]);
                }
            }

            return response()->json([
                'code' => 200, 
                'msg'  => $isCollected ? '收藏成功' : '已取消收藏',
                'data' => ['is_collected' => $isCollected]
            ]);
        });
    }



    public function getComments(Request $request, $id)
    {
        $myId = $this->getAuthUserId();
    
        $comments = Comment::with([
                'user', 
                'replies' => function($q) use ($myId) {
                    $q->with('user')
                      ->withCount(['likes as is_liked' => function($l) use ($myId) {
                          $l->where('user_id', $myId);
                      }])
                      ->limit(5);
                }
            ])
            ->where('post_id', $id)
            ->where('parent_id', 0)
            ->withCount(['likes as is_liked' => function($q) use ($myId) {
                $q->where('user_id', $myId);
            }])
            ->latest()
            ->paginate(15);
    
        return CommentResource::collection($comments);
    }

    //我收藏的列表
    public function userCollections(Request $request)
    {
        $myId = $request->user()->id;
        return $this->getPostList($request, function($query) use ($myId) {
            // 过滤：只找被我收藏过的帖子
            $query->whereHas('collections', function($q) use ($myId) {
                $q->where('user_id', $myId);
            });
        }, false); // 收藏夹里不显示作者已设为私密的帖子
    }

    //我喜欢的列表
    public function userLiked(Request $request)
    {
        $myId = $request->user()->id;
        return $this->getPostList($request, function($query) use ($myId) {
            // 过滤：只找被我点赞过的帖子
            $query->whereHas('likes', function($q) use ($myId) {
                $q->where('user_id', $myId);
            });
        }, false);
    }
    /**
     * 通用的帖子查询器
     */
    private function getPostList(Request $request, $filterCallback = null, $showPrivate = false)
    {
        $myId = $this->getAuthUserId();
    
        // 1. 基础关联预加载
        $query = Post::with([
            'user' => function ($q) use ($myId) {
                // 在获取帖子作者时，顺便判断“我”是否关注了作者
                $q->withCount(['followers as is_followed' => function ($sq) use ($myId) {
                    $sq->where('user_id', $myId);
                }]);
            },
            'tags'
        ])
        ->withUserStats($myId); // 调用您在模型里写的 Scope
    
        // 2. 状态过滤逻辑
        if ($showPrivate) {
            // 用于“我的发布”：作者本人可以看到“显示(1)”和“隐藏(0)”的帖子
            $query->whereIn('status', [0, 1]);
        } else {
            // 用于“发现页”、“他人主页”、“收藏夹”：只能看到状态为 1 的
            $query->where('status', 1);
        }
    
        // 3. 执行闭包传入的特定过滤逻辑（如 userId, whereHas 等）
        if ($filterCallback instanceof \Closure) {
            $filterCallback($query);
        }
    
        // 4. 统一排序并分页
        $posts = $query->latest()->paginate(10);
    
        return PostResource::collection($posts);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['code' => 403, 'msg' => '无权修改']);
        }

        $request->validate([
            'title'      => 'sometimes|max:100',
            'content'    => 'sometimes',
            'media_urls' => 'sometimes|array|min:1|max:18',
            'tags'       => 'nullable|array',
        ]);

        // 【补强】清理被用户从编辑器中删掉的旧图片
        if ($request->filled('media_urls')) {
            $oldUrls = $post->media_urls; // 模型需配置 cast array
            $newUrls = $request->media_urls;
            $deletedUrls = array_diff($oldUrls, $newUrls);

            foreach ($deletedUrls as $url) {
                $path = str_replace(asset('storage/'), '', $url);
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $post->update($request->only(['title', 'content', 'media_urls', 'city', 'address']));

        // 更新标签关系
        if ($request->has('tags')) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $post->tags()->sync($tagIds);
        }

        return response()->json(['code' => 200, 'msg' => '修改成功', 'data' => new PostResource($post->load('tags'))]);
    }

    /**
     * 切换帖子的公开/隐藏状态 (管理功能)
     */
    public function toggleVisibility(Request $request, $id)
    {
        // 确保只能操作自己的帖子，且不能操作已删除(-1)的帖子
        $post = Post::where('user_id', $request->user()->id)
                    ->where('status', '>=', 0) 
                    ->findOrFail($id);

        // 逻辑：1 -> 0 (隐藏), 0 -> 1 (显示)
        $post->status = ($post->status == 1) ? 0 : 1;
        $post->save();

        return response()->json([
            'code' => 200,
            'msg'  => $post->status == 1 ? '帖子已设为公开' : '帖子已设为私密',
            'data' => ['status' => $post->status]
        ]);
    }


    /**
     * 获取热门搜索和个人历史
     * Route: GET /api/posts/search-discovery
     */
    public function searchDiscovery()
    {
        $me = auth('sanctum')->user();
        
        // 1. 全站热搜：10分钟缓存一次
        // 逻辑：统计近7天内非空关键词的聚合热度
        $hotSearches = Cache::remember('global_hot_searches', 600, function () {
            return DB::table('search_histories')
                ->select('keyword', DB::raw('SUM(count) as total_count'))
                ->where('updated_at', '>=', now()->subDays(7))
                ->whereRaw('LENGTH(keyword) > 0') 
                ->groupBy('keyword')
                ->orderBy('total_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    $item->total_count = (int) $item->total_count;
                    return $item;
                });
        });
    
        // 2. 个人历史：去重、过滤、并只取最近10条
        $myHistories = [];
        if ($me) {
            $myHistories = DB::table('search_histories')
                ->where('user_id', $me->id)
                ->whereRaw('LENGTH(keyword) > 0') // 确保不取出空字符串
                ->orderBy('updated_at', 'desc')
                ->limit(30) // 多取一点用于去重
                ->pluck('keyword')
                ->map(function($k) {
                    return trim($k);
                })
                ->unique() // 集合去重
                ->values()
                ->take(10) // 最终只给前端10条
                ->toArray();
        }
    
        return response()->json([
            'code' => 200,
            'msg'  => '获取成功',
            'data' => [
                'hot'     => $hotSearches,
                'history' => $myHistories
            ]
        ]);
    }

    /**
     * 清除个人搜索历史
     */
    public function clearSearchHistory()
    {
        // 获取当前登录用户
        $user = auth('sanctum')->user();
    
        if (!$user) {
            return response()->json(['code' => 401, 'msg' => '未登录']);
        }
    
        DB::table('search_histories')
            ->where('user_id', $user->id)
            ->delete();
    
        return response()->json([
            'code' => 200,
            'msg'  => '搜索历史已清空'
        ]);
    }

    /**
     * 举报帖子
     * POST /api/posts/{id}/report
     */
    public function report(Request $request, $id)
    {
        $user = $request->user();
        $request->validate(['reason' => 'required|string|max:100']);
        $post = Post::findOrFail($id);
    
        try {
            DB::table('reports')->insert([
                'user_id'    => $user->id,
                'post_id'    => $post->id,
                'reason'     => $request->reason,
                'content'    => $request->content ?? '',
                'status'     => 0, // 初始为待处理
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['code' => 400, 'msg' => '您已举报过该内容']);
        }
    
        $count = DB::table('reports')->where('post_id', $post->id)->count();
    
        if ($count >= 5) {
            DB::table('reports')
                ->where('post_id', $post->id)
                ->update(['status' => 1, 'updated_at' => now()]);
        }
    
        return response()->json(['code' => 200, 'msg' => '举报已受理', 'data' => ['reports' => $count]]);
    }

    /**
     * 获取分享所需元数据
     * GET /api/posts/{id}/share-ticket
     */
    public function shareTicket($id)
    {
        $post = Post::select('id', 'title', 'content', 'media_urls', 'user_id')
                    ->with('user:id,nickname')
                    ->findOrFail($id);

        // 处理描述：截取正文前 50 个字，去掉换行
        $description = mb_substr(strip_tags($post->content), 0, 50) . '...';
        
        // 处理缩略图：取第一张图
        $thumb = !empty($post->media_urls) ? $post->media_urls[0] : '';

        return response()->json([
            'code' => 200,
            'data' => [
                'title'       => "【来自{$post->user->nickname}】" . $post->title,
                'desc'        => $description,
                'link'        => "",
                'imgUrl'      => $thumb,
                'type'        => 'link', // 分享类型：链接
            ]
        ]);
    }

    /**
     * 获取某个主评论下的所有二级回复 (分页)
     */
    /**
     * 获取某个主评论下的所有二级回复 (分页)
     */


    /**
     * 获取当前登录用户ID
     */
    private function getAuthUserId()
    {
        $me = auth('sanctum')->user();
        return $me ? $me->id : 0;
    }

}