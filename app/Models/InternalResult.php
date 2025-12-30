<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalResult extends Model
{
    use HasFactory;

    protected $casts = [
        'assignClass' => 'integer',
        'assignDepartment' => 'integer',
        'assignSession' => 'integer',
        'assignSection' => 'integer',
    ];
}
