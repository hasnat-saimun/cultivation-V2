<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherManagement;
use File;

class TeacherController extends Controller
{
    
    
    public function addTeacher(){
        $chk = TeacherManagement::orderBy('id','DESC')->first();
        return view('cultivation.add-teacher',['chk'=>$chk]);
    }
    public function confirmTeacher(Request $requ){
        $chk = TeacherManagement::where(['email'=>$requ->email])->orWhere(['teacherId'=>$requ->teacherId])->get();
        if(!$chk->isEmpty()):
            return back()->with('error','Opps Sorry! Profile already created');
        else:
            $teacherProfile    = new TeacherManagement();

            if(!empty($requ->file('avatar'))):
                $teacherProfileAvatar = $requ->file('avatar');
            $validated = $requ->validate([
                    'avatar' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'avatar.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'avatar.max'    => 'Each file must be less than 5MB.'
                ]);
                $newTeacherAvatar   = rand().date('Ymd').'.'.$teacherProfileAvatar->getClientOriginalExtension();
                $teacherProfileAvatar->move(public_path('upload/image/teacher'),$newTeacherAvatar);
                $teacherProfile->avatar        = $newTeacherAvatar; 
            endif;

            $teacherProfile->teacherId = $requ->teacherId;
            $teacherProfile->firstName = $requ->firstName;
            $teacherProfile->lastName = $requ->lastName;
            $teacherProfile->fathersName = $requ->fathersName;
            $teacherProfile->mothersName = $requ->mothersName;
            $teacherProfile->gender = $requ->gender;
            $teacherProfile->dob = $requ->dob;
            $teacherProfile->designation = $requ->designation;
            $teacherProfile->blGroup = $requ->blGroup;
            $teacherProfile->religion = $requ->religion;
            $teacherProfile->email = $requ->email;
            $teacherProfile->joinDate = $requ->joinDate;
            $teacherProfile->mobile = $requ->mobile;
            $teacherProfile->address = $requ->address;
            $teacherProfile->mpoIndex = $requ->mpoIndex;
            $teacherProfile->pdsId = $requ->pdsId;
            $teacherProfile->rank = $requ->rank;

            $teacherProfile->save();

            return back()->with('success','Owo Success! Profile created successfully');
        endif;
    }

    public function teacherList(){
        $profileData = TeacherManagement::all();
        return view('cultivation.teacherList',['profileData'=>$profileData]);
    }
    
    public function viewTeacher($id){
        $singleData= TeacherManagement::find($id);
        return view('cultivation.viewTeacher',['singleData'=>$singleData]);
    }
    public function editTeacher($id){
        $profileData = TeacherManagement::find($id);
        return view('cultivation.edit-teacher',['profileData'=>$profileData]);
    }

    public function updateTeacher(Request $requ){
        $teacherProfile = TeacherManagement::find($requ->teacherId);
        if(empty($teacherProfile)):
            return back()->with('error','Opps Sorry! Profile not found for update');
        else:
            $teacherProfile->firstName = $requ->firstName;
            $teacherProfile->lastName = $requ->lastName;
            $teacherProfile->fathersName = $requ->fathersName;
            $teacherProfile->mothersName = $requ->mothersName;
            $teacherProfile->gender = $requ->gender;
            $teacherProfile->dob = $requ->dob;
            $teacherProfile->designation = $requ->designation;
            $teacherProfile->blGroup = $requ->blGroup;
            $teacherProfile->religion = $requ->religion;
            $teacherProfile->email = $requ->email;
            $teacherProfile->joinDate = $requ->joinDate;
            $teacherProfile->mobile = $requ->mobile;
            $teacherProfile->address = $requ->address;
            $teacherProfile->mpoIndex = $requ->mpoIndex;
            $teacherProfile->pdsId = $requ->pdsId;
            $teacherProfile->rank = $requ->rank;
            $teacherProfile->save();

            return back()->with('success','Owo Success! Profile updated successfully');
        endif;
    }

    public function delTeacher($id){
        $teacherProfileData = TeacherManagement::find($id);
        if(empty($teacherProfileData)):
            return back()->with('error','Sorry! Profile failed to delete');
        else:
            $teacherProfileData->delete();
            return back()->with('success','Success! Profile successfully delete');
        endif;
    }

    public function delTeacherPhoto($id){
        $teacherProfileData = TeacherManagement::find($id);
        if(empty($teacherProfileData)):
            // return public_path('uploads/image/teacher/'.$teacherProfileData->avatar);
            return back()->with('error','Sorry! Profile picture failed to delete');
        else:
            if (File::exists(public_path('upload/image/teacher/'.$teacherProfileData->avatar))) {
                File::delete(public_path('upload/image/teacher/'.$teacherProfileData->avatar));
            }
            // return public_path('upload/image/teacher/'.$teacherProfileData->avatar);
            $teacherProfileData->avatar        = "";
            $teacherProfileData->save();
            return back()->with('success','Success! Profile picture deleted successfully');
        endif;
    }

    public function updateTeacherPhoto(Request $requ){
        $teacherProfileData = TeacherManagement::find($requ->profileId);
        if($teacherProfileData):
            if(!empty($requ->avatar)):
                $teacherAvatar = $requ->file('avatar');
            $validated = $requ->validate([
                    'avatar' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'avatar.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'avatar.max'    => 'Each file must be less than 5MB.'
                ]);
                $newTeacherAvatar = rand().date('Ymd').'.'.$teacherAvatar->getClientOriginalExtension();
                $teacherAvatar->move(public_path('upload/image/teacher/'),$newTeacherAvatar);

                $teacherProfileData->avatar = $newTeacherAvatar;
            endif;
                
            if($teacherProfileData->save()):
                return back()->with("success",'data update success');
            else:
                return back()->with("error",'data update failed');
            endif;
        else:
            return back()->with('error','Data update failed');
        endif;
    }
}
