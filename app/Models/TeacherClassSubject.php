<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherClassSubject extends Model
{
    use HasFactory;

    protected $table = 'teacher_class_subjects';

    protected $fillable = [
        'teacher_id', 'class_id', 'section_id', 'group_id', 'subject_id'
    ];
}
