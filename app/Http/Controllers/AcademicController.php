<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Syllabus;
use App\Models\SemisterPlan;
use App\Models\ExamRoutine;
use App\Models\ClassRoutine;
use App\Models\InternalResult;
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
        $item->assignSection    = $requ->assignSection ? (int)$requ->assignSection : null;
        $item->assignSession    = $requ->assignSession;
        if(!empty($requ->attachment)):
            $validated = $requ->validate([
                    'attachment' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:2048',
                     // max 2 MB
                ],[
                    'attachment.mimes'  => 'Allowed formats: pdf,jpeg,png,jpg,gif,webp,avif.',
                    'attachment.max'    => 'Each file must be less than 2MB.'
                ]);
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
        $item->assignSection    = $requ->assignSection ? (int)$requ->assignSection : null;
        $item->assignSession    = $requ->assignSession;
        if(!empty($requ->attachment)):
            $validated = $requ->validate([
                    'attachment' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:2048',
                     // max 2 MB
                ],[
                    'attachment.mimes'  => 'Allowed formats: pdf,jpeg,png,jpg,gif,webp,avif.',
                    'attachment.max'    => 'Each file must be less than 2MB.'
                ]);
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

    // Internal Results starts here
    public function internalResultManage(){
        $results = InternalResult::orderBy('id','DESC')->get();
        return view('academic.internalResult',["resultList"=>$results]);
    }

    public function saveInternalResult(Request $requ){
        $requ->validate([
            'title' => 'required|string|max:255',
            'assignClass' => 'nullable|integer',
            'assignDepartment' => 'nullable|integer',
            'assignSection' => 'nullable|integer|exists:section_manages,id',
            'assignSession' => 'nullable|integer',
            'attachment' => 'nullable|mimes:pdf,jpeg,png,jpg,gif,webp,avif|max:2048',
        ]);
        if(empty($requ->itemId)){
            $item = new InternalResult();
        }else{
            $item = InternalResult::find($requ->itemId) ?? new InternalResult();
        }

        $item->title            = $requ->title;
        $item->assignClass      = $requ->assignClass;
        $item->assignDepartment = $requ->assignDepartment;
        $item->assignSection    = $requ->assignSection ? (int)$requ->assignSection : null;
        $item->assignSession    = $requ->assignSession;

        if(!empty($requ->attachment)){
            $validated = $requ->validate([
                'attachment' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif|max:2048',
            ],[
                'attachment.mimes' => 'Allowed formats: pdf,jpeg,png,jpg,gif,webp,avif.',
                'attachment.max'   => 'Each file must be less than 2MB.'
            ]);
            $attachment = $requ->attachment;
            $newAttachment = rand().date('Ymd').'.'.$attachment->getClientOriginalExtension();
            $attachment->move(public_path('upload/image/cultivation/internalResult'), $newAttachment);
            $item->attachment = $newAttachment;
        }

        if($item->save()){
            return back()->with('success','Item successfully saved');
        }else{
            return back()->with('error','Item failed to save');
        }
    }

    public function editInternalResult($id){
        $results = InternalResult::orderBy('id','DESC')->get();
        return view('academic.internalResult',["itemId"=>$id, "resultList"=>$results]);
    }

    public function delInternalResult($id){
        $item = InternalResult::find($id);
        if(!empty($item)){
            if(File::exists(public_path('upload/image/cultivation/internalResult/').$item->attachment)){
                File::delete(public_path('upload/image/cultivation/internalResult/').$item->attachment);
            }
            $item->delete();
            return redirect()->route('internalResultManage')->with('success','Item deleted successfully');
        }else{
            return redirect()->route('internalResultManage')->with('error','Item failed to delete');
        }
    }

    public function delInternalResultContent($id){
        $item = InternalResult::find($id);
        if(!empty($item)){
            if(File::exists(public_path('upload/image/cultivation/internalResult/').$item->attachment)){
                File::delete(public_path('upload/image/cultivation/internalResult/').$item->attachment);
            }
            $item->attachment = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        }else{
            return back()->with('success','Item failed to delete');
        }
    }
    // Internal Results ends here

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
             $validated = $requ->validate([
                    'attachment' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:2048',
                     // max 2 MB
                ],[
                    'attachment.mimes'  => 'Allowed formats: pdf,jpeg,png,jpg,gif,webp,avif.',
                    'attachment.max'    => 'Each file must be less than 2MB.'
                ]);
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
        $examRoutine = ExamRoutine::where(function($q){
            $q->whereNull('status')->orWhere('status', '!=', 'result_routine');
        })->orderBy('id','DESC')->get();
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
             $validated = $requ->validate([
                    'attachment' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:2048',
                ],[
                    'attachment.mimes'  => 'Allowed formats: pdf,jpeg,png,jpg,gif,webp,avif.',
                    'attachment.max'    => 'Each file must be less than 2MB.'
                ]);
            $attachment = $requ->attachment;
            $newAttachment      = rand().date('Ymd').'.'.$attachment->getClientOriginalExtension();
            $attachment->move(public_path('upload/image/cultivation/examRoutine'),$newAttachment);
            $item->attachment   = $newAttachment;
        endif;

        if($item->save()):
            return back()->with('success','Item successfully saved');
        else:
            return back()->with('error','Item failed to save');
        endif;
    }

    public function editExamRoutine($id){
        $examRoutine = ExamRoutine::where(function($q){
            $q->whereNull('status')->orWhere('status', '!=', 'result_routine');
        })->orderBy('id','DESC')->get();
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
        $result = ExamRoutine::where(function($q){
            $q->whereNull('status')->orWhere('status', '!=', 'result_routine');
        })->get();
        return view('frontend.academic.examSchedule',['Datakey'=>$result]);
    }

    public function newSemister()
    {
        $result = SemisterPlan::get();
        return view('frontend.academic.semister',['Datakey'=>$result]);
    }
     //web front controller end
}
