<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoutine extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'assignClass',
        'assignSection',
        'assignDepartment',
        'assignSession',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(ClassRoutineItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
