<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notice;

class NoticeController extends Controller
{
    public function noticeList(){
        $notice = Notice::orderBy('id','DESC')->get();
        return view('cultivation.noticeList',['noticeBoard'=>$notice]);
    }

    public function prevNotice($id){
        $notice = Notice::find($id);
        if(!empty($notice)):
            return view('cultivation.prevNotice',['notice'=>$notice]);
        else:
            return back()->with('error','Sorry! No data found');
        endif;
    }

    public function newNotice(){
        return view('cultivation.notice-creation');
    }

    public function saveNotice(Request $requ){
        $notice = new Notice();
        $notice->headline   = $requ->noticeHeadline;
        $notice->body       = $requ->noticeBody;
        if($notice->save()):
            return back()->with('success','Yes! Notice created successfully');
        else:
            return back()->with('error','Sorry! No data found');
        endif;
    }

    public function editNotice($id){
        $notice = Notice::find($id);
        if(!empty($notice)):
            return view('cultivation.editNotice',['notice'=>$notice]);
        else:
            return back()->with('error','Sorry! No data found');
        endif;
    }

    public function updateNotice(Request $requ){
        $notice = Notice::find($requ->noticeId);
        if(!empty($notice)):
            $notice->headline   = $requ->noticeHeadline;
            $notice->body       = $requ->noticeBody;
            if($notice->save()):
                return back()->with('success','Congrats! Notice updated successfully');
            else:
                return back()->with('error','Sorry! No data found');
            endif;
        else:
            return back()->with('error','Sorry! No data found');
        endif;

        
    }
    public function delNotice($id){
        $delNotice = Notice::find($id);
        if($delNotice->delete()):
            return back()->with('success','Congrats! Data delete successfully');
        else:
            return back()->with('error','Sorry! Data failed to delete. Please try later');
        endif;
    }
}
