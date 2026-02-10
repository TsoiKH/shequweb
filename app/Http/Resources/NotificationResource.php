<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{

    public function toArray($request)
    {
        $description = '';
        switch ($this->type) {
            case 'like_post':
                $description = '赞了你的帖子';
                break;
            case 'like_comment':
                $description = '赞了你的评论';
                break;
            case 'comment':
                if ($this->comment && $this->comment->parent_id > 0) {
                    $description = '回复了你的评论';
                } else {
                    $description = '评论了你的帖子';
                }
                break;
            case 'follow':
                $description = '关注了你';
                break;
            case 'collect':
                $description = '收藏了你的帖子';
                break;
            default:
                $description = '给你发送了新动态';
        }

        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'desc'       => $description,
            'is_read'    => (bool)$this->is_read,
            'content'    => $this->content, 
            
            // 发起人信息
            'sender'     => $this->sender ? [
                'id'       => $this->sender->id,
                'nickname' => $this->sender->nickname,
                'avatar'   => $this->sender->avatar,
            ] : ['id' => 0, 'nickname' => '用户已注销', 'avatar' => null],

            'post'       => $this->post ? [
                'id'    => $this->post->id,
                'title' => $this->post->title,
                'cover' => !empty($this->post->media_urls) ? $this->post->media_urls[0] : null,
            ] : null,

            'time'       => $this->created_at->diffForHumans(),
        ];
    }
}