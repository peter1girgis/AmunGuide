<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;
    protected $fillable = [
        'payer_id',
        'amount',
        'status',
        'payable_type',
        'payable_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who made the payment
     */
    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Get the payable model (Tour or TourBooking)
     * Polymorphic relationship
     */
    public function payable()
    {
        return $this->morphTo();
    }
}
