<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tuitionFee extends Model
{
    protected $fillable = [
        'stdId',
        'feesType',
        'fee_month',
        'amount',
        'due_amount',
        'paid_amount',
        'payment_status',
        'class_id',
        'section_id',
        'session_id',
        'collected_by',
        'note',
    ];

    protected $casts = [
        'fee_month' => 'date',
        'amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'class_id' => 'integer',
        'section_id' => 'integer',
        'session_id' => 'integer',
        'collected_by' => 'integer',
    ];
}
