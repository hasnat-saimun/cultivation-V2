<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResultArchive;
use App\Models\newAdmission;
use App\Models\Exam;

class ResultArchiveController extends Controller
{
    public function transcript($id)
    {
        $archive = ResultArchive::with('student')->findOrFail($id);
        $className = optional(\App\Models\classManage::find($archive->old_class))->className ?? $archive->old_class;
        $sessionName = optional(\App\Models\sessionManage::find($archive->old_session))->session ?? $archive->old_session;
        $sectionName = optional(\App\Models\sectionManage::find($archive->old_section))->section ?? $archive->old_section;

        // Recalculate transcript data from marksheets (like general transcript)
        $student = $archive->student;
        $examId = request('exam_id') ?? $archive->exam_id ?? $archive->examId ?? null;
        $classId = $archive->old_class;
        $sessionId = $archive->old_session;
        $sectionId = $archive->old_section;
        $subjects = \App\Models\Subject::orderBy('id','ASC')->get()->keyBy('id');

        // Debug: log query parameters
        \Log::info('Archive Transcript Marksheet Query', [
            'studentId' => (string)$student->id,
            'examId' => (string)$examId,
            'classId' => (string)$classId,
            'sessionId' => (string)$sessionId,
        ]);

        $marksheetQuery = [
            'studentId' => (string)$student->id,
            'classId' => (string)$classId,
            'sessionId' => (string)$sessionId,
        ];
        if (!empty($examId)) {
            $marksheetQuery['examId'] = (string)$examId;
        }
        $marksheets = \App\Models\Marksheet::where($marksheetQuery)->get();

        // Debug: log found marksheets
        \Log::info('Archive Transcript Marksheet Results', [
            'marksheets_count' => $marksheets->count(),
            'marksheets' => $marksheets->map(function($m) { return $m->toArray(); }),
        ]);

        // Debug output: dump marksheets and subject IDs
        \Log::info('Archive Transcript Debug', [
            'student_id' => $student->id,
            'exam_id' => $examId,
            'marksheets_count' => $marksheets->count(),
            'marksheets' => $marksheets->map(function($m) { return $m->toArray(); }),
            'subject_ids_in_marksheets' => $marksheets->pluck('subjectId')->all(),
            'subject_ids_in_subjects' => $subjects->keys()->all(),
        ]);
        $gradeList = \App\Models\GradeList::orderBy('gradePoint','DESC')->get();

        // --- Robust subject logic: main, optional, religious, GPA, grade ---
        $subjectCache = $subjects->all();
        $rowSubjects = [];
        // Determine effective religious subject for this student (student id, then class default, then any)
        $effectiveReligiousId = null;
        $studentReligiousId = $student->religiousSubjectId ?? null;
        if ($studentReligiousId && isset($subjects[$studentReligiousId])) {
            $effectiveReligiousId = $studentReligiousId;
        } else {
            // Try class default
            $classDefault = \App\Models\ReligiousSubjectDefault::where('classId', $classId)->first();
            if ($classDefault && isset($subjects[$classDefault->subjectId])) {
                $effectiveReligiousId = $classDefault->subjectId;
            } else {
                // Fallback: any religious subject
                foreach ($subjects as $sub) {
                    if (!empty($sub->isReligious) && $sub->isReligious) {
                        $effectiveReligiousId = $sub->id;
                        break;
                    }
                }
            }
        }
        foreach ($marksheets as $m) {
            $sub = $subjects[$m->subjectId] ?? null;
            if (!$sub) continue;
            // Only include the effective religious subject if present
            if (!empty($sub->isReligious) && $sub->isReligious && $effectiveReligiousId && $sub->id != $effectiveReligiousId) {
                continue;
            }
            $rowSubjects[] = [
                'id' => $sub->id,
                'name' => $sub->subjectName,
                'type' => $sub->subjectType ?? $sub->type ?? 'Main',
                'isReligious' => (int)($sub->isReligious ?? 0),
                'cq' => $m->subjectMarks ?? '-',
                'mcq' => $m->objectMarks ?? '-',
                'practical' => $m->practicalMarks ?? '-',
                'total' => (is_numeric($m->subjectMarks) ? (float)$m->subjectMarks : 0) + (is_numeric($m->objectMarks) ? (float)$m->objectMarks : 0) + (is_numeric($m->practicalMarks) ? (float)$m->practicalMarks : 0),
            ];
        }
        $pairGroups = app(\App\Http\Controllers\MarksheetController::class)->detectSubjectPairs($subjects);
        $isFeatureWise = false;
        $subjectsPaired = app(\App\Http\Controllers\MarksheetController::class)->mergeSubjectsForRow($rowSubjects, $pairGroups, $subjectCache, $isFeatureWise);

        $mainSubjects = [];
        $optionalSubjects = [];
        $failedSubjects = [];
        $mainGradePoints = [];
        $optionalPoint = 0;
        $optionalFound = false;
        $hasFail = false;
        $subtotal = 0;
        foreach($subjectsPaired as $sr){
            // Rename 'gradePoint' to 'point' for Blade compatibility
            $sr['theory'] = $sr['cq'] ?? '-';
            $isOptional = (strtolower($sr['type'] ?? '') === 'optional');
            $isReligious = !empty($sr['isReligious']);
            // For non-paired (general) subjects, calculate grade/point using GradeList::forScore
            if (empty($sr['paired'])) {
                $gradeRow = null;
                if (is_numeric($sr['total'])) {
                    $gradeRow = \App\Models\GradeList::forScore((float)$sr['total']);
                }
                $sr['grade'] = $gradeRow ? $gradeRow->gradeName : ($sr['grade'] ?? '-');
                $sr['point'] = $gradeRow ? (float)$gradeRow->gradePoint : (($sr['grade'] ?? '-') === 'F' ? 0 : 0);
            } else {
                $sr['point'] = isset($sr['gradePoint']) ? (is_numeric($sr['gradePoint']) ? (float)$sr['gradePoint'] : 0) : 0;
            }
            // Only include the effective religious subject in GPA/grade calculation
            if($isOptional){
                $optionalSubjects[] = $sr;
                $optionalFound = true;
                $optionalPoint = $sr['point'];
                if(($sr['grade'] ?? '-') === 'F'){ $hasFail = true; $failedSubjects[] = $sr['name']; }
            } elseif($isReligious && $sr['id'] == $effectiveReligiousId) {
                $mainSubjects[] = $sr;
                $mainGradePoints[] = $sr['point'];
                $religiousSubjectName = $sr['name'];
                if(($sr['grade'] ?? '-') === 'F'){ $hasFail = true; $failedSubjects[] = $sr['name']; }
            } elseif(!$isReligious) {
                $mainSubjects[] = $sr;
                $mainGradePoints[] = $sr['point'];
                if(($sr['grade'] ?? '-') === 'F'){ $hasFail = true; $failedSubjects[] = $sr['name']; }
            }
            if(is_numeric($sr['total'])){ $subtotal += (float)$sr['total']; }
        }
        // Optional subject bonus for GPA (NCTB rule)
        $optionalBonus = ($optionalFound && $optionalPoint > 2) ? ($optionalPoint - 2) : 0;
        $mainCount = count($mainGradePoints);
        $finalGpa = $mainCount > 0 ? round((array_sum($mainGradePoints) + $optionalBonus) / $mainCount, 2) : '-';
        if ($hasFail) {
            $finalLetterGrade = 'F';
            $finalGpa = '0.00';
        } elseif ($mainCount > 0) {
            $gradeListRow = \App\Models\GradeList::forGpa((float)$finalGpa);
            $finalLetterGrade = $gradeListRow ? $gradeListRow->gradeName : '-';
        } else {
            $finalLetterGrade = '-';
            $finalGpa = '-';
        }
        $result = $hasFail ? 'Fail' : 'Pass';
        $transcriptData = [
            'main_subjects' => $mainSubjects,
            'optional_subjects' => $optionalSubjects,
            'failed_subjects' => $failedSubjects,
            'total_marks' => $subtotal,
            'gpa' => $finalGpa,
            'final_letter_grade' => $finalLetterGrade,
            'result' => $result,
            'religious_subject_name' => $religiousSubjectName ?? null,
        ];

        return view('result.archiveTranscript', compact('archive', 'className', 'sessionName', 'sectionName', 'transcriptData', 'gradeList'));
    }

    public function index(Request $request)
    {
        $query = ResultArchive::with(['student', 'exam']);
        if ($request->filled('classId')) {
            $query->where('old_class', $request->input('classId'));
        }
        if ($request->filled('sessionId')) {
            $query->where('old_session', $request->input('sessionId'));
        }
        if ($request->filled('sectionId')) {
            $query->where('old_section', $request->input('sectionId'));
        }
        if ($request->filled('roll')) {
            $query->where('old_roll', $request->input('roll'));
        }
        if ($request->filled('archive_year')) {
            $query->whereYear('created_at', $request->input('archive_year'));
        }
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->input('exam_id'));
        }
        $archives = $query->orderByRaw('CAST(old_roll as UNSIGNED) ASC')->get();

        // For filter dropdowns, load all possible values
        $classIds = ResultArchive::distinct()->pluck('old_class')->unique()->filter();
        $sessionIds = ResultArchive::distinct()->pluck('old_session')->unique()->filter();
        $sectionIds = ResultArchive::distinct()->pluck('old_section')->unique()->filter();

        $classNames = \App\Models\classManage::whereIn('id', $classIds)->pluck('className', 'id');
        $sessionNames = \App\Models\sessionManage::whereIn('id', $sessionIds)->pluck('session', 'id');
        $sectionNames = \App\Models\sectionManage::whereIn('id', $sectionIds)->pluck('section', 'id');

        $examList = Exam::pluck('examName', 'id');

        return view('result.resultArchive', compact('archives', 'classNames', 'sessionNames', 'sectionNames', 'examList'));
    }

}
