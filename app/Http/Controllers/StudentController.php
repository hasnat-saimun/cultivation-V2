<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentManagement;
use File;

class StudentController extends Controller
{
    
    public function admitStudent(){
        $chk = StudentManagement::orderBy('id','DESC')->first();
        return view('cultivation.admit-student',['chk'=>$chk]);
    }
    public function confirmAdmit(Request $requ){
        $chk = StudentManagement::where(['email'=>$requ->studentEmail])->orWhere(['admitId'=>$requ->admitId])->get();
        if(isset($chk) && count($chk)>0):
            return back()->with('error','Opps Sorry! Profile already created');
        else:
            $std    = new StudentManagement();
            if(!empty($requ->file('avatar'))):
                $stdAvatar = $requ->file('avatar');
                $newStdAvatar   = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
                $stdAvatar->move(public_path('upload/image/student'),$newStdAvatar);
                $std->avatar        = $newStdAvatar;
            endif;

            $std->firstName     = $requ->firstName;
            $std->lastName      = $requ->lastName;
            $std->fathersName   = $requ->fathersName;
            $std->mothersName   = $requ->mothersName;
            $std->address       = $requ->address;
            $std->gender        = $requ->gender;
            $std->dob           = $requ->dob;
            $std->roll          = $requ->classRoll;
            $std->email         = $requ->studentEmail;
            $std->mobile        = $requ->mobile;
            $std->blGroup       = $requ->blGroup;
            $std->class         = $requ->className;
            $std->session       = $requ->sessionName;
            $std->section       = $requ->sectionName;
            $std->religion      = $requ->religion;
            $std->admitId       = $requ->admitId;
            $std->save();

            return back()->with('success','Owo Success! Profile created successfully');
        endif;
    }

    public function studentList(){
        $stdData = StudentManagement::all();
        return view('cultivation.studentList',['studentData'=>$stdData]);
    }

    public function stdIdCard($id){
        $stdData = StudentManagement::find($id);
        return view('cultivation.stdIdCard',['std'=>$stdData]);
    }

    public function editStudent($id){
        $stdData = StudentManagement::find($id);
        return view('cultivation.edit-student',['stdData'=>$stdData]);
    }

    public function updateAdmit(Request $requ){
        $std = StudentManagement::find($requ->stdId);
        if(empty($std)):
            return back()->with('error','Opps Sorry! Profile not found for update');
        else:
            $std->firstName     = $requ->firstName;
            $std->lastName      = $requ->lastName;
            $std->fathersName   = $requ->fathersName;
            $std->mothersName   = $requ->mothersName;
            $std->address       = $requ->address;
            $std->gender        = $requ->gender;
            $std->dob           = $requ->dob;
            $std->roll          = $requ->classRoll;
            $std->email         = $requ->studentEmail;
            $std->mobile        = $requ->mobile;
            $std->blGroup       = $requ->blGroup;
            $std->class         = $requ->className;
            $std->session       = $requ->sessionName;
            $std->section       = $requ->sectionName;
            $std->religion      = $requ->religion;

            if(!empty($requ->file('avatar'))):
                $stdAvatar = $requ->file('avatar');
                $newStdAvatar   = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
                $stdAvatar->move(public_path('upload/image/student'),$newStdAvatar);
                $std->avatar        = $newStdAvatar;
            endif;
            $std->save();

            return back()->with('success','Owo Success! Profile updated successfully');
        endif;
    }

    public function delStudent($id){
        $stdData = StudentManagement::find($id);
        if(empty($stdData)):
            return back()->with('error','Sorry! Profile failed to delete');
        else:
            $stdData->delete();
            return back()->with('success','Success! Profile successfully delete');
        endif;
    }

    public function delStdAvatar($id){
        $stdData = StudentManagement::find($id);
        if(empty($stdData)):
            // return public_path('uploads/image/student/'.$stdData->avatar);
            return back()->with('error','Sorry! Profile picture failed to delete');
        else:
            if (File::exists(public_path('upload/image/student/'.$stdData->avatar))) {
                File::delete(public_path('upload/image/student/'.$stdData->avatar));
            }
            // return public_path('upload/image/student/'.$stdData->avatar);
            $stdData->avatar        = "";
            $stdData->save();
            return back()->with('success','Success! Profile picture deleted successfully');
        endif;
    }
}
