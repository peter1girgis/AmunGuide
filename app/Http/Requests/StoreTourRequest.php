<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreTourRequest - إنشاء جولة جديدة
 *
 * ✅ Validation محترف
 * ✅ رسائل خطأ بـ العربية
 * ✅ Authorization checks
 */
class StoreTourRequest extends FormRequest
{
    /**
     * التحقق من الصلاحيات
     */
    public function authorize(): bool
    {
        // فقط الـ guide يقدر ينشئ جولات
        return auth('sanctum')->check() && auth('sanctum')->user()->role === 'guide';
    }

    /**
     * الـ Validation Rules
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:tours,title',
            'price' => 'required|numeric|min:0|max:999999.99',
            'start_date' => 'required|date|after:today',
            'start_time' => 'required|date_format:H:i',
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
            'title.required' => 'عنوان الجولة مطلوب',
            'title.unique' => 'عنوان الجولة موجود بالفعل',
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقم',
            'price.min' => 'السعر يجب أن يكون موجب',
            'start_date.required' => 'تاريخ البداية مطلوب',
            'start_date.after' => 'تاريخ البداية يجب أن يكون في المستقبل',
            'start_time.required' => 'وقت البداية مطلوب',
            'start_time.date_format' => 'صيغة الوقت غير صحيحة (HH:mm)',
            'payment_method.in' => 'طريقة الدفع غير صحيحة',
            'places.array' => 'الأماكن يجب أن تكون قائمة',
            'places.*.exists' => 'الموقع غير موجود',
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
