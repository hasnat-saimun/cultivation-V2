<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\GradeList;
use App\Models\ServerConfig;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\ReligiousSubjectDefault;

class MarksheetController extends Controller
{
    public function addMarks(){
        // Restrict visible classes & subjects if teacher
        $adminId = session('cultivationAdmin');
        $user = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
        $isTeacher = $user && $user->isTeacher();
        $classIds = $isTeacher ? $user->access_class_array : [];
        $subjectIds = $isTeacher ? $user->access_subject_array : [];
        return view('result.add-marks', [
            'restrictedClassIds' => $classIds,
            'restrictedSubjectIds' => $subjectIds,
            'isTeacherAdmin' => $isTeacher,
        ]);
    }
    public function getMarks(Request $requ){
        // Server-side enforcement of teacher's assigned class & subject
        $adminId = session('cultivationAdmin');
        $user = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
        if($user && $user->isTeacher()){
            $allowedClasses = $user->access_class_array;
            $allowedSubjects = $user->access_subject_array;
            if(!in_array($requ->classId, $allowedClasses) || !in_array($requ->subjectId, $allowedSubjects)){
                return redirect()->route('addMarks')->with('error','Unauthorized class or subject selection');
            }
        }
        // Fetch students class-wise along with session and section filters
        $studentList = newAdmission::where([
            'className'   => (int)$requ->classId,
            'sessName'    => (int)$requ->sessionId,
            'sectionName' => (int)$requ->groupId,
        ])->orderBy('rollNumber','ASC')->orderBy('id','ASC')->get();
        return view('result.get-marks',[
            'studentList'=>$studentList,
            'groupId'=>$requ->groupId,
            'classId'=>$requ->classId,
            'sessionId'=>$requ->sessionId,
            'examId'=>$requ->examId,
            'subjectId'=>$requ->subjectId
        ]);
    }

    public function confirmMarks(Request $requ){
        // Enforce teacher role restrictions before saving
        $adminId = session('cultivationAdmin');
        $user = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
        if($user && $user->isTeacher()){
            if(!in_array($requ->classId, $user->access_class_array) || !in_array($requ->subjectId, $user->access_subject_array)){
                return redirect()->route('addMarks')->with('error','Unauthorized attempt to submit marks for this class/subject');
            }
        }
        $studentId = $requ->studentId;
        $totalData = count($studentId);
        $x = 0;
        while($x<$totalData){
            $chkData = Marksheet::where(['sessionId'=>$requ->sessionId,'classId'=>$requ->classId,'groupId'=>$requ->groupId,'studentId'=>$requ->studentId[$x],'examId'=>$requ->examId,'subjectId'=>$requ->subjectId])->first();
            if(isset($chkData) && !empty($chkData)):
                $chkData->delete();
            endif;
            $totalMarks = 0;
            if(isset($requ->cqMarks[$x]) && $requ->cqMarks[$x] !== null && $requ->cqMarks[$x] !== '') {
                $totalMarks += $requ->cqMarks[$x];
            }
            if(isset($requ->mcqMarks[$x]) && $requ->mcqMarks[$x] !== null && $requ->mcqMarks[$x] !== '') {
                $totalMarks += $requ->mcqMarks[$x];
            }
            if(isset($requ->practical[$x]) && $requ->practical[$x] !== null && $requ->practical[$x] !== '') {
                $totalMarks += $requ->practical[$x];
            }
            $grade = GradeList::whereRaw("'$totalMarks' BETWEEN minMark AND maxMark")->first();
            if(isset($grade) && !empty($grade)):
                $gradePoint = $grade->gradePoint;
                $laterGrade = $grade->gradeName;
            else:
                $gradePoint = 0.00;
                $laterGrade = 'F';
            endif;
            $marks = new Marksheet();
            
            $marks->studentId       = $requ->studentId[$x];
            $marks->classId         = $requ->classId;
            $marks->sessionId       = $requ->sessionId;
            $marks->examId          = $requ->examId;
            $marks->subjectId       = $requ->subjectId;
            $marks->groupId         = $requ->groupId;
            $marks->subjectMarks    = (isset($requ->cqMarks[$x]) && $requ->cqMarks[$x] !== '') ? $requ->cqMarks[$x] : null;

            $marks->objectMarks     = (isset($requ->mcqMarks[$x]) && $requ->mcqMarks[$x] !== '') ? $requ->mcqMarks[$x] : null;

            $marks->practicalMarks  = (isset($requ->practical[$x]) && $requ->practical[$x] !== '') ? $requ->practical[$x] : null;
            
            $marks->totalMarks      = $totalMarks;
            $marks->laterGrade      = $laterGrade;
            $marks->gradePoint      = $gradePoint;
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

    public function allMarksheet(Request $request){
        // Filter inputs (optional)
        $examId    = $request->get('examId');
        $classId   = $request->get('classId');
        $sessionId = $request->get('sessionId');
        $sectionId = $request->get('sectionId'); // group/section

        $exam      = $examId ? Exam::find($examId) : null;
        $isFeatureWise = $exam && $exam->passingSystem == 1; // same logic as single marksheet

        // Subjects (global list for header)
        $subjects = Subject::orderBy('id','ASC')->get();

    $passResults = [];
    $failResults = [];
    $subjectWise = [];
    $incompleteResults = [];
        $studentsLoaded = false;

        if($examId && $classId){
            // Build marks query for the selected filters
            $marksBaseQuery = Marksheet::where('examId',$examId)
                ->where('classId',$classId);
            if($sessionId){ $marksBaseQuery->where('sessionId',$sessionId); }
            if($sectionId){ $marksBaseQuery->where('groupId',$sectionId); }

            $studentIds = $marksBaseQuery->distinct()->pluck('studentId');
            $students = newAdmission::whereIn('id',$studentIds)->get();
            // Determine active subjects for this class/session/section/exam from marks present
            $activeSubjectIds = $marksBaseQuery->distinct()->pluck('subjectId')->map(fn($v) => (int)$v)->all();
            $studentsLoaded = true;

            // Pre-cache subject details to reduce queries
            $subjectCache = [];
            foreach($subjects as $s){ $subjectCache[$s->id] = $s; }

            // Grade list caching for 0-100 mapping
            $gradeList = GradeList::orderBy('minMark','ASC')->get();

            foreach($students as $stu){
                $selectedReligiousId = $stu->religiousSubjectId ? (int)$stu->religiousSubjectId : 0;
                $effectiveReligiousId = $selectedReligiousId > 0 ? $selectedReligiousId : $this->resolveReligiousSubjectForClass((int)$classId);
                // Build a fresh query per student to avoid accumulating where clauses
                $stuMarks = Marksheet::where('examId',$examId)
                    ->where('classId',$classId)
                    ->when($sessionId, function($q) use ($sessionId){ return $q->where('sessionId',$sessionId); })
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
                    $hasAnyMark = ($cq !== null) || ($mcq !== null) || ($pr !== null) || ($markRow && is_numeric($markRow->totalMarks));

                    // Displays
                    $cqDisplay = $cq !== null ? $cq : '-';
                    $mcqDisplay = $mcq !== null ? $mcq : '-';
                    $prDisplay = $pr !== null ? $pr : '-';

                    $total = 0;
                    if ($hasAnyMark) {
                        $total = ($cq !== null ? $cq : 0) + ($mcq !== null ? $mcq : 0) + ($pr !== null ? $pr : 0);
                        $subtotalMarks += $total;
                    } else {
                        if ($sub->subjectType === 'Main') {
                            // Count religious missing only if it's the effective one
                            if (!empty($sub->isReligious)) {
                                if ($effectiveReligiousId > 0 && (int)$sub->id === $effectiveReligiousId) {
                                    $missingMainSubjects++;
                                }
                            } else {
                                $missingMainSubjects++;
                            }
                        }
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
                        // Overall grade (by total marks)
                        $gradeRow = GradeList::forScore($total);
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
                        'cq' => $cqDisplay,
                        'mcq' => $mcqDisplay,
                        'practical' => $prDisplay,
                        'total' => $hasAnyMark ? ($markRow && is_numeric($markRow->totalMarks) ? $markRow->totalMarks : $total) : '-',
                        'grade' => $overallGrade,
                        'gradePoint' => $overallPoint > 0 ? number_format($overallPoint,2) : ($overallGrade==='F' ? '0.00' : '-')
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

                // Optional bonus (NCTB rule >2 only excess counts)
                $optionalBonus = ($optionalSubjectFound && $optionalPoint > 2) ? ($optionalPoint - 2) : 0;
                $mainCount = count($mainGradePoints);
                $isIncomplete = $missingMainSubjects > 0;
                $finalGpa = $mainCount > 0 ? round((array_sum($mainGradePoints) + $optionalBonus)/$mainCount, 2) : 0;
                $finalLetter = '-';
                if($isIncomplete){
                    $finalLetter = 'Incomplete';
                    $finalGpa = null;
                }elseif($hasFail){
                    $finalLetter = 'F';
                    $finalGpa = 0;
                }elseif($mainCount>0){
                    $avgRow = GradeList::forGpa($finalGpa);
                    $finalLetter = $avgRow ? $avgRow->gradeName : '-';
                }

                $rowPayload = [
                    'student' => $stu,
                    'subjects' => $perSubjectOutput,
                    'totalMarks' => $subtotalMarks,
                    'finalGpa' => number_format($finalGpa,2),
                    'finalLetter' => $finalLetter,
                    'isFail' => $hasFail,
                    'isIncomplete' => $isIncomplete,
                    'religiousSubjectIdUsed' => $effectiveReligiousId,
                    'religiousSubjectUsedName' => ($effectiveReligiousId && isset($subjectCache[$effectiveReligiousId])) ? $subjectCache[$effectiveReligiousId]->subjectName : null,
                ];
                if($isIncomplete){ $incompleteResults[] = $rowPayload; }
                elseif($hasFail){ $failResults[] = $rowPayload; } else { $passResults[] = $rowPayload; }
            }
        }

        // Skip subjects that have no data across the class (no rows in subjectWise)
        // Build active subject id list
        $activeSubjectIds = [];
        foreach ($subjectWise as $sid => $payload) {
            if (!empty($payload['rows'])) { $activeSubjectIds[] = (int) $sid; }
        }
        if (!empty($activeSubjectIds)) {
            // Filter header subjects to only active ones (preserve order)
            $subjects = $subjects->filter(function($s) use ($activeSubjectIds) {
                return in_array((int)$s->id, $activeSubjectIds, true);
            })->values();
            $orderIds = $subjects->map(fn($s) => (int)$s->id)->all();
            // Reorder and filter each student's subject cells to align with headers
            $reorder = function(array $rows) use ($orderIds) {
                $byId = [];
                foreach ($rows as $r) { $byId[(int)$r['id']] = $r; }
                $out = [];
                foreach ($orderIds as $oid) { if (isset($byId[$oid])) { $out[] = $byId[$oid]; } }
                return $out;
            };
            foreach ($passResults as &$row) { $row['subjects'] = $reorder($row['subjects']); }
            unset($row);
            foreach ($failResults as &$row) { $row['subjects'] = $reorder($row['subjects']); }
            unset($row);
            foreach ($incompleteResults as &$row) { $row['subjects'] = $reorder($row['subjects']); }
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
            'studentsLoaded' => $studentsLoaded,
            'exam' => $exam,
        ]);
    }

    public function generateMarksheet(Request $requ){
        // return $requ->all();
        $config = ServerConfig::first(); 

        $student = newAdmission::where('stdId', $requ->stdId)
        ->with(['marksheet'])
        ->first();
        return view('result.marksheetGenerate',['studentDetails'=>$student,'examId'=>$requ->examId,'config'=>$config]);
    }


    //front web site str
    public function internalResult(){
        return view('frontend.result.internalResult');
    }


    public function individualResult(){
        return view('frontend.result.individualResult');
    }
    //front web site end

    // Resolve effective religious subject for a class (mapping -> assigned -> any)
    private function resolveReligiousSubjectForClass(int $classId): int
    {
        if ($classId <= 0) return 0;
        $map = ReligiousSubjectDefault::where('classId', $classId)->first();
        if ($map) return (int)$map->subjectId;
        $sub = Subject::where('isReligious', true)
            ->where(function($q) use ($classId){
                $q->where('assign_class', (string)$classId)
                  ->orWhere('assign_class', '0');
            })
            ->orderBy('id')->first();
        if ($sub) return (int)$sub->id;
        $sub = Subject::where('isReligious', true)->orderBy('id')->first();
        return $sub ? (int)$sub->id : 0;
    }
}