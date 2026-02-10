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
        // 避开闭包里的复杂逻辑，先拿基础统计
        $user = User::withCount(['followings', 'followers', 'posts'])->find($targetId);

        if (!$user) {
            return response()->json(['code' => 404, 'msg' => '用户不存在']);
        }

        // 3. 统计获赞总数：从 posts 表直接求和，效率最高且最稳
        // 确保你的 posts 表有 like_count 字段
        $totalLikedCount = Post::where('user_id', $targetId)->sum('like_count');

        // 4. 判断“我”是否关注了该用户
        $isFollowed = false;
        if ($me && $me->id != $targetId) {
            $isFollowed = DB::table('follows')
                ->where('user_id', $me->id)
                ->where('following_id', $targetId)
                ->exists();
        }

        // 5. 组装返回
        return response()->json([
            'code' => 200,
            'msg'  => '获取成功',
            'data' => [
                'id'          => $user->id,
                'nickname'    => $user->nickname,
                'avatar'      => $user->avatar,
                'bio'         => $user->bio ?? '还没有填写简介',
                'city'        => $user->city,
                'stats'       => [
                    'following_count' => $user->followings_count,
                    'follower_count'  => $user->followers_count,
                    'post_count'      => $user->posts_count,
                    'total_liked'     => (int)$totalLikedCount,
                ],
                'is_followed' => $isFollowed,
                'is_me'       => $me ? ($me->id == $user->id) : false,
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

        // toggle 会返回被附加或删除的数组
        $result = $me->followings()->toggle($id);
        $isFollowed = count($result['attached']) > 0;

        if ($isFollowed) {
            Notification::create([
                'user_id'   => $id, 
                'sender_id' => $me->id,   
                'type'      => 'follow',
            ]);
        }

        return response()->json([
            'code' => 200,
            'msg'  => $isFollowed ? '关注成功' : '已取消关注',
            'data' => ['is_followed' => $isFollowed]
        ]);
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

        // 如果换了头像，尝试删除旧的物理文件
        if ($request->filled('avatar') && $request->avatar !== $user->avatar) {
            if ($user->avatar) {
                // 提取相对路径：例如从 http://localhost/storage/avatars/xxx.jpg 提取 avatars/xxx.jpg
                $relativePath = str_replace(asset('storage/'), '', $user->avatar);
                Storage::disk('public')->delete($relativePath);
            }
        }

        $user->update($request->only(['nickname', 'bio', 'avatar', 'city']));

        return response()->json(['code' => 200, 'msg' => '更新成功', 'data' => $user]);
    }

    /**
     * 统一列表格式化 (粉丝/关注)
     */
    protected function formatUserList($list)
    {
        $me = auth('sanctum')->user();
        $isFollowedIds = [];

        if ($me) {
            $userIds = $list->getCollection()->pluck('id')->toArray();
            $isFollowedIds = DB::table('follows')
                ->where('user_id', $me->id)
                ->whereIn('following_id', $userIds)
                ->pluck('following_id')
                ->toArray();
        }

        $list->getCollection()->transform(function($user) use ($isFollowedIds) {
            $user->is_followed = in_array($user->id, $isFollowedIds);
            return $user;
        });

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