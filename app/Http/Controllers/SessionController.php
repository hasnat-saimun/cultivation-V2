<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\sessionData;

class SessionController extends Controller
{
     
    public function createSession(){
        return view('result.new-session');
    }

    public function confirmSession(Request $requ){
        $chk = sessionData::where(['sessionName'=>$requ->sessionName]);
        if($chk->exists()):
            return back()->with('error','Alias already exist');
        else:
            $session = new sessionData();
            $aliasCreate = str_replace(' ','_',$requ->sessionName);
            $alias = strtolower($aliasCreate);

            $session->sessionName   = $requ->sessionName;
            $session->alias       = $alias;
            $session->save();
            return back()->with('success','Record successfully saved');
        endif;
    }

    public function allSession(){
        $itemData = sessionData::orderBy('id','DESC')->get();
        return view('result.sessionList',['itemData'=>$itemData]);
    }
    
    public function editSession($item){
        $itemData = sessionData::find($item);
        return view('result.edit-session',['item'=>$itemData]);
    }
    

    public function updateSession(Request $requ){
        $session = sessionData::find($requ->itemId);
        if(!empty($session) && $session->exists()):
            $aliasCreate = str_replace(' ','_',$requ->sessionName);
            $alias = strtolower($aliasCreate);

            $session->sessionName   = $requ->sessionName;
            $session->alias       = $alias;
            $session->save();
            return back()->with('success','Record successfully updated');
        else:
            return back()->with('error','No alias found for update');
        endif;
    }

    public function delSession($id){
        $itemData = sessionData::find($id);
        if(empty($itemData)):
            return back()->with('error','Sorry! Alias failed to delete');
        else:
            $itemData->delete();
            return back()->with('success','Success! Alias successfully delete');
        endif;
    }
}
