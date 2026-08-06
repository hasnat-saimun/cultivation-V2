<?php

namespace App\Services\ResultCalculation;

use App\Models\Exam;
use App\Models\ResultPublish;
use App\Models\newAdmission;
use App\Models\sessionManage;
use App\Services\PublishedResultReadyMarksService;
use App\Services\Students\StudentGenderService;
use Illuminate\Support\Collection;

class ResultCalculationBatchBuilder
{
    public function __construct(
        private BoardResultCalculator $calculator,
        private ResultCalculationInputBuilder $inputBuilder,
        private ComponentRequirementProfileBuilder $componentProfileBuilder,
        private ?PublishedResultReadyMarksService $publishedMarks = null,
        private ?PublishedResultFinalizer $publishedResultFinalizer = null,
        private ?StudentGenderService $studentGender = null,
    ) {}

    /** @return array{exam:Exam,students:Collection,entries:array<int,array>} */
    public function build(int $examId, int $classId, int $sessionId, ?int $sectionId = null, ?int $departmentId = null): array
    {
        return $this->buildBatch($examId, $classId, $sessionId, $sectionId, $departmentId, false);
    }

    /** Report-only population filter applied before calculation inputs are built. */
    public function buildForGender(int $examId, int $classId, int $sessionId, ?int $sectionId, ?int $departmentId, string $gender): array
    {
        return $this->buildBatch($examId, $classId, $sessionId, $sectionId, $departmentId, false, false, null, true, $gender);
    }

    /** @return array{exam:Exam,students:Collection,entries:array<int,array>,errors:array<int,array>} */
    public function buildTolerant(int $examId, int $classId, int $sessionId, ?int $sectionId = null, ?int $departmentId = null): array
    {
        return $this->buildBatch($examId, $classId, $sessionId, $sectionId, $departmentId, true);
    }

    /** Build only genuinely sectionless students for a class-wide publication scope. */
    public function buildSectionless(int $examId, int $classId, int $sessionId): array
    {
        $label = sessionManage::whereKey($sessionId)->value('session');
        return $this->buildBatch($examId, $classId, $sessionId, null, null, false, true, $label);
    }

    public function buildPublicationScope(int $examId, int $classId, int $sessionId, int $sectionId): array
    {
        $label = sessionManage::whereKey($sessionId)->value('session');
        return $this->buildBatch($examId, $classId, $sessionId, $sectionId, null, false, false, $label);
    }

    /** Raw pre-publication calculation used only by readiness validation. */
    public function buildSectionlessForReadiness(int $examId, int $classId, int $sessionId): array
    {
        $label = sessionManage::whereKey($sessionId)->value('session');
        return $this->buildBatch($examId, $classId, $sessionId, null, null, false, true, $label, false);
    }

    /** Raw pre-publication calculation used only by readiness validation. */
    public function buildPublicationScopeForReadiness(int $examId, int $classId, int $sessionId, int $sectionId): array
    {
        $label = sessionManage::whereKey($sessionId)->value('session');
        return $this->buildBatch($examId, $classId, $sessionId, $sectionId, null, false, false, $label, false);
    }

    private function buildBatch(
        int $examId,
        int $classId,
        int $sessionId,
        ?int $sectionId,
        ?int $departmentId,
        bool $tolerant,
        bool $sectionlessOnly = false,
        ?string $sessionLabel = null,
        bool $applyPublicationFinalization = true,
        string $gender = StudentGenderService::ALL,
    ): array
    {
        $exam = Exam::findOrFail($examId);
        $studentQuery = newAdmission::query()
            ->where('className', $classId)
            ->where(function ($query) use ($sessionId, $sessionLabel) {
                $query->where('sessName', (string) $sessionId);
                if ($sessionLabel !== null && $sessionLabel !== '') {
                    $query->orWhere('sessName', $sessionLabel);
                }
            })
            ->when($sectionId, fn ($query) => $query->where('sectionName', $sectionId))
            ->when($sectionlessOnly, fn ($query) => $query->whereNull('sectionName'))
            ->when($departmentId, fn ($query) => $query->where('departmentName', $departmentId));
        $students = $this->studentGender()->apply($studentQuery, $gender)
            ->orderByRaw('CAST(NULLIF(gender, "") AS UNSIGNED) ASC')
            ->orderByRaw('CAST(NULLIF(rollNumber, "") AS UNSIGNED) ASC')
            ->orderBy('id')
            ->get();

        $students->load(['marksheet' => function ($query) use ($examId, $classId, $sessionId, $sectionId, $sectionlessOnly) {
            $query->where('examId', $examId)->where('classId', $classId)->where('sessionId', $sessionId)
                ->when($sectionId, fn ($marks) => $marks->where('groupId', $sectionId))
                ->when($sectionlessOnly, fn ($marks) => $marks->whereNull('groupId'))
                ->orderBy('subjectId');
        }]);
        $studentGroups = $sectionId !== null || $sectionlessOnly
            ? collect([$students])
            : $students->groupBy(fn ($student) => is_numeric($student->sectionName ?? null)
                ? (string) (int) $student->sectionName
                : 'class')->values();
        foreach ($studentGroups as $scopeStudents) {
            $filterGroupId = $sectionlessOnly
                ? null
                : (is_numeric($scopeStudents->first()?->sectionName ?? null)
                    ? (int) $scopeStudents->first()->sectionName
                    : $sectionId);
            $filteredMarks = $this->publishedMarks()->filter(
                $scopeStudents->flatMap(fn ($student) => $student->marksheet),
                $examId,
                $sessionId,
                $classId,
                $filterGroupId,
            )->groupBy(fn ($mark) => (int) $mark->studentId);
            foreach ($scopeStudents as $student) {
                $student->setRelation('marksheet', $filteredMarks->get((int) $student->id, collect())->values());
            }
        }
        $subjectsByStudent = $this->inputBuilder->subjectsForStudents($students);
        $componentProfilesByStudent = $this->componentProfileBuilder->buildByStudent($students, $subjectsByStudent);
        $publishedScopes = $applyPublicationFinalization
            ? ResultPublish::query()
                ->where('status', ResultPublish::STATUS_PUBLISHED)
                ->where('examId', (string) $examId)
                ->where('sessionId', (string) $sessionId)
                ->where('classId', (string) $classId)
                ->get(['groupId', 'legacyImported'])
            : collect();
        $legacyClassPublication = $publishedScopes->contains(
            fn ($publication) => $publication->groupId === null && (bool) $publication->legacyImported
        );
        $publishedGroupIds = $publishedScopes->pluck('groupId')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->all();
        $sectionlessPublished = $publishedScopes->contains(fn ($publication) => $publication->groupId === null);
        $entries = []; $errors = [];
        foreach ($students as $student) {
            $subjects = $subjectsByStudent[(int) $student->id] ?? collect();
            try {
                $result = $this->calculator->calculate(
                    $student,
                    $exam,
                    $student->marksheet,
                    $subjects,
                    $componentProfilesByStudent[(int) $student->id] ?? [],
                );
                $studentSectionId = is_numeric($student->sectionName ?? null) ? (int) $student->sectionName : $sectionId;
                $isPublished = $studentSectionId === null
                    ? $sectionlessPublished
                    : ($legacyClassPublication || in_array($studentSectionId, $publishedGroupIds, true));
                if ($isPublished) {
                    $result = $this->publishedResultFinalizer()->finalize($result);
                }
                $entries[(int) $student->id] = [
                    'student' => $student,
                    'subjects' => $subjects,
                    'result' => $result,
                ];
            } catch (\Throwable $exception) {
                if (!$tolerant) throw $exception;
                $errors[(int) $student->id] = [
                    'student' => $student,
                    'exception' => get_class($exception),
                ];
            }
        }
        return compact('exam', 'students', 'entries', 'errors');
    }

    private function publishedMarks(): PublishedResultReadyMarksService
    {
        return $this->publishedMarks ??= app(PublishedResultReadyMarksService::class);
    }

    private function studentGender(): StudentGenderService
    {
        return $this->studentGender ??= app(StudentGenderService::class);
    }

    private function publishedResultFinalizer(): PublishedResultFinalizer
    {
        return $this->publishedResultFinalizer ??= app(PublishedResultFinalizer::class);
    }

}
