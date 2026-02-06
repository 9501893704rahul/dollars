<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'address',
        'photo_path',
        'beds',
        'baths',
        'latitude',
        'longitude',
        'geo_radius_m',
        'ical_airbnb_url',
        'ical_vrbo_url',
        'ical_booking_url',
        'ical_last_synced_at',
    ];

    protected $casts = [
        'ical_last_synced_at' => 'datetime',
    ];

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'property_room')
            ->withTimestamps()
            ->withPivot(['sort_order'])
            ->orderBy('property_room.sort_order');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id', 'id');
    }

    public function propertyTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'property_tasks')
            ->withTimestamps()
            ->withPivot(['sort_order', 'instructions', 'visible_to_owner', 'visible_to_housekeeper'])
            ->orderBy('property_tasks.sort_order');
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo_path
            ? (str_starts_with($this->photo_path, 'http') ? $this->photo_path : asset('storage/' . $this->photo_path))
            : asset('images/placeholders/property.png');
    }

    /**
     * Get calendar events synced from iCal feeds.
     */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /**
     * Get upcoming checkout events for this property.
     */
    public function upcomingCheckouts(int $days = 2)
    {
        return $this->calendarEvents()
            ->upcomingCheckouts($days)
            ->orderBy('end_date');
    }

    /**
     * Check if this property has any iCal feeds configured.
     */
    public function hasIcalFeeds(): bool
    {
        return $this->ical_airbnb_url 
            || $this->ical_vrbo_url 
            || $this->ical_booking_url;
    }
}
