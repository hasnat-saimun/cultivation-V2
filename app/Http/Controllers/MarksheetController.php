<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\GradeList;
use App\Models\ServerConfig;
use App\Models\Subject;
use App\Models\Exam;

class MarksheetController extends Controller
{
    public function addMarks(){
        return view('result.add-marks');
    }
    public function getMarks(Request $requ){
        $studentList = newAdmission::where(['sessName'=>$requ->sessionId,'sectionName'=>$requ->groupId])->get();
        return view('result.get-marks',['studentList'=>$studentList,'groupId'=>$requ->groupId,'classId'=>$requ->classId,'sessionId'=>$requ->sessionId,'examId'=>$requ->examId,'subjectId'=>$requ->subjectId]);
    }

    public function confirmMarks(Request $requ){
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
        $studentsLoaded = false;

        if($examId && $classId){
            // Build marks query for the selected filters
            $marksBaseQuery = Marksheet::where('examId',$examId)
                ->where('classId',$classId);
            if($sessionId){ $marksBaseQuery->where('sessionId',$sessionId); }
            if($sectionId){ $marksBaseQuery->where('groupId',$sectionId); }

            $studentIds = $marksBaseQuery->distinct()->pluck('studentId');
            $students = newAdmission::whereIn('id',$studentIds)->get();
            $studentsLoaded = true;

            // Pre-cache subject details to reduce queries
            $subjectCache = [];
            foreach($subjects as $s){ $subjectCache[$s->id] = $s; }

            // Grade list caching for 0-100 mapping
            $gradeList = GradeList::orderBy('minMark','ASC')->get();

            foreach($students as $stu){
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

                foreach($subjects as $sub){
                    $markRow = $marksBySubject[$sub->id] ?? null;
                    $cq = ($markRow && is_numeric($markRow->subjectMarks)) ? (float)$markRow->subjectMarks : null;
                    $mcq = ($markRow && is_numeric($markRow->objectMarks)) ? (float)$markRow->objectMarks : null;
                    $pr  = ($markRow && is_numeric($markRow->practicalMarks)) ? (float)$markRow->practicalMarks : null;

                    // If any component missing -> treat as '-'
                    $cqDisplay = $cq !== null ? $cq : '-';
                    $mcqDisplay = $mcq !== null ? $mcq : '-';
                    $prDisplay = $pr !== null ? $pr : '-';

                    $total = ($cq !== null ? $cq : 0) + ($mcq !== null ? $mcq : 0) + ($pr !== null ? $pr : 0);
                    $subtotalMarks += $total;

                    // Component grade percent (only if value & full mark available)
                    $fullCQ = $sub->CQ ?? 0; $fullMCQ = $sub->MCQ ?? 0; $fullPR = $sub->Practical ?? 0;
                    $cqPercent = ($fullCQ > 0 && $cq !== null) ? ($cq / $fullCQ) * 100 : null;
                    $mcqPercent = ($fullMCQ > 0 && $mcq !== null) ? ($mcq / $fullMCQ) * 100 : null;
                    $prPercent = ($fullPR > 0 && $pr !== null) ? ($pr / $fullPR) * 100 : null;

                    $componentGrades = [];
                    foreach(['cqPercent'=>$cqPercent,'mcqPercent'=>$mcqPercent,'prPercent'=>$prPercent] as $key=>$val){
                        if($val === null){
                            $componentGrades[$key] = '-';
                        }else{
                            $row = GradeList::where('minMark','<=',$val)->where('maxMark','>=',$val)->first();
                            $componentGrades[$key] = $row ? $row->gradeName : '-';
                        }
                    }

                    // Overall grade (by total marks, 0-100 scale assumed)
                    $gradeRow = GradeList::where('minMark','<=',$total)->where('maxMark','>=',$total)->first();
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

                    $rowForSubject = [
                        'id' => $sub->id,
                        'name' => $sub->subjectName,
                        'cq' => $cqDisplay,
                        'mcq' => $mcqDisplay,
                        'practical' => $prDisplay,
                        'total' => $markRow ? $markRow->totalMarks : ($cq!==null||$mcq!==null||$pr!==null ? $total : '-'),
                        'grade' => $overallGrade,
                        'gradePoint' => $overallPoint > 0 ? number_format($overallPoint,2) : ($overallGrade==='F' ? '0.00' : '-')
                    ];
                    $perSubjectOutput[] = $rowForSubject;

                    // Build subject-wise aggregation
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
                $finalGpa = $mainCount > 0 ? round((array_sum($mainGradePoints) + $optionalBonus)/$mainCount, 2) : 0;
                $finalLetter = '-';
                if($hasFail){
                    $finalLetter = 'F';
                    $finalGpa = 0;
                }elseif($mainCount>0){
                    $avgRow = GradeList::where('gradePoint','<=',$finalGpa)->orderBy('gradePoint','desc')->first();
                    $finalLetter = $avgRow ? $avgRow->gradeName : '-';
                }

                $rowPayload = [
                    'student' => $stu,
                    'subjects' => $perSubjectOutput,
                    'totalMarks' => $subtotalMarks,
                    'finalGpa' => number_format($finalGpa,2),
                    'finalLetter' => $finalLetter,
                    'isFail' => $hasFail
                ];
                if($hasFail){ $failResults[] = $rowPayload; } else { $passResults[] = $rowPayload; }
            }
        }

        return view('result.allMarksheet', [
            'subjects' => $subjects,
            'passResults' => $passResults,
            'failResults' => $failResults,
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
}