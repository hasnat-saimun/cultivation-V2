<?php

namespace App\Services\Students;

use App\Models\classManage;
use App\Models\Department;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
            'sessionId' => 'nullable|string|max:100',
            'classId' => 'nullable|string|max:100',
            'sectionId' => 'nullable|string|max:100',
            'departmentId' => 'nullable|string|max:100',
            'gender' => 'nullable|in:1,2,3',
            'search' => 'nullable|string|max:100',
        ])->valid();

        return [
            'sessionId' => isset($safe['sessionId']) ? trim((string) $safe['sessionId']) : null,
            'classId' => isset($safe['classId']) ? trim((string) $safe['classId']) : null,
            'sectionId' => isset($safe['sectionId']) ? trim((string) $safe['sectionId']) : null,
            'departmentId' => isset($safe['departmentId']) ? trim((string) $safe['departmentId']) : null,
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
        $sessionIdQuery = (clone $base)->whereNotNull('sessName')->select('sessName')->distinct();
        $sessionIds = $sessionIdQuery->pluck('sessName');

        $classScope = clone $base;
        if ($filters['sessionId']) $classScope->where('sessName', $filters['sessionId']);
        $classIdQuery = $classScope->whereNotNull('className')->select('className')->distinct();
        $classIds = $classIdQuery->pluck('className');

        $sectionScope = clone $classScope;
        if ($filters['classId']) $sectionScope->where('className', $filters['classId']);
        $sectionIdQuery = $sectionScope->whereNotNull('sectionName')->select('sectionName')->distinct();
        $sectionIds = $sectionIdQuery->pluck('sectionName');

        $departmentScope = clone $sectionScope;
        if ($filters['sectionId']) $departmentScope->where('sectionName', $filters['sectionId']);
        $departmentIdQuery = $departmentScope->whereNotNull('departmentName')->select('departmentName')->distinct();
        $departmentIds = $departmentIdQuery->pluck('departmentName');

        $sessionQuery = sessionManage::whereIn('id', $sessionIds)->orderBy('session');
        $classQuery = classManage::whereIn('id', $classIds)->orderBy('className');
        $sectionQuery = sectionManage::whereIn('id', $sectionIds)->orderBy('section');
        $departmentQuery = Department::whereIn('id', $departmentIds)->orderBy('departmentName');
        $options = [
            'sessions' => $this->resolvedOrSourceOptions($sessionIds, $sessionQuery->get(['id', 'session']), 'sessions', 'session'),
            'classes' => $this->resolvedOrSourceOptions($classIds, $classQuery->get(['id', 'className']), 'classes', 'className'),
            'sections' => $this->resolvedOrSourceOptions($sectionIds, $sectionQuery->get(['id', 'section']), 'sections', 'section'),
            'departments' => $this->resolvedOrSourceOptions($departmentIds, $departmentQuery->get(['id', 'departmentName']), 'departments', 'departmentName'),
            'genderOptions' => ['1' => 'Male', '2' => 'Female', '3' => 'Others'],
        ];

        return $options;
    }

    private function resolvedOrSourceOptions(
        Collection $sourceValues,
        Collection $resolved,
        string $group,
        string $labelField,
    ): Collection {
        $sourceValues = $sourceValues
            ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();

        if ($resolved->isNotEmpty() || $sourceValues->isEmpty()) {
            return $resolved;
        }

        Log::warning('student_filter_options_fallback', [
            'host' => request()->getHost(),
            'option_group' => $group,
            'distinct_source_count' => $sourceValues->count(),
            'resolved_master_count' => $resolved->count(),
        ]);

        return $sourceValues
            ->sort(fn ($left, $right) => strnatcasecmp((string) $left, (string) $right))
            ->values()
            ->map(fn ($value) => (object) ['id' => $value, $labelField => $value]);
    }
}
