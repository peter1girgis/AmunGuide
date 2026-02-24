<?php

namespace App\Http\Requests;

use App\Models\Chatbot_conversations;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $conversationId = $this->route('id');
        $conversation = Chatbot_conversations::find($conversationId);
        if(!$conversation){
            return true ;
        }
        $user = auth('sanctum')->user();

        if (!$user || !$conversation) {
            return false;
        }

        // User can only add messages to their own conversations
        return $conversation->user_id === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sender' => [
                'required',
                'string',
                'in:user,bot',
            ],
            'message' => [
                'required',
                'string',
                'min:1',
                'max:5000',
            ],
            // These fields are optional, used only if sender = bot
            'image_url' => [
                'nullable',
                'string',
                'url',
                'max:2048',
            ],
            'place_id' => [
                'nullable',
                'integer',
                'exists:places,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Sender Messages
            'sender.required' => 'The message sender is required',
            'sender.in' => 'The sender must be either "user" or "bot"',

            // Message Messages
            'message.required' => 'The message content is required',
            'message.string' => 'The message must be text',
            'message.min' => 'The message must be at least 1 character',
            'message.max' => 'The message cannot exceed 5000 characters',

            // Image URL Messages
            'image_url.url' => 'The image URL must be a valid URL',
            'image_url.max' => 'The image URL is too long',

            // Place ID Messages
            'place_id.integer' => 'Invalid place ID',
            'place_id.exists' => 'The selected place does not exist',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // If sender = bot and image_url is sent, check data validity
            if ($this->input('sender') === 'bot' && $this->has('image_url')) {
                // Check that image_url is not empty
                if (empty($this->input('image_url'))) {
                    $validator->errors()->add(
                        'image_url',
                        'Image URL cannot be empty when provided for bot messages'
                    );
                }
            }

            // If place_id is sent, it must be with image_url
            if ($this->has('place_id') && !$this->has('image_url')) {
                $validator->errors()->add(
                    'place_id',
                    'Place ID can only be used with an image URL'
                );
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sender' => 'message sender',
            'message' => 'message content',
            'image_url' => 'image URL',
            'place_id' => 'place ID',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Clean data before validation
        if ($this->has('sender')) {
            $this->merge([
                'sender' => strtolower(trim($this->input('sender'))),
            ]);
        }
    }
}
