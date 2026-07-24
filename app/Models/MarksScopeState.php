<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarksScopeState extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'sessionId', 'classId', 'groupId', 'examId', 'subjectId',
    ];

    protected $casts = [
        'revision' => 'integer',
        'confirmed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function scopeKey(): string
    {
        return $this->groupId === null ? 'class' : 'section:'.$this->groupId;
    }
}
