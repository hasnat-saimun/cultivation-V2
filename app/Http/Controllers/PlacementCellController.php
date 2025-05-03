<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlacementCell;
use App\Models\NeedyStudent;
use App\Models\needyStudentPanel;
use File;

class PlacementCellController extends Controller
{

    public function placementCell(){
        $placement = PlacementCell::orderBy('id','DESC')->get();
        return view('academic.placementCell',['placementList'=>$placement]);
    }

    public function savePlacementCell(Request $requ){
        if(empty($requ->itemId)):
            $item   = new PlacementCell();
        else:
            $item   = PlacementCell::find($requ->itemId);
        endif;

        $item->fullName            = $requ->fullName;
        $item->mobile              = $requ->mobile;
        $item->email               = $requ->email;
        $item->sessionYear         = $requ->sessionYear;
        $item->rollNumber          = $requ->rollNumber;
        $item->companyName         = $requ->companyName;
        $item->joinDate            = $requ->joinDate;
        $item->designation         = $requ->designation;
        $item->jobDetails          = $requ->jobDetails;
        if(!empty($requ->avatar)):
            $stdAvatar = $requ->file('avatar');
            $newAvatar = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
            $stdAvatar->move(public_path('upload/image/placementCell/'),$newAvatar);

            $item->avatar = $newAvatar;
        endif;
        // $item->status        = $requ->status;

        if($item->save()):
            return back()->with('success','Item successfully saved');
        else:
            return back()->with('error','Item failed to save');
        endif;
    }
    
    public function editPlc($id){
        $placement = PlacementCell::orderBy('id','DESC')->get();
        return view('academic.placementCell',['itemId'=>$id,'placementList'=>$placement]);
    }

    public function delPlcCon($id){
        $item = PlacementCell::find($id);
        // return public_path('upload/image/cultivation/syllabus/').$item->attachment;
        if(!empty($item)):
            if(File::exists(public_path('upload/image/placementCell/').$item->avatar)):
                File::delete(public_path('upload/image/placementCell/').$item->avatar);
            endif;
            $item->avatar = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delPlc($id){
        $item = PlacementCell::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/placementCell/').$item->avatar)):
                File::delete(public_path('upload/image/placementCell/').$item->avatar);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function needyStudentPanel(){
        $needy = needyStudentPanel::orderBy('id','DESC')->get();
        return view('academic.needyStudent',['needy'=>$needy]);
    }

    public function saveNeedyStdPanel(Request $requ){
        if(empty($requ->itemId)):
            $item   = new needyStudentPanel();
        else:
            $item   = needyStudentPanel::find($requ->itemId);
        endif;

        $item->fullName            = $requ->fullName;
        $item->mobile              = $requ->mobile;
        $item->email               = $requ->email;
        $item->sessionYear         = $requ->sessionYear;
        $item->rollNumber          = $requ->rollNumber;
        if(!empty($requ->avatar)):
            $stdAvatar = $requ->file('avatar');
            $newAvatar = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
            $stdAvatar->move(public_path('upload/image/neddyStudent/'),$newAvatar);

            $item->avatar = $newAvatar;
        endif;

        if(!empty($requ->attachment)):
            $stdAttachment = $requ->file('attachment');
            $newAttachment = rand().date('Ymd').'.'.$stdAttachment->getClientOriginalExtension();
            $stdAttachment->move(public_path('upload/image/neddyStudent/'),$newAttachment);

            $item->attachment = $newAttachment;
        endif;
        // $item->status        = $requ->status;

        if($item->save()):
            return back()->with('success','Item successfully saved');
        else:
            return back()->with('error','Item failed to save');
        endif;
    }
    
    public function editNeedyStdPanel($id){
        $needy = needyStudentPanel::orderBy('id','DESC')->get();
        return view('academic.needyStudent',['itemId'=>$id,'needy'=>$needy]);
    }

    public function delNeedyStdPanelCon($id){
        $item = needyStudentPanel::find($id);
        
        if(!empty($item)):
            if(File::exists(public_path('upload/image/neddyStudent/').$item->avatar)):
                File::delete(public_path('upload/image/neddyStudent/').$item->avatar);
            endif;
            $item->avatar = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delNeedyStdPaneldoc($id){
        $item = needyStudentPanel::find($id);
        

        if(!empty($item)):
            if(File::exists(public_path('upload/image/neddyStudent/').$item->attachment)):
                File::delete(public_path('upload/image/neddyStudent/').$item->attachment);
            endif;
            $item->attachment = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delNeedyStdPanel($id){
        $item = needyStudentPanel::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/neddyStudent/').$item->avatar)):
                File::delete(public_path('upload/image/neddyStudent/').$item->avatar);
            endif;
            if(File::exists(public_path('upload/image/neddyStudent/').$item->attachment)):
                File::delete(public_path('upload/image/neddyStudent/').$item->attachment);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }


    //fontend str
    public function placementCellView(){
        $syllabus  =   PlacementCell::all();
        return view('frontend.job.placementCell',['Datakey'=>$syllabus]);
    }

    public function jobNeedyStudentView(){
        $syllabus  =   needyStudentPanel::all();
        return view('frontend.job.jobNeedyStudent',['Datakey'=>$syllabus]);
    }
     //fontend end
}
