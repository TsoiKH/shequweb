<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;


    protected $fillable = [
        'nickname',
        'email',         
        'country_code', 
        'phone',         
        'password',
        'avatar',
        'city',
        'bio',
        'ip_address',
    ];

    /**
     * 敏感字段：在返回 JSON 给前端时会被隐藏
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 我关注的人
    public function followings()
    {
        return $this->belongsToMany(User::class, 'follows', 'user_id', 'following_id');
    }

    // 关注我的人
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'user_id');
    }

    /**
     * 判断是否已经关注了某人
     */
    public function isFollowing($targetUserId)
    {
        if (!$targetUserId) return false;
        return $this->followings()->where('following_id', $targetUserId)->exists();
    }

    // 我的帖子
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function getNicknameAttribute($value)
    {
        return $value ?: '游客' . $this->id;
    }

    public function getCityAttribute($value)
    {
        return $value ?: '地球某个角落';
    }

    /**
     * 用户收藏的所有记录
     */
    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    /**
     * 直接获取用户收藏的所有帖子 (通过中间表 Collection)
     */
    public function collectedPosts()
    {
        return $this->belongsToMany(Post::class, 'collections', 'user_id', 'post_id');
    }

    /**
     * 获取用户关联的所有社交账号
     */
    public function socials()
    {
        return $this->hasMany(SocialAccount::class, 'user_id', 'id');
    }
}