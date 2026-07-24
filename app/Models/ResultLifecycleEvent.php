<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultLifecycleEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['*'];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'change_set' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Result lifecycle events are append-only.'));
        static::deleting(fn () => throw new \LogicException('Result lifecycle events are append-only.'));
    }
}
