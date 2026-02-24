<?php

namespace App\Http\Requests;

use App\Models\Comments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreCommentRequest - Add new comment
 *
 * ✅ Professional validation
 * ✅ Error messages in English
 * ✅ Authorization checks
 * ✅ Support Polymorphic relationships
 */
class StoreCommentRequest extends FormRequest
{
    /**
     * Check authorization
     * Any authenticated user is authorized to add comment
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [
            // Content validation
            'content' => 'required|string|min:3|max:1000',

            // Commentable type validation (Tour, Place, Plan)
            'commentable_type' => [
                'required',
                'string',
                Rule::in(['tours', 'places', 'plans']),
            ],

            // Commentable ID validation (must exist in corresponding table)
            'commentable_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $type = $this->get('commentable_type');

                    if (!$type) {
                        return;
                    }

                    // Map type to model
                    $modelMap = [
                        'tours' => 'App\\Models\\Tours',
                        'places' => 'App\\Models\\Places',
                        'plans' => 'App\\Models\\Plans',
                    ];

                    if (!isset($modelMap[$type])) {
                        $fail('Comment type is invalid');
                        return;
                    }

                    $model = $modelMap[$type];
                    if (!$model::where('id', $value)->exists()) {
                        $fail("The requested resource ($type) does not exist");
                    }
                },
            ],
        ];
    }

    /**
     * Custom messages
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Comment content is required',
            'content.min' => 'Comment content must be at least 3 characters',
            'content.max' => 'Comment content must not exceed 1000 characters',
            'commentable_type.required' => 'Comment type is required',
            'commentable_type.in' => 'Comment type is invalid (tours, places, plans)',
            'commentable_id.required' => 'Resource ID is required',
            'commentable_id.integer' => 'Resource ID must be a number',
        ];
    }

    /**
     * Translated attributes
     */
    public function attributes(): array
    {
        return [
            'content' => 'comment content',
            'commentable_type' => 'comment type',
            'commentable_id' => 'resource ID',
        ];
    }
}

/**
 * UpdateCommentRequest - Update comment
 *
 * ✅ Update comment with content only
 * ✅ Authorization (owner only)
 */
// class UpdateCommentRequest extends FormRequest
// {
//     /**
//      * Check authorization
//      * Only owner or admin can edit
//      */
//     public function authorize(): bool
//     {
//         $commentId = $this->route('id');
//         $comment = Comments::find($commentId);

//         // If not found, Controller will handle 404, here we just protect logic
//         if (!$comment) return false;

//         return auth('sanctum')->check() && (
//             auth('sanctum')->id() === $comment->user_id ||
//             auth('sanctum')->user()->role === 'admin'
//         );
//     }

//     /**
//      * Validation Rules
//      */
//     public function rules(): array
//     {
//         return [
//             'content' => 'required|string|min:3|max:1000',
//         ];
//     }

//     /**
//      * Custom messages
//      */
//     public function messages(): array
//     {
//         return [
//             'content.required' => 'Comment content is required',
//             'content.min' => 'Comment content must be at least 3 characters',
//             'content.max' => 'Comment content must not exceed 1000 characters',
//         ];
//     }
// }
