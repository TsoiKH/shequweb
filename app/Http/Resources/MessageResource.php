<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request)
    {
        $myId = auth('sanctum')->id();

        return [
            'id'           => $this->id,
            'content'      => $this->content,
            'sender_id'    => $this->sender_id,
            'receiver_id'  => $this->receiver_id,
            'is_me'        => $this->sender_id === $myId,
            'is_read'      => (bool)$this->is_read,
            'time_display' => $this->created_at->isToday() 
                                ? $this->created_at->format('H:i') 
                                : $this->created_at->diffForHumans(),
            'created_at'   => $this->created_at->toDateTimeString(),
        ];
    }
}