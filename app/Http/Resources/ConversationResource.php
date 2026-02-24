<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'context' => $this->context,
            'context_label' => $this->getContextLabel(),

            // User information
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],

            // Conversation statistics
            'statistics' => [
                'messages_count' => $this->getMessagesCount(),
                'images_count' => $this->getImagesCount(),
                'has_images' => $this->hasImages(),
            ],

            // Last message
            'last_message' => $this->when(
                $this->getLastMessage(),
                function () {
                    $lastMessage = $this->getLastMessage();
                    return $lastMessage ? [
                        'sender' => $lastMessage->sender,
                        'preview' => \Str::limit($lastMessage->message, 50),
                        'sent_at' => $lastMessage->created_at->toDateTimeString(),
                    ] : null;
                }
            ),

            // Dates
            'created_at' => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    /**
     * Get context label
     */
    private function getContextLabel(): string
    {
        $labels = [
            'image_generation' => 'Image Generation',
            'travel_plan' => 'Travel Planning',
            'info_request' => 'Information Request',
            'general' => 'General Chat',
            'place_inquiry' => 'Place Inquiry',
            'tour_inquiry' => 'Tour Inquiry',
        ];

        return $labels[$this->context] ?? 'Unknown Context';
    }
}
