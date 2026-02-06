<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItemPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_item_id',
        'path',
        'note',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    /**
     * Get the checklist item this photo belongs to.
     */
    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    /**
     * Get the URL for this photo.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
