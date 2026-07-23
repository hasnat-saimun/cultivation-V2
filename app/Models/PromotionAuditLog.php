<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'student_id',
        'exam_id',
        'promotion_cycle_id',
        'engine',
        'old_session',
        'old_class',
        'old_section',
        'old_department',
        'old_roll',
        'new_session',
        'new_class',
        'new_section',
        'new_department',
        'new_roll',
        'performed_by',
        'ip_address',
        'actor_context',
        'reverted_at',
        'reverted_by',
        'revert_cycle_id',
        'revert_reason',
    ];

    protected $casts = [
        'reverted_at' => 'datetime',
    ];
}
