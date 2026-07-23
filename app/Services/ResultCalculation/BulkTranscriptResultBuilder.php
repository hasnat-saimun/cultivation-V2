<?php

namespace App\Services\ResultCalculation;

use App\Models\Exam;
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
    ) {}

    public function build(iterable $students, Exam $exam): array
    {
        $students = collect($students)->values();
        $subjectsByStudent = $this->inputBuilder->subjectsForStudents($students);
        $sessionNames = sessionManage::whereIn('id', $students->pluck('sessName')->filter())->pluck('session', 'id');
        $classIds = $students->pluck('className')->filter()->unique();
        $classNames = classManage::whereIn('id', $classIds)->pluck('className', 'id');
        $fallbackClassNames = Classes::whereIn('id', $classIds)->pluck('className', 'id');
        $sectionNames = sectionManage::whereIn('id', $students->pluck('sectionName')->filter())->pluck('section', 'id');
        $departmentIds = $students->map(fn ($student) => $student->departmentName ?? $student->departmentId ?? null)->filter()->unique();
        $departmentNames = Department::whereIn('id', $departmentIds)->pluck('departmentName', 'id');

        return $students->map(function ($student) use ($exam, $subjectsByStudent, $sessionNames, $classNames, $fallbackClassNames, $sectionNames, $departmentNames) {
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
                $result = $this->calculator->calculate($student, $exam, $student->marksheet, $subjects);
                $transcript['result'] = $this->presenter->present($result, $subjects, $student->marksheet);
                $transcript['usingBulkResultEngine'] = true;
            } catch (\Throwable $exception) {
                Log::error('Result engine bulk transcript calculation failed; using legacy student output.', [
                    'student_id' => (int) $student->id,
                    'exam_id' => (int) $exam->id,
                    'class_id' => (int) ($student->className ?? 0),
                    'session_id' => (int) ($student->sessName ?? 0),
                    'exception' => get_class($exception),
                ]);
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
        ];
    }
}
