<?php

namespace App\Services\ResultCalculation;

use App\Models\Exam;
use App\Models\GradeList;
use App\Models\classManage;
use App\Models\Classes;
use App\Models\Department;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Support\Facades\Log;

class BulkTranscriptResultBuilder
{
    public function __construct(
        private BoardResultCalculator $calculator,
        private TranscriptResultPresenter $presenter,
        private ResultCalculationInputBuilder $inputBuilder,
        private ComponentRequirementProfileBuilder $componentProfileBuilder,
    ) {}

    public function build(iterable $students, Exam $exam): array
    {
        return $this->buildPrepared($students, $exam, GradeList::all());
    }

    public function buildWithGradeRows(iterable $students, Exam $exam, iterable $gradeRows): array
    {
        return $this->buildPrepared($students, $exam, collect($gradeRows));
    }

    private function buildPrepared(iterable $students, Exam $exam, $gradeRows): array
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
        return $students->map(function ($student) use ($exam, $subjectsByStudent, $componentProfilesByStudent, $sessionNames, $classNames, $fallbackClassNames, $sectionNames, $departmentNames, $gradeRows) {
            $classId = (int) ($student->className ?? 0);
            $departmentId = (int) ($student->departmentName ?? $student->departmentId ?? 0);
            $transcript = $this->baseTranscript($student, [
                'sessionName' => (string) ($sessionNames[(int) ($student->sessName ?? 0)] ?? '-'),
                'className' => (string) ($classNames[$classId] ?? $fallbackClassNames[$classId] ?? '-'),
                'sectionName' => (string) ($sectionNames[(int) ($student->sectionName ?? $student->sectionId ?? 0)] ?? '-'),
                'departmentName' => (string) ($departmentNames[$departmentId] ?? '-'),
            ]);
            try {
                $subjects = $subjectsByStudent[(int) $student->id] ?? collect();
                $result = $this->calculator->calculate(
                    $student,
                    $exam,
                    $student->marksheet,
                    $subjects,
                    $componentProfilesByStudent[(int) $student->id] ?? [],
                );
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
}
