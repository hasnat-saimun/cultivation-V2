<?php

namespace App\Http\Controllers;
use App\Models\PhotoGallery;
use App\Models\VideoGallery;
use Illuminate\Http\Request;
use File;

class GalleryController extends Controller
{   
    //backend str

    //photo str
    public function newPhoto(){
        $placement = PhotoGallery::orderBy('id','DESC')->get();
        return view('academic.image',['placementList'=>$placement]);
    }
    public function savePhoto(Request $requ){
        if(empty($requ->itemId)):
            $item   = new PhotoGallery();
        else:
            $item   = PhotoGallery::find($requ->itemId);
        endif;

        $item->title          = $requ->title;
        if(!empty($requ->avatar)):
            $stdAvatar = $requ->file('avatar');
            $newAvatar = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
            $stdAvatar->move(public_path('upload/image/PhotoGallery/'),$newAvatar);

            $item->avatar = $newAvatar;
        endif;
        // $item->status        = $requ->status;

        if($item->save()):
            return back()->with('success','Item successfully saved');
        else:
            return back()->with('error','Item failed to save');
        endif;
    }

    public function editPhoto($id){
        $placement = PhotoGallery::orderBy('id','DESC')->get();
        return view('academic.image',['itemId'=>$id,'placementList'=>$placement]);
    }

    public function delPhotoContent($id){
        $item = PhotoGallery::find($id);
        // return public_path('upload/image/cultivation/syllabus/').$item->attachment;
        if(!empty($item)):
            if(File::exists(public_path('upload/image/PhotoGallery/').$item->avatar)):
                File::delete(public_path('upload/image/PhotoGallery/').$item->avatar);
            endif;
            $item->avatar = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delPhoto($id){
        $item = PhotoGallery::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/PhotoGallery/').$item->avatar)):
                File::delete(public_path('upload/image/PhotoGallery/').$item->avatar);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    //video part str
    public function newVideo(){
        $placement = VideoGallery::orderBy('id','DESC')->get();
        return view('academic.video',['placementList'=>$placement]);
    }
    public function saveVideo(Request $requ){
        if(empty($requ->itemId)):
            $item   = new VideoGallery();
        else:
            $item   = VideoGallery::find($requ->itemId);
        endif;

        $item->title          = $requ->title;
        if(!empty($requ->avatar)):
            $stdAvatar = $requ->file('avatar');
            $newAvatar = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
            $stdAvatar->move(public_path('upload/image/VideoGallery/'),$newAvatar);

            $item->avatar = $newAvatar;
        endif;
        // $item->status        = $requ->status;

        if($item->save()):
            return back()->with('success','Item successfully saved');
        else:
            return back()->with('error','Item failed to save');
        endif;
    }

    public function editVideo($id){
        $placement = VideoGallery::orderBy('id','DESC')->get();
        return view('academic.video',['itemId'=>$id,'placementList'=>$placement]);
    }

    public function delVideoContent($id){
        $item = VideoGallery::find($id);
        // return public_path('upload/image/cultivation/syllabus/').$item->attachment;
        if(!empty($item)):
            if(File::exists(public_path('upload/image/VideoGallery/').$item->avatar)):
                File::delete(public_path('upload/image/VideoGallery/').$item->avatar);
            endif;
            $item->avatar = NULL;
            $item->save();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    public function delVideo($id){
        $item = VideoGallery::find($id);
        if(!empty($item)):
            if(File::exists(public_path('upload/image/VideoGallery/').$item->avatar)):
                File::delete(public_path('upload/image/VideoGallery/').$item->avatar);
            endif;
            $item->delete();
            return back()->with('success','Item deleted successfully');
        else:
            return back()->with('success','Item failed to delete');
        endif;
    }

    //backend end


//fontend str
    public function videoPage(){
        
        $syllabus  =   VideoGallery::all();
        return view('frontend.gallery.video',['Datakey'=>$syllabus]);
    }

    public function imagePage(){
        
        $syllabus  =   PhotoGallery::all();
        return view('frontend.gallery.image',['Datakey'=>$syllabus]);
    }
    //fontend end
}
