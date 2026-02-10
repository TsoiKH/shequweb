<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    // 按我们的表规范，允许批量写入
    protected $fillable = ['user_id', 'likeable_id', 'likeable_type'];

    /**
     * 定义多态关联 (Polymorphic Relationship)
     * 这样这个 Like 模型既可以属于 Post，也可以属于 Comment
     */
    public function likeable()
    {
        return $this->morphTo();
    }
}