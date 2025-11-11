<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_date','class_id','section_id','session_id','student_id','teacher_id','status'
    ];

    public function student(){ return $this->belongsTo(newAdmission::class,'student_id'); }
    public function teacher(){ return $this->belongsTo(CultivationAdmin::class,'teacher_id'); }
    public function class(){ return $this->belongsTo(classManage::class,'class_id'); }
    public function section(){ return $this->belongsTo(sectionManage::class,'section_id'); }
    public function session(){ return $this->belongsTo(sessionManage::class,'session_id'); }
}
