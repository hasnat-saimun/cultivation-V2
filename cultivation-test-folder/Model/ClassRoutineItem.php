<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassRoutineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_routine_id',
        'class_day',
        'start_time',
        'end_time',
        'class_time',
        'subject_id',
        'subject_name',
        'sort_order',
    ];

    public function routine(): BelongsTo
    {
        return $this->belongsTo(ClassRoutine::class, 'class_routine_id');
    }
}
