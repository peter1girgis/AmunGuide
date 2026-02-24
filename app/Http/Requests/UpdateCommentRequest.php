<?php

namespace App\Http\Requests;

use App\Models\Comments;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateCommentRequest - Update comment
 *
 * ✅ Update comment with content only
 * ✅ Authorization (owner only)
 */
class UpdateCommentRequest extends FormRequest
{
    /**
     * Check authorization
     */
    public function authorize(): bool
    {
        $id = $this->route('comment') ?? $this->route('id');
        $comment = Comments::find($id);

        if (!$comment) return false; // Will return 403 Forbidden if not found

        $user = auth('sanctum')->user();
        return $user && ($user->id === $comment->user_id || $user->role === 'admin');
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|min:3|max:1000',
        ];
    }

    /**
     * Error messages
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Comment content is required',
            'content.min'      => 'Comment content must be at least 3 characters',
            'content.max'      => 'Comment content must not exceed 1000 characters',
        ];
    }
}
