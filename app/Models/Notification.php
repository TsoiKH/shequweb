<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'sender_id', 'type', 'post_id', 'comment_id', 'content', 'is_read'];

    // 发送者信息
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id')->select(['id', 'nickname', 'avatar']);
    }

    // 关联的帖子
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id')->select(['id', 'title']);
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
}