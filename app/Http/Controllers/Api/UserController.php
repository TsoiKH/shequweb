<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Models\Like;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * 获取当前登录用户信息
     */
    public function me(Request $request)
    {
        return response()->json(['code' => 200, 'data' => $request->user()]);
    }

    /**
     * 获取用户个人资料
     */
    public function profile($id = null)
    {
        // 1. 获取当前登录用户 (Sanctum 模式)
        $me = auth('sanctum')->user();
        $targetId = $id ?: ($me ? $me->id : null);

        if (!$targetId) {
            return response()->json(['code' => 401, 'msg' => '未指定用户ID且未登录']);
        }

        // 2. 核心查询：统计 关注数、粉丝数、帖子数
        $user = User::withCount(['followings', 'followers', 'posts'])->find($targetId);

        if (!$user) {
            return response()->json(['code' => 404, 'msg' => '用户不存在']);
        }

        // 3. 统计获赞总数
        $totalLikedCount = Post::where('user_id', $targetId)->sum('like_count');

        // 4. 判断“我”是否关注了该用户
        $isFollowed = false;
        if ($me && $me->id != $targetId) {
            $isFollowed = DB::table('follows')
                ->where('user_id', $me->id)
                ->where('following_id', $targetId)
                ->exists();
        }

        // 5. 组装返回 (修正 JSON 结构)
        return response()->json([
            'code' => 200,
            'msg'  => '获取成功',
            'data' => [
                'id'                => $user->id,
                'nickname'          => $user->nickname,
                'email'             => $user->email, 
                'avatar'            => $user->avatar,
                'city'              => $user->city,
                'bio'               => $user->bio ?? '还没有填写简介',
                'posts_count'       => $user->posts_count,
                'followings_count'  => $user->followings_count,
                'followers_count'   => $user->followers_count,
                'total_liked_count' => (int)$totalLikedCount, // 确保是整数
                'is_followed'       => $isFollowed,
                'is_me'             => $me ? ($me->id == $user->id) : false,
            ]
        ]);
    }

/**
     * 关注/取消关注
     */
    public function toggleFollow(Request $request, $id)
    {
        $me = $request->user();
        if ($me->id == $id) {
            return response()->json(['code' => 400, 'msg' => '你不能关注你自己']);
        }

        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['code' => 404, 'msg' => '目标用户不存在']);
        }

        return DB::transaction(function () use ($me, $id) {
            
            // toggle 会返回被附加 (attached) 或删除 (detached) 的 ID 数组
            $result = $me->followings()->toggle($id);
            $isFollowed = count($result['attached']) > 0;

            // 只有当“关注成功”时，才需要处理通知
            if ($isFollowed) {
                $exists = Notification::where('user_id', $id)
                    ->where('sender_id', $me->id)
                    ->where('type', 'follow')
                    ->where('is_read', false)
                    ->exists();

                if (!$exists) {
                    Notification::create([
                        'user_id'   => $id, 
                        'sender_id' => $me->id,   
                        'type'      => 'follow',
                    ]);
                }
            }

            return response()->json([
                'code' => 200,
                'msg'  => $isFollowed ? '关注成功' : '已取消关注',
                'data' => ['status' => $isFollowed ? 'followed' : 'unfollowed']
            ]);
        });
    }

    /**
     * 更新个人资料
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'nickname' => 'sometimes|string|max:20|unique:users,nickname,' . $user->id,
            'avatar'   => 'nullable|string',
            'bio'      => 'nullable|string|max:255',
            'city'     => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $user) {
            
            if ($request->filled('avatar') && $request->avatar !== $user->avatar) {
                if ($user->avatar) {
                    $relativePath = str_replace(asset('storage') . '/', '', $user->avatar);
                    if (Storage::disk('public')->exists($relativePath)) {
                        Storage::disk('public')->delete($relativePath);
                    }
                }
            }

            $user->update($request->only(['nickname', 'bio', 'avatar', 'city']));

            return response()->json([
                'code' => 200, 
                'msg' => '更新成功', 
                'data' => $user
            ]);
        });
    }

    /**
     * 上传头像文件
     * POST /api/users/upload-avatar
     */
    public function uploadAvatar(Request $request)
    {
        // 1. 验证文件是否合法 (jpg, png, jpeg, gif, 不超过 2MB)
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            
            // 2. 将文件保存到 storage/app/public/avatars 目录下
            // Laravel 会自动生成随机文件名，防止冲突
            $path = $file->store('avatars', 'public');

            // 3. 生成可访问的完整 URL
            $url = Storage::url($path);

            return response()->json([
                'code' => 200,
                'msg' => '上传成功',
                'data' => [
                    'avatar_url' => $url, // 把这个 URL 给 updateProfile 方法
                ]
            ]);
        }

        return response()->json(['code' => 400, 'msg' => '文件上传失败']);
    }

    /**
     * 统一列表格式化 (粉丝/关注)
     */
/**
     * 统一列表格式化 (粉丝/关注)
     */
    protected function formatUserList($list)
    {
        $me = auth('sanctum')->user();
        $isFollowedIds = [];

        if ($me && method_exists($list, 'getCollection')) {
            $userIds = $list->getCollection()->pluck('id')->toArray();
            
            if (!empty($userIds)) {
                $isFollowedIds = DB::table('follows')
                    ->where('user_id', $me->id)
                    ->whereIn('following_id', $userIds)
                    ->pluck('following_id')
                    ->toArray();
            }
        }

        if (method_exists($list, 'getCollection')) {
            $list->getCollection()->transform(function($user) use ($isFollowedIds) {
                $user->is_followed = in_array((int)$user->id, array_map('intval', $isFollowedIds));
                return $user;
            });
        }

        return response()->json(['code' => 200, 'data' => $list]);
    }

    public function followings($id) {
        $user = User::findOrFail($id);
        $list = $user->followings()->select('users.id', 'nickname', 'avatar', 'bio')->paginate(20);
        return $this->formatUserList($list);
    }

    public function followers($id) {
        $user = User::findOrFail($id);
        $list = $user->followers()->select('users.id', 'nickname', 'avatar', 'bio')->paginate(20);
        return $this->formatUserList($list);
    }
}