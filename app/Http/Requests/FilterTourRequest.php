<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FilterTourRequest - فلترة الجولات
 *
 * ✅ Query parameters validation
 */
class FilterTourRequest extends FormRequest
{
    /**
     * الجميع يقدرون يفلتروا
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * الـ Validation Rules
     */
    public function rules(): array
    {
        return [
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'guide_id' => 'nullable|integer|exists:users,id',
            'start_date' => 'nullable|date',
            'sort' => 'nullable|string|in:price_asc,price_desc,newest,popular',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * الرسائل المخصصة
     */
    public function messages(): array
    {
        return [
            'min_price.numeric' => 'الحد الأدنى للسعر يجب أن يكون رقم',
            'max_price.numeric' => 'الحد الأقصى للسعر يجب أن يكون رقم',
            'guide_id.exists' => 'الدليل غير موجود',
            'sort.in' => 'طريقة الترتيب غير صحيحة',
            'per_page.integer' => 'عدد النتائج يجب أن يكون رقم',
        ];
    }
}
