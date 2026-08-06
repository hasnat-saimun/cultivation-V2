<?php

namespace App\Services\ResultCalculation;

use App\Models\Exam;
use App\Models\GradeList;
use App\Models\ResultPublish;
use App\Models\classManage;
use App\Models\Classes;
use App\Models\Department;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Support\Facades\Log;
use App\Services\Students\StudentGenderService;

class BulkTranscriptResultBuilder
{
    public function __construct(
        private BoardResultCalculator $calculator,
        private TranscriptResultPresenter $presenter,
        private ResultCalculationInputBuilder $inputBuilder,
        private ComponentRequirementProfileBuilder $componentProfileBuilder,
        private ?PublishedResultFinalizer $publishedResultFinalizer = null,
        private ?ResultCalculationBatchBuilder $batchBuilder = null,
        private ?ResultMeritPositionService $meritPositionService = null,
    ) {}

    public function build(iterable $students, Exam $exam): array
    {
        return $this->buildPrepared($students, $exam, GradeList::all());
    }

    public function buildWithGradeRows(
        iterable $students,
        Exam $exam,
        iterable $gradeRows,
        string $gender = StudentGenderService::ALL,
    ): array
    {
        return $this->buildPrepared($students, $exam, collect($gradeRows), $gender);
    }

    private function buildPrepared(
        iterable $students,
        Exam $exam,
        $gradeRows,
        string $gender = StudentGenderService::ALL,
    ): array
    {
        $students = collect($students)->values();
        $subjectsByStudent = $this->inputBuilder->subjectsForStudents($students);
        $componentProfilesByStudent = $this->componentProfileBuilder->buildByStudent($students, $subjectsByStudent);
        $sessionNames = sessionManage::whereIn('id', $students->pluck('sessName')->filter())->pluck('session', 'id');
        $classIds = $students->pluck('className')->filter()->unique();
        $classNames = classManage::whereIn('id', $classIds)->pluck('className', 'id');
        $fallbackClassNames = Classes::whereIn('id', $classIds)->pluck('className', 'id');
        $sectionNames = sectionManage::whereIn('id', $students->pluck('sectionName')->filter())->pluck('section', 'id');
        $departmentIds = $students->map(fn ($student) => $student->departmentName ?? $student->departmentId ?? null)->filter()->unique();
        $departmentNames = Department::whereIn('id', $departmentIds)->pluck('departmentName', 'id');
        $sessionIds = $students->mapWithKeys(function ($student) {
            $raw = $student->sessName ?? null;
            $id = is_numeric($raw) ? (int) $raw : (int) (sessionManage::where('session', (string) $raw)->value('id') ?? 0);
            return [(int) $student->id => $id];
        });
        $meritPositions = collect();
        $students->groupBy(function ($student) use ($sessionIds) {
            return implode(':', [
                (int) ($sessionIds[(int) $student->id] ?? 0),
                (int) ($student->className ?? 0),
                is_numeric($student->sectionName ?? null) ? (int) $student->sectionName : 0,
                is_numeric($student->departmentName ?? null) ? (int) $student->departmentName : 0,
            ]);
        })->each(function ($scopeStudents) use ($exam, $sessionIds, $meritPositions, $gender) {
            $student = $scopeStudents->first();
            $sessionId = (int) ($sessionIds[(int) $student->id] ?? 0);
            $classId = (int) ($student->className ?? 0);
            if ($sessionId <= 0 || $classId <= 0) return;
            $batch = $this->batchBuilder()->buildForGender(
                (int) $exam->id,
                $classId,
                $sessionId,
                is_numeric($student->sectionName ?? null) ? (int) $student->sectionName : null,
                is_numeric($student->departmentName ?? null) ? (int) $student->departmentName : null,
                $gender,
            );
            foreach ($this->meritPositionService()->positions($batch['entries']) as $studentId => $position) {
                $meritPositions[(int) $studentId] = (int) $position;
            }
        });
        $publishedScopes = ResultPublish::query()
            ->where('status', ResultPublish::STATUS_PUBLISHED)
            ->where('examId', (string) $exam->id)
            ->whereIn('sessionId', $sessionIds->filter()->unique()->map(fn ($id) => (string) $id))
            ->whereIn('classId', $classIds->map(fn ($id) => (string) $id))
            ->get(['sessionId', 'classId', 'groupId', 'legacyImported'])
            ->groupBy(fn ($publication) => $publication->sessionId.':'.$publication->classId);
        return $students->map(function ($student) use ($exam, $subjectsByStudent, $componentProfilesByStudent, $sessionNames, $classNames, $fallbackClassNames, $sectionNames, $departmentNames, $gradeRows, $sessionIds, $publishedScopes, $meritPositions) {
            $classId = (int) ($student->className ?? 0);
            $departmentId = (int) ($student->departmentName ?? $student->departmentId ?? 0);
            $transcript = $this->baseTranscript($student, [
                'sessionName' => (string) ($sessionNames[(int) ($student->sessName ?? 0)] ?? '-'),
                'className' => (string) ($classNames[$classId] ?? $fallbackClassNames[$classId] ?? '-'),
                'sectionName' => (string) ($sectionNames[(int) ($student->sectionName ?? $student->sectionId ?? 0)] ?? '-'),
                'departmentName' => (string) ($departmentNames[$departmentId] ?? '-'),
            ]);
            $transcript['meritRank'] = $meritPositions[(int) $student->id] ?? null;
            try {
                $subjects = $subjectsByStudent[(int) $student->id] ?? collect();
                $result = $this->calculator->calculate(
                    $student,
                    $exam,
                    $student->marksheet,
                    $subjects,
                    $componentProfilesByStudent[(int) $student->id] ?? [],
                );
                $sessionId = (int) ($sessionIds[(int) $student->id] ?? 0);
                $sectionId = is_numeric($student->sectionName ?? null) ? (int) $student->sectionName : null;
                $publications = $publishedScopes->get($sessionId.':'.$classId, collect());
                $isPublished = $publications->contains(function ($publication) use ($sectionId) {
                    if ($sectionId === null) return $publication->groupId === null;
                    return (string) $publication->groupId === (string) $sectionId
                        || ($publication->groupId === null && (bool) $publication->legacyImported);
                });
                if ($isPublished) {
                    $result = $this->publishedResultFinalizer()->finalize($result);
                }
                $transcript['result'] = $this->presenter->presentWithGradeRows($result, $subjects, $student->marksheet, $gradeRows);
                $transcript['usingBulkResultEngine'] = true;
            } catch (\Throwable $exception) {
                Log::error('Centralized bulk transcript batch calculation failed; batch blocked.', [
                    'student_id' => (int) $student->id,
                    'exam_id' => (int) $exam->id,
                    'class_id' => (int) ($student->className ?? 0),
                    'session_id' => (int) ($student->sessName ?? 0),
                    'exception' => get_class($exception),
                ]);
                throw $exception;
            }
            return $transcript;
        })->all();
    }

    private function baseTranscript($student, array $metadata): array
    {
        return [
            'studentDetails' => $student,
            'meritRank' => null,
            'maxMarkedSubjects' => 0,
            'studentMarkedSubjects' => 0,
            'hideForMaxRule' => false,
            'usingBulkResultEngine' => false,
            'result' => null,
            'metadata' => $metadata,
            'studentIdentity' => [
                'studentId' => $student->stdId ?? $student->id,
                'studentName' => trim(($student->fullName ?? '').' '.($student->sureName ?? '')),
                'fatherName' => $student->fatherName ?? $student->father ?? '',
                'motherName' => $student->motherName ?? $student->mother ?? '',
                'rollNumber' => is_numeric($student->rollNumber)
                    ? str_pad((string) ((int) $student->rollNumber), 2, '0', STR_PAD_LEFT)
                    : (string) ($student->rollNumber ?? ''),
            ],
        ];
    }

    private function publishedResultFinalizer(): PublishedResultFinalizer
    {
        return $this->publishedResultFinalizer ??= app(PublishedResultFinalizer::class);
    }

    private function batchBuilder(): ResultCalculationBatchBuilder
    {
        return $this->batchBuilder ??= app(ResultCalculationBatchBuilder::class);
    }

    private function meritPositionService(): ResultMeritPositionService
    {
        return $this->meritPositionService ??= app(ResultMeritPositionService::class);
    }
}
