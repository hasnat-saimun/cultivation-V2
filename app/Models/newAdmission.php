<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Marksheet;

class newAdmission extends Model
{
    protected $table = 'new_admissions';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function marksheet() {
        return $this->hasMany(Marksheet::class, 'studentId', 'id');
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
        'guardianName',
        'guardianPhone',
        'relationGuardian',
    ];
}
