<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    // 允许批量写入的字段
    protected $fillable = ['user_id', 'post_id', 'parent_id', 'content', 'like_count'];

    /**
     * 关联发布者
     */
    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'nickname', 'avatar');
    }

    /**
     * 关联所属帖子
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * 关联子评论（回复）
     */
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }
}