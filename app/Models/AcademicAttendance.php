<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AcademicAttendance extends Model
{
    protected $fillable = [
        'exam_id', 'session_id', 'class_id', 'section_id', 'department_id',
        'student_id', 'working_days', 'present_days', 'absent_days',
        'created_by', 'updated_by', 'scope_key',
    ];

    protected $casts = [
        'exam_id' => 'integer', 'session_id' => 'integer', 'class_id' => 'integer',
        'section_id' => 'integer', 'department_id' => 'integer', 'student_id' => 'integer',
        'working_days' => 'integer', 'present_days' => 'integer', 'absent_days' => 'integer',
    ];
}
