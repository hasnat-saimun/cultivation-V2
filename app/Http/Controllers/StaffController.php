<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StaffManagement;
use File;

class StaffController extends Controller
{
    
    public function addStaff(){
        $chk = StaffManagement::orderBy('id','DESC')->first();
        return view('cultivation.add-staff',['chk'=>$chk]);
    }
    public function confirmStaff(Request $requ){
        $chk = StaffManagement::where(['email'=>$requ->staffMail])->orWhere(['staffId'=>$requ->staffId])->get();
        if(count($chk)>0):
            // return 0;
            return back()->with('error','Opps Sorry! Profile already created');
        else:
            // return 1;
            $staffProfile    = new StaffManagement();

            if(!empty($requ->file('avatar'))):
                $staffProfileAvatar = $requ->file('avatar');
                $newStaffAvatar   = rand().date('Ymd').'.'.$staffProfileAvatar->getClientOriginalExtension();
                $staffProfileAvatar->move(public_path('upload/image/staff'),$newStaffAvatar);
                $staffProfile->avatar        = $newStaffAvatar; 
            endif;

            $staffProfile->firstName     = $requ->firstName;
            $staffProfile->lastName      = $requ->lastName;
            $staffProfile->fathersName   = $requ->fathersName;
            $staffProfile->mothersName   = $requ->mothersName;
            $staffProfile->address       = $requ->address;
            $staffProfile->gender        = $requ->gender;
            $staffProfile->dob           = $requ->dob;
            $staffProfile->joinDate      = $requ->joinDate;
            $staffProfile->email         = $requ->staffMail;
            $staffProfile->mobile        = $requ->mobile;
            $staffProfile->blGroup       = $requ->blGroup;
            $staffProfile->designation   = $requ->designation;
            $staffProfile->religion      = $requ->religion;
            $staffProfile->staffId     = $requ->staffId;
            $staffProfile->save();

            return back()->with('success','Owo Success! Profile created successfully');
        endif;
    }

    public function staffList(){
        $profileData = StaffManagement::all();
        return view('cultivation.staffList',['profileData'=>$profileData]);
    }
    
    
    public function viewStaff($id){
        $singleData= StaffManagement::find($id);
        return view('cultivation.viewStaff',['singleData'=>$singleData]);
    }
    public function editStaff($id){
        $profileData = StaffManagement::find($id);
        return view('cultivation.edit-staff',['profileData'=>$profileData]);
    }

    public function updateStaff(Request $requ){
         $staffProfile = StaffManagement::find($requ->staffId);
        if(empty($staffProfile)):
            return back()->with('error','Opps Sorry! Profile not found for update');
        else:
            // return 1;
            $staffProfile->firstName     = $requ->firstName;
            $staffProfile->lastName      = $requ->lastName;
            $staffProfile->fathersName   = $requ->fathersName;
            $staffProfile->mothersName   = $requ->mothersName;
            $staffProfile->address       = $requ->address;
            $staffProfile->gender        = $requ->gender;
            $staffProfile->dob           = $requ->dob;
            $staffProfile->joinDate      = $requ->joinDate;
            $staffProfile->email         = $requ->staffMail;
            $staffProfile->mobile        = $requ->mobile;
            $staffProfile->blGroup       = $requ->blGroup;
            $staffProfile->designation   = $requ->designation;
            $staffProfile->religion      = $requ->religion;
            $staffProfile->staffId     = $requ->staffId;

            if(!empty($requ->file('avatar'))):
                $staffProfileAvatar = $requ->file('avatar');
                $newStaffAvatar   = rand().date('Ymd').'.'.$staffProfileAvatar->getClientOriginalExtension();
                $staffProfileAvatar->move(public_path('upload/image/staff'),$newStaffAvatar);
                $staffProfile->avatar        = $newStaffAvatar;
            endif;
            $staffProfile->save();

            return back()->with('success','Owo Success! Profile updated successfully');
        endif;
    }

    public function delStaff($id){
        $staffProfileData = StaffManagement::find($id);
        if(empty($staffProfileData)):
            return back()->with('error','Sorry! Profile failed to delete');
        else:
            $staffProfileData->delete();
            return back()->with('success','Success! Profile successfully delete');
        endif;
    }

    public function delStaffPhoto($id){
        $staffProfileData = StaffManagement::find($id);
        if(empty($staffProfileData)):
            // return public_path('uploads/image/staff/'.$staffProfileData->avatar);
            return back()->with('error','Sorry! Profile picture failed to delete');
        else:
            if (File::exists(public_path('upload/image/staff/'.$staffProfileData->avatar))) {
                File::delete(public_path('upload/image/staff/'.$staffProfileData->avatar));
            }
            // return public_path('upload/image/staff/'.$staffProfileData->avatar);
            $staffProfileData->avatar        = "";
            $staffProfileData->save();
            return back()->with('success','Success! Profile picture deleted successfully');
        endif;
    }

    public function updateStaffPhoto(Request $requ){
        $staffProfileData = StaffManagement::find($requ->staffId);
        if($staffProfileData):
            if(!empty($requ->avatar)):
                $staffAvatar = $requ->file('avatar');
                $newStaffAvatar = rand().date('Ymd').'.'.$staffAvatar->getClientOriginalExtension();
                $staffAvatar->move(public_path('upload/image/staff/'),$newStaffAvatar);

                $staffProfileData->avatar = $newStaffAvatar;
            endif;
                
            if($staffProfileData->save()):
                return back()->with("success",'data update success');
            else:
                return back()->with("error",'data update failed');
            endif;
        else:
            return back()->with('error','Data update failed');
        endif;
    }
}
