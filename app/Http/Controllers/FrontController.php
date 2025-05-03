<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentManagement;
use App\Models\StaffManagement;
use App\Models\TeacherManagement;
use App\Models\CultivationAdmin;
use Hash;
use sessionData;
use Session;

class FrontController extends Controller
{
    
    
    public function adminLogin(){
        $cultivation = CultivationAdmin::orderBy('id','DESC')->limit(1);
        return view('cultivation.login',['cultivation'=>$cultivation]);
    }
    
    public function cultivationLogin(Request $requ){
        $cultivation = CultivationAdmin::where(['adminUser'=>$requ->cultivationUser])->first();
        if($cultivation):
            if(!Hash::check($requ->cultivationPass,$cultivation->loginPassword)):
                return back()->with('error','Sorry! Wrong password provided');
            else:
                session(['cultivationAdmin' => $cultivation->id]);
                $requ->session()->regenerate();
                $requ->session()->put('adminSession',$chk->id);
                return redirect(route('cultivationIndex'));
            endif;
        else:
            return back()->with('error','Sorry! User not exist');
        endif;
    }
    
    public function adminRegister(Request $requ){
        $cultivation = CultivationAdmin::where(['adminUser'=>$requ->cultivationUser])->first();
        if($cultivation):
            return back()->with('error','Sorry! User or email already exist');
        else:
            $authPass    = Hash::make($requ->cultivationPass);
            $cultivation = new CultivationAdmin();

            $cultivation->adminName     = $requ->adminName;
            $cultivation->adminMail     = $requ->adminEmail;
            $cultivation->adminMobile   = $requ->adminMobile;
            $cultivation->userType      = "Admin";
            $cultivation->adminUser     = $requ->cultivationUser;
            $cultivation->loginPassword = $authPass;
            
            if($cultivation->save()):
                return back()->with('success','Success! Admin profile created successfully');
            else:
                return back()->with('success','error! There was an error. Please try later');
            endif;
        endif;
    }
    
    public function adminLogout(){
        Session::flush();
        return redirect(route('adminLogin'))->with('success','Yes! Logout successfull');
    }


    public function homePage(){
        return view('frontend.index');
    }
    
}
