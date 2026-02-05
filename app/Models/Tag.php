<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'use_count'];

    /**
     * 关联到帖子 (多对多)
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag');
    }
}