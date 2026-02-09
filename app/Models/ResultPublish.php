<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultPublish extends Model
{
    use HasFactory;

    protected $fillable = [
        'examId',
        'sessionId',
        'classId',
        'groupId',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
