<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherManagement extends Model
{
    protected $fillable = [
        'teacherId',
        'firstName', 
        'lastName',
        'fathersName',
        'mothersName',
        'gender',
        'dob',
        'designation',
        'designation_id',
        'blGroup',
        'religion',
        'email',
        'joinDate',
        'mobile',
        'address',
        'mpoIndex',
        'pdsId',
        'avatar',
        'rank'
    ];
    use HasFactory;

    public function designationModel()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }
}
