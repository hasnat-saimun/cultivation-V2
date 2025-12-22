<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReligiousSubjectDefault extends Model
{
    protected $table = 'religious_subject_defaults';
    protected $fillable = ['classId', 'subjectId'];
}
