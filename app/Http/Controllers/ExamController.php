<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;
use App\Models\newAdmission;

class ExamController extends Controller
{
    protected function validateExamRequest(Request $requ, $isUpdate = false)
    {
        $rules = [
            'examName' => 'required|string|max:255',
            'examClass' => 'required',
            'examDate' => 'required|date',
            'closeDate' => 'required|date|after_or_equal:examDate',
            'baseMark' => 'required|numeric|min:0',
            'passingSystem' => 'required|in:1,2',
        ];

        if ($isUpdate) {
            $rules['itemId'] = 'required|exists:exams,id';
        }

        return $requ->validate($rules);
    }

    
    public function createExam(){
        return view('result.new-exam');
    }

    public function confirmExam(Request $requ){
        $validated = $this->validateExamRequest($requ);
        $chk = Exam::where(['examName'=>$requ->examName]);
        if($chk->exists()):
            return back()->with('error','Alias already exist');
        else:
            $exam = new Exam();
            $aliasCreate = str_replace(' ','_',$validated['examName']);
            $alias = strtolower($aliasCreate);

            $exam->examName     = $validated['examName'];
            $exam->className    = $validated['examClass'];
            $exam->examDate     = $validated['examDate'];
            $exam->closeDate    = $validated['closeDate'];
            $exam->baseMark     = $validated['baseMark'];
            $exam->passingSystem = $validated['passingSystem'];
            $exam->alias        = $alias;
            $exam->save();
            return back()->with('success','Record successfully saved');
        endif;
    }

    public function allExam(){
        $itemData = Exam::orderBy('id','DESC')->get();
        return view('result.examList',['itemData'=>$itemData]);
    }
    
    public function editExam($item){
        $itemData = Exam::find($item);
        return view('result.edit-exam',['item'=>$itemData]);
    }
    

    public function updateExam(Request $requ){
        $validated = $this->validateExamRequest($requ, true);
        $exam = Exam::find($validated['itemId']);
        if(!empty($exam) && $exam->exists()):
            $aliasCreate = str_replace(' ','_',$validated['examName']);
            $alias = strtolower($aliasCreate);

            $exam->examName     = $validated['examName'];
            $exam->className    = $validated['examClass'];
            $exam->examDate     = $validated['examDate'];
            $exam->closeDate    = $validated['closeDate'];
            $exam->baseMark     = $validated['baseMark'];
            $exam->passingSystem = $validated['passingSystem'];
            $exam->alias        = $alias;
            $exam->save();
            return back()->with('success','Record successfully updated');
        else:
            return back()->with('error','No alias found for update');
        endif;
    }

    public function delExam($id){
        $itemData = Exam::find($id);
        if(empty($itemData)):
            return back()->with('error','Sorry! Alias failed to delete');
        else:
            $itemData->delete();
            return back()->with('success','Success! Alias successfully delete');
        endif;
    }

    // admit card controller

    public function admitCard(){
        return view('result.admitCard');
    }

    public function getAdmitCard(Request $requ){
        $studentList = newAdmission::where(['sessName'=>$requ->sessionId,'sectionName'=>$requ->groupId,'className'=>$requ->classId])->get();
        return view('result.get-admitCard',['studentList'=>$studentList,'groupId'=>$requ->groupId,'classId'=>$requ->classId,'sessionId'=>$requ->sessionId,'examId'=>$requ->examId]);
    }

    public function attendSheet(){
        return view('result.attendSheet');
    }

    public function getAttendSheet(Request $requ){
        $studentList = newAdmission::where(['sessName'=>$requ->sessionId,'sectionName'=>$requ->groupId,'className'=>$requ->classId])->get();
        return view('result.getAttendSheet',['studentList'=>$studentList,'groupId'=>$requ->groupId,'classId'=>$requ->classId,'sessionId'=>$requ->sessionId,'examId'=>$requ->examId]);
    }
}
