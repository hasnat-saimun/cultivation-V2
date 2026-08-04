<?php

namespace App\Services;

use App\Models\{ClassManage, Subject};
use Illuminate\Support\Facades\DB;

final class SubjectScopeSplitPreviewService
{
    public function __construct(private SubjectScopeSplitService $splitter) {}

    public function preview(int $sourceId, ?int $destinationId, array $remain, array $migrate, bool $createDestination): array
    {
        $report = $this->splitter->execute($sourceId, $destinationId, $remain, $migrate, false, null, $createDestination);
        $marks = DB::table('marksheets')->where('subjectId', $sourceId)->whereIn('classId', $report['migrate']);

        return $report + [
            'source' => Subject::findOrFail($sourceId),
            'destination' => $destinationId ? Subject::findOrFail($destinationId) : null,
            'class_names' => ClassManage::whereIn('id', $report['migrate'])->pluck('className', 'id')->all(),
            'student_count' => (clone $marks)->distinct()->count('studentId'),
            'exam_count' => (clone $marks)->distinct()->count('examId'),
            'session_count' => (clone $marks)->distinct()->count('sessionId'),
            'teacher_assignment_count' => $report['counts']['teacher_class_subjects.subject_id'] ?? 0,
            'curriculum_mapping_count' => $report['counts']['curriculum_subject_mappings.subject_id'] ?? 0,
            'mark_count' => $report['counts']['marksheets.subjectId'] ?? 0,
            'blockers' => [],
        ];
    }
}
