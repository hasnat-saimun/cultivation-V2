<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\classManage as ClassManage;
use App\Models\Subject;
use App\Models\sectionManage as SectionManage;
use App\Models\TeacherClassSubject;
use Illuminate\Support\Facades\DB;

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
    public function sections() {
        return $this->belongsToMany(SectionManage::class, 'teacher_sections', 'teacher_id', 'section_id');
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

    public function getAccessSectionArrayAttribute(){
        $ids = $this->sections()->pluck('section_id')->toArray();
        if (!empty($ids)) return $ids;
        return $this->accessSection ? array_filter(array_map('intval', explode(',', $this->accessSection))) : [];
    }

    public function isTeacher(): bool { return (int)($this->userType ?? 0) === self::ROLE_TEACHER; }
    public function isCash(): bool { return (int)($this->userType ?? 0) === self::ROLE_CASH; }
    public function isGeneral(): bool { return (int)($this->userType ?? 0) === self::ROLE_GENERAL; }
    
    use HasFactory;

    // Check whether this teacher may teach a specific class+subject+(optional)section
    public function canTeachClassSubject(int $classId, int $subjectId, $sectionId = null): bool
    {
        // First check per-class-subject assignments if present
        $q = TeacherClassSubject::where('teacher_id', $this->id)
            ->where('class_id', $classId)
            ->where(function($qq) use ($subjectId){
                $qq->whereNull('subject_id')->orWhere('subject_id', $subjectId);
            });

        if($sectionId !== null && $sectionId !== ''){
            // match either specific section or null (meaning no section restriction)
            $q->where(function($qq2) use ($sectionId){
                $qq2->whereNull('section_id')->orWhere('section_id', $sectionId);
            });
        }

        return $q->exists();
    }

}
