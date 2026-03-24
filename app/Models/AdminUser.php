<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    // 指定映射的数据库表名
    protected $table = 'admin_users';

    // 定义可批量赋值的字段
    protected $fillable = ['username', 'password', 'name'];

    // 定义隐藏字段（如密码）
    protected $hidden = ['password', 'remember_token'];
}