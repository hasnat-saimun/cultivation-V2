<?php

namespace App\Http\Controllers;
use App\Models\registerSchool;
use File;

use Illuminate\Http\Request;

class registerController extends Controller
{
    public function registerForm(){
        return view('admin.registerForm');
    }

    public function registerList(){
        $regData = registerSchool::all();
        return view('admin.registerList',['regData'=>$regData]);
    }

    public function saveRegForm(Request $requ){
        $server = new registerSchool();

        $server->insName             = $requ->insName;
        $server->insAddress          = $requ->insAddress;
        $server->officeMobile        = $requ->officeMobile;
        $server->webName             = $requ->webName;
        $server->officeMail          = $requ->officeMail;
        // $server->insLogo             = $requ->insLogo;

        if(!empty($requ->insLogo)):
            $insLogo        = $requ->insLogo;
            $newInsLogo     = rand().date('Ymd').'.'.$insLogo->getClientOriginalExtension();
            $insLogo->move(public_path('upload/image/registerLogo'),$newInsLogo);
            $server->insLogo           = $newInsLogo;
        endif;

        if($server->save()):
            return back()->with('success','Data saved successfully');
        else:
            return back()->with('error','Data failed to save');
        endif;
    }

    
    public function registerEdit($id){
        $regData = registerSchool::find($id);
        return view('admin.editRegForm',['regData'=>$regData]);
    }

    public function registerLogoDel($id){
        $regData = registerSchool::find($id);
        if(empty($regData)):
            // return public_path('uploads/image/teacher/'.$teacherProfileData->avatar);
            return back()->with('error','Sorry! Profile picture failed to delete');
        else:
            if (File::exists(public_path('upload/image/registerLogo/'.$regData->insLogo))) {
                File::delete(public_path('upload/image/registerLogo/'.$regData->insLogo));
            }
            // return public_path('upload/image/teacher/'.$teacherProfileData->avatar);
            $regData->insLogo        = "";
            $regData->save();
            return back()->with('success','Success! Profile picture deleted successfully');
        endif;
    }

    public function registerLogoUpdate(Request $requ){
        $regData = registerSchool::find($requ->regId);
        if($regData):
            if(!empty($requ->insLogo)):
                $regLogo = $requ->file('insLogo');
                $newRegLogo = rand().date('Ymd').'.'.$regLogo->getClientOriginalExtension();
                $regLogo->move(public_path('upload/image/registerLogo/'),$newRegLogo);

                $regData->insLogo = $newRegLogo;
            endif;
                
            if($regData->save()):
                return back()->with("success",'data update success');
            else:
                return back()->with("error",'data update failed');
            endif;
        else:
            return back()->with('error','Data update failed');
        endif;
    }


    public function registerUpdate(Request $requ){
        $server = registerSchool::find($requ->regId);
            if($server):

        $server->insName             = $requ->insName;
        $server->insAddress          = $requ->insAddress;
        $server->officeMobile        = $requ->officeMobile;
        $server->webName             = $requ->webName;
        $server->officeMail          = $requ->officeMail;

        if($server->save()):
            return back()->with('success','Data saved successfully');
        else:
            return back()->with('error','Data failed to save');
        endif;
    else:
        return back()->with('error','Data update failed');
    endif;
    }
    
    public function registerDel($id){
        $dltData = registerSchool::find($id);

        if($dltData->delete()):
            return back()->with('success','data entry successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }
}
