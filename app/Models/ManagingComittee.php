<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagingComittee extends Model
{
    use HasFactory;

    protected $fillable = [
        'fullName',
        'qualification',
        'designation',
        'designation_id',
        'jobDetails',
        'mobile',
        'email',
        'address',
        'validYear',
        'status',
        'avatar',
    ];

    public function designationModel()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }
}
