<?php

declare(strict_types=1);

namespace App\Models;

use Dom\Comment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


class Plans extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'title',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who created this plan
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all plan items in this plan
     */
    public function planItems()
    {
        return $this->hasMany(Plan_items::class , 'plan_id');
    }

    public function tours()
    {

        return $this->hasMany(Tours::class, 'plan_id');
    }

    /**
     * Get all places in this plan (through plan_items)
     */
    public function places()
    {
        return $this->belongsToMany(Places::class, 'plan_items' , 'plan_id', 'place_id')
                    ->withPivot('day_index')
                    ->withTimestamps();;
    }

    /**
     * Get all comments on this plan (polymorphic)
     */
    public function comments()
    {
        return $this->morphMany(Comments::class, 'commentable');
    }

    /**
     * Get all likes on this plan (polymorphic)
     */
    public function likes()
    {
        return $this->morphMany(Likes::class, 'likeable');
    }

    // -------------------------------------------------------------------------
    // Local Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope: filter by partial title match.
     *
     * Usage: Plan::searchByTitle('cairo')->get();
     */
    public function scopeSearchByTitle(Builder $query, string $title): Builder
    {
        return $query->where('title', 'like', "%{$title}%");
    }

    /**
     * Scope: filter plans that belong to a specific user.
     *
     * Usage: Plan::forUser(auth()->id())->get();
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: only plans that have at least one place.
     *
     * Usage: Plan::withPlaces()->get();
     */
    public function scopeWithPlaces(Builder $query): Builder
    {
        return $query->has('places');
    }

    /**
     * Scope: plans ordered from newest to oldest.
     *
     * Usage: Plan::latest()->get();  (already built-in, but aliased here for clarity)
     */
    public function scopeNewest(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope: only plans whose total ticket price is within a budget.
     *
     * Usage: Plan::withinBudget(500)->get();
     */
    public function scopeWithinBudget(Builder $query, float $maxBudget): Builder
    {
        return $query->whereHas('places', fn (Builder $q) => $q, '>=', 1)
                     ->withSum('places', 'ticket_price')
                     ->having('places_sum_ticket_price', '<=', $maxBudget);
    }

    // -------------------------------------------------------------------------
    // Helper Functions
    // -------------------------------------------------------------------------

    /**
     * Calculate the total ticket price of all places in this plan.
     * Relies on the `places` relationship being loaded (or lazy-loads it).
     */
    public function totalPrice(): float
    {
        return (float) $this->places->sum('ticket_price');
    }

    /**
     * Return the number of unique days scheduled in this plan.
     */
    public function totalDays(): int
    {
        return $this->planItems
                    ->pluck('day_index')
                    ->filter()           // remove nulls
                    ->unique()
                    ->count();
    }

    /**
     * A plan is considered "complete" when:
     *  - It has at least one place.
     *  - Every plan_item has a day_index assigned.
     */
    public function isComplete(): bool
    {
        if ($this->planItems->isEmpty()) {
            return false;
        }

        return $this->planItems->every(fn (Plan_items $item): bool => $item->day_index !== null);
    }

    /**
     * Return a human-readable summary string for the plan.
     */
    public function summary(): string
    {
        $placeCount = $this->places->count();
        $days       = $this->totalDays();
        $total      = number_format($this->totalPrice(), 2);

        return "Plan \"{$this->title}\": {$placeCount} place(s), {$days} day(s), total EGP {$total}.";
    }
}
