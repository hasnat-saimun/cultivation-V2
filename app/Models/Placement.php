<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Placement extends Model
{
    use HasFactory;

    protected $table = 'exam_placements';

    protected $fillable = [
        'studentId',
        'classId',
        'sessionId',
        'groupId',
        'examId',
        'subjectsCount',
        'totalGradePoints',
        'gpa',
        'totalMarks',
        'position',
        'status',
    ];
}
