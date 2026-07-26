<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurriculumSubjectMapping extends Model
{
    use HasFactory;

    protected $table = 'curriculum_subject_mappings';

    protected $fillable = [
        'session_id',
        'class_id',
        'section_id',
        'department_id',
        'subject_id',
        'mapping_type',
        'sort_order',
        'is_active',
        'source',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'subject_id' => 'int',
        'sort_order' => 'int',
        'is_active' => 'bool',
    ];

    public const TYPE_MAIN = 'main';

    public static function normalizeSectionScope(?int $sectionId): string
    {
        return $sectionId === null ? 'class' : 'section:'.$sectionId;
    }

    public static function normalizeDepartmentScope(?int $departmentId): string
    {
        return $departmentId === null ? 'all' : 'department:'.$departmentId;
    }

    /** @return array<int,string> */
    public static function sectionScopeCandidates(?int $sectionId): array
    {
        return $sectionId === null
            ? ['class', 'section:all']
            : ['section:'.$sectionId];
    }

    /** @return array<int,string> */
    public static function departmentScopeCandidates(?int $departmentId): array
    {
        return $departmentId === null
            ? ['all', 'department:all']
            : ['department:'.$departmentId];
    }
}
