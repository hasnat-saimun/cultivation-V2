<?php

namespace App\Services\Students;

use App\Models\classManage;
use App\Models\Department;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class StudentFilterService
{
    /**
     * Build the complete, shared view contract used by student list screens.
     */
    public function viewPayload(Request $request, string $action, ?string $resetUrl = null): array
    {
        $filters = $this->filters($request);

        return [
            'filters' => $filters,
            'filterOptions' => $this->options($filters),
            'filterAction' => $action,
            'filterResetUrl' => $resetUrl ?? $action,
        ];
    }

    public function filters(Request $request): array
    {
        $safe = Validator::make($request->query(), [
            'sessionId' => 'nullable|integer|min:1',
            'classId' => 'nullable|integer|min:1',
            'sectionId' => 'nullable|integer|min:1',
            'departmentId' => 'nullable|integer|min:1',
            'gender' => 'nullable|in:1,2,3',
            'search' => 'nullable|string|max:100',
        ])->valid();

        return [
            'sessionId' => isset($safe['sessionId']) ? (int) $safe['sessionId'] : null,
            'classId' => isset($safe['classId']) ? (int) $safe['classId'] : null,
            'sectionId' => isset($safe['sectionId']) ? (int) $safe['sectionId'] : null,
            'departmentId' => isset($safe['departmentId']) ? (int) $safe['departmentId'] : null,
            'gender' => isset($safe['gender']) ? (string) $safe['gender'] : null,
            'search' => isset($safe['search']) ? trim((string) $safe['search']) : null,
        ];
    }

    public function query(array $filters): Builder
    {
        $query = newAdmission::query()->with([
            'classInfo:id,className', 'sectionInfo:id,section',
            'sessionInfo:id,session', 'departmentInfo:id,departmentName',
        ])->select('new_admissions.*');

        foreach (['sessionId' => 'sessName', 'classId' => 'className', 'sectionId' => 'sectionName', 'departmentId' => 'departmentName'] as $filter => $column) {
            if (! empty($filters[$filter])) {
                $query->where('new_admissions.'.$column, $filters[$filter]);
            }
        }
        if (! empty($filters['gender'])) {
            $query->where('new_admissions.gender', $filters['gender']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $nested) use ($search): void {
                $nested->where('new_admissions.fullName', 'like', "%{$search}%")
                    ->orWhere('new_admissions.sureName', 'like', "%{$search}%")
                    ->orWhere('new_admissions.stdId', 'like', "%{$search}%")
                    ->orWhere('new_admissions.phone', 'like', "%{$search}%");
            });
        }

        return $query->professionalOrder();
    }

    public function options(array $filters): array
    {
        $base = newAdmission::query();
        $sessionIds = (clone $base)->whereNotNull('sessName')->distinct()->pluck('sessName');

        $classScope = clone $base;
        if ($filters['sessionId']) $classScope->where('sessName', $filters['sessionId']);
        $classIds = $classScope->whereNotNull('className')->distinct()->pluck('className');

        $sectionScope = clone $classScope;
        if ($filters['classId']) $sectionScope->where('className', $filters['classId']);
        $sectionIds = $sectionScope->whereNotNull('sectionName')->distinct()->pluck('sectionName');

        $departmentScope = clone $sectionScope;
        if ($filters['sectionId']) $departmentScope->where('sectionName', $filters['sectionId']);
        $departmentIds = $departmentScope->whereNotNull('departmentName')->distinct()->pluck('departmentName');

        return [
            'sessions' => sessionManage::whereIn('id', $sessionIds)->orderBy('session')->get(['id', 'session']),
            'classes' => classManage::whereIn('id', $classIds)->orderBy('className')->get(['id', 'className']),
            'sections' => sectionManage::whereIn('id', $sectionIds)->orderBy('section')->get(['id', 'section']),
            'departments' => Department::whereIn('id', $departmentIds)->orderBy('departmentName')->get(['id', 'departmentName']),
            'genderOptions' => ['1' => 'Male', '2' => 'Female', '3' => 'Others'],
        ];
    }
}
