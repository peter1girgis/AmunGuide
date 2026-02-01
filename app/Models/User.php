<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ResetPasswordNotificationCustom;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotificationCustom($token));
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $fillable = [
        'name',
        'email',
        'profile_image',
        'phone',
        'address',
        'national_id',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get all tours created by this guide
     */
    public function tours()
    {
        return $this->hasMany(Tours::class, 'guide_id');
    }

    /**
     * Get all bookings made by this tourist
     */
    public function tourBookings()
    {
        return $this->hasMany(Tour_bookings::class, 'tourist_id');
    }

    /**
     * Get all payments made by this user
     */
    public function payments()
    {
        return $this->hasMany(Payments::class, 'payer_id');
    }

    /**
     * Get all plans created by this user
     */
    public function plans()
    {
        return $this->hasMany(Plans::class);
    }

    /**
     * Get all activities of this user
     */
    public function activities()
    {
        return $this->hasMany(User_activities::class);
    }

    /**
     * Get all comments made by this user
     */
    public function comments()
    {
        return $this->hasMany(Comments::class);
    }

    /**
     * Get all likes made by this user
     */
    public function likes()
    {
        return $this->hasMany(Likes::class);
    }

    /**
     * Get all chatbot conversations of this user
     */
    public function chatbotConversations()
    {
        return $this->hasMany(Chatbot_conversations::class);
    }
}
