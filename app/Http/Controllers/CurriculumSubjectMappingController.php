<?php

namespace App\Http\Controllers;

use App\Models\CurriculumSubjectMapping;
use App\Models\Department;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Subject;
use App\Services\DepartmentBasedClassDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CurriculumSubjectMappingController extends Controller
{
    public function __construct(private DepartmentBasedClassDetector $departmentBasedClasses) {}

    public function index(Request $request): View
    {
        $sessionId = $request->filled('sessionId') ? (int) $request->input('sessionId') : null;
        $classId = $request->filled('classId') ? (int) $request->input('classId') : null;
        $sectionId = $request->filled('sectionId') ? (int) $request->input('sectionId') : null;
        $departmentId = $request->filled('departmentId') ? (int) $request->input('departmentId') : null;
        $mappingType = $request->input('mappingType', CurriculumSubjectMapping::TYPE_MAIN);

        $scopeMappings = collect();
        if ($sessionId !== null && $classId !== null) {
            $mappingQuery = CurriculumSubjectMapping::query()
                ->where('session_id', (string) $sessionId)
                ->where('class_id', (string) $classId)
                ->where('mapping_type', (string) $mappingType);

            $this->applyScopeFilter($mappingQuery, $sectionId, $departmentId);

            $scopeMappings = $mappingQuery
                ->orderBy('sort_order')
                ->orderBy('subject_id')
                ->get();
        }

        return view('result.curriculum-mapping-manage', [
            'sessions' => sessionManage::orderBy('id', 'DESC')->get(['id', 'session']),
            'classes' => classManage::orderBy('id')->get(['id', 'className']),
            'sections' => sectionManage::orderBy('id')->get(['id', 'section']),
            'departments' => Department::orderBy('id')->get(['id', 'departmentName']),
            'subjects' => Subject::query()
                ->orderByRaw("CASE WHEN subjectType = 'Optional' THEN 1 ELSE 0 END")
                ->orderBy('subjectName')
                ->get(['id', 'subjectName', 'subjectType', 'isReligious']),
            'scopeMappings' => $scopeMappings,
            'selected' => [
                'sessionId' => $sessionId,
                'classId' => $classId,
                'sectionId' => $sectionId,
                'departmentId' => $departmentId,
                'mappingType' => $mappingType,
            ],
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sessionId' => ['required', 'integer', 'min:1'],
            'classId' => ['required', 'integer', 'min:1'],
            'sectionId' => ['nullable', 'integer', 'min:1'],
            'departmentId' => ['nullable', 'integer', 'min:1'],
            'mappingType' => ['nullable', 'string', 'in:'.CurriculumSubjectMapping::TYPE_MAIN],
            'subjectIds' => ['array'],
            'subjectIds.*' => ['integer', 'min:1'],
            'sortOrders' => ['array'],
            'sortOrders.*' => ['nullable', 'integer', 'min:1'],
        ]);

        $class = classManage::findOrFail((int) $data['classId']);
        $departmentBased = $this->departmentBasedClasses->isDepartmentBasedClass((string) $class->className);

        $sessionId = (int) $data['sessionId'];
        $classId = (int) $data['classId'];
        $sectionId = isset($data['sectionId']) ? (int) $data['sectionId'] : null;
        $departmentId = isset($data['departmentId']) ? (int) $data['departmentId'] : null;
        $mappingType = (string) ($data['mappingType'] ?? CurriculumSubjectMapping::TYPE_MAIN);
        if (!$departmentBased) {
            $departmentId = null;
        }

        $subjectIds = collect($data['subjectIds'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $validSubjects = Subject::query()->whereIn('id', $subjectIds)->get(['id', 'subjectName']);
        $validSubjectIds = $validSubjects->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $selectedSet = array_fill_keys($validSubjectIds, true);
        $rawSortOrders = collect($data['sortOrders'] ?? [])
            ->mapWithKeys(fn ($value, $key) => [(int) $key => ($value === null || $value === '' ? null : (int) $value)]);

        $providedOrders = collect($validSubjectIds)
            ->map(fn (int $subjectId) => $rawSortOrders->get($subjectId))
            ->filter(fn ($order) => $order !== null)
            ->values();
        if ($providedOrders->count() !== $providedOrders->unique()->count()) {
            throw ValidationException::withMessages([
                'sortOrders' => ['Sort order values must be unique for selected subjects.'],
            ]);
        }

        try {
            $result = DB::transaction(function () use ($sessionId, $classId, $sectionId, $departmentId, $mappingType, $validSubjectIds, $selectedSet, $rawSortOrders): array {
                $existingRows = CurriculumSubjectMapping::query()
                    ->where('session_id', (string) $sessionId)
                    ->where('class_id', (string) $classId)
                    ->where('mapping_type', $mappingType);

                $this->applyScopeFilter($existingRows, $sectionId, $departmentId);

                $existingRows = $existingRows->lockForUpdate()->get();
                $existingBySubject = $existingRows->keyBy(fn ($row) => (int) $row->subject_id);
                $nextSortOrder = (int) ($existingRows->max('sort_order') ?? 0) + 1;

                $selectedSubjects = collect($validSubjectIds)
                    ->map(function (int $subjectId) use ($rawSortOrders, $existingBySubject, &$nextSortOrder): array {
                        $existing = $existingBySubject->get($subjectId);
                        $requested = $rawSortOrders->get($subjectId);
                        $resolved = $requested;
                        if ($resolved === null && $existing) {
                            $resolved = (int) $existing->sort_order;
                        }
                        if ($resolved === null || $resolved <= 0) {
                            $resolved = $nextSortOrder++;
                        }

                        return [
                            'subject_id' => $subjectId,
                            'sort_order' => $resolved,
                        ];
                    })
                    ->sort(function (array $left, array $right): int {
                        $orderCompare = $left['sort_order'] <=> $right['sort_order'];
                        if ($orderCompare !== 0) {
                            return $orderCompare;
                        }

                        return $left['subject_id'] <=> $right['subject_id'];
                    })
                    ->values();

                $created = 0;
                $reactivated = 0;
                $unchanged = 0;
                $deactivated = 0;

                foreach ($selectedSubjects as $selected) {
                    $subjectId = (int) $selected['subject_id'];
                    $sortOrder = (int) $selected['sort_order'];
                    /** @var CurriculumSubjectMapping|null $existing */
                    $existing = $existingBySubject->get((int) $subjectId);
                    if ($existing === null) {
                        CurriculumSubjectMapping::query()->create([
                            'session_id' => (string) $sessionId,
                            'class_id' => (string) $classId,
                            'section_id' => $sectionId === null ? null : (string) $sectionId,
                            'department_id' => $departmentId === null ? null : (string) $departmentId,
                            'subject_id' => (int) $subjectId,
                            'mapping_type' => $mappingType,
                            'sort_order' => $sortOrder,
                            'is_active' => 1,
                            'source' => 'manual',
                        ]);
                        $created++;
                        continue;
                    }

                    $isActive = (bool) $existing->is_active;
                    $payload = [
                        'sort_order' => $sortOrder,
                        'source' => 'manual',
                    ];
                    if (!$isActive) {
                        $payload['is_active'] = 1;
                        $reactivated++;
                    } else {
                        $unchanged++;
                    }
                    $existing->fill($payload);
                    if ($existing->isDirty()) {
                        $existing->save();
                    }
                }

                foreach ($existingRows as $existing) {
                    $subjectId = (int) $existing->subject_id;
                    if (!isset($selectedSet[$subjectId]) && (bool) $existing->is_active) {
                        $existing->is_active = 0;
                        $existing->source = 'manual';
                        $existing->save();
                        $deactivated++;
                    }
                }

                return compact('created', 'reactivated', 'unchanged', 'deactivated');
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateKeyException($exception)) {
                $duplicateNames = $this->duplicateSubjectNames($sessionId, $classId, $sectionId, $departmentId, $mappingType, $validSubjects);
                $nameSuffix = $duplicateNames === []
                    ? ''
                    : ' Subjects: '.implode(', ', $duplicateNames).'.';

                return redirect()->route('resultCurriculumMappingManage', [
                    'sessionId' => $sessionId,
                    'classId' => $classId,
                    'sectionId' => $sectionId,
                    'departmentId' => $departmentId,
                    'mappingType' => $mappingType,
                ])->with('error', 'Selected subject is already mapped for this session, class, section and department scope.'.$nameSuffix);
            }

            throw $exception;
        }

        return redirect()->route('resultCurriculumMappingManage', [
            'sessionId' => $sessionId,
            'classId' => $classId,
            'sectionId' => $sectionId,
            'departmentId' => $departmentId,
            'mappingType' => $mappingType,
        ])->with('success', sprintf(
            'Curriculum mapping saved: %d added, %d already mapped, %d reactivated, %d deactivated.',
            $result['created'],
            $result['unchanged'],
            $result['reactivated'],
            $result['deactivated']
        ));
    }

    public function copyPreview(Request $request): JsonResponse
    {
        $data = $this->validateCopyRequest($request);

        $rows = CurriculumSubjectMapping::query()
            ->where('session_id', (string) $data['sourceSessionId'])
            ->where('class_id', (string) $data['sourceClassId'])
            ->where('mapping_type', CurriculumSubjectMapping::TYPE_MAIN)
            ->where('is_active', 1);

        $this->applyScopeFilter($rows, $data['sourceSectionId'], $data['sourceDepartmentId']);

        $rows = $rows
            ->orderBy('sort_order')
            ->orderBy('subject_id')
            ->get();

        $subjectNames = Subject::query()
            ->whereIn('id', $rows->pluck('subject_id')->all())
            ->pluck('subjectName')
            ->values()
            ->all();

        return response()->json([
            'count' => $rows->count(),
            'subjectNames' => $subjectNames,
        ]);
    }

    public function copy(Request $request): RedirectResponse
    {
        $data = $this->validateCopyRequest($request);

        $targetClass = classManage::findOrFail($data['targetClassId']);
        $targetDepartmentBased = $this->departmentBasedClasses->isDepartmentBasedClass((string) $targetClass->className);
        $targetDepartmentId = $targetDepartmentBased ? $data['targetDepartmentId'] : null;

        $sourceRowsQuery = CurriculumSubjectMapping::query()
            ->where('session_id', (string) $data['sourceSessionId'])
            ->where('class_id', (string) $data['sourceClassId'])
            ->where('mapping_type', CurriculumSubjectMapping::TYPE_MAIN)
            ->where('is_active', 1);

        $this->applyScopeFilter(
            $sourceRowsQuery,
            $data['sourceSectionId'],
            $data['sourceDepartmentId']
        );

        $sourceRows = $sourceRowsQuery
            ->orderBy('sort_order')
            ->orderBy('subject_id')
            ->get();

        DB::transaction(function () use ($data, $targetDepartmentId, $sourceRows): void {
            $deleteQuery = CurriculumSubjectMapping::query()
                ->where('session_id', (string) $data['targetSessionId'])
                ->where('class_id', (string) $data['targetClassId'])
                ->where('mapping_type', CurriculumSubjectMapping::TYPE_MAIN);
            $this->applyScopeFilter($deleteQuery, $data['targetSectionId'], $targetDepartmentId);
            $deleteQuery->delete();

            foreach ($sourceRows as $sourceRow) {
                CurriculumSubjectMapping::query()->create([
                    'session_id' => (string) $data['targetSessionId'],
                    'class_id' => (string) $data['targetClassId'],
                    'section_id' => $data['targetSectionId'] === null ? null : (string) $data['targetSectionId'],
                    'department_id' => $targetDepartmentId === null ? null : (string) $targetDepartmentId,
                    'subject_id' => (int) $sourceRow->subject_id,
                    'mapping_type' => CurriculumSubjectMapping::TYPE_MAIN,
                    'sort_order' => (int) $sourceRow->sort_order,
                    'is_active' => 1,
                    'source' => 'copy',
                ]);
            }
        });

        return redirect()->route('resultCurriculumMappingManage', [
            'sessionId' => $data['targetSessionId'],
            'classId' => $data['targetClassId'],
            'sectionId' => $data['targetSectionId'],
            'departmentId' => $targetDepartmentId,
        ])->with('success', 'Curriculum mappings copied.');
    }

    /** @return array<string,int|null> */
    private function validateCopyRequest(Request $request): array
    {
        $data = $request->validate([
            'sourceSessionId' => ['required', 'integer', 'min:1'],
            'sourceClassId' => ['required', 'integer', 'min:1'],
            'sourceSectionId' => ['nullable', 'integer', 'min:1'],
            'sourceDepartmentId' => ['nullable', 'integer', 'min:1'],
            'targetSessionId' => ['required', 'integer', 'min:1'],
            'targetClassId' => ['required', 'integer', 'min:1'],
            'targetSectionId' => ['nullable', 'integer', 'min:1'],
            'targetDepartmentId' => ['nullable', 'integer', 'min:1'],
        ]);

        return [
            'sourceSessionId' => (int) $data['sourceSessionId'],
            'sourceClassId' => (int) $data['sourceClassId'],
            'sourceSectionId' => isset($data['sourceSectionId']) ? (int) $data['sourceSectionId'] : null,
            'sourceDepartmentId' => isset($data['sourceDepartmentId']) ? (int) $data['sourceDepartmentId'] : null,
            'targetSessionId' => (int) $data['targetSessionId'],
            'targetClassId' => (int) $data['targetClassId'],
            'targetSectionId' => isset($data['targetSectionId']) ? (int) $data['targetSectionId'] : null,
            'targetDepartmentId' => isset($data['targetDepartmentId']) ? (int) $data['targetDepartmentId'] : null,
        ];
    }

    private function applyScopeFilter($query, ?int $sectionId, ?int $departmentId): void
    {
        $sectionScopes = CurriculumSubjectMapping::sectionScopeCandidates($sectionId);
        $departmentScopes = CurriculumSubjectMapping::departmentScopeCandidates($departmentId);

        $query->where(function ($scopeQuery) use ($sectionScopes, $sectionId): void {
            $scopeQuery->whereIn('normalized_section_scope', $sectionScopes)
                ->orWhere(function ($fallback) use ($sectionId): void {
                    $fallback->whereNull('normalized_section_scope');
                    if ($sectionId === null) {
                        $fallback->where(function ($allScope): void {
                            $allScope->whereNull('section_id')->orWhere('section_id', '');
                        });
                    } else {
                        $fallback->where('section_id', (string) $sectionId);
                    }
                });
        });

        $query->where(function ($scopeQuery) use ($departmentScopes, $departmentId): void {
            $scopeQuery->whereIn('normalized_department_scope', $departmentScopes)
                ->orWhere(function ($fallback) use ($departmentId): void {
                    $fallback->whereNull('normalized_department_scope');
                    if ($departmentId === null) {
                        $fallback->where(function ($allScope): void {
                            $allScope->whereNull('department_id')->orWhere('department_id', '');
                        });
                    } else {
                        $fallback->where('department_id', (string) $departmentId);
                    }
                });
        });
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1062;
    }

    /** @return array<int,string> */
    private function duplicateSubjectNames(
        int $sessionId,
        int $classId,
        ?int $sectionId,
        ?int $departmentId,
        string $mappingType,
        $selectedSubjects
    ): array {
        $subjectIds = collect($selectedSubjects)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($subjectIds === []) {
            return [];
        }

        $rows = CurriculumSubjectMapping::query()
            ->where('session_id', (string) $sessionId)
            ->where('class_id', (string) $classId)
            ->where('mapping_type', $mappingType)
            ->whereIn('subject_id', $subjectIds);
        $this->applyScopeFilter($rows, $sectionId, $departmentId);

        $existingSubjectIds = $rows->pluck('subject_id')->map(fn ($id) => (int) $id)->all();
        if ($existingSubjectIds === []) {
            return [];
        }

        return Subject::query()
            ->whereIn('id', $existingSubjectIds)
            ->orderBy('subjectName')
            ->pluck('subjectName')
            ->values()
            ->all();
    }
}
