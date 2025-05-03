<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;

class ClassController extends Controller
{
    public function createClass(){
        return view('result.new-class');
    }

    public function confirmClass(Request $requ){
        $chk = Classes::where(['className'=>$requ->className]);
        if($chk->exists()):
            return back()->with('error','Alias already exist');
        else:
            $class = new Classes();
            $aliasCreate = str_replace(' ','_',$requ->className);
            $alias = strtolower($aliasCreate);

            $class->className   = $requ->className;
            $class->alias       = $alias;
            $class->save();
            return back()->with('success','Record successfully saved');
        endif;
    }

    public function allClasses(){
        $itemData = Classes::orderBy('id','DESC')->get();
        return view('result.classList',['itemData'=>$itemData]);
    }
    
    public function editClass($item){
        $itemData = Classes::find($item);
        return view('academic.edit-class',['item'=>$itemData]);
    }
    

    public function updateClass(Request $requ){
        $class = Classes::find($requ->itemId);
        if(!empty($class) && $class->exists()):
            $aliasCreate = str_replace(' ','_',$requ->className);
            $alias = strtolower($aliasCreate);

            $class->className   = $requ->className;
            $class->alias       = $alias;
            $class->save();
            return back()->with('success','Record successfully updated');
        else:
            return back()->with('error','No alias found for update');
        endif;
    }

    public function delClass($id){
        $itemData = Classes::find($id);
        if(empty($itemData)):
            return back()->with('error','Sorry! Alias failed to delete');
        else:
            $itemData->delete();
            return back()->with('success','Success! Alias successfully delete');
        endif;
    }
}