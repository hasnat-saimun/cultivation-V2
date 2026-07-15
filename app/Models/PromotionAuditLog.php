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
        'old_session',
        'old_class',
        'old_section',
        'old_roll',
        'new_session',
        'new_class',
        'new_section',
        'new_roll',
        'performed_by',
        'ip_address',
    ];
}
