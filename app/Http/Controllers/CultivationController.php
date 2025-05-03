<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServerConfig;
use App\Models\CultivationAdmin;
use Hash;
use sessionData;
use File;

class CultivationController extends Controller
{
    public function cultivationIndex(){
        return view('cultivation.index');
    }

    public function serverConfig(){
        return view('cultivation.configuration');
    }

    public function adminProfile(){
        return view('cultivation.adminProfile');
    }
    
    public function saveAdminProfile(Request $requ){
        $cultivation = CultivationAdmin::find($requ->adminId);
        if(empty($cultivation)):
            return back()->with('error','Sorry! No data found');
        else:
            $cultivation->adminName     = $requ->adminName;
            $cultivation->adminMail     = $requ->adminEmail;
            $cultivation->adminMobile   = $requ->adminMobile;
            
            if($cultivation->save()):
                return back()->with('success','Success! Admin profile updated successfully');
            else:
                return back()->with('success','error! There was an error. Please try later');
            endif;
        endif;
    }
    
    public function changeAdminPassword(Request $requ){
        $cultivation = CultivationAdmin::find($requ->adminId);
        if(empty($cultivation)):
            return back()->with('error','Sorry! No data found');
        else:
            if(!Hash::check($requ->oldPassword,$cultivation->loginPassword)):
                return back()->with('error','Sorry! old password wrong provided');
            else:
                if($requ->newPassword !== $requ->confirmPassword):
                    return back()->with('error','Sorry! new password and confirm password does not match');
                else:
                    $authPass    = Hash::make($requ->newPassword);
                    $cultivation->loginPassword   = $authPass;
                    
                    if($cultivation->save()):
                        return back()->with('success','Success! Password change successfully');
                    else:
                        return back()->with('success','error! There was an error. Please try later');
                    endif;
                endif;
            endif;
        endif;
    }

    public function saveConfig(Request $requ){
        if(empty($requ->serverId)):
            $server = new ServerConfig();
        else:
            $server = ServerConfig::find($requ->serverId);
        endif;

        $server->institueName       = $requ->insName;
        $server->address            = $requ->insAddress;
        $server->principalName      = $requ->principalName;
        $server->principalMobile    = $requ->principalMobile;
        $server->principalMail      = $requ->principalMail;
        $server->officeMobile       = $requ->officeMobile;
        $server->officeEmail        = $requ->officeMail;
        $server->facebookPage       = $requ->facebookPage;
        $server->twitterLink        = $requ->twitterLink;
        $server->einNumber          = $requ->einNumber;
        $server->studentIdPrefix    = $requ->studentIdPrefix;
        $server->teacherIdPrefix    = $requ->teacherIdPrefix;
        $server->staffIdPrefix      = $requ->staffIdPrefix;
        $server->youtubeChanel      = $requ->youtubeChanel;
        $server->establishDate      = $requ->establishDate;

        if(!empty($requ->insLogo)):
            $insLogo        = $requ->insLogo;
            $newInsLogo     = rand().date('Ymd').'.'.$insLogo->getClientOriginalExtension();
            $insLogo->move(public_path('upload/image/cultivation'),$newInsLogo);
            $server->logo           = $newInsLogo;
        endif;
        if(!empty($requ->principalSign)):
            $principalSign          = $requ->principalSign;
            $newPrincipalSign       = rand().date('Ymd').'.'.$principalSign->getClientOriginalExtension();
            $principalSign->move(public_path('upload/image/cultivation'),$newPrincipalSign);
            $server->principalSign  = $newPrincipalSign;
        endif;
        if(!empty($requ->adminPhoto)):
            $adminPhoto             = $requ->adminPhoto;
            $newAdminPhoto          = rand().date('Ymd').'.'.$adminPhoto->getClientOriginalExtension();
            $adminPhoto->move(public_path('upload/image/cultivation'),$newAdminPhoto);
            $server->avatar         = $newAdminPhoto;
        endif;
        if(!empty($requ->favicon)):
            $favicon                = $requ->favicon;
            $newFavicon             = rand().date('Ymd').'.'.$favicon->getClientOriginalExtension();
            $favicon->move(public_path('upload/image/cultivation'),$newFavicon);
            $server->favicon        = $newFavicon;
        endif;

        if($server->save()):
            return back()->with('success','Data saved successfully');
        else:
            return back()->with('error','Data failed to save');
        endif;
    }

    public function delAvatar($id){
        $avatar = ServerConfig::find($id);
        if(!empty($avatar)):
            if(File::exists(public_path('upload/image/cultivation/').$avatar->avatar)):
                File::delete(public_path('upload/image/cultivation/').$avatar->avatar);
            endif;
            $avatar->avatar   = "";
            $avatar->save();
            return back()->with('success','Avatar delete successful');
        else:
            return back()->with('success','Avatar failed to delete');
        endif;
    }

    public function delSign($id){
        $sign = ServerConfig::find($id);
        if(!empty($sign)):
            if(File::exists(public_path('upload/image/cultivation/').$sign->principalSign)):
                File::delete(public_path('upload/image/cultivation/').$sign->principalSign);
            endif;
            $sign->principalSign   = "";
            $sign->save();
            return back()->with('success','Principal Sign delete successful');
        else:
            return back()->with('success','Principal Sign failed to delete');
        endif;
    }

    public function delLogo($id){
        $logo = ServerConfig::find($id);
        if(!empty($logo)):
            // return public_path('upload/image/cultivation/').$logo->logo;
            if(File::exists(public_path('upload/image/cultivation/').$logo->logo)):
                File::delete(public_path('upload/image/cultivation/').$logo->logo);
            endif;
            $logo->logo   = "";
            $logo->save();
            return back()->with('success','Logo delete successful');
        else:
            return back()->with('success','Logo failed to delete');
        endif;
    }

    public function delFavicon($id){
        $favicon = ServerConfig::find($id);
        if(!empty($favicon)):
            if(File::exists(public_path('upload/image/cultivation/').$favicon->favicon)):
                File::delete(public_path('upload/image/cultivation/').$favicon->favicon);
            endif;
            $favicon->favicon   = "";
            $favicon->save();
            return back()->with('success','Favicon delete successful');
        else:
            return back()->with('success','Favicon failed to delete');
        endif;
    }

    public function saveAvatar(Request $requ){
        $avatar = ServerConfig::find($requ->serverId);
        if(!empty($avatar)):
            if(File::exists(public_path('upload/image/cultivation/').$avatar->avatar)):
                File::delete(public_path('upload/image/cultivation/').$avatar->avatar);
            endif;
            $adminPhoto             = $requ->adminPhoto;
            $newAdminPhoto          = rand().date('Ymd').'.'.$adminPhoto->getClientOriginalExtension();
            $adminPhoto->move(public_path('upload/image/cultivation'),$newAdminPhoto);
            $avatar->avatar         = $newAdminPhoto;
            if($avatar->save()):
                return back()->with('success','Avatar saved successfully');
            else:
                return back()->with('error','Avatar failed to save');
            endif;
        else:
            return back()->with('success','Avatar not found');
        endif;
    }

    public function saveSign(Request $requ){
        $sign = ServerConfig::find($requ->serverId);
        if(!empty($sign)):
            if(File::exists(public_path('upload/image/cultivation/').$sign->principalSign)):
                File::delete(public_path('upload/image/cultivation/').$sign->principalSign);
            endif;
            $principalSign             = $requ->principalSign;
            $newSign          = rand().date('Ymd').'.'.$principalSign->getClientOriginalExtension();
            $principalSign->move(public_path('upload/image/cultivation'),$newSign);
            $sign->principalSign         = $newSign;
            if($sign->save()):
                return back()->with('success','Avatar saved successfully');
            else:
                return back()->with('error','Avatar failed to save');
            endif;
        else:
            return back()->with('success','Avatar not found');
        endif;
    }

    public function saveLogo(Request $requ){
        $logo = ServerConfig::find($requ->serverId);
        if(!empty($logo)):
            if(File::exists(public_path('upload/image/cultivation/').$logo->logo)):
                File::delete(public_path('upload/image/cultivation/').$logo->logo);
            endif;
            $insLogo             = $requ->insLogo;
            $newLogo          = rand().date('Ymd').'.'.$insLogo->getClientOriginalExtension();
            $insLogo->move(public_path('upload/image/cultivation'),$newLogo);
            $logo->logo         = $newLogo;
            if($logo->save()):
                return back()->with('success','Logo saved successfully');
            else:
                return back()->with('error','Logo failed to save');
            endif;
        else:
            return back()->with('success','Logo not found');
        endif;
    }

    public function saveFavicon(Request $requ){
        $data = ServerConfig::find($requ->serverId);
        if(!empty($data)):
            if(File::exists(public_path('upload/image/cultivation/').$data->favicon)):
                File::delete(public_path('upload/image/cultivation/').$data->favicon);
            endif;
            $favicon             = $requ->favicon;
            $newFavicon          = rand().date('Ymd').'.'.$favicon->getClientOriginalExtension();
            $favicon->move(public_path('upload/image/cultivation'),$newFavicon);
            $data->favicon         = $newFavicon;
            if($data->save()):
                return back()->with('success','Favicon saved successfully');
            else:
                return back()->with('error','Favicon failed to save');
            endif;
        else:
            return back()->with('success','Favicon not found');
        endif;
    }
}
