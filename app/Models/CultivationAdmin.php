<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CultivationAdmin extends Model
{
    public function classes() {
        return $this->belongsToMany(ClassManage::class, 'cultivation_admins', 'id', 'accessClass');
    }
    public function subjects() {
        return $this->belongsToMany(Subject::class, 'cultivation_admins', 'id', 'accessSubject');
    }
    public function getAccessClassArrayAttribute(){
        return $this->accessClass ? explode(',', $this->accessClass) : [];
    }

    public function getAccessSubjectArrayAttribute(){
        return $this->accessSubject ? explode(',', $this->accessSubject) : [];
    }
    
    use HasFactory;
}
