<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\newAdmission;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\Department;
use File;


class admissionController extends Controller
{
    public function admitStudent(){
        $classDetails = classManage::all();
        $sectionDetails= sectionManage::all();
        $chk = newAdmission::orderBy('id','DESC')->first();
        return view('cultivation.admit-student',['chk'=>$chk,'classDetails'=>$classDetails,'sectionDatails'=>$sectionDetails]);
    }
    // public function newAdmission(){
    //     $classDetails = classManage::all();
    //     $sectionDetails= sectionManage::all();
    //     return view('cultivation.admit-student',['classDetails'=>$classDetails,'sectionDatails'=>$sectionDetails]);
    // }

    public function studentList(){
        $stdData = newAdmission::all();
        return view('cultivation.studentList',['studentData'=>$stdData]);
    }

    public function studentPromotion(){
        return view('cultivation.promotStd');
    }

    public function confirmPromotData(Request $requ){
        
        $studentId = $requ->checkbox;
        $totalData = count($studentId);
        // return $studentId;
        $x = 0;
        while($x<$totalData){
            $update = newAdmission::where(['stdId'=>$requ->studentId[$x]])->first();

            $update->className         = $requ->promotId;
            $update->save();
            $x++;
        }
        // return $x;
        if($x>=$totalData):
            return redirect(route('studentPromotion'))->with('success','Student profile promoted successfull');
        else:
            return redirect(route('studentPromotion'))->with('error','Student profile promoted failed');
        endif;
    }

    public function getPromotionData(Request $requ){
        $studentList = newAdmission::where(['sessName'=>$requ->sessionId,'sectionName'=>$requ->groupId,'className'=>$requ->classId])->get();
        return view('cultivation.promotData',['studentList'=>$studentList,'groupId'=>$requ->groupId,'classId'=>$requ->classId,'sessionId'=>$requ->sessionId]);
    }
    
    
    public function confirmAdmit(Request $requ){
        $chk = newAdmission::where(['rollNumber'=>$requ->rollNumber,'className'=>$requ->className,'sessName'=>$requ->sessName,'sectionName'=>$requ->sectionName])->get();
        if(!empty($chk) && count($chk)>0):
            return back()->with('error','Data already exist');
        else:
            $data = new newAdmission();
            
            $data->fullName         = $requ->fullName;
            $data->sureName         = $requ->sureName;
            $data->father           = $requ->fatherName;
            $data->mother           = $requ->motherName;
            $data->gender           = $requ->gender;
            $data->dob              = $requ->dob;
            $data->blGroup          = $requ->blGroup;
            $data->religion         = $requ->religion;
            $data->address          = $requ->address;
            $data->mail             = $requ->mail;
            $data->phone            = $requ->phone;
            $data->sessName         = $requ->sessName;
            $data->className        = $requ->className;
            $data->departmentName   = $requ->departmentName;
            $data->sectionName      = $requ->sectionName;
            $data->rollNumber       = $requ->rollNumber;
            $data->gurdianName      = $requ->gurdian;
            $data->gurdianMobile    = $requ->gurdianPhone;
            $data->relationGurdian  = $requ->relationWithStd;
            $data->status           = "newProfile";
            $stdId                  = $requ->stdId;

            $data->stdId = $stdId;

            if(!empty($requ->avatar)):
                $stdAvatar = $requ->file('avatar');
                $newAvatar = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
                $stdAvatar->move(public_path('upload/image/student/'),$newAvatar);

                $data->avatar = $newAvatar;
            endif;


            if($data->save()):
                return back()->with('success','Data saved sucessfully');
            else:
                return back()->with('error','An error ocoured! please try later');
            endif;
        endif;
    }
    public function viewAdmission($id){
        $singleData= newAdmission::find($id);
        return view('cultivation.viewStudent',['singleData'=>$singleData]);
    }


    public function stdIdCard($id){
        $stdData = newAdmission::find($id);
        return view('cultivation.stdIdCard',['std'=>$stdData]);
    }

    public function editStudent($id){
        
        $classDetails = classManage::all();
        $sectionDetails= sectionManage::all();
        $departmentDetails= Department::all();
        $stdDataa= newAdmission::find($id);
        return view('cultivation.edit-student',['classDetails'=>$classDetails,'sectionDatails'=>$sectionDetails,'stdData'=>$stdDataa,'departmentDetails'=>$departmentDetails]);
    }

    //update
    public function updateAdmit(Request $requ){
            $data = newAdmission::find($requ->stdId);
            if($data->count()>0):

                $data->fullName         = $requ->fullName;
                $data->sureName         = $requ->sureName;
                $data->father           = $requ->fatherName;
                $data->mother           = $requ->motherName;
                $data->gender           = $requ->gender;
                $data->dob              = $requ->dob;
                $data->blGroup          = $requ->blGroup;
                $data->religion         = $requ->religion;
                $data->address          = $requ->address;
                $data->mail             = $requ->mail;
                $data->phone            = $requ->phone;
                $data->sessName         = $requ->sessName;
                $data->className        = $requ->className;
                $data->departmentName   = $requ->departmentName;
                $data->sectionName      = $requ->sectionName;
                $data->rollNumber       = $requ->rollNumber;
                $data->gurdianName      = $requ->gurdian;
                $data->gurdianMobile    = $requ->gurdianPhone;
                $data->relationGurdian  = $requ->relationWithStd;
                
                if($data->save()):
                    return back()->with("success",'data update success');
                else:
                    return back()->with("error",'data update failed');
                endif;
            else:
                return back()->with('error','Data update failed');
            endif;
     }

     public function delStudentPhoto($id){
        $teacherProfileData = newAdmission::find($id);
        if(empty($teacherProfileData)):
            // return public_path('uploads/image/teacher/'.$teacherProfileData->avatar);
            return back()->with('error','Sorry! Profile picture failed to delete');
        else:
            if (File::exists(public_path('upload/image/student/'.$teacherProfileData->avatar))) {
                File::delete(public_path('upload/image/student/'.$teacherProfileData->avatar));
            }
            // return public_path('upload/image/teacher/'.$teacherProfileData->avatar);
            $teacherProfileData->avatar        = "";
            $teacherProfileData->save();
            return back()->with('success','Success! Profile picture deleted successfully');
        endif;
    }

    public function stdPhotoUpdate(Request $requ){
        $data = newAdmission::find($requ->stdId);
        if($data->count()>0):
            if(!empty($requ->avatar)):
                $stdAvatar = $requ->file('avatar');
                $newAvatar = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
                $stdAvatar->move(public_path('upload/image/student/'),$newAvatar);

                $data->avatar = $newAvatar;
            endif;
                
            if($data->save()):
                return back()->with("success",'data update success');
            else:
                return back()->with("error",'data update failed');
            endif;
        else:
            return back()->with('error','Data update failed');
        endif;
    }
     
    //delelte 
    public function delStudent($id){
        $dltData = newAdmission::find($id);

        if($dltData->delete()):
            return back()->with('success','data entry successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }


}
