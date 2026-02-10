<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    // 指定表名
    protected $table = 'verification_codes';

    // 允许批量赋值的字段
    protected $fillable = [
        'account',
        'country_code',
        'code',
        'type',
        'status',
        'expired_at'
    ];

    // 声明日期格式，方便直接对比 Carbon 对象
    protected $casts = [
        'expired_at' => 'datetime',
    ];
}