<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\sessionManage;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\feesManager;
use App\Models\Department;
class individualController extends Controller
{
    //add session
    public function createSession(){
        return view('result.new-session');
    }

    //save session 
    public function confirmSession(Request $requ){
        $chkData = sessionManage::where(['session'=>$requ->session])->get();

        if(!empty($chkData) && count($chkData)>0):
            return back()->with('error','Data entry failed');
        else:
            $savedata = new sessionManage();
            
            $savedata ->session = $requ->session;

            if($savedata->save()):
                return back()->with('success','Data saved successfully');
            else:
                return back()->with('error','An error ocoured! please try later');
            endif;
        endif;
        
    }

    
    public function allSession(){
        $itemData = sessionManage::orderBy('id','DESC')->get();
        return view('result.sessionList',['itemData'=>$itemData]);
    }

    //edit session
    public function editSession($item){
        $itemData = sessionManage::find($item);
        return view('result.edit-session',['item'=>$itemData]);
    }

    //update session 
    public function updateSession(Request $requ){
        $chkData = sessionManage::where(['session'=>$requ->session])->get();

        if(!empty($chkData) && count($chkData)>0):
            return back()->with('error','Data entry failed');
        else:
            $updateData =  sessionManage::find($requ->itemId);
            $updateData ->session = $requ->session;

            if($updateData->save()):
                return redirect(route('sessionForm'))->with("success",'update successfully');
            else:
                return back()->with("error",'Data update failed');
            endif;
        endif;
    
    }
    
    
    //delelte session
    public function delSession($id){
        $dltData = sessionManage::find($id);

        if($dltData->delete()):
            return back()->with('success','data Delete successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }
    //add class
    public function createClass(){
        return view('result.new-class');
    }

    //save class 
    public function confirmClass(Request $requ){
        $chkData = classManage::where(['className'=>$requ->className])->get();

        if(!empty($chkData) && count($chkData)>0):
            return back()->with('error','Data entry failed');
        else:
            $savedata = new classManage();
            
            $savedata ->className = $requ->className;

            if($savedata->save()):
                return back()->with('success','Data saved successfully');
            else:
                return back()->with('error','An error ocoured! please try later');
            endif;
        endif;
        
    }

    //class list
    public function allClasses(){
        $itemData = classManage::orderBy('id','DESC')->get();
        return view('result.classList',['itemData'=>$itemData]);
    }

    //edit class
    public function editClass($item){
        $itemData = classManage::find($item);
        return view('result.edit-class',['item'=>$itemData]);
    }

     //update class 
     public function updateClass(Request $requ){
        $chkData = classManage::where(['className'=>$requ->className])->get();

        if(!empty($chkData) && count($chkData)>0):
            return back()->with('error','Data entry failed');
        else:
            $updateData =  classManage::find($requ->itemId);
            
            $updateData ->className = $requ->className;

            if($updateData->save()):
                return redirect(route('classForm'))->with("success",'update successfully');
            else:
                return back()->with("error",'Data update failed');
            endif;
        endif;
        
    }
    
    //delelte class
    public function delClass($id){
        $dltData = classManage::find($id);

        if($dltData->delete()):
            return back()->with('success','data Delete successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }

     //add Department
    public function createDepartment(){
        return view('result.new-department');
    }


    //save section 
    public function confirmDepartment(Request $requ){
       $chkData = Department::where(['departmentName'=>$requ->departmentName])->get();

        if(!empty($chkData) && count($chkData)>0):
            return back()->with('error','Data entry failed');
        else:
            $savedata = new Department();
            
            $savedata -> departmentName = $requ->departmentName;

            if($savedata->save()):
                return back()->with('success','Data saved successfully');
            else:
                return back()->with('error','An error ocoured! please try later');
            endif;
        endif;
        
    }

    public function allDepartment(){
        $itemData = Department::orderBy('id','DESC')->get();
        return view('result.departmentList',['itemData'=>$itemData]);
    }

    //edit section
    public function editDepartment($item){
        $itemData = Department::find($item);
        return view('result.edit-department',['item'=>$itemData]);
    }

    //update section 
    public function updateDepartment(Request $requ){
        $chkData = Department::where(['departmentName'=>$requ->departmentName])->get();

        if($chkData->isEmpty() && $chkData->count()>0):
            return back()->with('error','Data entry failed');
        else:
            $updateData =  Department::find($requ->itemId);
            $updateData ->departmentName = $requ->departmentName;

            if($updateData->save()):
                return back()->with("success",'update successfully');
            else:
                return back()->with("error",'Data update failed');
            endif;
        endif;
    
    }

    //delelte section
    public function delDepartment($id){
        $dltData = Department::find($id);

        if($dltData->delete()):
            return back()->with('success','data Delete successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }

    //add section
    public function createSection(){
        return view('result.new-section');
    }


    //save section 
    public function confirmSection(Request $requ){
       $chkData = sectionManage::where(['section'=>$requ->section])->get();

        if(!empty($chkData) && count($chkData)>0):
            return back()->with('error','Data entry failed');
        else:
            $savedata = new sectionManage();
            
            $savedata -> section = $requ->section;

            if($savedata->save()):
                return back()->with('success','Data saved successfully');
            else:
                return back()->with('error','An error ocoured! please try later');
            endif;
        endif;
        
    }

    public function allSection(){
        $itemData = sectionManage::orderBy('id','DESC')->get();
        return view('result.sectionList',['itemData'=>$itemData]);
    }

    //edit section
    public function editSection($item){
        $itemData = sectionManage::find($item);
        return view('result.edit-section',['item'=>$itemData]);
    }

    //update section 
    public function updateSection(Request $requ){
        $chkData = sectionManage::where(['section'=>$requ->section])->get();

        if($chkData->isEmpty() && $chkData->count()>0):
            return back()->with('error','Data entry failed');
        else:
            $updateData =  sectionManage::find($requ->itemId);
            $updateData ->section = $requ->section;

            if($updateData->save()):
                return back()->with("success",'update successfully');
            else:
                return back()->with("error",'Data update failed');
            endif;
        endif;
    
    }

    //delelte section
    public function delSection($id){
        $dltData = sectionManage::find($id);

        if($dltData->delete()):
            return back()->with('success','data Delete successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }


     //add fees
    public function feesForm(){
        $feesLi = feesManager::all();
        return view ('account.feesForm',['feesList'=>$feesLi]); 
    }
    //save fees 
    public function saveFees(Request $requ){
        $chkData = feesManager::where(['feesName'=>$requ->feesName])->get();

        if(!empty($chkData) && count($chkData)>0):
            return back()->with('error','Data entry failed');
        else:
            $savedata = new feesManager();
            
            $savedata ->feesName = $requ->feesName;

            if($savedata->save()):
                return back()->with('success','Data saved successfully');
            else:
                return back()->with('error','An error ocoured! please try later');
            endif;
        endif;
        
    }

    //edit fees
    public function editFees($id){
        $feesData = feesManager::find($id);
        return view('account.editFees',['editData'=>$feesData]);
    }

    //update fees 
    public function updateFees(Request $requ){
        $chkData = feesManager::where(['feesName'=>$requ->feesName])->get();

        if(!empty($chkData) && count($chkData)>0):
            return back()->with('error','Data entry failed');
        else:
            $updateData =  feesManager::find($requ->feesId);
            $updateData ->feesName = $requ->feesName;

            if($updateData->save()):
                return redirect(route('feesForm'))->with("success",'update successfully');
            else:
                return back()->with("error",'Data update failed');
            endif;
        endif;
    
    }
    
    
    //delelte fee
    public function deleteFees($id){
        $dltData = feesManager::find($id);

        if($dltData->delete()):
            return back()->with('success','data Delete successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }

}
