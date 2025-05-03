<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    
    public function createDepartment(){
        return view('result.new-department');
    }

    public function confirmDepartment(Request $requ){
        $chk = Department::where(['departmentName'=>$requ->departmentName]);
        if($chk->exists()):
            return back()->with('error','Alias already exist');
        else:
            $department = new Department();
            $aliasCreate = str_replace(' ','_',$requ->departmentName);
            $alias = strtolower($aliasCreate);

            $department->departmentName   = $requ->departmentName;
            $department->alias       = $alias;
            $department->save();
            return back()->with('success','Record successfully saved');
        endif;
    }

    public function allDepartment(){
        $itemData = Department::orderBy('id','DESC')->get();
        return view('result.departmentList',['itemData'=>$itemData]);
    }
    
    public function editDepartment($item){
        $itemData = Department::find($item);
        return view('result.edit-department',['item'=>$itemData]);
    }
    

    public function updateDepartment(Request $requ){
        $department = Department::find($requ->itemId);
        if(!empty($department) && $department->exists()):
            $aliasCreate = str_replace(' ','_',$requ->departmentName);
            $alias = strtolower($aliasCreate);

            $department->departmentName   = $requ->departmentName;
            $department->alias       = $alias;
            $department->save();
            return back()->with('success','Record successfully updated');
        else:
            return back()->with('error','No alias found for update');
        endif;
    }

    public function delDepartment($id){
        $itemData = Department::find($id);
        if(empty($itemData)):
            return back()->with('error','Sorry! Alias failed to delete');
        else:
            $itemData->delete();
            return back()->with('success','Success! Alias successfully delete');
        endif;
    }
}
