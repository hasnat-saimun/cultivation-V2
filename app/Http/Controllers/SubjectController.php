<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;

class SubjectController extends Controller
{
    
    
    public function createSubject(){
        return view('result.new-subject');
    }

    public function confirmSubject(Request $requ){
        $chk = Subject::where(['subjectName'=>$requ->subjectName]);
        if($chk->exists()):
            return back()->with('error','Alias already exist');
        else:
            $subject = new Subject();
            $aliasCreate = str_replace(' ','_',$requ->subjectName);
            $alias = strtolower($aliasCreate);

            $subject->subjectName   = $requ->subjectName;
            $subject->subjectType   = $requ->subjectType;
            $subject->alias       = $alias;
            $subject->save();
            return back()->with('success','Record successfully saved');
        endif;
    }

    public function allSubject(){
        $itemData = Subject::orderBy('id','DESC')->get();
        return view('result.subjectList',['itemData'=>$itemData]);
    }
    
    public function editSubject($item){
        $itemData = Subject::find($item);
        return view('result.edit-subject',['item'=>$itemData]);
    }
    

    public function updateSubject(Request $requ){
        $subject = Subject::find($requ->itemId);
        if(!empty($subject) && $subject->exists()):
            $aliasCreate = str_replace(' ','_',$requ->subjectName);
            $alias = strtolower($aliasCreate);

            $subject->subjectName   = $requ->subjectName;
            $subject->subjectType   = $requ->subjectType;
            $subject->alias       = $alias;
            $subject->save();
            return back()->with('success','Record successfully updated');
        else:
            return back()->with('error','No alias found for update');
        endif;
    }

    public function delSubject($id){
        $itemData = Subject::find($id);
        if(empty($itemData)):
            return back()->with('error','Sorry! Alias failed to delete');
        else:
            $itemData->delete();
            return back()->with('success','Success! Alias successfully delete');
        endif;
    }
}
