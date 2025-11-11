<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\CultivationAdmin;
use App\Models\newAdmission;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\sessionManage;

class AttendanceController extends Controller
{
    public function index()
    {
    $adminId = session('cultivationAdmin');
    $user = $adminId ? CultivationAdmin::find($adminId) : null;
        $isTeacher = $user && $user->isTeacher();
        $classes = $isTeacher
            ? classManage::whereIn('id', $user->access_class_array)->get()
            : classManage::orderBy('id','ASC')->get();
        $sections = sectionManage::orderBy('id','ASC')->get();
        $sessions = sessionManage::orderBy('id','ASC')->get();
        return view('attendance.index', compact('classes','sections','sessions','isTeacher'));
    }

    public function fetch(Request $requ)
    {
        $requ->validate([
            'date' => 'required|date',
            'classId' => 'required|integer',
            'sessionId' => 'nullable|integer',
            'sectionId' => 'nullable|integer',
        ]);
        $adminId = session('cultivationAdmin');
        $user = $adminId ? CultivationAdmin::find($adminId) : null;
        if($user && $user->isTeacher()){
            if(!in_array((int)$requ->classId, $user->access_class_array)){
                return back()->with('error','Unauthorized class');
            }
        }
        $query = newAdmission::query();
    // Strictly match by all provided filters (use actual column names on new_admissions)
    $query->where('className', (int)$requ->classId);
    if($requ->sessionId){ $query->where('sessName', (int)$requ->sessionId); }
    if($requ->sectionId){ $query->where('sectionName', (int)$requ->sectionId); }
        $students = $query->orderBy('id','ASC')->get();
        // Fetch existing attendance for the given date/class/section to pre-fill
        $attQ = Attendance::query()
            ->where('attendance_date', $requ->date)
            ->where('class_id', (int)$requ->classId);
        if($requ->sectionId){
            $attQ->where('section_id', (int)$requ->sectionId);
        } else {
            $attQ->whereNull('section_id');
        }
        $existing = $attQ->pluck('status','student_id');
        // Editing is always allowed now; keep variable for view compatibility
        $allowEdit = true;
        // Fetch meta details for smart header
        $classObj = classManage::find((int)$requ->classId);
        $sessionObj = $requ->sessionId ? sessionManage::find((int)$requ->sessionId) : null;
        $sectionObj = $requ->sectionId ? sectionManage::find((int)$requ->sectionId) : null;
        return view('attendance.mark', [
            'students' => $students,
            'date' => $requ->date,
            'classId' => (int)$requ->classId,
            'sectionId' => $requ->sectionId ? (int)$requ->sectionId : null,
            'sessionId' => $requ->sessionId ? (int)$requ->sessionId : null,
            'existing' => $existing,
            'allowEdit' => $allowEdit,
            'classObj' => $classObj,
            'sessionObj' => $sessionObj,
            'sectionObj' => $sectionObj,
        ]);
    }

    public function store(Request $requ)
    {
        $requ->validate([
            'date' => 'required|date',
            'classId' => 'required|integer',
            'studentId' => 'required|array',
            'status' => 'required|array',
        ]);
        $adminId = session('cultivationAdmin');
        $user = $adminId ? CultivationAdmin::find($adminId) : null;
        if(!$user){ return redirect()->route('adminLogin'); }
        if($user->isTeacher() && !in_array((int)$requ->classId, $user->access_class_array)){
            return back()->with('error','Unauthorized class');
        }

        $date = $requ->date;
        $classId = (int)$requ->classId;
        $sectionId = $requ->sectionId ? (int)$requ->sectionId : null;
        $sessionId = $requ->sessionId ? (int)$requ->sessionId : null;
        $ids = $requ->studentId;
        $statuses = $requ->status;
        // Overwrite existing on save using updateOrCreate (no date restriction)
        $created = 0; $updated = 0;
        foreach($ids as $i => $sid){
            $st = $statuses[$i] ?? 'Present';
            $model = Attendance::updateOrCreate(
                [
                    'attendance_date' => $date,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'student_id' => (int)$sid,
                ],
                [
                    'session_id' => $sessionId,
                    'teacher_id' => (int)$user->id,
                    'status' => $st,
                ]
            );
            if($model->wasRecentlyCreated){ $created++; } else { $updated++; }
        }
        $msg = "Attendance saved. Created: {$created}, Updated: {$updated}";
        return redirect()->route('attendanceIndex')->with('success',$msg);
    }

    // Attendance reporting view
    public function report(Request $requ)
    {
        $adminId = session('cultivationAdmin');
        $user = $adminId ? CultivationAdmin::find($adminId) : null;
        $isTeacher = $user && $user->isTeacher();
        $classes = $isTeacher
            ? classManage::whereIn('id', $user->access_class_array)->get()
            : classManage::orderBy('id','ASC')->get();
        $sections = sectionManage::orderBy('id','ASC')->get();
        $sessions = sessionManage::orderBy('id','ASC')->get();

        // Filters (optional)
        $filters = [
            'date' => $requ->query('date'),
            'classId' => $requ->query('classId'),
            'sessionId' => $requ->query('sessionId'),
            'sectionId' => $requ->query('sectionId'),
            'studentId' => $requ->query('studentId'),
            'studentName' => $requ->query('studentName'),
        ];
        $records = collect();
        if($filters['classId']){
            if($isTeacher && !in_array((int)$filters['classId'], $user->access_class_array)){
                return back()->with('error','Unauthorized class');
            }
            $q = Attendance::query()->with(['student','class','section','session']);
            $q->where('class_id', (int)$filters['classId']);
            if($filters['date']){ $q->where('attendance_date', $filters['date']); }
            if($filters['sessionId']){ $q->where('session_id', (int)$filters['sessionId']); }
            if($filters['sectionId']){ $q->where('section_id', (int)$filters['sectionId']); }
            if($filters['studentId']){ $q->where('student_id', (int)$filters['studentId']); }
            if($filters['studentName']){
                $name = $filters['studentName'];
                $q->whereHas('student', function($qq) use ($name){
                    $qq->where('fullName','like',"%$name%")
                       ->orWhere('sureName','like',"%$name%");
                });
            }
            $records = $q->orderBy('attendance_date','DESC')->limit(500)->get();
        }
        return view('attendance.report', compact('classes','sections','sessions','filters','records'));
    }

    // Export CSV
    public function exportCsv(Request $requ)
    {
        $requ->validate([
            'classId' => 'required|integer'
        ]);
        $adminId = session('cultivationAdmin');
        $user = $adminId ? CultivationAdmin::find($adminId) : null;
        if($user && $user->isTeacher() && !in_array((int)$requ->classId, $user->access_class_array)){
            return back()->with('error','Unauthorized class');
        }
    $q = Attendance::query()->with(['student','class','section','session']);
        $q->where('class_id', (int)$requ->classId);
        if($requ->date){ $q->where('attendance_date', $requ->date); }
        if($requ->sessionId){ $q->where('session_id', (int)$requ->sessionId); }
        if($requ->sectionId){ $q->where('section_id', (int)$requ->sectionId); }
        if($requ->studentId){ $q->where('student_id', (int)$requ->studentId); }
        if($requ->studentName){
            $name = $requ->studentName;
            $q->whereHas('student', function($qq) use ($name){
                $qq->where('fullName','like',"%$name%")
                   ->orWhere('sureName','like',"%$name%");
            });
        }
        $rows = $q->orderBy('attendance_date','DESC')->get();
        $filename = 'attendance_export_'.date('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];
        $callback = function() use ($rows){
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date','Class','Section','Session','Student ID','Student Name','Status','Teacher']);
            foreach($rows as $r){
                fputcsv($out, [
                    $r->attendance_date,
                    $r->class ? $r->class->className : $r->class_id,
                    $r->section ? $r->section->section : $r->section_id,
                    $r->session ? $r->session->session : $r->session_id,
                    $r->student_id,
                    $r->student ? trim(($r->student->fullName ?? '').' '.($r->student->sureName ?? '')) : '',
                    $r->status,
                    $r->teacher ? $r->teacher->adminName : $r->teacher_id,
                ]);
            }
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    // Print-friendly HTML (browser can save as PDF)
    public function print(Request $requ)
    {
        $requ->validate([
            'classId' => 'required|integer'
        ]);
        $adminId = session('cultivationAdmin');
        $user = $adminId ? CultivationAdmin::find($adminId) : null;
        if($user && $user->isTeacher() && !in_array((int)$requ->classId, $user->access_class_array)){
            return back()->with('error','Unauthorized class');
        }
    $q = Attendance::query()->with(['student','teacher','class','section','session']);
        $q->where('class_id', (int)$requ->classId);
        if($requ->date){ $q->where('attendance_date', $requ->date); }
        if($requ->sessionId){ $q->where('session_id', (int)$requ->sessionId); }
        if($requ->sectionId){ $q->where('section_id', (int)$requ->sectionId); }
        if($requ->studentId){ $q->where('student_id', (int)$requ->studentId); }
        if($requ->studentName){
            $name = $requ->studentName;
            $q->whereHas('student', function($qq) use ($name){
                $qq->where('fullName','like',"%$name%")
                   ->orWhere('sureName','like',"%$name%");
            });
        }
        $rows = $q->orderBy('attendance_date','DESC')->get();
        return view('attendance.print', [
            'rows' => $rows,
            'filters' => [
                'date' => $requ->date,
                'classId' => (int)$requ->classId,
                'sessionId' => $requ->sessionId,
                'sectionId' => $requ->sectionId,
            ]
        ]);
    }
}
