<?php

namespace App\Http\Requests;

use App\Models\Comments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreCommentRequest - إضافة تعليق جديد
 *
 * ✅ Validation محترف
 * ✅ رسائل خطأ بـ العربية
 * ✅ Authorization checks
 * ✅ دعم Polymorphic relationships
 */
class StoreCommentRequest extends FormRequest
{
    /**
     * التحقق من الصلاحيات
     * أي user مصرح بإضافة تعليق
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    /**
     * الـ Validation Rules
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
                        $fail('نوع التعليق غير صحيح');
                        return;
                    }

                    $model = $modelMap[$type];
                    if (!$model::where('id', $value)->exists()) {
                        $fail("المورد المطلوب ($type) غير موجود");
                    }
                },
            ],
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
            'commentable_type.required' => 'نوع التعليق مطلوب',
            'commentable_type.in' => 'نوع التعليق غير صحيح (tours, places, plans)',
            'commentable_id.required' => 'معرف المورد مطلوب',
            'commentable_id.integer' => 'معرف المورد يجب أن يكون رقم',
        ];
    }

    /**
     * الـ Attributes المترجمة
     */
    public function attributes(): array
    {
        return [
            'content' => 'محتوى التعليق',
            'commentable_type' => 'نوع التعليق',
            'commentable_id' => 'معرف المورد',
        ];
    }
}

/**
 * UpdateCommentRequest - تحديث تعليق
 *
 * ✅ تحديث التعليق فقط بـ content
 * ✅ Authorization (owner فقط)
 */
// class UpdateCommentRequest extends FormRequest
// {
//     /**
//      * التحقق من الصلاحيات
//      * فقط الـ owner أو admin يقدر يعدل
//      */
//     public function authorize(): bool
//     {
//         $commentId = $this->route('id');
//         $comment = Comments::find($commentId);

//         // لو مش موجود، الـ Controller هيتعامل مع الـ 404، هنا بس بنحمي الـ logic
//         if (!$comment) return false;

//         return auth('sanctum')->check() && (
//             auth('sanctum')->id() === $comment->user_id ||
//             auth('sanctum')->user()->role === 'admin'
//         );
//     }

//     /**
//      * الـ Validation Rules
//      */
//     public function rules(): array
//     {
//         return [
//             'content' => 'required|string|min:3|max:1000',
//         ];
//     }

//     /**
//      * الرسائل المخصصة
//      */
//     public function messages(): array
//     {
//         return [
//             'content.required' => 'محتوى التعليق مطلوب',
//             'content.min' => 'محتوى التعليق يجب أن يكون 3 أحرف على الأقل',
//             'content.max' => 'محتوى التعليق لا يجب أن يتجاوز 1000 حرف',
//         ];
//     }
// }
