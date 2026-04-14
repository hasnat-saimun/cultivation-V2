<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassWiseFeeSetup extends Model
{
    protected $fillable = [
        'class_id',
        'fees_type_id',
        'setup_amount',
    ];

    protected $casts = [
        'class_id' => 'integer',
        'fees_type_id' => 'integer',
        'setup_amount' => 'decimal:2',
    ];
}
