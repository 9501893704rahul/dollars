<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'uid',
        'source',
        'summary',
        'description',
        'start_date',
        'end_date',
        'checkout_alert_sent',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'checkout_alert_sent' => 'boolean',
    ];

    /**
     * Get the property this event belongs to.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Scope to get upcoming checkout events (events ending today or tomorrow).
     */
    public function scopeUpcomingCheckouts($query, int $days = 2)
    {
        return $query->whereBetween('end_date', [
            now()->startOfDay(),
            now()->addDays($days)->endOfDay(),
        ]);
    }

    /**
     * Check if this event has a checkout today.
     */
    public function hasCheckoutToday(): bool
    {
        return $this->end_date->isToday();
    }

    /**
     * Check if this event has a checkout tomorrow.
     */
    public function hasCheckoutTomorrow(): bool
    {
        return $this->end_date->isTomorrow();
    }
}
