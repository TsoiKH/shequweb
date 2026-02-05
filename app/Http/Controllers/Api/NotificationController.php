<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Message;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // 获取通知列表
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->query('tab');
    
        $query = Notification::with(['sender', 'post', 'comment'])
                    ->where('user_id', $user->id);
    
        if ($tab === 'likes') {
            // 合流：点赞和收藏都放在这里
            $query->whereIn('type', ['like_post', 'like_comment', 'collect']);
        } elseif ($tab === 'comments') {
            $query->where('type', 'comment');
        } elseif ($tab === 'follows') {
            $query->where('type', 'follow');
        }
    
        $notifications = $query->latest()->paginate(20);
    
        // 标记当前页为已读
        $ids = collect($notifications->items())->pluck('id');
        if ($ids->isNotEmpty()) {
            Notification::whereIn('id', $ids)->update(['is_read' => 1]);
        }
    
        return NotificationResource::collection($notifications);
    }

    // 获取未读消息统计（用于在 App 底部显示数字红点）
    public function unreadCount(Request $request)
    {
        $userId = $request->user()->id;
        
        $stats = Notification::where('user_id', $userId)
            ->where('is_read', 0)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN type IN ('like_post', 'like_comment', 'collect') THEN 1 ELSE 0 END) as likes,
                SUM(CASE WHEN type = 'comment' THEN 1 ELSE 0 END) as comments,
                SUM(CASE WHEN type = 'follow' THEN 1 ELSE 0 END) as follows
            ")
            ->first();

        $unreadMessages = Message::where('receiver_id', $userId)->where('is_read', 0)->count();

        return response()->json([
            'code' => 200,
            'data' => [
                'total' => (int)$stats->total + $unreadMessages,
                'likes' => (int)$stats->likes, // 这里包含了收藏的未读数
                'direct_messages' => $unreadMessages, // 私信未读数
                'comments' => (int)$stats->comments,
                'follows' => (int)$stats->follows,
            ]
        ]);
    }
}