<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_activities extends Model
{
    use HasFactory;
    protected $table = 'user_activities';

    /**
     * ✅ الـ Fillable - الحقول المسموحة للـ Mass Assignment
     */
    protected $fillable = [
        'user_id',
        'activity_type',
        'search_query',
        'place_id',
        'details',
    ];

    /**
     * ✅ الـ Casts - تحويل البيانات
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'details' => 'array', // حفظ JSON و استرجاعها كـ array
    ];

    /**
     * ✅ العلاقات - Relationships
     */

    /**
     * علاقة مع User Model
     * كل activity ينتمي لـ user واحد فقط
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة مع Place Model
     * كل activity قد تنتمي لـ place (nullable)
     */
    public function place()
    {
        return $this->belongsTo(Places::class);
    }

    /**
     * ✅ Scopes - استعلامات مخصصة
     */

    /**
     * جميع نشاطات البحث
     */
    public function scopeSearchActivities($query)
    {
        return $query->where('activity_type', 'search');
    }

    /**
     * جميع نشاطات الزيارات
     */
    public function scopeVisitActivities($query)
    {
        return $query->where('activity_type', 'visit');
    }

    /**
     * جميع نشاطات مستخدم معين
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * جميع النشاطات في فترة زمنية معينة
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * آخر N نشاط
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }
}
