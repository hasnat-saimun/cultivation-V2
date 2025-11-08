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
        $type = (int) $requ->noticeType;

        $baseRules = [
            'noticeType' => 'required|in:1,2',
            'noticeHeadline' => 'required|string|max:255',
        ];
        $typeRules = $type === 2
            ? ['attachment' => 'required|file|mimes:jpg,jpeg,png,gif,pdf|max:5120']
            : ['noticeBody' => 'required|string'];

        $validated = $requ->validate($baseRules + $typeRules);

        $notice = new Notice();
        $notice->headline = $validated['noticeHeadline'];
        $notice->body = $type === 1 ? ($validated['noticeBody'] ?? null) : null;

        if ($type === 2 && $requ->hasFile('attachment')) {
            $file = $requ->file('attachment');
            $ext  = strtolower($file->getClientOriginalExtension());
            $name = uniqid('notice_').'_'.date('Ymd').'.'.$ext;
            $dest = public_path('upload/notice');
            if(!is_dir($dest)) {
                mkdir($dest,0755,true);
            }
            $file->move($dest,$name);
            $notice->attachment = 'upload/notice/'.$name; // relative path for web use
        } else {
            $notice->attachment = null;
        }

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
        if(empty($notice)){
            return back()->with('error','Sorry! No data found');
        }

        $type = (int) $requ->noticeType;
        $baseRules = [
            'noticeId' => 'required|integer',
            'noticeType' => 'required|in:1,2',
            'noticeHeadline' => 'required|string|max:255',
        ];
        $typeRules = $type === 2
            ? ['attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf|max:5120', 'removeAttachment' => 'nullable|in:1']
            : ['noticeBody' => 'required|string'];

        $validated = $requ->validate($baseRules + $typeRules);

        $notice->headline = $validated['noticeHeadline'];

        if($type === 1){
            // Text notice
            $notice->body = $validated['noticeBody'] ?? null;
            // Clear attachment if switching from type 2
            if(!empty($notice->attachment)){
                $old = public_path($notice->attachment);
                if(is_file($old)) @unlink($old);
            }
            $notice->attachment = null;
        } else {
            // Attachment notice
            $notice->body = null;

            // Remove existing attachment if requested
            if($requ->filled('removeAttachment') && !empty($notice->attachment)){
                $old = public_path($notice->attachment);
                if(is_file($old)) @unlink($old);
                $notice->attachment = null;
            }

            // Upload new file if provided
            if($requ->hasFile('attachment')){
                $file = $requ->file('attachment');
                $ext  = strtolower($file->getClientOriginalExtension());
                $name = uniqid('notice_').'_'.date('Ymd').'.'.$ext;
                $dest = public_path('upload/notice');
                if(!is_dir($dest)) mkdir($dest,0755,true);
                $file->move($dest,$name);
                // Remove old if present
                if(!empty($notice->attachment)){
                    $old = public_path($notice->attachment);
                    if(is_file($old)) @unlink($old);
                }
                $notice->attachment = 'upload/notice/'.$name;
            }
        }

        if($notice->save()){
            return redirect()->route('noticeList')->with('success','Congrats! Notice updated successfully');
        }
        return back()->with('error','Sorry! Failed to update.');
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
