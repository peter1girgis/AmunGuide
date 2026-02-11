<?php

namespace App\Http\Requests;

use App\Models\Comments;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateCommentRequest - تحديث تعليق
 *
 * ✅ تحديث التعليق فقط بـ content
 * ✅ Authorization (owner فقط)
 */
class UpdateCommentRequest extends FormRequest
{
    /**
     * التحقق من الصلاحيات
     */
    public function authorize(): bool
    {
        $id = $this->route('comment') ?? $this->route('id');
        $comment = Comments::find($id);

        if (!$comment) return false; // هيرجع 403 Forbidden لو مش موجود

        $user = auth('sanctum')->user();
        return $user && ($user->id === $comment->user_id || $user->role === 'admin');
    }

    /**
     * قوانين التحقق
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|min:3|max:1000',
        ];
    }

    /**
     * رسائل الخطأ
     */
    public function messages(): array
    {
        return [
            'content.required' => 'محتوى التعليق مطلوب',
            'content.min'      => 'محتوى التعليق يجب أن يكون 3 أحرف على الأقل',
            'content.max'      => 'محتوى التعليق لا يجب أن يتجاوز 1000 حرف',
        ];
    }
}
