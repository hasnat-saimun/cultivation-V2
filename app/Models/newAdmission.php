<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Marksheet;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Department;
use App\Models\Subject;
use App\Services\Students\StudentOrderingService;
use Illuminate\Database\Eloquent\Builder;

class newAdmission extends Model
{
    protected $table = 'new_admissions';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function marksheet() {
        return $this->hasMany(Marksheet::class, 'studentId', 'id');
    }

    public function classInfo()
    {
        return $this->belongsTo(classManage::class, 'className', 'id');
    }

    public function sectionInfo()
    {
        return $this->belongsTo(sectionManage::class, 'sectionName', 'id');
    }

    public function sessionInfo()
    {
        return $this->belongsTo(sessionManage::class, 'sessName', 'id');
    }

    public function departmentInfo()
    {
        return $this->belongsTo(Department::class, 'departmentName', 'id');
    }

    public function religiousSubject()
    {
        return $this->belongsTo(Subject::class, 'religiousSubjectId', 'id');
    }

    public function fourthSubject()
    {
        return $this->belongsTo(Subject::class, 'fourthSubjectId', 'id');
    }

    public function scopeProfessionalOrder(Builder $query, string $table = 'new_admissions'): Builder
    {
        return app(StudentOrderingService::class)->apply($query, $table);
    }

    public function getRollNumberAttribute($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }
        return is_numeric($value)
            ? str_pad((string)((int)$value), 2, '0', STR_PAD_LEFT)
            : $value;
    }

    public function getStudentNameAttribute(): string
    {
        $name = trim(((string) ($this->fullName ?? '')) . ' ' . ((string) ($this->sureName ?? '')));
        return $name !== '' ? preg_replace('/\s+/', ' ', $name) : 'N/A';
    }

    protected $fillable = [
        'stdId',
        'fullName',
        'sureName',
        'father',
        'mother',
        'gender',
        'dob',
        'blGroup',
        'religion',
        'mail',
        'phone',
        'address',
        'sessName',
        'className',
        'departmentName',
        'sectionName',
        'religiousSubjectId',
        'fourthSubjectId',
        'rollNumber',
        'gurdianName',
        'gurdianMobile',
        'relationGurdian',
    ];
}
