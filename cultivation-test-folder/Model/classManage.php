<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CultivationAdmin;

class classManage extends Model
{
    public function attendanceAdmins()
    {
        return $this->hasMany(CultivationAdmin::class, 'primary_class_id');
    }
}
