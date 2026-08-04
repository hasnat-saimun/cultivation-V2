<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\CultivationAdmin;
use App\Models\newAdmission;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Jobs\SendSmsJob;
use App\Models\SmsSetting;
use App\Models\TeacherClassSubject;
use App\Services\CultivationAdminResolver;
use App\Services\TeacherAuthorizationService;
use App\Services\AttendanceSaveService;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    private CultivationAdminResolver $adminResolver;
    private TeacherAuthorizationService $teacherAuth;

    public function __construct(CultivationAdminResolver $adminResolver, TeacherAuthorizationService $teacherAuth, private AttendanceSaveService $attendanceSaver)
    {
        $this->adminResolver = $adminResolver;
        $this->teacherAuth = $teacherAuth;
    }

    private function teacherClassIds(?CultivationAdmin $user): array
    {
        if (!$user || !$user->isTeacher()) {
            return [];
        }

        return $this->teacherAuth->assignedClassTeacherClassIds($user);
    }

    private function ensureTeacherHasClassAssignment(?CultivationAdmin $user)
    {
        if ($user && $user->isTeacher() && !$this->teacherAuth->isAssignedClassTeacher($user)) {
            return redirect()->route('cultivationIndex')
                ->with('error', 'No class teacher assignment found. Attendance access is disabled.');
        }
        return null;
    }

    private function canAccessTeacherAttendanceContext(?CultivationAdmin $user, int $classId, ?int $sectionId = null, bool $strictSection = false): bool
    {
        return $this->teacherAuth->canAccessClassTeacherContext($user, $classId, $sectionId, $strictSection);
    }

    public function index()
    {
    $user = $this->adminResolver->current();
        if ($guard = $this->ensureTeacherHasClassAssignment($user)) {
            return $guard;
        }
        $isTeacher = $user && $user->isTeacher();
        $teacherClassIds = $this->teacherClassIds($user);
        // If teacher has a primary class (class teacher), restrict attendance classes to that one.
        if ($isTeacher && !empty($user->primary_class_id)) {
            $classes = classManage::where('id', (int)$user->primary_class_id)->get();
        } else {
            $classes = $isTeacher
                ? classManage::whereIn('id', $teacherClassIds)->get()
                : classManage::orderBy('id','ASC')->get();
        }
        // Sections: if class-teacher has a primary section, restrict to that section; otherwise fall back to assigned sections
        $sections = sectionManage::orderBy('id','ASC')->get();
        if ($isTeacher) {
            if (!empty($user->primary_section_id)) {
                $sections = sectionManage::where('id', (int)$user->primary_section_id)->get();
            }
        }
        // (sections already configured above)
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
        $user = $this->adminResolver->current();
        if ($guard = $this->ensureTeacherHasClassAssignment($user)) {
            return $guard;
        }
        $isTeacher = $user && $user->isTeacher();
        if($isTeacher){
            $sectionId = $requ->sectionId ? (int)$requ->sectionId : null;
            if(!$this->canAccessTeacherAttendanceContext($user, (int)$requ->classId, $sectionId, false)){
                return back()->with('error','Unauthorized class');
            }
            if($requ->sectionId && !$this->canAccessTeacherAttendanceContext($user, (int)$requ->classId, $sectionId, true)){
                return back()->with('error','Unauthorized section');
            }
        }
        $query = newAdmission::query();
    // Strictly match by all provided filters (use actual column names on new_admissions)
    $query->where('className', (int)$requ->classId);
    if($requ->sessionId){ $query->where('sessName', (int)$requ->sessionId); }
    if($requ->sectionId){ $query->where('sectionName', (int)$requ->sectionId); }
        $students = $query->professionalOrder()->get();
        // If attendance table not yet migrated, avoid querying and proceed with empty existing map
        if(!Schema::hasTable('attendances')){
            $existing = collect();
        } else {
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
        }
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
        $user = $this->adminResolver->current();
        if(!$user){ return redirect()->route('adminLogin'); }
        if ($guard = $this->ensureTeacherHasClassAssignment($user)) {
            return $guard;
        }
        if($user->isTeacher()){
            $sectionId = $requ->sectionId ? (int)$requ->sectionId : null;
            if(!$this->canAccessTeacherAttendanceContext($user, (int)$requ->classId, $sectionId, false)){
                return back()->with('error','Unauthorized class');
            }
            if($requ->sectionId){
                if(!$this->canAccessTeacherAttendanceContext($user, (int)$requ->classId, $sectionId, true)){
                    return back()->with('error','Unauthorized section');
                }
            }
        }

        $date = $requ->date;
        $classId = (int)$requ->classId;
        $sectionId = $requ->sectionId ? (int)$requ->sectionId : null;
        $sessionId = $requ->sessionId ? (int)$requ->sessionId : null;
        $ids = $requ->studentId;
        $statuses = $requ->status;
        $population = newAdmission::query()->whereIn('id', array_map('intval', $ids))->get();
        $result = $this->attendanceSaver->save($date, $classId, $sectionId, $sessionId, $user, $ids, $statuses, $population);
        $created = $result['created']; $updated = $result['updated'];
        $msg = "Attendance saved. Created: {$created}, Updated: {$updated}";
        return redirect()->route('attendanceIndex')->with('success',$msg);
    }

    // Attendance reporting view
    public function report(Request $requ)
    {
        $user = $this->adminResolver->current();
        if ($guard = $this->ensureTeacherHasClassAssignment($user)) {
            return $guard;
        }
        $isTeacher = $user && $user->isTeacher();
        $teacherClassIds = $this->teacherClassIds($user);
        $classes = $isTeacher
            ? classManage::whereIn('id', $teacherClassIds)->get()
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
            if(!Schema::hasTable('attendances')){
                return view('attendance.report', compact('classes','sections','sessions','filters','records'))
                    ->with('error','Attendance table not migrated yet.');
            }
            if($isTeacher && !in_array((int)$filters['classId'], $teacherClassIds, true)){
                if(!$this->canAccessTeacherAttendanceContext($user, (int)$filters['classId'], $filters['sectionId'] ? (int)$filters['sectionId'] : null, false)){
                return back()->with('error','Unauthorized class');
                }
            }
            if($isTeacher && $filters['sectionId']){
                if(!$this->canAccessTeacherAttendanceContext($user, (int)$filters['classId'], (int)$filters['sectionId'], true)){
                    return back()->with('error','Unauthorized section');
                }
            }
            // Eager load all related models for name rendering
            $q = Attendance::query()->with(['student','class','section','session','teacher']);
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
            // Derive teacher name(s) for header
            $teacherNames = $records->map(function($r){ return $r->teacher ? $r->teacher->adminName : null; })
                                     ->filter()
                                     ->unique()
                                     ->values();
            if($teacherNames->count()){
                $filters['teacherName'] = $teacherNames->implode(', ');
            }
        }
        return view('attendance.report', compact('classes','sections','sessions','filters','records'));
    }

    // Export CSV
    public function exportCsv(Request $requ)
    {
        $requ->validate([
            'classId' => 'required|integer'
        ]);
        $user = $this->adminResolver->current();
        if ($guard = $this->ensureTeacherHasClassAssignment($user)) {
            return $guard;
        }
        if($user && $user->isTeacher()){
            $sectionId = $requ->sectionId ? (int)$requ->sectionId : null;
            if(!$this->canAccessTeacherAttendanceContext($user, (int)$requ->classId, $sectionId, false)){
                return back()->with('error','Unauthorized class');
            }
            if($requ->sectionId){
                if(!$this->canAccessTeacherAttendanceContext($user, (int)$requ->classId, $sectionId, true)){
                    return back()->with('error','Unauthorized section');
                }
            }
        }
        if(!Schema::hasTable('attendances')){
            return back()->with('error','Attendance table not migrated yet.');
        }
        // Eager load teacher for proper name in CSV
    $q = Attendance::query()->with(['student','class','section','session','teacher']);
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
        $user = $this->adminResolver->current();
        if ($guard = $this->ensureTeacherHasClassAssignment($user)) {
            return $guard;
        }
        if($user && $user->isTeacher()){
            $sectionId = $requ->sectionId ? (int)$requ->sectionId : null;
            if(!$this->canAccessTeacherAttendanceContext($user, (int)$requ->classId, $sectionId, false)){
                return back()->with('error','Unauthorized class');
            }
            if($requ->sectionId){
                if(!$this->canAccessTeacherAttendanceContext($user, (int)$requ->classId, $sectionId, true)){
                    return back()->with('error','Unauthorized section');
                }
            }
        }
        if(!Schema::hasTable('attendances')){
            return back()->with('error','Attendance table not migrated yet.');
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

        // Resolve human readable names for filters
        $classObj = classManage::find((int)$requ->classId);
        $sessionObj = $requ->sessionId ? sessionManage::find((int)$requ->sessionId) : null;
        $sectionObj = $requ->sectionId ? sectionManage::find((int)$requ->sectionId) : null;

        // Build teacher header value (could be multiple across rows)
        $teacherNames = $rows->map(function($r){ return $r->teacher ? $r->teacher->adminName : null; })
                             ->filter()
                             ->unique()
                             ->values();
        $teacherHeader = $teacherNames->count() ? $teacherNames->implode(', ') : null;

        $institute = method_exists($this,'getInstituteMeta') ? $this->getInstituteMeta() : [];
        return view('attendance.print', [
            'rows' => $rows,
            'filters' => [
                'date' => $requ->date,
                'classId' => (int)$requ->classId,
                'className' => $classObj ? $classObj->className : null,
                'sessionId' => $requ->sessionId,
                'sessionName' => $sessionObj ? $sessionObj->session : null,
                'sectionId' => $requ->sectionId,
                'sectionName' => $sectionObj ? $sectionObj->section : null,
                'teacherName' => $teacherHeader,
            ],
            'institute' => $institute,
        ]);
    }
    // Monthly compact sheet similar to reference (matrix of days vs students)
    public function monthly(Request $requ)
    {
        $month = (int)($requ->query('month', date('m'))); // 1-12
        $year  = (int)($requ->query('year', date('Y')));
        $classId = $requ->query('classId');
        $sessionId = $requ->query('sessionId');
        $sectionId = $requ->query('sectionId');

        $filters = compact('month','year','classId','sessionId','sectionId');

        $user = $this->adminResolver->current();
        if ($guard = $this->ensureTeacherHasClassAssignment($user)) {
            return $guard;
        }
        $isTeacher = $user && $user->isTeacher();
        $teacherClassIds = $this->teacherClassIds($user);

        $classes = $isTeacher
            ? classManage::whereIn('id', $teacherClassIds)->get()
            : classManage::orderBy('id','ASC')->get();
        $sections = sectionManage::orderBy('id','ASC')->get();
        $sessions = sessionManage::orderBy('id','ASC')->get();

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

        $matrix = [];
        $students = collect();
        $summary = [];
        $dayTotals = [
            'P' => array_fill(1,$daysInMonth,0),
            'A' => array_fill(1,$daysInMonth,0),
            'T' => array_fill(1,$daysInMonth,0),
            'E' => array_fill(1,$daysInMonth,0),
        ];
        $weekdays = [];
        for($d=1;$d<=$daysInMonth;$d++){
            $weekdays[$d] = date('D', strtotime(sprintf('%04d-%02d-%02d',$year,$month,$d)));
        }
        if($classId){
            if(!Schema::hasTable('attendances')){
                return view('attendance.monthly', compact('classes','sections','sessions','filters','daysInMonth','matrix','students'))
                    ->with('error','Attendance table not migrated yet.');
            }
            if($isTeacher && !$this->canAccessTeacherAttendanceContext($user, (int)$classId, $sectionId ? (int)$sectionId : null, false)){
                return back()->with('error','Unauthorized class');
            }
            if($isTeacher && $sectionId){
                if(!$this->canAccessTeacherAttendanceContext($user, (int)$classId, (int)$sectionId, true)){
                    return back()->with('error','Unauthorized section');
                }
            }
            $studQ = newAdmission::query()->where('className',(int)$classId);
            if($sessionId){ $studQ->where('sessName',(int)$sessionId); }
            if($sectionId){ $studQ->where('sectionName',(int)$sectionId); }
            $students = $studQ->professionalOrder()->get();

            $attQ = Attendance::query()
                ->whereBetween('attendance_date', [$startDate,$endDate])
                ->where('class_id',(int)$classId);
            if($sessionId){ $attQ->where('session_id',(int)$sessionId); }
            if($sectionId){ $attQ->where('section_id',(int)$sectionId); }
            $records = $attQ->get();

            $statusMap = [
                'Present' => 'P',
                'Absent' => 'A',
                'Late' => 'T',
                'Excused' => 'E',
            ];
            foreach($students as $st){
                $matrix[$st->id] = array_fill(1, $daysInMonth, '');
            }
            foreach($records as $r){
                $day = (int)date('j', strtotime($r->attendance_date));
                if(isset($matrix[$r->student_id][$day])){
                    $code = $statusMap[$r->status] ?? substr($r->status,0,1);
                    $matrix[$r->student_id][$day] = $code;
                    if(isset($dayTotals[$code][$day])){ $dayTotals[$code][$day]++; }
                }
            }
            foreach($students as $st){
                $cells = $matrix[$st->id];
                $totals = [
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'excused' => 0,
                ];
                foreach($cells as $c){
                    if($c === 'P'){ $totals['present']++; }
                    elseif($c === 'A'){ $totals['absent']++; }
                    elseif($c === 'T'){ $totals['late']++; }
                    elseif($c === 'E'){ $totals['excused']++; }
                }
                $summary[$st->id] = $totals;
            }
        }
        // Resolve header names for filter display
        $classObj = $classId ? classManage::find((int)$classId) : null;
        $sessionObj = $sessionId ? sessionManage::find((int)$sessionId) : null;
        $sectionObj = $sectionId ? sectionManage::find((int)$sectionId) : null;
        $filters['className'] = $classObj ? $classObj->className : null;
        $filters['sessionName'] = $sessionObj ? $sessionObj->session : null;
        $filters['sectionName'] = $sectionObj ? $sectionObj->section : null;

        $institute = method_exists($this,'getInstituteMeta') ? $this->getInstituteMeta() : [];
    return view('attendance.monthly', compact('classes','sections','sessions','filters','daysInMonth','matrix','students','summary','institute','weekdays','dayTotals'));
    }

    public function monthlyExport(Request $requ)
    {
        $user = $this->adminResolver->current();
        if ($guard = $this->ensureTeacherHasClassAssignment($user)) {
            return $guard;
        }

        $month = (int)($requ->query('month', date('m')));
        $year  = (int)($requ->query('year', date('Y')));
        $classId = $requ->query('classId');
        $sessionId = $requ->query('sessionId');
        $sectionId = $requ->query('sectionId');
        if(!$classId){ return back()->with('error','Class is required'); }
        if($user && $user->isTeacher() && !$this->canAccessTeacherAttendanceContext($user, (int)$classId, $sectionId ? (int)$sectionId : null, false)){
            return back()->with('error','Unauthorized class');
        }
        if(!Schema::hasTable('attendances')){ return back()->with('error','Attendance table not migrated yet.'); }
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

        $studQ = newAdmission::query()->where('className',(int)$classId);
        if($sessionId){ $studQ->where('sessName',(int)$sessionId); }
        if($sectionId){ $studQ->where('sectionName',(int)$sectionId); }
        $students = $studQ->professionalOrder()->get();

        $attQ = Attendance::query()
            ->whereBetween('attendance_date', [$startDate,$endDate])
            ->where('class_id',(int)$classId);
        if($sessionId){ $attQ->where('session_id',(int)$sessionId); }
        if($sectionId){ $attQ->where('section_id',(int)$sectionId); }
        $records = $attQ->get();

        $statusMap = [ 'Present' => 'P','Absent' => 'A','Late' => 'T','Excused' => 'E' ];
        $matrix = [];
        foreach($students as $st){ $matrix[$st->id] = array_fill(1, $daysInMonth, ''); }
        foreach($records as $r){
            $day = (int)date('j', strtotime($r->attendance_date));
            if(isset($matrix[$r->student_id][$day])){
                $matrix[$r->student_id][$day] = $statusMap[$r->status] ?? substr($r->status,0,1);
            }
        }

        $filename = sprintf('attendance_monthly_%04d_%02d_class_%s.csv', $year, $month, $classId);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];
        $callback = function() use ($students,$matrix,$daysInMonth){
            $out = fopen('php://output', 'w');
            $head = ['Roll','Student'];
            for($d=1;$d<=$daysInMonth;$d++){ $head[] = (string)$d; }
            array_push($head,'Present','Absent','Late','Excused');
            fputcsv($out, $head);
            foreach($students as $st){
                $row = [ $st->rollNumber, trim(($st->fullName ?? '').' '.($st->sureName ?? '')) ];
                $cells = $matrix[$st->id] ?? array_fill(1,$daysInMonth,'');
                $totals = ['P'=>0,'A'=>0,'T'=>0,'E'=>0];
                for($d=1;$d<=$daysInMonth;$d++){
                    $c = $cells[$d] ?? '';
                    $row[] = $c;
                    if(isset($totals[$c])){ $totals[$c]++; }
                }
                array_push($row, $totals['P'],$totals['A'],$totals['T'],$totals['E']);
                fputcsv($out, $row);
            }
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function monthlyPrint(Request $requ)
    {
        $user = $this->adminResolver->current();
        if ($guard = $this->ensureTeacherHasClassAssignment($user)) {
            return $guard;
        }

        $month = (int)($requ->query('month', date('m')));
        $year  = (int)($requ->query('year', date('Y')));
        $classId = $requ->query('classId');
        $sessionId = $requ->query('sessionId');
        $sectionId = $requ->query('sectionId');
        if(!$classId){ return back()->with('error','Class is required'); }
        if($user && $user->isTeacher() && !$this->canAccessTeacherAttendanceContext($user, (int)$classId, $sectionId ? (int)$sectionId : null, false)){
            return back()->with('error','Unauthorized class');
        }
        if(!Schema::hasTable('attendances')){ return back()->with('error','Attendance table not migrated yet.'); }
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
        $weekdays = [];
        for($d=1;$d<=$daysInMonth;$d++){ $weekdays[$d] = date('D', strtotime(sprintf('%04d-%02d-%02d',$year,$month,$d))); }
        $studQ = newAdmission::query()->where('className',(int)$classId);
        if($sessionId){ $studQ->where('sessName',(int)$sessionId); }
        if($sectionId){ $studQ->where('sectionName',(int)$sectionId); }
        $students = $studQ->professionalOrder()->get();
        $attQ = Attendance::query()
            ->whereBetween('attendance_date', [$startDate,$endDate])
            ->where('class_id',(int)$classId);
        if($sessionId){ $attQ->where('session_id',(int)$sessionId); }
        if($sectionId){ $attQ->where('section_id',(int)$sectionId); }
        $records = $attQ->get();
        $statusMap = [ 'Present' => 'P','Absent' => 'A','Late' => 'T','Excused' => 'E' ];
        $matrix = [];
        $dayTotals = [ 'P'=>array_fill(1,$daysInMonth,0),'A'=>array_fill(1,$daysInMonth,0),'T'=>array_fill(1,$daysInMonth,0),'E'=>array_fill(1,$daysInMonth,0) ];
        $summary = [];
        foreach($students as $st){ $matrix[$st->id] = array_fill(1,$daysInMonth,''); }
        foreach($records as $r){
            $day = (int)date('j', strtotime($r->attendance_date));
            if(isset($matrix[$r->student_id][$day])){
                $code = $statusMap[$r->status] ?? substr($r->status,0,1);
                $matrix[$r->student_id][$day] = $code;
                if(isset($dayTotals[$code][$day])){ $dayTotals[$code][$day]++; }
            }
        }
        foreach($students as $st){
            $cells = $matrix[$st->id];
            $totals = ['present'=>0,'absent'=>0,'late'=>0,'excused'=>0];
            foreach($cells as $c){
                if($c==='P'){ $totals['present']++; }
                elseif($c==='A'){ $totals['absent']++; }
                elseif($c==='T'){ $totals['late']++; }
                elseif($c==='E'){ $totals['excused']++; }
            }
            $summary[$st->id] = $totals;
        }
        $classObj = classManage::find((int)$classId);
        $sessionObj = $sessionId ? sessionManage::find((int)$sessionId) : null;
        $sectionObj = $sectionId ? sectionManage::find((int)$sectionId) : null;
        $filters = [
            'month'=>$month,
            'year'=>$year,
            'classId'=>$classId,
            'sessionId'=>$sessionId,
            'sectionId'=>$sectionId,
            'className'=>$classObj ? $classObj->className : null,
            'sessionName'=>$sessionObj ? $sessionObj->session : null,
            'sectionName'=>$sectionObj ? $sectionObj->section : null,
        ];
        $institute = method_exists($this,'getInstituteMeta') ? $this->getInstituteMeta() : [];
        return view('attendance.monthly-print', compact('students','matrix','dayTotals','summary','daysInMonth','weekdays','filters','institute'));
    }
}
