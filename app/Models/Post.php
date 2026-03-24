<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'user_id', 
        'title', 
        'content', 
        'media_type', 
        'media_urls', 
        'country', 
        'city', 
        'status',
        'ip_address',
        'address', 
        'collect_count',
    ];

    /**
     * 属性类型转换
     * 这样你存入数组时会自动 json_encode，取出时自动 json_decode
     */
    protected $casts = [
        'media_urls' => 'array',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class)->select(['id', 'nickname', 'avatar']);
    }

    /**
     * 帖子下的评论
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    /**
     * 帖子的点赞记录
     */
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * 判断当前用户是否点赞
     */
    public function isLikedBy($user)
    {
        if (!$user) return false;
    
        if ($this->relationLoaded('likes')) {
            return $this->likes->contains('user_id', $user->id);
        }
    
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * 关联标签 (多对多)
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    /**
     * 关联收藏 (一对多)
     */
    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    public function scopeWithUserStats($query, $myId)
    {
        return $query->withCount([
            'likes as is_liked' => function($q) use ($myId) {
                $q->where('user_id', $myId);
            },
            'collections as is_collected' => function($q) use ($myId) {
                $q->where('user_id', $myId);
            }
        ]);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function pendingReports()
    {
        return $this->hasMany(Report::class)->where('status', 0);
    }
}