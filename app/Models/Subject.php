<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'subjectName', 'alias', 'subjectType', 'passingSystem', 'assign_class', 'CQ', 'MCQ', 'Practical', 'isReligious'
    ];
}
