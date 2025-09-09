<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServerConfig;
use App\Models\CultivationAdmin;
use App\Models\Subject;
use App\Models\classManage as ClassModel;
use Illuminate\Support\Str;
use Hash;
use sessionData;
use File;
use Intervention\Image\Laravel\Facades\Image;

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
        $server->principalDesignation = $requ->principalDesignation;
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
        $server->eduMinName         = $requ->eduMinName;
        $server->boardChairmanName  = $requ->boardChairmanName;
        $server->mapEmbed           = $requ->mapEmbed;

        if(!empty($requ->insLogo)):
            $insLogo        = $requ->insLogo;
            $validated = $requ->validate([
                    'logo' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'logo.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'logo.max'    => 'Each file must be less than 5MB.'
                ]);
            $newInsLogo     = rand().date('Ymd').'.'.$insLogo->getClientOriginalExtension();
            $insLogo->move(public_path('upload/image/cultivation'),$newInsLogo);
            $server->logo           = $newInsLogo;
        endif;
        if(!empty($requ->principalSign)):
            $principalSign          = $requ->principalSign;
            $validated = $requ->validate([
                    'principalSign' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'principalSign.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'principalSign.max'    => 'Each file must be less than 5MB.'
                ]);
            $newPrincipalSign       = rand().date('Ymd').'.'.$principalSign->getClientOriginalExtension();
            $principalSign->move(public_path('upload/image/cultivation'),$newPrincipalSign);
            $server->principalSign  = $newPrincipalSign;
        endif;
        if(!empty($requ->adminPhoto)):
            // $adminPhoto             = $requ->adminPhoto;
            $validated = $requ->validate([
                    'adminPhoto' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'adminPhoto.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'adminPhoto.max'    => 'Each file must be less than 5MB.'
                ]);

            $file = $requ->file('adminPhoto');

            // Use guessed extension from MIME (safer than client original)
            $ext  = strtolower($file->extension()); // e.g. jpg|jpeg|png|webp|avif
            $name = Str::uuid().'.'.$ext;

            // Ensure destination directory exists
            $dir = public_path('upload/image/cultivation');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $path = $dir.DIRECTORY_SEPARATOR.$name;

            // Read & resize (keeps aspect, prevents upscaling)
            $img = Image::read($file)->cover(200, 300);

            // Encode with quality for lossy formats (PNG will ignore "quality")
            $binary = $img->encodeByExtension($ext, quality: 80);

            // Write file to disk
            file_put_contents($path, (string) $binary);
            $server->avatar         = $name;
        endif;
        if(!empty($requ->favicon)):
            $favicon                = $requ->favicon;
            $validated = $requ->validate([
                    'favicon' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'favicon.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'favicon.max'    => 'Each file must be less than 5MB.'
                ]);
            $newFavicon             = rand().date('Ymd').'.'.$favicon->getClientOriginalExtension();
            $favicon->move(public_path('upload/image/cultivation'),$newFavicon);
            $server->favicon        = $newFavicon;
        endif;

        
        if(!empty($requ->eduMinImg)):
            $eduMinImg                = $requ->eduMinImg;
            $validated = $requ->validate([
                    'eduMinImg' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'eduMinImg.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'eduMinImg.max'    => 'Each file must be less than 5MB.'
                ]);
            $newEduMinImg             = rand().date('Ymd').'.'.$eduMinImg->getClientOriginalExtension();
            $eduMinImg->move(public_path('upload/image/cultivation'),$newEduMinImg);
            $server->eduMinImg        = $newEduMinImg;
        endif;

        if(!empty($requ->boardChairmanImg)):
            $boardChairmanImg                = $requ->boardChairmanImg;
            $validated = $requ->validate([
                    'boardChairmanImg' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'boardChairmanImg.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'boardChairmanImg.max'    => 'Each file must be less than 5MB.'
                ]);
            $newBoardChairmanImg             = rand().date('Ymd').'.'.$boardChairmanImg->getClientOriginalExtension();
            $boardChairmanImg->move(public_path('upload/image/cultivation'),$newBoardChairmanImg);
            $server->boardChairmanImg        = $newBoardChairmanImg;
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

    
    public function delEduMinImg($id){
        $eduMinImg = ServerConfig::find($id);
        if(!empty($eduMinImg)):
            if(File::exists(public_path('upload/image/cultivation/').$eduMinImg->eduMinImg)):
                File::delete(public_path('upload/image/cultivation/').$eduMinImg->eduMinImg);
            endif;
            $eduMinImg->eduMinImg   = "";
            $eduMinImg->save();
            return back()->with('success','eduMinImg delete successful');
        else:
            return back()->with('success','eduMinImg failed to delete');
        endif;
    }

     public function delBoardChairmanImg($id){
        $boardChairmanImg = ServerConfig::find($id);
        if(!empty($boardChairmanImg)):
            if(File::exists(public_path('upload/image/cultivation/').$boardChairmanImg->boardChairmanImg)):
                File::delete(public_path('upload/image/cultivation/').$boardChairmanImg->boardChairmanImg);
            endif;
            $boardChairmanImg->boardChairmanImg   = "";
            $boardChairmanImg->save();
            return back()->with('success','boardChairmanImg delete successful');
        else:
            return back()->with('success','boardChairmanImg failed to delete');
        endif;
    }

    public function saveAvatar(Request $requ){
        $avatar = ServerConfig::find($requ->serverId);
        if(!empty($avatar)):
            if(File::exists(public_path('upload/image/cultivation/').$avatar->avatar)):
                File::delete(public_path('upload/image/cultivation/').$avatar->avatar);
            endif;
            $adminPhoto             = $requ->adminPhoto;
            $validated = $requ->validate([
                    'adminPhoto' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'adminPhoto.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'adminPhoto.max'    => 'Each file must be less than 5MB.'
                ]);

            $file = $requ->file('adminPhoto');

            // Use guessed extension from MIME (safer than client original)
            $ext  = strtolower($file->extension()); // e.g. jpg|jpeg|png|webp|avif
            $fileName = Str::uuid().'.'.$ext;

            // Ensure destination directory exists
            $dir = public_path('upload/image/cultivation');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $path = $dir.DIRECTORY_SEPARATOR.$fileName;

            // Read & resize (keeps aspect, prevents upscaling)
            $img = Image::read($file)->cover(400, 450);

            // Encode with quality for lossy formats (PNG will ignore "quality")
            $binary = $img->encodeByExtension($ext, quality: 80);

            // Write file to disk
            file_put_contents($path, (string) $binary);
            $avatar->avatar         = $fileName;
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
            $validated = $requ->validate([
                    'principalSign' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'principalSign.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'principalSign.max'    => 'Each file must be less than 5MB.'
                ]);
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
            $validated = $requ->validate([
                    'insLogo' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'insLogo.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'insLogo.max'    => 'Each file must be less than 5MB.'
                ]);
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
            $validated = $requ->validate([
                    'favicon' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'favicon.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'favicon.max'    => 'Each file must be less than 5MB.'
                ]);
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

    
    public function saveEduMinImg(Request $requ){
        $data = ServerConfig::find($requ->serverId);
        if(!empty($data)):
            if(File::exists(public_path('upload/image/cultivation/').$data->eduMinImg)):
                File::delete(public_path('upload/image/cultivation/').$data->eduMinImg);
            endif;
            $eduMinImg             = $requ->eduMinImg;
            $validated = $requ->validate([
                    'eduMinImg' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'eduMinImg.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'eduMinImg.max'    => 'Each file must be less than 5MB.'
                ]);
            $newEduMinImg          = rand().date('Ymd').'.'.$eduMinImg->getClientOriginalExtension();
            $eduMinImg->move(public_path('upload/image/cultivation'),$newEduMinImg);
            $data->eduMinImg         = $newEduMinImg;
            if($data->save()):
                return back()->with('success','eduMinImg saved successfully');
            else:
                return back()->with('error','eduMinImg failed to save');
            endif;
        else:
            return back()->with('success','eduMinImg not found');
        endif;
    } 
    
    public function saveBoardChairmanImg(Request $requ){
        $data = ServerConfig::find($requ->serverId);
        if(!empty($data)):
            if(File::exists(public_path('upload/image/cultivation/').$data->boardChairmanImg)):
                File::delete(public_path('upload/image/cultivation/').$data->boardChairmanImg);
            endif;
            $boardChairmanImg             = $requ->boardChairmanImg;
            $validated = $requ->validate([
                    'boardChairmanImg' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'boardChairmanImg.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'boardChairmanImg.max'    => 'Each file must be less than 5MB.'
                ]);
            $newBoardChairmanImg          = rand().date('Ymd').'.'.$boardChairmanImg->getClientOriginalExtension();
            $boardChairmanImg->move(public_path('upload/image/cultivation'),$newBoardChairmanImg);
            $data->boardChairmanImg         = $newBoardChairmanImg;
            if($data->save()):
                return back()->with('success','boardChairmanImg saved successfully');
            else:
                return back()->with('error','boardChairmanImg failed to save');
            endif;
        else:
            return back()->with('success','boardChairmanImg not found');
        endif;
    }

     public function userType(){
        $subjectList = Subject::orderBy('id','ASC')->get();
        $classList   = ClassModel::orderBy('id','ASC')->get();
        return view('userPanal.userRegister',compact('subjectList','classList'));
    }

     public function saveUser(Request $requ){

        $cultivation = New CultivationAdmin;
        
        $cultivation->adminName     = $requ->adminName;
        $cultivation->adminMail     = $requ->userMail;
        $cultivation->adminMobile   = $requ->userMobile;
        $cultivation->adminUser     = $requ->userName;
        $cultivation->userType     = $requ->userType;
        $cultivation->loginPassword = $authPass;
            
        if($cultivation->save()):
            return back()->with('success','Success! Admin profile created successfully');
        else:
            return back()->with('success','error! There was an error. Please try later');
        endif;
    }

     public function userRegList(){
        $currentUserId = auth()->id();
        $userList = CultivationAdmin::where('id', '!=', $currentUserId)
            ->orderBy('id','ASC')->get();
        return view('userPanal.userList',compact('userList'));
    }
}
