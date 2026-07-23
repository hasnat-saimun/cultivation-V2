<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerConfig extends Model
{
    use HasFactory;

    public const RANKING_METHOD_GRADING = 'grading';
    public const RANKING_METHOD_TOTAL_MARKS = 'total_marks';

    public const RANKING_METHODS = [
        self::RANKING_METHOD_GRADING,
        self::RANKING_METHOD_TOTAL_MARKS,
    ];

    protected $fillable = ['ranking_method'];
}
