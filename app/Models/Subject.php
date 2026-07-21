<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CultivationAdmin;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'subjectName', 'alias', 'subjectType', 'passingSystem', 'assign_class', 'CQ', 'MCQ', 'Practical', 'isReligious'
    ];

    public function teachers()
    {
        return $this->belongsToMany(CultivationAdmin::class, 'teacher_subjects', 'subject_id', 'teacher_id');
    }
}
