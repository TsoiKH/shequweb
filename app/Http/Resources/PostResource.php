<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        // 获取当前登录用户，用于判断权限
        $loginUser = auth('sanctum')->user();

        return [
            'post_id'      => $this->id,
            'title'        => $this->title,
            'desc'         => $this->content,
            'type'         => $this->media_type,
            'images'       => $this->media_urls,
            
            // 1. 位置信息
            'location'     => [
                'city'     => $this->city ?? '未知城市',
                'country'  => $this->country,
                'address'  => $this->address,
                'full'     => ($this->city && $this->country) ? $this->city . ', ' . $this->country : ($this->city ?: $this->address),
            ],

            // 2. 标签系统
            'tags'         => $this->tags ? $this->tags->pluck('name') : [],

            // 3. 统计与互动状态
            'stat'         => [
                'likes'        => (int) $this->like_count, 
                'comments'     => (int) $this->comment_count,
                'collections'  => (int) $this->collect_count, 
                'views'        => (int) $this->view_count,     
                
                // 互动状态：由 Controller 里的 withUserStats 注入
                'is_liked'     => (bool)($this->is_liked ?? false),
                'is_collected' => (bool)($this->is_collected ?? false),
            ],

            // 4. 用户信息
            'user' => [
                'id'          => $this->user->id ?? 0,
                'nickname'    => $this->user->nickname ?? '已注销用户',
                'avatar'      => $this->user->avatar ?? asset('storage/default_avatar.png'), // 提供默认头像
                'is_followed' => (bool)($this->user->is_followed ?? false),
            ],

            // 5. 权限标识 (用于前端显示编辑/删除按钮)
            'permissions' => [
                'can_edit'   => $loginUser ? ($loginUser->id === $this->user_id) : false,
                'can_delete' => $loginUser ? ($loginUser->id === $this->user_id) : false,
            ],

            // 6. 时间与状态
            'publish_time' => $this->created_at->diffForHumans(),
            'date'         => $this->created_at->format('Y-m-d H:i'),
            'ip_address'   => $this->ip_address,
            'is_deleted'   => $this->deleted_at !== null, // 如果开启了软删除，告知前端状态
        ];
    }
}