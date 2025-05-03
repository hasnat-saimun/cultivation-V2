<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GradeList;

class GradeListController extends Controller
{
    public function createGrade(){
        return view('result.new-grade');
    }

    public function confirmGrade(Request $requ){
        $chk = GradeList::where(['gradeName'=>$requ->gradeName]);
        if($chk->exists()):
            return back()->with('error','Alias already exist');
        else:
            $grade = new GradeList();

            $grade->gradeName   = $requ->gradeName;
            $grade->gradePoint  = $requ->gradePoint;
            $grade->minMark     = $requ->minMark;
            $grade->maxMark     = $requ->maxMark;
            $grade->minGp       = $requ->minGp;
            $grade->maxGp       = $requ->maxGp;
            $grade->save();
            return back()->with('success','Record successfully saved');
        endif;
    }

    public function allGrade(){
        $itemData = GradeList::orderBy('id','ASC')->get();
        return view('result.gradeList',['itemData'=>$itemData]);
    }
    
    public function editGrade($item){
        $itemData = GradeList::find($item);
        return view('result.edit-grade',['item'=>$itemData]);
    }
    

    public function updateGrade(Request $requ){
        $grade = GradeList::find($requ->itemId);
        if(!empty($grade) && $grade->exists()):

            $grade->gradeName   = $requ->gradeName;
            $grade->gradePoint  = $requ->gradePoint;
            $grade->minMark     = $requ->minMark;
            $grade->maxMark     = $requ->maxMark;
            $grade->minGp       = $requ->minGp;
            $grade->maxGp       = $requ->maxGp;
            $grade->save();
            return back()->with('success','Record successfully updated');
        else:
            return back()->with('error','No alias found for update');
        endif;
    }

    public function delGrade($id){
        $itemData = GradeList::find($id);
        if(empty($itemData)):
            return back()->with('error','Sorry! Alias failed to delete');
        else:
            $itemData->delete();
            return back()->with('success','Success! Alias successfully delete');
        endif;
    }
}
