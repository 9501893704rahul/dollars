<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CleaningSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'owner_id',
        'housekeeper_id',
        'scheduled_date',
        'scheduled_time',
        'status',
        'started_at',
        'ended_at',
        'gps_confirmed_at',
        'start_latitude',
        'start_longitude'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'gps_confirmed_at' => 'datetime'
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

        public function housekeeper(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'housekeeper_id');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class, 'session_id');
    }
    public function photos(): HasMany
    {
        return $this->hasMany(RoomPhoto::class, 'session_id');
    }
}
