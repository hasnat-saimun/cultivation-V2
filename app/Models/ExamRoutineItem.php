<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Subject;

class ExamRoutineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_routine_id',
        'exam_date',
        'exam_day',
        'start_time',
        'end_time',
        'exam_time',
        'subject_id',
        'subject_name',
        'sort_order',
    ];

    public function routine(): BelongsTo
    {
        return $this->belongsTo(ExamRoutine::class, 'exam_routine_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
