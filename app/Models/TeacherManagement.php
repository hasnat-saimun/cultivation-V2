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
        'blGroup',
        'religion',
        'email',
        'joinDate',
        'mobile',
        'address',
        'mpoIndex',
        'pdoId',
        'avatar',
        'rank'
    ];
    use HasFactory;
}
