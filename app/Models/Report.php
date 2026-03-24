<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    // 允许批量赋值的字段
    protected $fillable = ['user_id', 'post_id', 'reason', 'content', 'status'];

    // 关联举报者
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 关联被举报的帖子
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}