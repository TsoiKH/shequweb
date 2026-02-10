<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // 1. 发送消息
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content'     => 'required|string|max:1000',
        ]);

        $message = Message::create([
            'sender_id'   => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'content'     => $request->content,
            'is_read'     => 0
        ]);

        return response()->json(['code' => 200, 'msg' => '发送成功', 'data' => new MessageResource($message)]);
    }

    // 2. 聊天详情（增量拉取）
    public function chatHistory(Request $request, $userId)
    {
        $myId = auth()->id();
        // last_id 是实时性的关键：前端传本地已有的最大 ID
        $lastId = $request->get('last_id', 0);

        $messages = Message::where(function($q) use ($myId, $userId) {
                $q->where('sender_id', $myId)->where('receiver_id', $userId);
            })
            ->orWhere(function($q) use ($myId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $myId);
            })
            ->where('id', '>', $lastId) // 核心：只拉取新消息
            ->orderBy('id', 'asc')
            ->get();

        // 自动已读处理
        Message::where('sender_id', $userId)
            ->where('receiver_id', $myId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json(['code' => 200, 'data' => MessageResource::collection($messages)]);
    }

    // 3. 会话列表
    public function conversationList()
    {
        $myId = auth()->id();

        // 获取包含我的最新消息，按人分组
        $messages = Message::where('sender_id', $myId)
            ->orWhere('receiver_id', $myId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique(function ($m) use ($myId) {
                // 确保 A->B 和 B->A 在列表中只占一行
                return $m->sender_id == $myId ? $m->receiver_id : $m->sender_id;
            });

        return response()->json(['code' => 200, 'data' => MessageResource::collection($messages)]);
    }
}