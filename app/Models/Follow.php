<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    protected $fillable = ['user_id', 'following_id'];

    /**
     * 关联粉丝（发起关注的人）
     */
    public function follower()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联偶像（被关注的人）
     */
    public function following()
    {
        return $this->belongsTo(User::class, 'following_id');
    }
}