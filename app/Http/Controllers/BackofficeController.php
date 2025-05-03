<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\newAdmission;
use App\Models\StaffManagement;
use App\Models\TeacherManagement;
use App\Models\CultivationAdmin;
use Hash;
use sessionData;

class BackofficeController extends Controller
{
    //cultivation controller goes here
    public function index(){
        $stdData = newAdmission::all();
        return view('academic.index',['studentData'=>$stdData]);
    }

    public function accountPart(){
        return view('account.index');
    }
    
    public function resultPart(){
        return view('result.index');
    }
}
