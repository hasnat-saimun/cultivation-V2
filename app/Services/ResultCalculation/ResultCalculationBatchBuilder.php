<?php

namespace App\Services\ResultCalculation;

use App\Models\Exam;
use App\Models\newAdmission;
use App\Models\sessionManage;
use Illuminate\Support\Collection;

class ResultCalculationBatchBuilder
{
    public function __construct(
        private BoardResultCalculator $calculator,
        private ResultCalculationInputBuilder $inputBuilder,
    ) {}

    /** @return array{exam:Exam,students:Collection,entries:array<int,array>} */
    public function build(int $examId, int $classId, int $sessionId, ?int $sectionId = null, ?int $departmentId = null): array
    {
        return $this->buildBatch($examId, $classId, $sessionId, $sectionId, $departmentId, false);
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

    private function buildBatch(
        int $examId,
        int $classId,
        int $sessionId,
        ?int $sectionId,
        ?int $departmentId,
        bool $tolerant,
        bool $sectionlessOnly = false,
        ?string $sessionLabel = null,
    ): array
    {
        $exam = Exam::findOrFail($examId);
        $students = newAdmission::query()
            ->where('className', $classId)
            ->where(function ($query) use ($sessionId, $sessionLabel) {
                $query->where('sessName', (string) $sessionId);
                if ($sessionLabel !== null && $sessionLabel !== '') {
                    $query->orWhere('sessName', $sessionLabel);
                }
            })
            ->when($sectionId, fn ($query) => $query->where('sectionName', $sectionId))
            ->when($sectionlessOnly, fn ($query) => $query->whereNull('sectionName'))
            ->when($departmentId, fn ($query) => $query->where('departmentName', $departmentId))
            ->orderByRaw('CAST(NULLIF(rollNumber, "") AS UNSIGNED) ASC')
            ->orderBy('id')
            ->get();

        $students->load(['marksheet' => function ($query) use ($examId, $classId, $sessionId, $sectionId, $sectionlessOnly) {
            $query->where('examId', $examId)->where('classId', $classId)->where('sessionId', $sessionId)
                ->when($sectionId, fn ($marks) => $marks->where('groupId', $sectionId))
                ->when($sectionlessOnly, fn ($marks) => $marks->whereNull('groupId'))
                ->orderBy('subjectId');
        }]);
        $subjectsByStudent = $this->inputBuilder->subjectsForStudents($students);
        $entries = []; $errors = [];
        foreach ($students as $student) {
            $subjects = $subjectsByStudent[(int) $student->id] ?? collect();
            try {
                $entries[(int) $student->id] = [
                    'student' => $student,
                    'subjects' => $subjects,
                    'result' => $this->calculator->calculate($student, $exam, $student->marksheet, $subjects),
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
}
