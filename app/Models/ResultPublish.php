<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultPublish extends Model
{
    use HasFactory;

    public const STATUS_PUBLISHED = 'published';
    public const STATUS_UNPUBLISHED = 'unpublished';

    protected $attributes = [
        'status' => self::STATUS_PUBLISHED,
        'revision' => 1,
        'legacyImported' => false,
    ];

    protected $fillable = [
        'examId',
        'sessionId',
        'classId',
        'groupId',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'unpublished_at' => 'datetime',
        'revision' => 'integer',
        'legacyImported' => 'boolean',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isUnpublished(): bool
    {
        return $this->status === self::STATUS_UNPUBLISHED;
    }

    public function normalizedScopeKey(): string
    {
        return $this->groupId === null ? 'class' : 'section:'.$this->groupId;
    }
}
