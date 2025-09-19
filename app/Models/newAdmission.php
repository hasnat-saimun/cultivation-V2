<?php

namespace App\Models;

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
}
