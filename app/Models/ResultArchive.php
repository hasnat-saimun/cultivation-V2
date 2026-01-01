<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'old_class',
        'old_roll',
        'old_session',
        'old_section',
        'exam_id',
        'result_data',
    ];

    protected $casts = [
        'result_data' => 'array',
    ];
    public function student()
    {
        return $this->belongsTo(newAdmission::class, 'student_id', 'id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'id');
    }
}
