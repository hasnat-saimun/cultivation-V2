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
        'entered_by',
        'entered_by_role',
        'updated_by',
        'updated_by_role',
        'subjectMarks',
        'objectMarks',
        'practicalMarks',
        'totalMarks',
        'laterGrade',
        'gradePoint',
    ];
}
