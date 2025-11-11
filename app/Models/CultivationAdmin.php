<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\classManage as ClassManage;
use App\Models\Subject;

class CultivationAdmin extends Model
{
    // Role constants
    public const ROLE_TEACHER = 1;
    public const ROLE_CASH    = 2;
    public const ROLE_GENERAL = 3;

    // Legacy string-based relations (stay for backward compatibility)
    public function classes() {
        return $this->belongsToMany(ClassManage::class, 'teacher_classes', 'teacher_id', 'class_id');
    }
    public function subjects() {
        return $this->belongsToMany(Subject::class, 'teacher_subjects', 'teacher_id', 'subject_id');
    }
    public function getAccessClassArrayAttribute(){
        // Prefer pivot if exists, fallback to legacy comma field
        $ids = $this->classes()->pluck('class_id')->toArray();
        if (!empty($ids)) return $ids;
        return $this->accessClass ? array_filter(array_map('intval', explode(',', $this->accessClass))) : [];
    }

    public function getAccessSubjectArrayAttribute(){
        $ids = $this->subjects()->pluck('subject_id')->toArray();
        if (!empty($ids)) return $ids;
        return $this->accessSubject ? array_filter(array_map('intval', explode(',', $this->accessSubject))) : [];
    }

    public function isTeacher(): bool { return (int)($this->userType ?? 0) === self::ROLE_TEACHER; }
    public function isCash(): bool { return (int)($this->userType ?? 0) === self::ROLE_CASH; }
    public function isGeneral(): bool { return (int)($this->userType ?? 0) === self::ROLE_GENERAL; }
    
    use HasFactory;
}
