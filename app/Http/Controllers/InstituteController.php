<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstituteDetails;
use App\Models\PrincipalSpeech;
use App\Models\ExPrincipal;
use App\Models\ManagingComittee;
use App\Models\TeacherManagement;
use App\Models\StaffManagement;
use App\Models\ServerConfig;
use App\Models\HomeInfo;
use App\Models\HomeSlider;
use File;

class InstituteController extends Controller

{   
     public function sliderInfo(){
        $sliderData = HomeSlider::all();
        return view('academic.homeSlider',['data'=>$sliderData]);
    }

     public function sliderDetail(Request $requ){
        if(empty($requ->pageId)):
            $institute   = new HomeSlider();
        else:
            $institute = HomeSlider::find($requ->pageId);
        endif;

        $institute->headLine     = $requ->headLine;
        $institute->detail      = $requ->detail;
        
        if(!empty($requ->avatar)):
            $heroImg        = $requ->avatar;
            $newheroImg     = rand().date('Ymd').'.'.$heroImg->getClientOriginalExtension();
            $heroImg->move(public_path('/upload/image/webHomepage'),$newheroImg);
            $institute->avatar      = $newheroImg;
        endif;

        if($institute->save()):
            return back()->with('success','Congrats! Data saved successfully');
        else:
            return back()->with('error','Sorry! Data failed to save. Please try later');
        endif;
    }

    
    public function editSlider($id){
        $sliderData = HomeSlider::all();
        return view('academic.homeSlider',['itemId'=>$id,'data'=>$sliderData]);
    }

    
    public function delSliderImg($id){
        $item = HomeSlider::find($id);
        // return public_path('upload/image/cultivation/syllabus/').$item->attachment;
        if(!empty($item)):
            if(File::exists(public_path('upload/image/webHomepage/').$item->avatar)):
                File::delete(public_path('upload/image/webHomepage/').$item->avatar);
            endif;
            $item->avatar = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    
    public function delSlider($id){
        $item = HomeSlider::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/webHomepage/').$item->avatar)):
                File::delete(public_path('upload/image/webHomepage/').$item->avatar);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }


     public function homeInfo(){
        return view('academic.homePage');
    }

      public function homeDetails(Request $requ){
        if(!empty($requ->pageId)):
            $institute = HomeInfo::find($requ->pageId);
        else:
            $institute = new HomeInfo();
        endif;
        $institute->eduMinName              = $requ->eduMinName;
        $institute->eduMinDetail            = $requ->eduMinDetail;
        $institute->boardChairmanName       = $requ->boardChairmanName;
        $institute->boardChairmanDetail     = $requ->boardChairmanDetail;
        $institute->principalName           = $requ->principalName;
        $institute->principalDetail         = $requ->principalDetail;
        $institute->founded                 = $requ->founded;
        $institute->area                    = $requ->area;
        $institute->teacherTotal            = $requ->teacherTotal;
        $institute->studentTotal            = $requ->studentTotal;
        $institute->notice                  = $requ->notice;
        $institute->wcMsgHadeline           = $requ->wcMsgHadeline;
        $institute->wclMsgDescription       = $requ->wclMsgDescription;
        $institute->missionDescription      = $requ->missionDescription;
        $institute->writerName              = $requ->writerName;
        $institute->mainGoal                = $requ->mainGoal;
        
        
        if(!empty($requ->eduMinImg)):
            $eduMinImg        = $requ->eduMinImg;
            $neweduMinImg     = rand().date('Ymd').'.'.$eduMinImg->getClientOriginalExtension();
            $eduMinImg->move(public_path('upload/image/webHomepage'),$neweduMinImg);
            $institute->eduMinImg      = $neweduMinImg;
        endif;
        
        if(!empty($requ->boardChairmanImg)):
            $boardChairmanImg        = $requ->boardChairmanImg;
            $newboardChairmanImg     = rand().date('Ymd').'.'.$boardChairmanImg->getClientOriginalExtension();
            $boardChairmanImg->move(public_path('upload/image/webHomepage'),$newboardChairmanImg);
            $institute->boardChairmanImg      = $newboardChairmanImg;
        endif;
        
        if(!empty($requ->principalImg)):
            $principalImg        = $requ->principalImg;
            $newprincipalImg     = rand().date('Ymd').'.'.$principalImg->getClientOriginalExtension();
            $principalImg->move(public_path('upload/image/webHomepage'),$newprincipalImg);
            $institute->principalImg      = $newprincipalImg;
        endif;



        if($institute->save()):
            return back()->with('success','Congrats! Data saved successfully');
        else:
            return back()->with('error','Sorry! Data failed to save. Please try later');
        endif;
    }


    public function delEduMinImg($id){
        $item = HomeInfo::find($id);
        // return public_path('upload/image/cultivation/syllabus/').$item->attachment;
        if(!empty($item)):
            if(File::exists(public_path('upload/image/webHomepage').$item->eduMinImg)):
                File::delete(public_path('upload/image/webHomepage').$item->eduMinImg);
            endif;
            $item->eduMinImg = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delBoardChairmanImg($id){
        $item = HomeInfo::find($id);
        // return public_path('upload/image/cultivation/syllabus/').$item->attachment;
        if(!empty($item)):
            if(File::exists(public_path('upload/image/webHomepage').$item->boardChairmanImg)):
                File::delete(public_path('upload/image/webHomepage').$item->boardChairmanImg);
            endif;
            $item->boardChairmanImg = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delPrincipalImg($id){
        $item = HomeInfo::find($id);
        // return public_path('upload/image/cultivation/syllabus/').$item->attachment;
        if(!empty($item)):
            if(File::exists(public_path('upload/image/webHomepage').$item->principalImg)):
                File::delete(public_path('upload/image/webHomepage').$item->principalImg);
            endif;
            $item->principalImg = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

// fdfdefsdfg
    public function insInfo(){
        return view('academic.instituteInfo');
    }

    public function insDetails(Request $requ){
        if(!empty($requ->pageId)):
            $institute = InstituteDetails::find($requ->pageId);
        else:
            $institute = new InstituteDetails();
        endif;

        $institute->insHeadline     = $requ->insHeadline;
        $institute->insDetails      = $requ->insDetails;
        $institute->establishDate   = $requ->establishDate;
        $institute->landSize        = $requ->landSize;
        $institute->mission         = $requ->ourMission;
        $institute->vision          = $requ->ourVision;
        
        if(!empty($requ->heroImg)):
            $heroImg        = $requ->heroImg;
            $newheroImg     = rand().date('Ymd').'.'.$heroImg->getClientOriginalExtension();
            $heroImg->move(public_path('upload/image/cultivation'),$newheroImg);
            $institute->heroImg      = $newheroImg;
        endif;

        if($institute->save()):
            return back()->with('success','Congrats! Data saved successfully');
        else:
            return back()->with('error','Sorry! Data failed to save. Please try later');
        endif;
    }

    public function delHeroImg($id){
        $item = InstituteDetails::find($id);
        // return public_path('upload/image/cultivation/syllabus/').$item->attachment;
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/').$item->heroImg)):
                File::delete(public_path('upload/image/cultivation/').$item->heroImg);
            endif;
            $item->heroImg = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }


    public function principalSpeech(){
        return view('academic.principalSpeech');
    }

    public function savePrincipalSpeech(Request $requ){
        if(!empty($requ->speechId)):
            $speech = PrincipalSpeech::find($requ->speechId);
        else:
            $speech = new PrincipalSpeech();
        endif;

        $speech->importantSpeech        = $requ->importantSpeech;
        $speech->generalSpeech          = $requ->generalSpeech;
        if($speech->save()):
            return back()->with('success','Congrats! Data saved successfully');
        else:
            return back()->with('error','Sorry! Data failed to save. Please try later');
        endif;
    }

    //ex principal
    public function editExPrincipal($id){
        $placement = ExPrincipal::orderBy('id','DESC')->get();
        return view('academic.exPrincipal',['exId'=>$id,'placementList'=>$placement]);
    }

    public function exPrincipal(){
        $exp = ExPrincipal::orderBy('id','DESC')->get();
        return view('academic.exPrincipal',['expList'=>$exp]);
    }

    public function saveExPrincipal(Request $requ){
        if(empty($requ->explId)):
            $exPrincipal = new ExPrincipal();
        else:
            $exPrincipal = ExPrincipal::find($requ->explId);
        endif;

        $exPrincipal->fullName      = $requ->fullName;
        $exPrincipal->mobile        = $requ->mobileNumber;
        $exPrincipal->email         = $requ->emailAddress;
        $exPrincipal->startFrom     = $requ->joinDate;
        $exPrincipal->endTo         = $requ->exitDate;
        $exPrincipal->designation   = $requ->designation;
        if(!empty($requ->avatar)):
            $stdAvatar = $requ->file('avatar');
            $newAvatar = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
            $stdAvatar->move(public_path('upload/image/exPrincipal/'),$newAvatar);

            $exPrincipal->avatar = $newAvatar;
        endif;

        if($exPrincipal->save()):
            return back()->with('success','Congrats! Data saved successfully');
        else:
            return back()->with('error','Sorry! Data failed to save. Please try later');
        endif;
    }

    public function viewExPrincipal($id){
        $singleData= ExPrincipal::find($id);
        return view('academic.viewExPrincipal',['singleData'=>$singleData]);
    }

    public function delexPlcCon($id){
        $item = ExPrincipal::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/exPrincipal/').$item->avatar)):
                File::delete(public_path('upload/image/exPrincipal/').$item->avatar);
            endif;
            $item->avatar = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }
    public function delExPrincipal($id){
        $item = ExPrincipal::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/exPrincipal/').$item->avatar)):
                File::delete(public_path('upload/image/exPrincipal/').$item->avatar);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    //committee
    public function editManagingCommittee($id){
        return view('academic.managingCommittee',['commId'=>$id]);
    }

    public function managingCommittee(){
        return view('academic.managingCommittee');
    }

    public function viewManagingCommittee($id){
        $singleData= ManagingComittee::find($id);
        return view('academic.viewManagingCommittee',['singleData'=>$singleData]);
    }

    public function saveManagingCommittee(Request $requ){
        if(empty($requ->proId)):
            $committee = new ManagingComittee();
        else:
            $committee = ManagingComittee::find($requ->proId);
        endif;

        $committee->fullName        = $requ->fullName;
        $committee->mobile          = $requ->mobileNumber;
        $committee->email           = $requ->emailAddress;
        $committee->qualification   = $requ->qualification;
        $committee->jobDetails      = $requ->jobDetails;
        $committee->designation     = $requ->designation;
        $committee->address         = $requ->address;
        $committee->validYear       = $requ->validYear;
        $committee->status          = "Active";
        
        if(!empty($requ->avatar)):
            $avatar        = $requ->avatar;
            $newAvatar     = rand().date('Ymd').'.'.$avatar->getClientOriginalExtension();
            $avatar->move(public_path('upload/image/cultivation'),$newAvatar);
            $committee->avatar      = $newAvatar;
        endif;

        if($committee->save()):
            return back()->with('success','Congrats! Data saved successfully');
        else:
            return back()->with('error','Sorry! Data failed to save. Please try later');
        endif;
    }

    

    public function delImgContent($id){
        $item = ManagingComittee::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/').$item->avatar)):
                File::delete(public_path('upload/image/cultivation/').$item->avatar);
            endif;
            $item->avatar = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }
    public function delManagingCommittee($id){
        $item = ManagingComittee::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/cultivation/').$item->avatar)):
                File::delete(public_path('upload/image/cultivation/').$item->avatar);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    //web support
    public function supportPage(){
        return view('frontend.support');
    }

    //institute info
    public function institutePage(){
        $syllabus  =   InstituteDetails::all();
        return view('frontend.institute.instituteInfo',['Datakey'=>$syllabus]);
    }
    //principalSpeech
    public function principalSpeechPage(){
        $pSpeech  =   PrincipalSpeech::orderBy('id','DESC')->first();

        $principalData  = TeacherManagement::where(['designation'=>1])->orWhere(['designation'=>2])->first();
        $cultivation    = ServerConfig::orderBy('id','DESC')->first();
        // $cultivation->count();
        return view('frontend.institute.principalSpeech',['pSpeech'=>$pSpeech,'cultivation'=>$cultivation,'principal'=>$principalData]);
    }
    //X-principal
    public function exprincipalPage(){
        $syllabus  =   ExPrincipal::all();
        return view('frontend.institute.exprincipal',['Datakey'=>$syllabus]);
    }

    //teacher list page
    public function teacherPage(){
        $syllabus  =   TeacherManagement::all();
        return view('frontend.institute.teachers',['Datakey'=>$syllabus]);
    }

    //staff list page
    public function staffPage(){
        $syllabus  =   StaffManagement::all();
        return view('frontend.institute.staff',['Datakey'=>$syllabus]);
    }

    //comittee list page
    public function comitteePage(){
        $syllabus  =   ManagingComittee::all();
        return view('frontend.institute.comittee',['Datakey'=>$syllabus]);
    }
}
