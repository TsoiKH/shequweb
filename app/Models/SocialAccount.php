<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    // 指定表名
    protected $table = 'social_accounts';

    // 允许批量赋值的字段
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'avatar'
    ];

    /**
     * 关联到主用户表
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}