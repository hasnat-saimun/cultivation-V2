<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\GradeList;
use App\Models\serverConfig;

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

    public function allMarksheet(){
        return view('result.allMarksheet');
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