<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\GradeList;
use App\Models\ServerConfig;
use App\Models\sessionManage;
use App\Models\ResultPublish;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\ReligiousSubjectDefault;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;

class MarksheetController extends Controller
{
    private function resolveSessionIdFromValue($sessionValue): ?int
    {
        if ($sessionValue === null || $sessionValue === '') {
            return null;
        }

        if (is_numeric($sessionValue)) {
            return (int)$sessionValue;
        }

        $mappedId = sessionManage::where('session', (string)$sessionValue)->value('id');
        return $mappedId ? (int)$mappedId : null;
    }

    private function isResultPublished(int $examId, int $sessionId, int $classId, $groupId = null): bool
    {
        return ResultPublish::where('examId', $examId)
            ->where('sessionId', $sessionId)
            ->where('classId', $classId)
            ->where(function($q) use ($groupId){
                $q->whereNull('groupId');
                if($groupId){
                    $q->orWhere('groupId', $groupId);
                }
            })
            ->exists();
    }
    public function addMarks(){
        // Restrict visible classes & subjects if teacher
        $adminId = session('cultivationAdmin');
        $user = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
        $isTeacher = $user && $user->isTeacher();
        // For general teachers (marks entry) allow multiple classes; class-teacher can still have primary class but
        // marks entry should use assigned classes array.
        $classIds = $isTeacher ? $user->access_class_array : [];
        $subjectIds = $isTeacher ? $user->access_subject_array : [];
        return view('result.add-marks', [
            'restrictedClassIds' => $classIds,
            'restrictedSubjectIds' => $subjectIds,
            'isTeacherAdmin' => $isTeacher,
        ]);
    }
    public function getMarks(Request $requ){
        $subjectId = (int)$requ->subjectId;
        $subject = Subject::find($subjectId);
        $isOptionalSubject = $subject && strcasecmp((string)$subject->subjectType, 'Optional') === 0;

        $groupId = $requ->groupId ?: null;
        $optionalGroupId = $requ->optionalGroupId ?: null;

        $studentBaseQuery = newAdmission::where('className', (int)$requ->classId)
            ->when($groupId, function($q) use ($groupId){
                return $q->where('sectionName', (int)$groupId);
            })
            ->when($optionalGroupId, function($q) use ($optionalGroupId){
                return $q->where('departmentName', (int)$optionalGroupId);
            })
            ->when($isOptionalSubject, function($q) use ($subjectId){
                return $q->where(function($qq) use ($subjectId){
                    $qq->where('fourthSubjectId', $subjectId)
                        ->orWhereNull('fourthSubjectId')
                        ->orWhere('fourthSubjectId', 0);
                });
            });

        $studentSessionValue = $requ->sessionId ?: (clone $studentBaseQuery)->orderBy('id','DESC')->value('sessName');
        $sessionId = $this->resolveSessionIdFromValue($studentSessionValue);
        $sessionId = $sessionId ?: (int)sessionManage::orderBy('id','DESC')->value('id');

        if (!$studentSessionValue && $sessionId) {
            $studentSessionValue = (string)$sessionId;
        }

        if(!$sessionId){
            return redirect()->route('addMarks')->with('error','Session not found');
        }
        $isFinalPublished = $this->isResultPublished((int)$requ->examId, (int)$sessionId, (int)$requ->classId, $groupId);
        // Server-side enforcement of teacher's assigned class & subject
        $adminId = session('cultivationAdmin');
        $user = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
        $isTeacherAdmin = $user && $user->isTeacher();
        if($user && $user->isTeacher()){
            $allowed = $user->canTeachClassSubject((int)$requ->classId, (int)$requ->subjectId, $groupId, $optionalGroupId);
            if(!$allowed){
                return redirect()->route('addMarks')->with('error','Unauthorized class or subject selection');
            }
        }

        // Fetch students class-wise along with session and section filters
        $studentQuery = clone $studentBaseQuery;
        if($studentSessionValue){
            $studentQuery->where('sessName', $studentSessionValue);
        }

        $studentList = $studentQuery
            ->orderByRaw('CAST(NULLIF(rollNumber, "") AS UNSIGNED) ASC')
            ->orderBy('id','ASC')
            ->get();

        // Fallback for legacy data where sessName stores id/text inconsistently
        if($studentList->isEmpty() && $studentSessionValue && is_numeric($studentSessionValue)){
            $sessionText = sessionManage::where('id', (int)$studentSessionValue)->value('session');
            if($sessionText){
                $studentList = (clone $studentBaseQuery)
                    ->where('sessName', $sessionText)
                    ->orderByRaw('CAST(NULLIF(rollNumber, "") AS UNSIGNED) ASC')
                    ->orderBy('id','ASC')
                    ->get();
            }
        }

        return view('result.get-marks',[
            'studentList'=>$studentList,
            'groupId'=>$groupId,
            'optionalGroupId'=>$optionalGroupId,
            'classId'=>$requ->classId,
            'sessionId'=>$sessionId,
            'examId'=>$requ->examId,
            'subjectId'=>$requ->subjectId,
            'isFinalPublished'=>$isFinalPublished,
            'isTeacherAdmin'=>$isTeacherAdmin,
        ]);
    }

    public function confirmMarks(Request $requ){
        $subjectId = (int)$requ->subjectId;
        $subject = Subject::find($subjectId);
        $isOptionalSubject = $subject && strcasecmp((string)$subject->subjectType, 'Optional') === 0;

        $sessionId = $this->resolveSessionIdFromValue($requ->sessionId);
        if(!$sessionId){
            $sessionCandidate = newAdmission::where('className', (int)$requ->classId)
                ->when($requ->groupId, function($q) use ($requ){
                    return $q->where('sectionName', (int)$requ->groupId);
                })
                ->when($requ->optionalGroupId, function($q) use ($requ){
                    return $q->where('departmentName', (int)$requ->optionalGroupId);
                })
                ->when($isOptionalSubject, function($q) use ($subjectId){
                    return $q->where('fourthSubjectId', $subjectId);
                })
                ->orderBy('id','DESC')
                ->value('sessName');
            $sessionId = $this->resolveSessionIdFromValue($sessionCandidate);
        }
        $sessionId = $sessionId ?: (int)sessionManage::orderBy('id','DESC')->value('id');
        if(!$sessionId){
            return redirect()->route('addMarks')->with('error','Session not found');
        }
        $groupId = $requ->groupId ?: null;
        $optionalGroupId = $requ->optionalGroupId ?: null;
        // Enforce teacher role restrictions before saving
        $adminId = session('cultivationAdmin');
        $user = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
        $isTeacherAdmin = $user && $user->isTeacher();
        $isFinalPublished = $this->isResultPublished((int)$requ->examId, (int)$sessionId, (int)$requ->classId, $groupId);
        if($isTeacherAdmin && $isFinalPublished){
            return redirect()->route('addMarks')->with('error','Final result is published. Marks entry is locked for teachers.');
        }
        if($user && $user->isTeacher()){
            $allowed = $user->canTeachClassSubject((int)$requ->classId, (int)$requ->subjectId, $groupId, $optionalGroupId);
            if(!$allowed){
                return redirect()->route('addMarks')->with('error','Unauthorized attempt to submit marks for this class/subject');
            }
        }

        $allowedOptionalStudentIds = [];
        if($isOptionalSubject){
            $allowedOptionalStudentIds = newAdmission::where('className', (int)$requ->classId)
                ->where('sessName', (int)$sessionId)
                ->when($groupId, function($q) use ($groupId){
                    return $q->where('sectionName', (int)$groupId);
                })
                ->when($optionalGroupId, function($q) use ($optionalGroupId){
                    return $q->where('departmentName', (int)$optionalGroupId);
                })
                ->where(function($q) use ($subjectId){
                    $q->where('fourthSubjectId', $subjectId)
                        ->orWhereNull('fourthSubjectId')
                        ->orWhere('fourthSubjectId', 0);
                })
                ->pluck('id')
                ->map(fn($v) => (int)$v)
                ->all();
            $allowedOptionalStudentIds = array_fill_keys($allowedOptionalStudentIds, true);
        }

        $studentId = $requ->studentId;
        $totalData = count($studentId);
        $x = 0;
        $skipped = 0;
        $actorId = $user ? (int)$user->id : null;
        $actorRole = ($user && $user->isTeacher()) ? 'teacher' : 'admin';
        while($x<$totalData){
            if($isOptionalSubject && !isset($allowedOptionalStudentIds[(int)$requ->studentId[$x]])){
                $skipped++;
                $x++;
                continue;
            }

            $existingMark = Marksheet::where([
                'sessionId'=>$sessionId,
                'classId'=>$requ->classId,
                'studentId'=>$requ->studentId[$x],
                'examId'=>$requ->examId,
                'subjectId'=>$requ->subjectId
            ])->when($groupId, function($q) use ($groupId){
                return $q->where('groupId',$groupId);
            })->first();
            if(isset($existingMark) && !empty($existingMark)):
                // If existing marks are entered by another teacher, do not overwrite
                if($user && $user->isTeacher() && (int)$existingMark->teacher_id !== (int)$user->id){
                    $skipped++;
                    $x++;
                    continue;
                }
            endif;
            $totalMarks = 0;
            $hasAny = false;
            if(isset($requ->cqMarks[$x]) && $requ->cqMarks[$x] !== null && $requ->cqMarks[$x] !== '') {
                $totalMarks += (float)$requ->cqMarks[$x];
                $hasAny = true;
            }
            if(isset($requ->mcqMarks[$x]) && $requ->mcqMarks[$x] !== null && $requ->mcqMarks[$x] !== '') {
                $totalMarks += (float)$requ->mcqMarks[$x];
                $hasAny = true;
            }
            if(isset($requ->practical[$x]) && $requ->practical[$x] !== null && $requ->practical[$x] !== '') {
                $totalMarks += (float)$requ->practical[$x];
                $hasAny = true;
            }

            // Skip saving a marksheet row if no marks were entered
            if(!$hasAny){
                $x++;
                continue;
            }

            $grade = GradeList::forScore((float)$totalMarks);
            if(isset($grade) && !empty($grade)){
                $gradePoint = $grade->gradePoint;
                $laterGrade = $grade->gradeName;
            }else{
                $gradePoint = 0.00;
                $laterGrade = 'F';
            }
            $marks = $existingMark ?: new Marksheet();

            $marks->studentId       = $requ->studentId[$x];
            $marks->classId         = $requ->classId;
            $marks->sessionId       = $sessionId;
            $marks->examId          = $requ->examId;
            $marks->subjectId       = $requ->subjectId;
            $marks->groupId         = $groupId;
            $marks->subjectMarks    = (isset($requ->cqMarks[$x]) && $requ->cqMarks[$x] !== '') ? (float)$requ->cqMarks[$x] : null;
            $marks->objectMarks     = (isset($requ->mcqMarks[$x]) && $requ->mcqMarks[$x] !== '') ? (float)$requ->mcqMarks[$x] : null;
            $marks->practicalMarks  = (isset($requ->practical[$x]) && $requ->practical[$x] !== '') ? (float)$requ->practical[$x] : null;
            $marks->totalMarks      = $totalMarks;
            $marks->laterGrade      = $laterGrade;
            $marks->gradePoint      = $gradePoint;
            if(!$existingMark){
                $marks->entered_by      = $actorId;
                $marks->entered_by_role = $actorRole;
            }
            $marks->updated_by      = $actorId;
            $marks->updated_by_role = $actorRole;
            $marks->teacher_id      = $actorId;
            $marks->save();

            $x++;
        }
        // return $x;
        if($x>=$totalData):
            return redirect(route('addMarks'))->with('success','Marks added successfull');
        else:
            return redirect(route('addMarks'))->with('error','Marks added failed');
        endif;
    }

    public function createMarksheet(){
        return view('result.createMarksheet');
    }

    public function finalPublishIndex(){
        $examList = Exam::orderBy('id','DESC')->get();
        $sessionList = sessionManage::orderBy('id','DESC')->get();
        $classList = \App\Models\classManage::orderBy('id','DESC')->get();
        $sectionList = \App\Models\sectionManage::orderBy('id','DESC')->get();
        $publishedList = ResultPublish::orderBy('published_at','DESC')->get();

        $examNames = Exam::orderBy('id','DESC')->pluck('examName','id');
        $sessionNames = sessionManage::orderBy('id','DESC')->pluck('session','id');
        $classNames = \App\Models\classManage::orderBy('id','DESC')->pluck('className','id');
        $sectionNames = \App\Models\sectionManage::orderBy('id','DESC')->pluck('section','id');

        return view('result.final-publish', compact(
            'examList',
            'sessionList',
            'classList',
            'sectionList',
            'publishedList',
            'examNames',
            'sessionNames',
            'classNames',
            'sectionNames'
        ));
    }

    public function finalPublishStore(Request $requ){
        $requ->validate([
            'examId' => 'required|integer',
            'sessionId' => 'required|integer',
            'classId' => 'required',
            'groupId' => 'nullable|integer',
            'action' => 'required|in:publish,unpublish',
        ]);

        $classId = $requ->classId;
        if($classId !== 'all' && !ctype_digit((string)$classId)){
            return back()->with('error', 'Invalid class selection.');
        }

        $groupId = $requ->groupId ? (string)$requ->groupId : null;
        $classIds = [];
        if($classId === 'all'){
            $classIds = \App\Models\classManage::orderBy('id','DESC')->pluck('id')->map(fn($v) => (string)$v)->all();
        } else {
            $classIds = [(string)$classId];
        }

        foreach($classIds as $cid){
            $payload = [
                'examId' => (string)$requ->examId,
                'sessionId' => (string)$requ->sessionId,
                'classId' => $cid,
                'groupId' => $groupId,
            ];

            if($requ->action === 'publish'){
                ResultPublish::updateOrCreate(
                    $payload,
                    [
                        'published_by' => session('cultivationAdmin'),
                        'published_at' => now(),
                    ]
                );
            } else {
                ResultPublish::where($payload)->delete();
            }
        }

        $msg = $requ->action === 'publish'
            ? 'Final result published successfully.'
            : 'Final result unpublished successfully.';
        return back()->with('success', $msg);
    }

    public function allMarksheet(Request $request){
        // Filter inputs (optional)
        $examId    = $request->get('examId');
        $classId   = $request->get('classId');
        $sessionId = $request->get('sessionId');
        $sectionId = $request->get('sectionId'); // group/section
        $departmentId = $request->get('departmentId');

        $exam      = $examId ? Exam::find($examId) : null;
        $isFeatureWise = $exam && $exam->passingSystem == 1; // same logic as single marksheet

        // Subjects (global list for header)
        $subjects = Subject::orderBy('id','ASC')->get();

    $passResults = [];
    $failResults = [];
    $subjectWise = [];
    $incompleteResults = [];
        $studentsLoaded = false;

        if($examId && $classId && $sessionId){
            // Build marks query for the selected filters
            $marksBaseQuery = Marksheet::where('examId',$examId)
                ->where('classId',$classId);
            $marksBaseQuery->where('sessionId',$sessionId);
            if($sectionId){ $marksBaseQuery->where('groupId',$sectionId); }

            $studentIds = $marksBaseQuery->distinct()->pluck('studentId');
            $students = newAdmission::whereIn('id',$studentIds)
                ->when($departmentId, function($q) use ($departmentId){
                    return $q->where('departmentName', (int)$departmentId);
                })
                ->get();
            $filteredStudentIds = $students->pluck('id');
            // Determine active subjects for this class/session/section/exam from marks present
            $activeSubjectIds = Marksheet::where('examId',$examId)
                ->where('classId',$classId)
                ->where('sessionId',$sessionId)
                ->when($sectionId, function($q) use ($sectionId){ return $q->where('groupId',$sectionId); })
                ->when($departmentId, function($q) use ($filteredStudentIds){
                    return $q->whereIn('studentId', $filteredStudentIds);
                })
                ->distinct()
                ->pluck('subjectId')
                ->map(fn($v) => (int)$v)
                ->all();
            $studentsLoaded = true;
            $maxMarkedSubjects = 0;

            // Pre-cache subject details to reduce queries
            $subjectCache = [];
            foreach($subjects as $s){ $subjectCache[$s->id] = $s; }

            // Grade list caching for 0-100 mapping
            $gradeList = GradeList::orderBy('minMark','ASC')->get();

            foreach($students as $stu){
                $selectedReligiousId = $stu->religiousSubjectId ? (int)$stu->religiousSubjectId : 0;
                $selectedFourthSubjectId = $stu->fourthSubjectId ? (int)$stu->fourthSubjectId : 0;
                $effectiveReligiousId = $selectedReligiousId > 0 ? $selectedReligiousId : $this->resolveReligiousSubjectForClass((int)$classId);
                // Build a fresh query per student to avoid accumulating where clauses
                $stuMarks = Marksheet::where('examId',$examId)
                    ->where('classId',$classId)
                    ->where('sessionId',$sessionId)
                    ->when($sectionId, function($q) use ($sectionId){ return $q->where('groupId',$sectionId); })
                    ->where('studentId',$stu->id)
                    ->get();
                $marksBySubject = [];
                foreach($stuMarks as $m){
                    $marksBySubject[$m->subjectId] = $m; // last wins if duplicates
                }

                $mainGradePoints = [];
                $optionalPoint = 0; $optionalSubjectFound = false;
                $subtotalMarks = 0; $hasFail = false;
                $perSubjectOutput = [];

                $missingMainSubjects = 0;
                $markedSubjectsCount = 0;
                foreach($subjects as $sub){
                    // Per-student religious subject rule: include only the effective religious subject (student-selected or class default)
                    if (!empty($sub->isReligious)) {
                        if ($effectiveReligiousId === 0 || (int)$sub->id !== $effectiveReligiousId) {
                            continue;
                        }
                    }
                    // Skip subjects that have no marks across the class filters (inactive)
                    if (!in_array((int)$sub->id, $activeSubjectIds, true)) { continue; }
                    $markRow = $marksBySubject[$sub->id] ?? null;
                    $cq = ($markRow && is_numeric($markRow->subjectMarks)) ? (float)$markRow->subjectMarks : null;
                    $mcq = ($markRow && is_numeric($markRow->objectMarks)) ? (float)$markRow->objectMarks : null;
                    $pr  = ($markRow && is_numeric($markRow->practicalMarks)) ? (float)$markRow->practicalMarks : null;
                    // Only consider subjects with at least one component mark; ignore total-only rows
                    $hasAnyMark = ($cq !== null) || ($mcq !== null) || ($pr !== null);

                    // Displays
                    $cqDisplay = $cq !== null ? $cq : '-';
                    $mcqDisplay = $mcq !== null ? $mcq : '-';
                    $prDisplay = $pr !== null ? $pr : '-';

                    $total = 0;
                    if ($hasAnyMark) {
                        $total = ($cq !== null ? $cq : 0) + ($mcq !== null ? $mcq : 0) + ($pr !== null ? $pr : 0);
                        $subtotalMarks += $total;
                        $markedSubjectsCount++;
                    } else {
                        // Skip subjects without any marks from calculations (do not mark incomplete)
                    }

                    // Component grade percent (only if value & full mark available)
                    $fullCQ = $sub->CQ ?? 0; $fullMCQ = $sub->MCQ ?? 0; $fullPR = $sub->Practical ?? 0;
                    $cqPercent = ($fullCQ > 0 && $cq !== null) ? ($cq / $fullCQ) * 100 : null;
                    $mcqPercent = ($fullMCQ > 0 && $mcq !== null) ? ($mcq / $fullMCQ) * 100 : null;
                    $prPercent = ($fullPR > 0 && $pr !== null) ? ($pr / $fullPR) * 100 : null;

                    $componentGrades = [];
                    $overallGrade = '-';
                    $overallPoint = 0;
                    if ($hasAnyMark) {
                        foreach(['cqPercent'=>$cqPercent,'mcqPercent'=>$mcqPercent,'prPercent'=>$prPercent] as $key=>$val){
                            if($val === null){
                                $componentGrades[$key] = '-';
                            }else{
                                $row = GradeList::forScore($val);
                                $componentGrades[$key] = $row ? $row->gradeName : '-';
                            }
                        }
                        // Overall grade (by normalized percentage of subject full marks)
                        $subjectFullMark = ((float)$fullCQ + (float)$fullMCQ + (float)$fullPR);
                        $totalPercent = $subjectFullMark > 0 ? (($total / $subjectFullMark) * 100) : null;
                        $gradeRow = $totalPercent !== null ? GradeList::forScore($totalPercent) : null;
                        $overallGrade = $gradeRow ? $gradeRow->gradeName : '-';
                        $overallPoint = $gradeRow ? $gradeRow->gradePoint : 0;
                        // Feature-wise fail override
                        if($isFeatureWise && (in_array('F',$componentGrades))){
                            $overallGrade = 'F';
                            $overallPoint = 0;
                            $hasFail = true;
                        }
                        if($overallGrade === 'F'){ $hasFail = true; }
                        if($sub->subjectType === 'Main'){
                            $mainGradePoints[] = $overallPoint;
                        }elseif($sub->subjectType === 'Optional'){
                            $optionalSubjectFound = true; $optionalPoint = $overallPoint; // Only one optional considered
                        }
                    }

                    $rowForSubject = [
                        'id' => $sub->id,
                        'name' => $sub->subjectName,
                        'type' => $sub->subjectType,
                        'isReligious' => (int)($sub->isReligious ?? 0),
                        'hasCQFeature' => ((float)$fullCQ > 0),
                        'hasMCQFeature' => ((float)$fullMCQ > 0),
                        'hasPracticalFeature' => ((float)$fullPR > 0),
                        'cq' => $cqDisplay,
                        'mcq' => $mcqDisplay,
                        'practical' => $prDisplay,
                        'total' => $hasAnyMark ? ($markRow && is_numeric($markRow->totalMarks) ? $markRow->totalMarks : $total) : '-',
                        'grade' => $overallGrade,
                        'gradePoint' => $overallPoint > 0 ? number_format($overallPoint,2) : ($overallGrade==='F' ? '0.00' : '-'),
                        'cqGrade' => $componentGrades['cqPercent'] ?? '-',
                        'mcqGrade' => $componentGrades['mcqPercent'] ?? '-',
                        'prGrade' => $componentGrades['prPercent'] ?? '-',
                    ];
                    $perSubjectOutput[] = $rowForSubject;

                    // Build subject-wise aggregation (include all, religious already filtered to effective per student)
                    if(!isset($subjectWise[$sub->id])){
                        $subjectWise[$sub->id] = [
                            'subjectId' => $sub->id,
                            'subjectName' => $sub->subjectName,
                            'rows' => []
                        ];
                    }
                    // Include only if at least one component exists or total is numeric
                    $hasAnyMark = ($rowForSubject['cq'] !== '-') || ($rowForSubject['mcq'] !== '-') || ($rowForSubject['practical'] !== '-') || ($rowForSubject['total'] !== '-');
                    if($hasAnyMark){
                        $subjectWise[$sub->id]['rows'][] = [
                            'studentId' => $stu->stdId,
                            'studentName' => trim(($stu->fullName ?? '').' '.($stu->sureName ?? '')),
                            'cq' => $rowForSubject['cq'],
                            'mcq' => $rowForSubject['mcq'],
                            'practical' => $rowForSubject['practical'],
                            'total' => $rowForSubject['total'],
                            'grade' => $rowForSubject['grade'],
                            'gradePoint' => $rowForSubject['gradePoint'],
                            'isFail' => $rowForSubject['grade'] === 'F',
                        ];
                    }
                }

                // Pair-subject merge for this student
                $pairGroups = $this->detectSubjectPairs($subjects);
                $subjectsPaired = $this->mergeSubjectsForRow($perSubjectOutput, $pairGroups, $subjectCache, $isFeatureWise);
                // Recompute subtotal, GPA and counts using paired rows
                $subtotalPaired = 0; $mainGradePointsPaired = []; $optionalPointPaired = 0; $optionalFoundPaired = false; $hasFailPaired = false; $markedPairedCount = 0;
                foreach($subjectsPaired as $sr){
                    $hasAny = ($sr['cq'] !== '-') || ($sr['mcq'] !== '-') || ($sr['practical'] !== '-') || ($sr['total'] !== '-');
                    if($hasAny){ $markedPairedCount++; }
                    if(is_numeric($sr['total'])){ $subtotalPaired += (float)$sr['total']; }
                    if(($sr['grade'] ?? '-') === 'F'){ $hasFailPaired = true; }
                    // parse gradePoint display
                    $gp = ($sr['grade'] === 'F') ? 0.0 : (is_numeric($sr['gradePoint']) ? (float)$sr['gradePoint'] : null);
                    if($gp !== null){
                        if(($sr['type'] ?? 'Main') === 'Main'){ $mainGradePointsPaired[] = $gp; }
                        else{
                            $sourceIds = $sr['sourceIds'] ?? [];
                            if($selectedFourthSubjectId > 0 && in_array($selectedFourthSubjectId, $sourceIds, true)){
                                $optionalFoundPaired = true;
                                $optionalPointPaired = $gp;
                            }
                        }
                    }
                }
                // If no subjects have marks at all, skip this student entirely (paired criterion)
                if ($markedPairedCount === 0) { continue; }

                $optionalBonus = ($optionalFoundPaired && $optionalPointPaired > 2) ? ($optionalPointPaired - 2) : 0;
                $mainCount = count($mainGradePointsPaired);
                $isIncomplete = false; // blank subjects skipped
                $finalGpa = $mainCount > 0 ? round((array_sum($mainGradePointsPaired) + $optionalBonus)/$mainCount, 2) : 0;
                $finalLetter = '-';
                if($isIncomplete){
                    $finalLetter = 'Incomplete'; $finalGpa = null;
                }elseif($hasFailPaired){
                    $finalLetter = 'F'; $finalGpa = 0;
                }elseif($mainCount>0){
                    $avgRow = GradeList::forGpa($finalGpa);
                    $finalLetter = $avgRow ? $avgRow->gradeName : '-';
                }

                $rowPayload = [
                    'student' => $stu,
                    'subjects' => $subjectsPaired,
                    'totalMarks' => $subtotalPaired,
                    'finalGpa' => number_format($finalGpa,2),
                    'finalLetter' => $finalLetter,
                    'isFail' => $hasFailPaired,
                    'isIncomplete' => $isIncomplete,
                    'religiousSubjectIdUsed' => $effectiveReligiousId,
                    'fourthSubjectIdUsed' => $selectedFourthSubjectId,
                    'religiousSubjectUsedName' => ($effectiveReligiousId && isset($subjectCache[$effectiveReligiousId])) ? $subjectCache[$effectiveReligiousId]->subjectName : null,
                    'markedSubjectsCount' => $markedPairedCount,
                ];
                if ($markedPairedCount > $maxMarkedSubjects) { $maxMarkedSubjects = $markedPairedCount; }
                if($isIncomplete){ $incompleteResults[] = $rowPayload; }
                elseif($hasFailPaired){ $failResults[] = $rowPayload; } else { $passResults[] = $rowPayload; }
            }
        }

        // Keep all matched students; do not prune by max subject-count.

        // Skip subjects that have no data across the class (no rows in subjectWise)
        // Build active subject id list
        $activeSubjectIds = [];
        foreach ($subjectWise as $sid => $payload) {
            if (!empty($payload['rows'])) { $activeSubjectIds[] = (int) $sid; }
        }
        if (!empty($activeSubjectIds)) {
            // Build paired headers from active subjects
            $activeSubjects = $subjects->filter(function($s) use ($activeSubjectIds) {
                return in_array((int)$s->id, $activeSubjectIds, true);
            })->values();
            $pairGroups = $this->detectSubjectPairs($activeSubjects);
            // Headers: paired names followed by singletons not in pairs
            $pairedIdsFlat = [];
            foreach($pairGroups as $pg){ foreach($pg['ids'] as $id){ $pairedIdsFlat[(int)$id] = true; } }
            $headers = [];
            foreach($pairGroups as $pg){ $o = (object)['subjectName' => $pg['name'], 'isPaired' => true]; $headers[] = $o; }
            foreach($activeSubjects as $s){ if(!isset($pairedIdsFlat[(int)$s->id])){ $headers[] = (object)['subjectName' => $s->subjectName, 'isPaired' => false]; } }
            $subjects = collect($headers);
            // Reorder rows to follow header names
            $orderNames = array_map(function($o){ return $o->subjectName; }, $headers);
            $reorderByName = function(array $rows) use ($orderNames){
                $byName = [];
                foreach($rows as $r){ $byName[(string)$r['name']] = $r; }
                $out = [];
                foreach($orderNames as $nm){ if(isset($byName[$nm])){ $out[] = $byName[$nm]; } }
                return $out;
            };
            foreach ($passResults as &$row) { $row['subjects'] = $reorderByName($row['subjects']); }
            unset($row);
            foreach ($failResults as &$row) { $row['subjects'] = $reorderByName($row['subjects']); }
            unset($row);
            foreach ($incompleteResults as &$row) { $row['subjects'] = $reorderByName($row['subjects']); }
            unset($row);
        } else {
            // If no active subjects found, clear headers to avoid empty grid
            $subjects = collect([]);
            foreach ($passResults as &$row) { $row['subjects'] = []; }
            unset($row);
            foreach ($failResults as &$row) { $row['subjects'] = []; }
            unset($row);
            foreach ($incompleteResults as &$row) { $row['subjects'] = []; }
            unset($row);
        }

        // Optional compact mode: per-student, only show subjects with actual marks
        $compactMode = (bool) $request->get('compact');
        $passResultsCompact = [];
        $failResultsCompact = [];
        $incompleteResultsCompact = [];
        // Sort results by student roll number ASC before compact mapping
        $sortByRoll = function(&$arr){
            usort($arr, function($a,$b){
                $ra = isset($a['student']->rollNumber) ? (int)$a['student']->rollNumber : 0;
                $rb = isset($b['student']->rollNumber) ? (int)$b['student']->rollNumber : 0;
                if($ra === $rb){ return $a['student']->id <=> $b['student']->id; }
                return $ra <=> $rb;
            });
        };
        $sortByRoll($passResults);
        $sortByRoll($failResults);
        $sortByRoll($incompleteResults);

        // Merit ranking for passed students: by GPA desc, then total marks desc, then roll asc
        $rankMap = [];
        if (!empty($passResults)) {
            $sortedForMerit = $passResults;
            usort($sortedForMerit, function($a,$b){
                $ga = is_numeric($a['finalGpa']) ? (float)$a['finalGpa'] : -1.0;
                $gb = is_numeric($b['finalGpa']) ? (float)$b['finalGpa'] : -1.0;
                if ($ga !== $gb) { return $gb <=> $ga; }
                $ta = is_numeric($a['totalMarks']) ? (float)$a['totalMarks'] : 0.0;
                $tb = is_numeric($b['totalMarks']) ? (float)$b['totalMarks'] : 0.0;
                if ($ta !== $tb) { return $tb <=> $ta; }
                $ra = isset($a['student']->rollNumber) ? (int)$a['student']->rollNumber : 0;
                $rb = isset($b['student']->rollNumber) ? (int)$b['student']->rollNumber : 0;
                if ($ra !== $rb) { return $ra <=> $rb; }
                return $a['student']->id <=> $b['student']->id;
            });
            $rank = 1;
            foreach ($sortedForMerit as $row) {
                $sid = $row['student']->id;
                if (!isset($rankMap[$sid])) { $rankMap[$sid] = $rank; }
                $rank++;
            }
            // Attach merit rank to pass results
            foreach ($passResults as &$row) {
                $sid = $row['student']->id;
                $row['meritRank'] = $rankMap[$sid] ?? null;
            }
            unset($row);
        }
        if ($compactMode) {
            $filterHasMarks = function(array $rows) {
                $out = [];
                foreach ($rows as $r) {
                    $hasAny = ($r['cq'] !== '-') || ($r['mcq'] !== '-') || ($r['practical'] !== '-') || ($r['total'] !== '-');
                    if ($hasAny) { $out[] = $r; }
                }
                return $out;
            };
            foreach ($passResults as $row) {
                $row['subjectsCompact'] = $filterHasMarks($row['subjects']);
                // keep merit rank if present
                if (isset($row['meritRank'])) { $row['meritRank'] = $row['meritRank']; }
                $passResultsCompact[] = $row;
            }
            foreach ($failResults as $row) {
                $row['subjectsCompact'] = $filterHasMarks($row['subjects']);
                $failResultsCompact[] = $row;
            }
            foreach ($incompleteResults as $row) {
                $row['subjectsCompact'] = $filterHasMarks($row['subjects']);
                $incompleteResultsCompact[] = $row;
            }
        }

        return view('result.allMarksheet', [
            'subjects' => $subjects,
            'passResults' => $passResults,
            'failResults' => $failResults,
            'incompleteResults' => $incompleteResults,
            'passResultsCompact' => $passResultsCompact,
            'failResultsCompact' => $failResultsCompact,
            'incompleteResultsCompact' => $incompleteResultsCompact,
            'compactMode' => $compactMode,
            'examId' => $examId,
            'classId' => $classId,
            'sessionId' => $sessionId,
            'sectionId' => $sectionId,
            'departmentId' => $departmentId,
            'studentsLoaded' => $studentsLoaded,
            'exam' => $exam,
        ]);
    }

    public function generateMarksheet(Request $requ){
        // return $requ->all();
        $config = ServerConfig::first(); 
        $examId = (int)$requ->examId;
        $stdIdInput = trim((string)($requ->stdId ?? $requ->studentId ?? $requ->id ?? ''));

        $studentQuery = newAdmission::query();
        if ($stdIdInput !== '') {
            $studentQuery->where(function($q) use ($stdIdInput){
                $q->where('stdId', $stdIdInput)
                  ->orWhereRaw('TRIM(stdId) = ?', [$stdIdInput]);
                if (ctype_digit($stdIdInput)) {
                    $q->orWhere('id', (int)$stdIdInput);
                }
            });
        } else {
            $studentQuery->whereRaw('1 = 0');
        }

        $student = $studentQuery
            ->with(['marksheet' => function($q) use ($examId){
                $q->where('examId', $examId)->orderBy('subjectId', 'ASC');
            }])
            ->first();

        // Apply classwise max-subject rule to individual page
        $maxMarkedSubjects = 0; $studentMarkedSubjects = 0; $hideForMaxRule = false;
        if ($student && $examId) {
            $classId = $student->className ?? null;
            $sessionId = $student->sessName ?? null;
            $sectionId = $student->sectionName ?? null; // aka group/section
            if ($classId) {
                $base = Marksheet::where('examId',$examId)->where('classId',$classId);
                if ($sessionId) { $base = $base->where('sessionId',$sessionId); }
                if ($sectionId) { $base = $base->where('groupId',$sectionId); }
                $studentIds = $base->distinct()->pluck('studentId');
                // Cache subjects
                $subjectCache = Subject::orderBy('id','ASC')->get()->keyBy('id');
                foreach ($studentIds as $sid) {
                    $rows = Marksheet::where('examId',$examId)->where('classId',$classId)
                        ->when($sessionId, function($q) use ($sessionId){ return $q->where('sessionId',$sessionId); })
                        ->when($sectionId, function($q) use ($sectionId){ return $q->where('groupId',$sectionId); })
                        ->where('studentId',$sid)->get();
                    // Pair-aware counting: group subjects by base alias, include only effective religious subject
                    $stuRow = newAdmission::find($sid);
                    $selectedReligiousId = $stuRow && $stuRow->religiousSubjectId ? (int)$stuRow->religiousSubjectId : 0;
                    $effectiveReligiousId = $selectedReligiousId > 0 ? $selectedReligiousId : $this->resolveReligiousSubjectForClass((int)$classId);
                    $groups = [];
                    foreach($rows as $r){
                        $sub = isset($subjectCache[$r->subjectId]) ? $subjectCache[$r->subjectId] : null;
                        if(!$sub) continue;
                        if(($sub->isReligious ?? false)){
                            if($effectiveReligiousId === 0 || (int)$sub->id !== $effectiveReligiousId){ continue; }
                        }
                        $base = $this->basePairAlias($sub->alias ?? $sub->subjectName ?? '');
                        $key = $base ?: ('single_'.(int)$sub->id);
                        $hasAny = is_numeric($r->subjectMarks) || is_numeric($r->objectMarks) || is_numeric($r->practicalMarks);
                        if(!isset($groups[$key])){ $groups[$key] = false; }
                        if($hasAny){ $groups[$key] = true; }
                    }
                    $cnt = 0; foreach($groups as $k=>$has){ if($has){ $cnt++; } }
                    if ($cnt > $maxMarkedSubjects) { $maxMarkedSubjects = $cnt; }
                    if ($sid == $student->id) { $studentMarkedSubjects = $cnt; }
                }
                if ($maxMarkedSubjects > 0 && $studentMarkedSubjects < $maxMarkedSubjects) {
                    $hideForMaxRule = true;
                }
            }
        }

        // Compute classwise merit position by sum of subject totals, including failed
        $meritRank = null;
        try {
            if ($student && $examId) {
                $classId = $student->className ?? null;
                $sessionId = $student->sessName ?? null;
                $sectionId = $student->sectionName ?? null;
                if ($classId) {
                    $base = Marksheet::where('examId',$examId)->where('classId',$classId);
                    if ($sessionId) { $base = $base->where('sessionId',$sessionId); }
                    if ($sectionId) { $base = $base->where('groupId',$sectionId); }
                    $studentIds = $base->distinct()->pluck('studentId');
                    $subjectCache = Subject::orderBy('id','ASC')->get()->keyBy('id');
                    $rankItems = [];
                    foreach ($studentIds as $sid) {
                        $rows = Marksheet::where('examId',$examId)->where('classId',$classId)
                            ->when($sessionId, function($q) use ($sessionId){ return $q->where('sessionId',$sessionId); })
                            ->when($sectionId, function($q) use ($sectionId){ return $q->where('groupId',$sectionId); })
                            ->where('studentId',$sid)->get();
                        $stuRow = newAdmission::find($sid);
                        $selectedReligiousId = $stuRow && $stuRow->religiousSubjectId ? (int)$stuRow->religiousSubjectId : 0;
                        $effectiveReligiousId = $selectedReligiousId > 0 ? $selectedReligiousId : $this->resolveReligiousSubjectForClass((int)$classId);
                        $sum = 0.0;
                        foreach ($rows as $r) {
                            $sub = isset($subjectCache[$r->subjectId]) ? $subjectCache[$r->subjectId] : null;
                            if($sub && ($sub->isReligious ?? false)){
                                if($effectiveReligiousId === 0 || (int)$sub->id !== $effectiveReligiousId){
                                    continue;
                                }
                            }
                            $hasAny = is_numeric($r->subjectMarks) || is_numeric($r->objectMarks) || is_numeric($r->practicalMarks);
                            if(!$hasAny) { continue; }
                            $sum += (is_numeric($r->subjectMarks)?(float)$r->subjectMarks:0) + (is_numeric($r->objectMarks)?(float)$r->objectMarks:0) + (is_numeric($r->practicalMarks)?(float)$r->practicalMarks:0);
                        }
                        $rankItems[] = ['sid'=>(int)$sid, 'total'=>$sum];
                    }
                    usort($rankItems, function($a,$b){
                        if ($a['total'] == $b['total']) return 0;
                        return ($a['total'] > $b['total']) ? -1 : 1; // desc
                    });
                    $rank = 0; $prevTotal = null; $map = [];
                    foreach ($rankItems as $it) {
                        if ($prevTotal === null || $it['total'] != $prevTotal) { $rank++; $prevTotal = $it['total']; }
                        $map[$it['sid']] = $rank;
                    }
                    $meritRank = $map[$student->id] ?? null;
                }
            }
        } catch (\Throwable $e) {
            $meritRank = null;
        }

        return view('result.marksheetGenerate',[
            'studentDetails'=>$student,
            'examId'=>$examId,
            'config'=>$config,
            'maxMarkedSubjects' => $maxMarkedSubjects,
            'studentMarkedSubjects' => $studentMarkedSubjects,
            'hideForMaxRule' => $hideForMaxRule,
            'meritRank' => $meritRank,
        ]);
    }


    //front web site str
    public function internalResult(){
        return view('frontend.result.internalResult');
    }


    public function individualResult(){
        return view('frontend.result.individualResult');
    }
    //front web site end

    // Transcript generation: class/section student list and open per-student transcripts
    public function transcriptList(Request $request)
    {
        $examId    = $request->get('examId');
        $classId   = $request->get('classId');
        $sessionId = $request->get('sessionId');
        $sectionId = $request->get('sectionId');
        $departmentId = $request->get('departmentId');

        $students = collect();
        $studentsLoaded = false;
        if ($classId) {
            $q = newAdmission::query()->where('className', (int)$classId);
            if ($sessionId) { $q->where('sessName', (int)$sessionId); }
            if ($sectionId) { $q->where('sectionName', (int)$sectionId); }
            if ($departmentId) { $q->where('departmentName', (int)$departmentId); }
            $students = $q
                ->orderByRaw('CAST(NULLIF(rollNumber, "") AS UNSIGNED) ASC')
                ->orderBy('id','ASC')
                ->get();
            $studentsLoaded = true;
        }

        return view('result.transcriptList', [
            'examId' => $examId,
            'classId' => $classId,
            'sessionId' => $sessionId,
            'sectionId' => $sectionId,
            'departmentId' => $departmentId,
            'students' => $students,
            'studentsLoaded' => $studentsLoaded,
        ]);
    }

    public function bulkTranscriptPdf(Request $request)
    {
        $request->validate([
            'examId' => 'required|integer',
            'stdIds' => 'required|array|min:1',
            'stdIds.*' => 'required',
        ]);

        $examId = (int)$request->input('examId');
        $rawIds = collect($request->input('stdIds', []))
            ->map(fn($v) => trim((string)$v))
            ->filter()
            ->unique()
            ->values();

        if ($rawIds->isEmpty()) {
            return back()->with('error', 'No students selected for PDF.');
        }

        $numericIds = $rawIds->filter(fn($v) => ctype_digit($v))->map(fn($v) => (int)$v)->values();

        $students = newAdmission::query()
            ->where(function($q) use ($rawIds, $numericIds){
                $q->whereIn('stdId', $rawIds);
                if ($numericIds->isNotEmpty()) {
                    $q->orWhereIn('id', $numericIds);
                }
            })
            ->orderByRaw('CAST(NULLIF(rollNumber, "") AS UNSIGNED) ASC')
            ->orderBy('id', 'ASC')
            ->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'No matching students found for selected IDs.');
        }

        $exam = Exam::find($examId);
        if (!$exam) {
            return back()->with('error', 'Selected exam not found.');
        }

        $students->load(['marksheet' => function($q) use ($examId){
            $q->where('examId', $examId)->orderBy('subjectId', 'ASC');
        }]);

        $transcripts = [];
        foreach ($students as $student) {
            $transcripts[] = [
                'studentDetails' => $student,
                'meritRank' => null,
                'maxMarkedSubjects' => 0,
                'studentMarkedSubjects' => 0,
                'hideForMaxRule' => false,
            ];
        }

        try {
            @set_time_limit(180);
            @ini_set('memory_limit', '512M');

            $config = ServerConfig::first();
            $html = view('result.bulk-transcript-pdf', [
                'exam' => $exam,
                'transcripts' => $transcripts,
                'config' => $config,
            ])->render();

            $fileName = 'bulk-transcripts-exam-'.$examId.'-'.date('Y-m-d').'.pdf';

            if (class_exists(Dompdf::class) && class_exists(Options::class)) {
                $options = new Options();
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);

                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                return response($dompdf->output(), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                ]);
            }

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
                return $pdf->download($fileName);
            }

            throw new \RuntimeException('No PDF engine available (Dompdf classes missing).');
        } catch (\Throwable $e) {
            Log::error('Bulk transcript PDF generation failed', [
                'exam_id' => $examId,
                'student_count' => count($transcripts),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', 'PDF generation failed on server. Please contact admin.');
        }
    }

    private function resolveIslamReligiousSubjectId(?int $classId = null): int
    {
        $query = Subject::where('isReligious', true)
            ->where(function($q){
                $q->whereRaw('LOWER(subjectName) LIKE ?', ['%islam%'])
                  ->orWhereRaw('LOWER(alias) LIKE ?', ['%islam%']);
            });

        if (!empty($classId) && $classId > 0) {
            $query->where(function($q) use ($classId){
                $q->where('assign_class', (string)$classId)
                  ->orWhere('assign_class', '0');
            });
        }

        $sub = $query
            ->orderByRaw("CASE WHEN LOWER(subjectName) LIKE '%111%' OR LOWER(alias) LIKE '%111%' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        return $sub ? (int)$sub->id : 0;
    }

    // Resolve effective religious subject for a class (mapping -> Islam default -> assigned -> any)
    private function resolveReligiousSubjectForClass(int $classId): int
    {
        if ($classId <= 0) {
            $islamGlobal = $this->resolveIslamReligiousSubjectId(null);
            if ($islamGlobal > 0) return $islamGlobal;
            $sub = Subject::where('isReligious', true)->orderBy('id')->first();
            return $sub ? (int)$sub->id : 0;
        }

        $map = ReligiousSubjectDefault::where('classId', $classId)->first();
        if ($map && Subject::where('id', (int)$map->subjectId)->where('isReligious', true)->exists()) {
            return (int)$map->subjectId;
        }

        $islamForClass = $this->resolveIslamReligiousSubjectId($classId);
        if ($islamForClass > 0) return $islamForClass;

        $sub = Subject::where('isReligious', true)
            ->where(function($q) use ($classId){
                $q->where('assign_class', (string)$classId)
                  ->orWhere('assign_class', '0');
            })
            ->orderBy('id')->first();
        if ($sub) return (int)$sub->id;

        $islamGlobal = $this->resolveIslamReligiousSubjectId(null);
        if ($islamGlobal > 0) return $islamGlobal;

        $sub = Subject::where('isReligious', true)->orderBy('id')->first();
        return $sub ? (int)$sub->id : 0;
    }

    /**
     * Compute a normalized base alias for pairing (e.g., bangla_1st_paper -> bangla).
     */
    private function basePairAlias(?string $alias): ?string
    {
        if(!$alias) return null;
        $a = strtolower(trim($alias));
        // Config-driven mapping (aliases/names)
        $mapA = config('subject_pairs.aliases', []);
        $mapN = config('subject_pairs.names', []);
        if(isset($mapA[$a])){
            $mapped = strtolower(trim((string)$mapA[$a]));
            $mapped = str_replace(['-','  '],'_', $mapped);
            $mapped = preg_replace('/__+/', '_', $mapped);
            return trim($mapped, '_');
        }
        // try original (non-lower) against names map too
        $orig = trim($alias);
        if(isset($mapN[$orig])){
            $mapped = strtolower(trim((string)$mapN[$orig]));
            $mapped = str_replace(['-','  '],'_', $mapped);
            $mapped = preg_replace('/__+/', '_', $mapped);
            return trim($mapped, '_');
        }
        // remove common paper tokens
        $a = str_replace(['-','  '],'_', $a);
        $a = preg_replace('/(_1st|_first)/','', $a);
        $a = preg_replace('/(_2nd|_second)/','', $a);
        $a = preg_replace('/(_paper|_part|_p)$/','', $a);
        $a = preg_replace('/__+/', '_', $a);
        return trim($a, '_');
    }

    /**
     * Detect subject pairs from active subjects using alias patterns and optional config.
     * Returns array of groups: [ [ 'base' => 'bangla', 'ids' => [id1,id2], 'name' => 'Bangla' ], ... ]
     */
    public function detectSubjectPairs($subjects)
    {
        $groups = [];
        $byBase = [];
        $mapIds = config('subject_pairs.ids', []);
        $disp = config('subject_pairs.displayNames', []);
        foreach($subjects as $s){
            // id mapping override
            if(isset($mapIds[$s->id])){
                $base = $this->basePairAlias((string)$mapIds[$s->id]);
            } else {
                $base = $this->basePairAlias($s->alias ?? '') ?: $this->basePairAlias($s->subjectName ?? '');
            }
            if(!$base) continue;
            $byBase[$base] = $byBase[$base] ?? [];
            $byBase[$base][] = $s;
        }
        foreach($byBase as $base => $list){
            if(count($list) >= 2){
                // choose display name from first, stripping trailing paper tokens from subjectName
                $name = $disp[strtolower($base)] ?? $list[0]->subjectName;
                $name = preg_replace('/\s*(1st|2nd)\s*Paper$/i','', $name);
                $groups[] = [
                    'base' => $base,
                    'ids' => array_map(fn($x)=> (int)$x->id, $list),
                    'name' => $name
                ];
            }
        }
        return $groups;
    }

    /**
     * Merge per-subject rows into paired rows according to detected groups.
     * $rowSubjects: array of rows with keys: id, name, type, cq, mcq, practical, total, grade, gradePoint
     * $subjectCache: map[id] => Subject model
     */
    public function mergeSubjectsForRow(array $rowSubjects, array $pairGroups, array $subjectCache, bool $isFeatureWise): array
    {
        $used = [];
        $out = [];
        $indexById = [];
        foreach($rowSubjects as $r){ $indexById[(int)$r['id']] = $r; }

        // helper to parse numeric values
        $num = function($v){ return is_numeric($v) ? (float)$v : 0.0; };

        foreach($pairGroups as $g){
            $ids = $g['ids'];
            $rows = [];
            foreach($ids as $id){ if(isset($indexById[$id])){ $rows[] = $indexById[$id]; $used[$id] = true; } }
            // Merge even if only one paper is present; skip only if none present
            if(count($rows) === 0){ continue; }
            $type = 'Main';
            foreach($rows as $r){ if(($r['type'] ?? 'Main') === 'Optional') { $type = 'Optional'; break; } }
            $cq = 0; $mcq = 0; $pr = 0; $total = 0;
            $anyMark = false; $anyFail = false;
            $fullCQ = 0; $fullMCQ = 0; $fullPr = 0;
            foreach($rows as $r){
                $cq += $num($r['cq']); $mcq += $num($r['mcq']); $pr += $num($r['practical']);
                $total += $num($r['total']);
                if(is_numeric($r['cq']) || is_numeric($r['mcq']) || is_numeric($r['practical']) || is_numeric($r['total'])){ $anyMark = true; }
                // For paired subjects, do not propagate paper-level fail; final fail will be based on combined total only
                $sub = $subjectCache[(int)$r['id']] ?? null;
                if($sub){ $fullCQ += (float)($sub->CQ ?? 0); $fullMCQ += (float)($sub->MCQ ?? 0); $fullPr += (float)($sub->Practical ?? 0); }
            }
            $grade = '-'; $pointDisplay = '-';
            if($anyMark){
                // Component-wise fail under feature-wise
                $cqPct = ($fullCQ > 0) ? ($cq / $fullCQ) * 100 : null;
                $mcqPct = ($fullMCQ > 0) ? ($mcq / $fullMCQ) * 100 : null;
                $prPct = ($fullPr > 0) ? ($pr / $fullPr) * 100 : null;
                $cGrades = [];
                foreach(['cqPct'=>$cqPct,'mcqPct'=>$mcqPct,'prPct'=>$prPct] as $k=>$v){
                    if($v === null){ $cGrades[$k] = '-'; } else { $row = GradeList::forScore($v); $cGrades[$k] = $row ? $row->gradeName : '-'; }
                }
                // Passing system for paired subjects: NOT feature-wise. Only combined total determines fail.
                if(false){ /* placeholder to keep diff simple */ }
                if($anyFail){
                    $grade = 'F'; $pointDisplay = '0.00';
                }else{
                    $fullSum = $fullCQ + $fullMCQ + $fullPr;
                    $percent = ($fullSum > 0) ? ($total / $fullSum) * 100 : null;
                    if($percent !== null){
                        $gRow = GradeList::forScore($percent);
                        $grade = $gRow ? $gRow->gradeName : '-';
                        $pointDisplay = $gRow ? number_format($gRow->gradePoint,2) : '-';
                    } else {
                        $grade = '-'; $pointDisplay = '-';
                    }
                }
            }
            // capture per-paper components for column-based display
            $paper1 = null; $paper2 = null;
            if(count($rows) >= 1){
                $r1 = $rows[0];
                $paper1 = [
                    'cq' => $r1['cq'],
                    'mcq' => $r1['mcq'],
                    'practical' => $r1['practical'],
                    'cqGrade' => $r1['cqGrade'] ?? '-',
                    'mcqGrade' => $r1['mcqGrade'] ?? '-',
                    'prGrade' => $r1['prGrade'] ?? '-',
                ];
            }
            if(count($rows) >= 2){
                $r2 = $rows[1];
                $paper2 = [
                    'cq' => $r2['cq'],
                    'mcq' => $r2['mcq'],
                    'practical' => $r2['practical'],
                    'cqGrade' => $r2['cqGrade'] ?? '-',
                    'mcqGrade' => $r2['mcqGrade'] ?? '-',
                    'prGrade' => $r2['prGrade'] ?? '-',
                ];
            }
            $out[] = [
                'id' => implode('-', $ids),
                'sourceIds' => array_values(array_map(fn($x) => (int)$x, $ids)),
                'name' => $g['name'],
                'type' => $type,
                'isReligious' => 0,
                'hasCQFeature' => ((float)$fullCQ > 0),
                'hasMCQFeature' => ((float)$fullMCQ > 0),
                'hasPracticalFeature' => ((float)$fullPr > 0),
                'paired' => true,
                'paper1' => $paper1,
                'paper2' => $paper2,
                'cq' => $anyMark ? ($cq > 0 ? $cq : '-') : '-',
                'mcq' => $anyMark ? ($mcq > 0 ? $mcq : '-') : '-',
                'practical' => $anyMark ? ($pr > 0 ? $pr : '-') : '-',
                'total' => $anyMark ? ($total > 0 ? $total : '-') : '-',
                'grade' => $grade,
                'gradePoint' => $pointDisplay,
                'cqGrade' => $cGrades['cqPct'] ?? '-',
                'mcqGrade' => $cGrades['mcqPct'] ?? '-',
                'prGrade' => $cGrades['prPct'] ?? '-',
            ];
        }

        // add non-paired subjects, enrich with component grades
        foreach($rowSubjects as $r){
            if(!isset($used[(int)$r['id']])){
                $sub = $subjectCache[(int)$r['id']] ?? null;
                $fullCQ = $sub ? (float)($sub->CQ ?? 0) : 0.0;
                $fullMCQ = $sub ? (float)($sub->MCQ ?? 0) : 0.0;
                $fullPr = $sub ? (float)($sub->Practical ?? 0) : 0.0;
                $cqPct = ($fullCQ>0 && is_numeric($r['cq'])) ? ((float)$r['cq'] / $fullCQ) * 100 : null;
                $mcqPct = ($fullMCQ>0 && is_numeric($r['mcq'])) ? ((float)$r['mcq'] / $fullMCQ) * 100 : null;
                $prPct = ($fullPr>0 && is_numeric($r['practical'])) ? ((float)$r['practical'] / $fullPr) * 100 : null;
                $rr = $r;
                $rr['paired'] = false;
                $rr['paper1'] = null; $rr['paper2'] = null;
                $rr['sourceIds'] = [(int)$r['id']];
                $rr['hasCQFeature'] = ($fullCQ > 0);
                $rr['hasMCQFeature'] = ($fullMCQ > 0);
                $rr['hasPracticalFeature'] = ($fullPr > 0);
                $rr['cqGrade'] = $cqPct!==null ? (GradeList::forScore($cqPct)->gradeName ?? '-') : '-';
                $rr['mcqGrade'] = $mcqPct!==null ? (GradeList::forScore($mcqPct)->gradeName ?? '-') : '-';
                $rr['prGrade'] = $prPct!==null ? (GradeList::forScore($prPct)->gradeName ?? '-') : '-';
                $out[] = $rr;
            }
        }
        // preserve order roughly by subject id
        usort($out, function($a,$b){ return strcmp((string)$a['name'], (string)$b['name']); });
        return $out;
    }
}