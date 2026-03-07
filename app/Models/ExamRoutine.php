<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamRoutine extends Model
{
    use HasFactory;

    public function entries(): HasMany
    {
        return $this->hasMany(ExamRoutineItem::class)->orderBy('exam_date')->orderBy('id');
    }
}
