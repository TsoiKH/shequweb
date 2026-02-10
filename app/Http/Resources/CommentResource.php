<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'content'    => $this->content,
            'like_count' => (int) $this->like_count, 
            'is_liked'   => isset($this->is_liked) ? (bool)$this->is_liked : false,
            'user'       => [
                'id'       => $this->user->id,
                'nickname' => $this->user->nickname,
                'avatar'   => $this->user->avatar,
            ],
            'time'       => $this->created_at->diffForHumans(),
            'reply_count' => $this->replies_count ?? $this->replies()->count(),
            'preview_replies' => CommentResource::collection($this->whenLoaded('replies', function() {
                return $this->replies->take(3); 
            })),
        ];
    }
}