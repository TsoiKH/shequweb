<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /**
     * 允许批量赋值的字段
     */
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'is_read'
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * 关联：发送者
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * 关联：接收者
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * 辅助方法：判断消息是否属于当前用户
     */
    public function isMine()
    {
        return $this->sender_id === auth()->id();
    }
    
    /**
     * 作用域：查询未读消息
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}