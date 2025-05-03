<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\sectionManage;
use App\Models\classManage;
use App\Models\sessionManage;
use App\Models\tuitionFee;
use App\Models\newAdmission;
use App\Models\feesManager;



class tuitionController extends Controller
{
    // tuition form page
    public function tuitionFee(){
        $sectionDetails= sectionManage::all();
        $classDetails= classManage::all();
        $sessionDetails = sessionManage::all();
        return view('account.tuition.tuitionFeesFrom',['sectionData'=>$sectionDetails,'classData'=>$classDetails,'sessionData'=>$sessionDetails,]);
    }

    // get tution admission student on form page
    public function getStudentForTutionFee($stdId){
        $studentList = newAdmission::where(['stdId'=>$stdId])->first();
        $feesDetails = feesManager::all();
        // return count($studentList);
        return view('account.tuition.getTutionStudentList',['studentData'=>$studentList,'feesData'=>$feesDetails]);
    }

    public function saveTuitionfee(Request $requ){
        $saveData = new tuitionFee();

        $saveData->stdId      = $requ->stdId;
        $saveData->feesType   = $requ->feesType;
        $saveData->amount     = $requ->amount;


        if($saveData->save()):
            return back()->with('success','Data saved sucessfully');
        else:
            return back()->with('error','An error ocoured! please try later');
        endif;
    }
    
    // tution Fee List
    public function tuitionFeeList(){
        $tutionfeeData = tuitionFee::all();
        return view('account.tuition.tuitionFeesList',['tfd'=>$tutionfeeData]);
    }

    public function tuitionFeeView($id){
        $singleData = tuitionFee::find($id);
        return view('account.tuition.viewTutionFee',['singleView'=>$singleData]);
        // $stdData = newAdmission::where(['stdId'=>$singleData->stdId])->first();
        // return view('tuition.viewTutionFee',['singleView'=>$singleData,'stdData'=>$stdData]);
    }

    //edit tiutionfee
    public function editTuitionFee($id){
        $sectionData= sectionManage::all();
        $classData= classManage::all();
        $sessionData = sessionManage::all();
        $tuitionFeeData = tuitionFee::find($id); 
        if(!empty($tuitionFeeData) && count((array)$tuitionFeeData)):
            $studentDetails =  newAdmission::where(['stdId' => $tuitionFeeData->stdId])->first();
        else:
            $studentDetails = 0;
        endif;
        return view('account.tuition.editTuitionFee',['editData'=>$tuitionFeeData,'sectionDetails'=>$sectionData,'classDetails'=>$classData ,'sessionDetails'=>$sessionData,'stdData'=>$studentDetails]);
    }

    //update tiutionfee 
    public function updateTuitionFee(Request $requ){
            $updateData =  tuitionFee::find($requ->tuitionFeeId);
            $updateData->session         = $requ->session;
            $updateData->className       = $requ->className;
            $updateData->section         = $requ->section;
            $updateData->stdName         = $requ->stdName;
            $updateData->rollNumber      = $requ->rollNumber;
            $updateData->amount          = $requ->amount;

            if($updateData->save()):
                return redirect(route('tuitionFeeList'))->with("success",'update successfully');
            else:
                return back()->with("error",'Data update failed');
            endif;
    
    }

    //delelte 
    public function dltTuitionFee($id){
        $dltData = tuitionFee::find($id);

        if($dltData->delete()):
            return back()->with('success','data entry successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }

     //report
     
    public function tuitionReport($id){
        $singleData = tuitionFee::find($id);
        return view('account.tuition.report',['singleView'=>$singleData]);
    }

    public function feesReport(){
        return view('account.tuition.feesReport');
    }

    public function getFeesReport(Request $requ){
        $from   = date('Y-m-d', strtotime($requ->fromDate));
        $to     = date('Y-m-d', strtotime($requ->toDate));

        $query = tuitionFee::where(['stdId'=>$requ->stdId])->whereBetween('created_at', [$from." 00:00:00",$to." 23:59:59"])->get();
        $student = newAdmission::where(['stdId'=>$requ->stdId])->get()->first();
        if(!$query->isEmpty()):
            return view('account.tuition.generateStudentReport',['feesList'=>$query,'student'=>$student]);
        else:
            return back()->with('error','Sorry! No data found with your query');
        endif;
    }

}
