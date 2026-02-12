<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This resource combines everything: conversation, messages, and images
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'context' => $this->context,
            'context_label' => $this->getContextLabel(),

            // معلومات المستخدم
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],

            // جميع الرسائل (بالترتيب الزمني)
            'messages' => MessageResource::collection(
                $this->whenLoaded('messages', function () {
                    return $this->messages()->oldest()->get();
                })
            ),

            // جميع الصور المولدة
            'generated_images' => ImageResource::collection(
                $this->whenLoaded('generatedImages')
            ),

            // إحصائيات المحادثة
            'statistics' => [
                'total_messages' => $this->getMessagesCount(),
                'user_messages' => $this->getUserMessages()->count(),
                'bot_messages' => $this->getBotMessages()->count(),
                'total_images' => $this->getImagesCount(),
            ],

            // التواريخ
            'created_at' => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    /**
     * الحصول على تسمية السياق
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

    /**
     * Additional meta information
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
