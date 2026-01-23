<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffManagement extends Model
{
    use HasFactory;

    protected $fillable = [
        'staffId',
        'firstName',
        'lastName',
        'fathersName',
        'mothersName',
        'address',
        'gender',
        'dob',
        'joinDate',
        'designation',
        'designation_id',
        'position',
        'profileId',
        'email',
        'mobile',
        'blGroup',
        'religion',
        'avatar',
        'rank',
    ];

    public function designationModel()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }
}
