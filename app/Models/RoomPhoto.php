<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RoomPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'room_id',
        'path',
        'captured_at',
        'has_timestamp_overlay'
    ];
    protected $casts = ['captured_at' => 'datetime', 'has_timestamp_overlay' => 'bool'];

    public function getUrlAttribute()
    {
        return Str::startsWith($this->path, ['http://', 'https://'])
            ? $this->path
            : asset('storage/' . $this->path);
    }
}
