<?php

namespace App\Http\Requests;

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
     * فقط الـ owner أو admin يقدر يعدل
     */
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        return auth('sanctum')->check() && (
            auth('sanctum')->id() === $comment->user_id ||
            auth('sanctum')->user()->role === 'admin'
        );
    }

    /**
     * الـ Validation Rules
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|min:3|max:1000',
        ];
    }

    /**
     * الرسائل المخصصة
     */
    public function messages(): array
    {
        return [
            'content.required' => 'محتوى التعليق مطلوب',
            'content.min' => 'محتوى التعليق يجب أن يكون 3 أحرف على الأقل',
            'content.max' => 'محتوى التعليق لا يجب أن يتجاوز 1000 حرف',
        ];
    }
}
