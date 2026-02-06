<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskMedia extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'type', 'url', 'thumbnail', 'caption', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
    protected function url(): Attribute
    {
        return Attribute::get(function ($value) {
            if (!$value) return $value;
            return Str::startsWith($value, ['http://', 'https://'])
                ? $value
                : asset('storage/' . $value);
        });
    }

    /**
     * Same treatment for thumbnail.
     */
    protected function thumbnail(): Attribute
    {
        return Attribute::get(function ($value) {
            if (!$value) return $value;
            return Str::startsWith($value, ['http://', 'https://'])
                ? $value
                : asset('storage/' . $value);
        });
    }
}
