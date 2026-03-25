<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    /**
     * 发表评论
     */
    public function store(Request $request, $postId)
    {
        $request->validate([
            'content'   => 'required|string|max:500',
            'parent_id' => 'nullable|integer', 
        ]);
    
        $post = Post::findOrFail($postId);
        $currentUser = $request->user();
        $parentComment = null;
    
        // 1. 检查父评论是否存在
        if ($request->filled('parent_id') && $request->parent_id > 0) {
            $parentComment = Comment::where('id', $request->parent_id)
                                    ->where('post_id', $postId)
                                    ->first();
            if (!$parentComment) {
                return response()->json(['code' => 400, 'msg' => '被回复的评论不存在']);
            }
        }
    
        // 2. 创建评论
        $comment = Comment::create([
            'user_id'   => $currentUser->id,
            'post_id'   => $postId,
            'parent_id' => $request->parent_id ?? 0,
            'content'   => $request->content,
        ]);
    
        // 3. 增加帖子评论计数
        $post->increment('comment_count');
    
        $targetUserId = 0;
        
        if ($parentComment) {
            // 场景 A: 这是一条“回复”。通知原评论的作者。
            $targetUserId = $parentComment->user_id;
        } else {
            // 场景 B: 这是一条“顶级评论”。通知帖子作者。
            $targetUserId = $post->user_id;
        }
    
        // 只有当接收者不是本人时，才插入通知记录
        if ($targetUserId > 0 && $targetUserId != $currentUser->id) {
            Notification::create([
                'user_id'    => $targetUserId,
                'sender_id'  => $currentUser->id,
                'type'       => 'comment',
                'post_id'    => $postId,
                'comment_id' => $comment->id,
                'content'    => $request->content,
                'is_read'    => 0,
            ]);
        }
    
        return response()->json([
            'code' => 200,
            'msg'  => '评论成功',
            'data' => new CommentResource($comment->load('user'))
        ]);
    }

    /**
     * 删除评论
     */
    public function destroy(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        $post = Post::findOrFail($comment->post_id);
    
        if ($comment->user_id !== $request->user()->id && $post->user_id !== $request->user()->id) {
            return response()->json(['code' => 403, 'msg' => '无权删除'], 403);
        }
    
        return DB::transaction(function () use ($comment, $post, $id) {
            $idsToDelete = Comment::where('id', $id)
                ->orWhere('parent_id', $id)
                ->pluck('id');
            
            $countToDelete = $idsToDelete->count();
            Comment::whereIn('id', $idsToDelete)->delete();
    
            Notification::where('post_id', $post->id)
                ->whereIn('comment_id', $idsToDelete)
                ->delete();
    
            $post->decrement('comment_count', $countToDelete);
    
            return response()->json(['code' => 200, 'msg' => '删除成功']);
        });
    }

    /**
     * 切换评论点赞状态
     */
    public function toggleLike(Request $request, $id)
    {
        $user = $request->user();
        $comment = Comment::findOrFail($id);
    
        $likeQuery = $comment->likes()->where('user_id', $user->id);
        $like = (clone $likeQuery)->first();
    
        if ($like) {
            $likeQuery->delete();
            $comment->decrement('like_count');
            $status = 'unliked';
        } else {
            $comment->likes()->create(['user_id' => $user->id]);
            $comment->increment('like_count');
            $status = 'liked';
    
            if ($comment->user_id != $user->id) {
                Notification::create([
                    'user_id'    => $comment->user_id,
                    'sender_id'  => $user->id,
                    'type'       => 'like_comment',
                    'post_id'    => $comment->post_id,
                    'comment_id' => $comment->id,
                ]);
            }
        }
    
        return response()->json([
            'code' => 200,
            'msg' => $status == 'liked' ? '点赞评论成功' : '已取消点赞',
            'data' => [
                'status' => $status,
                'current_count' => $comment->like_count
            ]
        ]);
    }

    /**
     * 获取某个主评论下的所有二级回复 (分页)
     */
    public function getReplies(Request $request, $id)
    {
        $parentComment = Comment::findOrFail($id);

        // 分页获取子评论
        $replies = Comment::with(['user'])
            ->where('parent_id', $id)
            ->orderBy('created_at', 'asc') // 二级回复通常按时间正序排列
            ->paginate(20);

        return response()->json([
            'code' => 200,
            'msg'  => '获取成功',
            'data' => [
                'parent_comment_id' => $id,
                'replies' => CommentResource::collection($replies)->response()->getData(true)
            ]
        ]);
    }
}
