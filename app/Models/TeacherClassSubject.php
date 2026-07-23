<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherClassSubject extends Model
{
    use HasFactory;

    protected $table = 'teacher_class_subjects';

    protected $fillable = [
        'teacher_id', 'session_id', 'class_id', 'section_id', 'group_id', 'subject_id', 'gender_scope'
    ];

    public function getGenderScopeLabelAttribute(): string
    {
        $scope = strtolower(trim((string) ($this->gender_scope ?? 'all')));

        return match ($scope) {
            'male' => 'Male',
            'female' => 'Female',
            default => 'All',
        };
    }
}
