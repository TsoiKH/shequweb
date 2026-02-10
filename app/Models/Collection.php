<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = ['user_id', 'post_id'];

    /**
     * 关联到所属用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联到被收藏的帖子
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}