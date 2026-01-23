<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function teacherManagement()
    {
        return $this->hasMany(TeacherManagement::class);
    }

    public function staffManagement()
    {
        return $this->hasMany(StaffManagement::class);
    }

    public function managingCommittee()
    {
        return $this->hasMany(ManagingComittee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function byType($type)
    {
        return self::where('type', $type)->where('is_active', true)->orderBy('sort_order')->get();
    }

    public static function teacherDesignations()
    {
        return self::byType('teacher');
    }

    public static function staffDesignations()
    {
        return self::byType('staff');
    }

    public static function committeeDesignations()
    {
        return self::byType('committee');
    }
}
