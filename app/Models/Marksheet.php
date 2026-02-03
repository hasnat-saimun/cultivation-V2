<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marksheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'studentId',
        'classId',
        'sessionId',
        'groupId',
        'examId',
        'subjectId',
        'teacher_id',
        'subjectMarks',
        'objectMarks',
        'practicalMarks',
        'totalMarks',
        'laterGrade',
        'gradePoint',
    ];
}
