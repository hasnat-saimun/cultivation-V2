<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Syllabus;
use App\Models\SemisterPlan;
use App\Models\ExamRoutine;
use App\Models\ClassRoutine;
use File;

class AcademicController extends Controller
{
    public function index(){
        return view('academic.index');
    }

    public function syllabusManage(){
        $syllabus = Syllabus::orderBy('id','DESC')->get();
        return view('academic.syllabus',['syllabusList'=>$syllabus]);
    }

    public function saveSyllabus(Request $requ){
        if(empty($requ->itemId)):
            $item   = new Syllabus();
        else:
            $item   = Syllabus::find($requ->itemId);
        endif;

        $item->title            = $requ->title;
        $item->assignClass      = $requ->assignClass;
        $item->assignDepartment = $requ->assignDepartment;
        $item->assignSession    = $requ->assignSession;
        if(!empty($requ->attachment)):
            $attachment = $requ->attachment;
            $newAttachment      = rand().date('Ymd').'.'.$attachment->getClientOriginalExtension();
            $attachment->move(public_path('upload/image/cultivation/syllabus'),$newAttachment);
            $item->attachment   = $newAttachment;
        endif;
        // $item->status        = $requ->status;

        if($item->save()):
            return back()->with('success','Item successfully saved');
        else:
            return back()->with('error','Item failed to save');
        endif;
    }

    public function editSyllabus($id){
        $syllabus = Syllabus::orderBy('id','DESC')->get();
        return view('academic.syllabus',['itemId'=>$id,'syllabusList'=>$syllabus]);
    }

    public function delSyllabus($id){
        $item = Syllabus::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/syllabus/').$item->attachment)):
                File::delete(public_path('upload/image/cultivation/syllabus/').$item->attachment);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delSyllabusContent($id){
        $item = Syllabus::find($id);
        // return public_path('upload/image/cultivation/syllabus/').$item->attachment;
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/syllabus/').$item->attachment)):
                File::delete(public_path('upload/image/cultivation/syllabus/').$item->attachment);
            endif;
            $item->attachment = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }
    // syllabus ends here

    public function semisterPlanManage(){
        $semisterPlan = SemisterPlan::orderBy('id','DESC')->get();
        return view('academic.semisterPlan',['semisterPlanList'=>$semisterPlan]);
    }

    public function saveSemisterPlan(Request $requ){
        if(empty($requ->itemId)):
            $item   = new SemisterPlan();
        else:
            $item   = SemisterPlan::find($requ->itemId);
        endif;

        $item->title            = $requ->title;
        $item->assignClass      = $requ->assignClass;
        $item->assignDepartment = $requ->assignDepartment;
        $item->assignSession    = $requ->assignSession;
        if(!empty($requ->attachment)):
            $attachment = $requ->attachment;
            $newAttachment      = rand().date('Ymd').'.'.$attachment->getClientOriginalExtension();
            $attachment->move(public_path('upload/image/cultivation/semisterPlan'),$newAttachment);
            $item->attachment   = $newAttachment;
        endif;
        // $item->status        = $requ->status;

        if($item->save()):
            return back()->with('success','Item successfully saved');
        else:
            return back()->with('error','Item failed to save');
        endif;
    }

    public function editSemisterPlan($id){
        $semisterPlan = SemisterPlan::orderBy('id','DESC')->get();
        return view('academic.semisterPlan',['itemId'=>$id,'semisterPlanList'=>$semisterPlan]);
    }

    public function delSemisterPlan($id){
        $item = SemisterPlan::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/semisterPlan/').$item->attachment)):
                File::delete(public_path('upload/image/cultivation/semisterPlan/').$item->attachment);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delSemisterPlanContent($id){
        $item = SemisterPlan::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/semisterPlan/').$item->attachment)):
                File::delete(public_path('upload/image/cultivation/semisterPlan/').$item->attachment);
            endif;
            $item->attachment = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    // Semister plan ends here

    public function classRoutineManage(){
        $classRoutine = ClassRoutine::orderBy('id','DESC')->get();
        return view('academic.classRoutine',['classRoutineList'=>$classRoutine]);
    }

    public function saveClassRoutine(Request $requ){
        if(empty($requ->itemId)):
            $item   = new ClassRoutine();
        else:
            $item   = ClassRoutine::find($requ->itemId);
        endif;

        $item->title            = $requ->title;
        $item->assignClass      = $requ->assignClass;
        $item->assignDepartment = $requ->assignDepartment;
        $item->assignSession    = $requ->assignSession;
        if(!empty($requ->attachment)):
            $attachment = $requ->attachment;
            $newAttachment      = rand().date('Ymd').'.'.$attachment->getClientOriginalExtension();
            $attachment->move(public_path('upload/image/cultivation/classRoutine'),$newAttachment);
            $item->attachment   = $newAttachment;
        endif;
        // $item->status        = $requ->status;

        if($item->save()):
            return back()->with('success','Item successfully saved');
        else:
            return back()->with('error','Item failed to save');
        endif;
    }

    public function editClassRoutine($id){
        $classRoutine = ClassRoutine::orderBy('id','DESC')->get();
        return view('academic.classRoutine',['itemId'=>$id,'classRoutineList'=>$classRoutine]);
    }

    public function delClassRoutine($id){
        $item = ClassRoutine::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/classRoutine/').$item->attachment)):
                File::delete(public_path('upload/image/cultivation/classRoutine/').$item->attachment);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delClassRoutineContent($id){
        $item = ClassRoutine::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/classRoutine/').$item->attachment)):
                File::delete(public_path('upload/image/cultivation/classRoutine/').$item->attachment);
            endif;
            $item->attachment = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    // class routine ends here

    public function examRoutineManage(){
        $examRoutine = ExamRoutine::orderBy('id','DESC')->get();
        return view('academic.examRoutine',['examRoutineList'=>$examRoutine]);
    }

    public function saveExamRoutine(Request $requ){
        if(empty($requ->itemId)):
            $item   = new ExamRoutine();
        else:
            $item   = ExamRoutine::find($requ->itemId);
        endif;

        $item->title            = $requ->title;
        $item->assignClass      = $requ->assignClass;
        $item->assignDepartment = $requ->assignDepartment;
        $item->assignSession    = $requ->assignSession;
        if(!empty($requ->attachment)):
            $attachment = $requ->attachment;
            $newAttachment      = rand().date('Ymd').'.'.$attachment->getClientOriginalExtension();
            $attachment->move(public_path('upload/image/cultivation/examRoutine'),$newAttachment);
            $item->attachment   = $newAttachment;
        endif;
        // $item->status        = $requ->status;

        if($item->save()):
            return back()->with('success','Item successfully saved');
        else:
            return back()->with('error','Item failed to save');
        endif;
    }

    public function editExamRoutine($id){
        $examRoutine = ExamRoutine::orderBy('id','DESC')->get();
        return view('academic.examRoutine',['itemId'=>$id,'examRoutineList'=>$examRoutine]);
    }

    public function delExamRoutine($id){
        $item = ExamRoutine::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/examRoutine/').$item->attachment)):
                File::delete(public_path('upload/image/cultivation/examRoutine/').$item->attachment);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delExamRoutineContent($id){
        $item = ExamRoutine::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/examRoutine/').$item->attachment)):
                File::delete(public_path('upload/image/cultivation/examRoutine/').$item->attachment);
            endif;
            $item->attachment = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }


    //web front controller str
    public function newSyllabus()
    {
        $syllabus  =   Syllabus::all();
        return view('frontend.academic.syllabus',['Datakey'=>$syllabus]);
    }

    public function newClassSchedule()
    {   
        $result=ClassRoutine::get();
        return view('frontend.academic.classSchedule',['Datakey'=>$result]);
    }

    public function newExamSchedule()
    {
        $result=ExamRoutine::get();
        return view('frontend.academic.examSchedule',['Datakey'=>$result]);
    }

    public function newSemister()
    {
        $result = SemisterPlan::get();
        return view('frontend.academic.semister',['Datakey'=>$result]);
    }
     //web front controller end
}
