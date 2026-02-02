<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateTourRequest - تحديث جولة
 *
 * ✅ معظم الحقول اختيارية
 * ✅ نفس الـ Validation لكن أخف
 */
class UpdateTourRequest extends FormRequest
{
    /**
     * التحقق من الصلاحيات
     */
    public function authorize(): bool
    {
        $tour = $this->route('tour');

        return auth('sanctum')->check() && (
            auth('sanctum')->id() === $tour->guide_id ||
            auth('sanctum')->user()->role === 'admin'
        );
    }

    /**
     * الـ Validation Rules
     */
    public function rules(): array
    {
        $tour = $this->route('tour');

        return [
            'title' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tours', 'title')->ignore($tour->id),
            ],
            'price' => 'sometimes|numeric|min:0|max:999999.99',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'payment_method' => 'nullable|string|in:cash,card,both',
            'details' => 'nullable|string|max:2000',
            'places' => 'nullable|array|max:20',
            'places.*' => 'integer|exists:places,id',
        ];
    }

    /**
     * الرسائل المخصصة
     */
    public function messages(): array
    {
        return [
            'title.unique' => 'عنوان الجولة موجود بالفعل',
            'title.string' => 'عنوان الجولة يجب أن يكون نص',
            'price.numeric' => 'السعر يجب أن يكون رقم',
            'start_time.date_format' => 'صيغة الوقت غير صحيحة (HH:mm)',
            'payment_method.in' => 'طريقة الدفع غير صحيحة',
            'places.array' => 'الأماكن يجب أن تكون قائمة',
        ];
    }

    /**
     * الـ Attributes المترجمة
     */
    public function attributes(): array
    {
        return [
            'title' => 'عنوان الجولة',
            'price' => 'السعر',
            'start_date' => 'تاريخ البداية',
            'start_time' => 'وقت البداية',
            'payment_method' => 'طريقة الدفع',
            'details' => 'التفاصيل',
            'places' => 'الأماكن',
        ];
    }
}
