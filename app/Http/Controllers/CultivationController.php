<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServerConfig;
use App\Models\CultivationAdmin;
use App\Models\Subject;
use App\Models\classManage as ClassModel;
use App\Models\sectionManage as SectionModel;
use App\Models\Department;
use App\Models\Attendance;
use App\Models\newAdmission;
use App\Models\cashManage;
use App\Models\TeacherManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Hash;
use sessionData;
use File;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CultivationController extends Controller
{
    private function buildAdminFormData(?CultivationAdmin $user = null): array
    {
        $sectionList = SectionModel::orderBy('id', 'ASC')->get();
        $attendanceTakenMap = $this->attendanceTakenMap($user);

        return [
            'subjectList' => $this->availableAdminSubjects($user),
            'classList' => ClassModel::orderBy('id', 'ASC')->get(),
            'sectionList' => $sectionList,
            'groupList' => Department::orderBy('id', 'ASC')->get(),
            'attendanceClassList' => $this->availableAttendanceClasses($user, $sectionList, $attendanceTakenMap),
            'attendanceTakenMap' => $attendanceTakenMap,
        ];
    }

    private function availableAdminSubjects(?CultivationAdmin $user = null)
    {
        $adminId = $user?->id;

        return Subject::query()
            ->where(function ($query) use ($adminId) {
                if ($adminId) {
                    $query->whereDoesntHave('teachers', function ($teacherQuery) use ($adminId) {
                        $teacherQuery->where('cultivation_admins.id', '!=', $adminId);
                    })->orWhereHas('teachers', function ($teacherQuery) use ($adminId) {
                        $teacherQuery->where('cultivation_admins.id', $adminId);
                    });

                    return;
                }

                $query->whereDoesntHave('teachers');
            })
            ->orderBy('id', 'ASC')
            ->get();
    }

    private function attendanceTakenMap(?CultivationAdmin $user = null): array
    {
        $claims = CultivationAdmin::query()
            ->select('primary_class_id', 'primary_section_id')
            ->whereNotNull('primary_class_id')
            ->when($user?->id, function ($query, $adminId) {
                $query->where('id', '!=', $adminId);
            })
            ->get();

        $takenMap = [];
        foreach ($claims as $claim) {
            $classId = (int) $claim->primary_class_id;
            $sectionKey = $claim->primary_section_id === null ? '__none__' : (string) $claim->primary_section_id;
            $takenMap[$classId][] = $sectionKey;
        }

        foreach ($takenMap as $classId => $sectionKeys) {
            $takenMap[$classId] = array_values(array_unique($sectionKeys));
        }

        return $takenMap;
    }

    private function availableAttendanceClasses(?CultivationAdmin $user, $sectionList, array $attendanceTakenMap)
    {
        $availableSectionKeys = ['__none__'];
        foreach ($sectionList as $section) {
            $availableSectionKeys[] = (string) $section->id;
        }

        $currentClassId = $user && $user->primary_class_id ? (int) $user->primary_class_id : null;

        return ClassModel::query()
            ->orderBy('id', 'ASC')
            ->get()
            ->filter(function ($class) use ($availableSectionKeys, $attendanceTakenMap, $currentClassId) {
                $classId = (int) $class->id;
                if ($currentClassId === $classId) {
                    return true;
                }

                $takenForClass = $attendanceTakenMap[$classId] ?? [];

                return count(array_diff($availableSectionKeys, $takenForClass)) > 0;
            })
            ->values();
    }

    private function parseTeacherAssignmentPayload(Request $requ): array
    {
        $rawCls = $requ->input('className', []);
        $rawSec = $requ->input('section', []);
        $rawSub = $requ->input('subject', []);
        $rawGrp = $requ->input('optionalGroup', []);

        $sectionIds = SectionModel::pluck('id')->map(function ($id) {
            return (int) $id;
        })->toArray();

        $classIds = [];
        $subjectIds = [];
        $sectionIdPool = [];
        $assignmentRows = [];

        for ($i = 0; $i < count($rawCls); $i++) {
            $classValue = $rawCls[$i] ?? null;
            if (!is_numeric($classValue)) {
                continue;
            }

            $classId = (int) $classValue;
            $classIds[] = $classId;

            $subjectValue = $rawSub[$i] ?? null;
            $subjectId = is_numeric($subjectValue) ? (int) $subjectValue : null;
            if ($subjectId) {
                $subjectIds[] = $subjectId;
            }

            $sectionValue = $rawSec[$i] ?? null;
            $groupValue = $rawGrp[$i] ?? null;
            $groupId = is_numeric($groupValue) ? (int) $groupValue : null;

            if ($sectionValue === 'all') {
                foreach ($sectionIds as $sectionId) {
                    $sectionIdPool[] = $sectionId;
                    $assignmentRows[] = [
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'group_id' => $groupId,
                        'subject_id' => $subjectId,
                    ];
                }
                continue;
            }

            $normalizedSectionId = null;
            if ($sectionValue !== 'none' && $sectionValue !== null && $sectionValue !== '' && is_numeric($sectionValue)) {
                $normalizedSectionId = (int) $sectionValue;
                $sectionIdPool[] = $normalizedSectionId;
            }

            $assignmentRows[] = [
                'class_id' => $classId,
                'section_id' => $normalizedSectionId,
                'group_id' => $groupId,
                'subject_id' => $subjectId,
            ];
        }

        $dedupedRows = [];
        $seenKeys = [];
        foreach ($assignmentRows as $row) {
            $key = $row['class_id'].'-'.($row['section_id'] ?? 'n').'-'.($row['group_id'] ?? 'n').'-'.($row['subject_id'] ?? 'n');
            if (isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;
            $dedupedRows[] = $row;
        }

        return [
            'class_ids' => array_values(array_unique($classIds)),
            'subject_ids' => array_values(array_unique(array_filter($subjectIds))),
            'section_ids' => array_values(array_unique(array_filter($sectionIdPool))),
            'assignment_rows' => $dedupedRows,
        ];
    }

    private function ensureSubjectAssignmentsAvailable(array $subjectIds, ?int $ignoreAdminId = null): void
    {
        $subjectIds = array_values(array_unique(array_filter(array_map('intval', $subjectIds))));
        if (empty($subjectIds)) {
            return;
        }

        Subject::query()->whereIn('id', $subjectIds)->lockForUpdate()->get(['id']);

        $conflict = DB::table('teacher_subjects')
            ->join('subjects', 'subjects.id', '=', 'teacher_subjects.subject_id')
            ->whereIn('teacher_subjects.subject_id', $subjectIds)
            ->when($ignoreAdminId, function ($query, $adminId) {
                $query->where('teacher_subjects.teacher_id', '!=', $adminId);
            })
            ->select('teacher_subjects.subject_id', 'subjects.subjectName')
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'subject' => ['This subject is already assigned to another admin.'],
            ]);
        }
    }

    private function ensureAttendanceAssignmentAvailable(?int $classId, ?int $sectionId, ?int $ignoreAdminId = null): void
    {
        if (!$classId) {
            return;
        }

        ClassModel::query()->whereKey($classId)->lockForUpdate()->get(['id']);
        if ($sectionId) {
            SectionModel::query()->whereKey($sectionId)->lockForUpdate()->get(['id']);
        }

        $conflict = CultivationAdmin::query()
            ->where('primary_class_id', $classId)
            ->when($sectionId === null, function ($query) {
                $query->whereNull('primary_section_id');
            }, function ($query) use ($sectionId) {
                $query->where('primary_section_id', $sectionId);
            })
            ->when($ignoreAdminId, function ($query, $adminId) {
                $query->where('id', '!=', $adminId);
            })
            ->lockForUpdate()
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'primaryClass' => ['This attendance class is already assigned to another admin.'],
            ]);
        }
    }

    private function syncTeacherClassSubjectRows(CultivationAdmin $cultivation, array $assignmentRows): void
    {
        $existingRows = DB::table('teacher_class_subjects')
            ->where('teacher_id', $cultivation->id)
            ->get();

        $existingMap = [];
        foreach ($existingRows as $row) {
            $key = $row->class_id.'-'.($row->section_id ?? 'n').'-'.($row->group_id ?? 'n').'-'.($row->subject_id ?? 'n');
            $existingMap[$key] = $row;
        }

        $desiredMap = [];
        foreach ($assignmentRows as $row) {
            $key = $row['class_id'].'-'.($row['section_id'] ?? 'n').'-'.($row['group_id'] ?? 'n').'-'.($row['subject_id'] ?? 'n');
            $desiredMap[$key] = $row;
        }

        foreach ($existingMap as $key => $existingRow) {
            if (isset($desiredMap[$key])) {
                continue;
            }

            DB::table('teacher_class_subjects')
                ->where('teacher_id', $cultivation->id)
                ->where('class_id', $existingRow->class_id)
                ->where(function ($query) use ($existingRow) {
                    if ($existingRow->section_id === null) {
                        $query->whereNull('section_id');
                    } else {
                        $query->where('section_id', $existingRow->section_id);
                    }
                })
                ->where(function ($query) use ($existingRow) {
                    if ($existingRow->group_id === null) {
                        $query->whereNull('group_id');
                    } else {
                        $query->where('group_id', $existingRow->group_id);
                    }
                })
                ->where(function ($query) use ($existingRow) {
                    if ($existingRow->subject_id === null) {
                        $query->whereNull('subject_id');
                    } else {
                        $query->where('subject_id', $existingRow->subject_id);
                    }
                })
                ->delete();
        }

        $toInsert = [];
        foreach ($desiredMap as $key => $row) {
            if (isset($existingMap[$key])) {
                continue;
            }

            $toInsert[] = [
                'teacher_id' => $cultivation->id,
                'class_id' => $row['class_id'],
                'section_id' => $row['section_id'],
                'group_id' => $row['group_id'],
                'subject_id' => $row['subject_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($toInsert)) {
            DB::table('teacher_class_subjects')->insert($toInsert);
        }
    }

    public function cultivationIndex(){
        $adminId = session('cultivationAdmin');
        $user = $adminId ? CultivationAdmin::find($adminId) : null;
        $isTeacher = $user && $user->isTeacher();
        $today = date('Y-m-d');
        // Earnings timeframe (today|month|all) via query param ?earningsScope=
        $scope = request()->query('earningsScope','all');
        // Handle missing table gracefully on fresh setups
        if(!Schema::hasTable('attendances')){
            $summary = [
                'total' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
            ];
            $attendanceRate = 0;
            $metrics = [
                'students' => newAdmission::count(),
                'teachers' => TeacherManagement::count(),
                'parents'  => 0,
                'earnings' => 0,
                'earningsScope' => $scope,
            ];
            return view('cultivation.index', compact('summary','today','isTeacher','metrics','attendanceRate'))
                ->with('error','Attendance table not migrated yet.');
        }
        $q = Attendance::query()->where('attendance_date', $today);
        if($isTeacher){
            $classIds = $user->access_class_array ?? [];
            if(!empty($classIds)){
                $q->whereIn('class_id', $classIds);
            } else {
                // If teacher has no classes assigned, show zeroes
                $summary = [
                    'total' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'excused' => 0,
                ];
                return view('cultivation.index', compact('summary','today','isTeacher'));
            }
        }
        $summary = [
            'total' => (clone $q)->count(),
            'present' => (clone $q)->where('status','Present')->count(),
            'absent' => (clone $q)->where('status','Absent')->count(),
            'late' => (clone $q)->where('status','Late')->count(),
            'excused' => (clone $q)->where('status','Excused')->count(),
        ];
        // Dashboard headline metrics
        // Incoming vs outgoing markers for cash ledger classification
        $incomingMarkers = ['credit','income','in','cr','receive','received','payment_in','deposit','Credit','Income','In','CR','Receive','Received','Payment_In','Deposit'];
        // Attempt profit/loss for current month using 'date' column first (fallback to created_at)
        $firstMonthDay = date('Y-m-01');
        $lastMonthDay  = date('Y-m-t');
        // Use same logic for box and chart: always include both date and created_at for current month
        $incomeMonth = cashManage::query()
            ->where(function($qq) use($firstMonthDay,$lastMonthDay){
                $qq->whereBetween('date', [$firstMonthDay,$lastMonthDay])
                   ->orWhereBetween(DB::raw('DATE(created_at)'), [$firstMonthDay,$lastMonthDay]);
            })
            ->whereIn('transaction',$incomingMarkers)
            ->selectRaw('COALESCE(SUM(CAST(amount as DECIMAL(18,2))),0) as total')->value('total');
        $expenseMonth = cashManage::query()
            ->where(function($qq) use($firstMonthDay,$lastMonthDay){
                $qq->whereBetween('date', [$firstMonthDay,$lastMonthDay])
                   ->orWhereBetween(DB::raw('DATE(created_at)'), [$firstMonthDay,$lastMonthDay]);
            })
            ->whereNotIn('transaction',$incomingMarkers)
            ->selectRaw('COALESCE(SUM(CAST(amount as DECIMAL(18,2))),0) as total')->value('total');
        $monthlyProfitLoss = (float)$incomeMonth - (float)$expenseMonth;
        // Earnings (legacy box) still available but now mapped to selected scope; keep for backward compatibility
        $cashQ = cashManage::query()->whereIn('transaction', $incomingMarkers);
        if($scope === 'today'){
            $cashQ->whereDate('created_at', $today);
        } elseif($scope === 'month'){
            $cashQ->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'));
        }
        $cashIncoming = $cashQ->selectRaw('COALESCE(SUM(CAST(amount as DECIMAL(18,2))),0) as total')->value('total');
        // Parents count: prefer guardian phone if column exists, else guardian name, else fallback to student count
        $parentsCount = 0;
        if (Schema::hasColumn('new_admissions', 'gurdianPhone')) {
            $parentsCount = newAdmission::whereNotNull('gurdianPhone')
                ->where('gurdianPhone','!=','')
                ->distinct('gurdianPhone')
                ->count('gurdianPhone');
        } elseif (Schema::hasColumn('new_admissions', 'gurdian')) {
            $parentsCount = newAdmission::whereNotNull('gurdian')
                ->where('gurdian','!=','')
                ->distinct('gurdian')
                ->count('gurdian');
        } else {
            $parentsCount = newAdmission::count();
        }
        // Teacher Panel: Count all teachers (userType=ROLE_TEACHER) since no is_active/is_deleted columns exist
        $metrics = [
            'students' => newAdmission::count(),
            'teachers' => TeacherManagement::count(),
            'parents'  => $parentsCount,
            'earnings' => (float)$cashIncoming,
            'monthlyProfitLoss' => $monthlyProfitLoss,
            'monthlyProfitIncome' => (float)$incomeMonth,
            'monthlyProfitExpense' => (float)$expenseMonth,
            'earningsScope' => $scope,
        ];
        // Build monthly cash chart (labels, income, expense) with fallback to created_at if date column empty
        $daysInMonth = (int)date('t');
        $labels = [];
        $incomeSeries = array_fill(0,$daysInMonth,0.0);
        $expenseSeries = array_fill(0,$daysInMonth,0.0);
        $ledgerRows = cashManage::query()
            ->where(function($qq) use($firstMonthDay,$lastMonthDay){
                $qq->whereBetween('date', [$firstMonthDay,$lastMonthDay])
                   ->orWhereBetween(DB::raw('DATE(created_at)'), [$firstMonthDay,$lastMonthDay]);
            })
            ->select(['id','transaction','amount','date','created_at'])
            ->get();
        foreach($ledgerRows as $row){
            // Determine effective date (prefer explicit date column if non-empty)
            $effectiveDate = $row->date && trim($row->date) !== '' ? $row->date : ($row->created_at ? $row->created_at->format('Y-m-d') : null);
            if(!$effectiveDate) continue;
            if(substr($effectiveDate,0,7) !== date('Y-m')) continue; // ensure current month
            $day = (int)substr($effectiveDate,8,2); // 1-31
            $index = $day - 1;
            if($index < 0 || $index >= $daysInMonth) continue;
            $amt = (float)$row->amount;
            $isIncome = in_array(strtolower($row->transaction), $incomingMarkers, true);
            if($isIncome){
                $incomeSeries[$index] += $amt;
            } else {
                $expenseSeries[$index] += $amt;
            }
        }
        for($d=1;$d<=$daysInMonth;$d++){ $labels[] = (string)$d; }
        $metrics['cashChart'] = [ 'labels'=>$labels, 'income'=>$incomeSeries, 'expense'=>$expenseSeries ];
        $attendanceRate = $summary['total'] > 0 ? round(($summary['present'] / $summary['total']) * 100) : 0;
        return view('cultivation.index', compact('summary','today','isTeacher','metrics','attendanceRate'));
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

        if(empty($server)):
            $server = new ServerConfig();
        endif;

        $allowedSmsTypes = ['present_only','absent_only','both'];
        $smsTypeRaw = strtolower(trim((string)$requ->sms_type));
        $smsType = in_array($smsTypeRaw, $allowedSmsTypes, true) ? $smsTypeRaw : 'both';
        $allowedInstituteTypes = ['kindergarten', 'high_school', 'college', 'university'];

        $adminId = session('cultivationAdmin');
        $currentAdmin = $adminId ? CultivationAdmin::find($adminId) : null;
        $isSuperAdmin = $currentAdmin && ((int)($currentAdmin->userType ?? 0) > CultivationAdmin::ROLE_GENERAL);

        $currentInstituteType = strtolower(trim((string)($server->institute_type ?? '')));
        $requestedInstituteType = strtolower(trim((string)$requ->input('institute_type')));
        $nextInstituteType = $currentInstituteType;

        if($isSuperAdmin):
            if($requestedInstituteType !== ''):
                if(!in_array($requestedInstituteType, $allowedInstituteTypes, true)):
                    return back()->with('error', 'Invalid institute type selected');
                endif;
                $nextInstituteType = $requestedInstituteType;
            endif;
        else:
            if($requestedInstituteType !== '' && $requestedInstituteType !== $currentInstituteType):
                Log::warning('Blocked non-super-admin institute_type update attempt', [
                    'admin_id' => $adminId,
                    'requested_institute_type' => $requestedInstituteType,
                ]);
            endif;
        endif;

        if($nextInstituteType === ''):
            $nextInstituteType = 'high_school';
        endif;

        $server->instituteName      = $requ->insName;
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
        $server->sms_type           = $smsType;
        $server->sms_body_present   = $requ->sms_body_present;
        $server->sms_body_absent    = $requ->sms_body_absent;
        $server->institute_type     = $nextInstituteType;
        $server->active_theme       = $nextInstituteType;

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
        return view('userPanal.userRegister', $this->buildAdminFormData());
    }

     public function editUser($id){
        $user = CultivationAdmin::find($id);
        if(empty($user)){
            return back()->with('error','Sorry! No data found');
        }
        return view('userPanal.userRegister', array_merge($this->buildAdminFormData($user), [
            'user' => $user,
        ]));
    }

     public function saveUser(Request $requ){
        $validated = $requ->validate([
            'adminName' => 'required|string|max:255',
            'userName' => 'required|string|max:255',
            'userMobile' => 'required|string|max:255',
            'userMail' => 'required|string|email|max:255',
            'userType' => 'required|integer|in:1,2,3',
            'pass' => $requ->filled('userId') ? 'nullable|string|min:1' : 'required|string|min:1',
            'confirmPass' => $requ->filled('userId') ? 'nullable|string' : 'required|string',
            'primaryClass' => 'nullable|integer|exists:class_manages,id',
            'primarySection' => 'nullable|integer|exists:section_manages,id',
            'className' => 'array',
            'className.*' => 'nullable|integer|exists:class_manages,id',
            'section' => 'array',
            'section.*' => 'nullable',
            'optionalGroup' => 'array',
            'optionalGroup.*' => 'nullable|integer|exists:departments,id',
            'subject' => 'array',
            'subject.*' => 'nullable|integer|exists:subjects,id',
        ]);

        if(!$requ->filled('userId') && $requ->pass !== $requ->confirmPass) {
            throw ValidationException::withMessages([
                'confirmPass' => ['Password and Confirm Password do not match'],
            ]);
        }

        $existingUser = $requ->filled('userId') ? CultivationAdmin::find($requ->userId) : null;
        if($requ->filled('userId') && !$existingUser) {
            return back()->with('error', 'User not found for update');
        }

        $teacherPayload = (int) $requ->userType === CultivationAdmin::ROLE_TEACHER
            ? $this->parseTeacherAssignmentPayload($requ)
            : ['class_ids' => [], 'subject_ids' => [], 'section_ids' => [], 'assignment_rows' => []];

        DB::transaction(function () use ($requ, $existingUser, $teacherPayload) {
            $ignoreAdminId = $existingUser?->id;

            if (!$existingUser) {
                $duplicateUser = CultivationAdmin::query()
                    ->where(function ($query) use ($requ) {
                        $query->where('adminUser', $requ->userName)
                            ->orWhere('adminMail', $requ->userMail);
                    })
                    ->lockForUpdate()
                    ->first();

                if ($duplicateUser) {
                    throw ValidationException::withMessages([
                        'userName' => ['User already exists with this user name or email address.'],
                    ]);
                }
            }

            $primaryClassId = $requ->filled('primaryClass') ? (int) $requ->primaryClass : null;
            $primarySectionId = $requ->filled('primarySection') ? (int) $requ->primarySection : null;

            $currentSubjectIds = $ignoreAdminId
                ? DB::table('teacher_subjects')->where('teacher_id', $ignoreAdminId)->pluck('subject_id')->map(function ($id) {
                    return (int) $id;
                })->toArray()
                : [];
            $newSubjectClaims = array_values(array_diff($teacherPayload['subject_ids'], $currentSubjectIds));
            $this->ensureSubjectAssignmentsAvailable($newSubjectClaims, $ignoreAdminId);

            $isNewAttendanceClaim = !$existingUser
                || (int) ($existingUser->primary_class_id ?? 0) !== (int) ($primaryClassId ?? 0)
                || (int) ($existingUser->primary_section_id ?? 0) !== (int) ($primarySectionId ?? 0);
            if ($isNewAttendanceClaim) {
                $this->ensureAttendanceAssignmentAvailable($primaryClassId, $primarySectionId, $ignoreAdminId);
            }

            $cultivation = $existingUser
                ? CultivationAdmin::query()->lockForUpdate()->find($existingUser->id)
                : new CultivationAdmin();

            if ($requ->filled('pass')) {
                $cultivation->loginPassword = Hash::make($requ->pass);
            }

            if (!$existingUser) {
                $cultivation->adminUser = $requ->userName;
                $cultivation->adminMail = $requ->userMail;
            }

            $cultivation->adminName = $requ->adminName;
            $cultivation->adminMobile = $requ->userMobile;
            $cultivation->userType = $requ->userType;
            $cultivation->primary_class_id = $primaryClassId;
            $cultivation->primary_section_id = $primarySectionId;
            $cultivation->save();

            if ((int)$requ->userType === CultivationAdmin::ROLE_TEACHER) {
                $cultivation->classes()->sync($teacherPayload['class_ids']);
                $cultivation->subjects()->sync($teacherPayload['subject_ids']);
                $cultivation->sections()->sync($teacherPayload['section_ids']);
                $this->syncTeacherClassSubjectRows($cultivation, $teacherPayload['assignment_rows']);
            } else {
                $cultivation->classes()->sync([]);
                $cultivation->subjects()->sync([]);
                $cultivation->sections()->sync([]);
                DB::table('teacher_class_subjects')->where('teacher_id', $cultivation->id)->delete();
            }
        });

        $msg = $requ->filled('userId') ? 'Success! Admin profile updated successfully' : 'Success! Admin profile created successfully';
        return back()->with('success', $msg);
    }

    public function userRegList(){
        $currentUserId = session('cultivationAdmin');
        $userList = CultivationAdmin::with([
                'subjects:id,subjectName',
                'primaryClass:id,className',
                'primarySection:id,section',
            ])
            ->where('id', '!=', $currentUserId)
            ->orderBy('id','ASC')
            ->get();
        return view('userPanal.userList',compact('userList'));
    }
    public function deleteUser($id)
    {
        $user = CultivationAdmin::find($id);
        if (!$user) {
            return back()->with('error', 'User not found');
        }
        if ($user->delete()) {
            return back()->with('success', 'User deleted successfully');
        } else {
            return back()->with('error', 'Failed to delete user');
        }
    }

    /**
     * Return subjects allowed for the current teacher for a given class and section.
     * Accepts POST with `classId`, optional `sectionId`, optional `optionalGroupId` and returns JSON list of subjects.
     */
    public function teacherSubjects(\Illuminate\Http\Request $requ)
    {
        $adminId = session('cultivationAdmin');
        $user = $adminId ? CultivationAdmin::find($adminId) : null;

        $classId = (int)($requ->input('classId') ?? 0);
        $sectionId = $requ->filled('sectionId') ? (int)$requ->input('sectionId') : null;
        $optionalGroupId = $requ->filled('optionalGroupId') ? (int)$requ->input('optionalGroupId') : null;

        if(!$classId){
            return response()->json(['error'=>'classId required'], 400);
        }

        if($user && $user->isTeacher()){
            $q = \Illuminate\Support\Facades\DB::table('teacher_class_subjects')
                ->where('teacher_id', $user->id)
                ->where('class_id', $classId)
                ->where(function($qq) use ($sectionId){
                    if($sectionId === null) {
                        $qq->whereNull('section_id')->orWhereNotNull('section_id');
                    } else {
                        $qq->whereNull('section_id')->orWhere('section_id', $sectionId);
                    }
                })
                ->where(function($qg) use ($optionalGroupId){
                    if($optionalGroupId === null) {
                        $qg->whereNull('group_id')->orWhereNotNull('group_id');
                    } else {
                        $qg->whereNull('group_id')->orWhere('group_id', $optionalGroupId);
                    }
                });

            $subjectIds = $q->distinct()->pluck('subject_id')->filter()->values()->all();
            $subjects = Subject::whereIn('id', $subjectIds)->orderBy('subjectName')->get(['id','subjectName']);
            return response()->json($subjects);
        }

        $subjects = Subject::orderBy('subjectName')->get(['id','subjectName']);
        return response()->json($subjects);
    }

    // Debug helper methods removed in production - use internal APIs or logs when needed

    public function updateAdminPhoto(Request $requ)
    {
        $admin = CultivationAdmin::find($requ->adminId);
        if (!$admin) {
            return back()->with('error', 'Sorry! No data found');
        }

        if ($requ->hasFile('avatar')) {
            $validated = $requ->validate([
                'avatar' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif|max:5120',
            ], [
                'avatar.mimes' => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                'avatar.max'   => 'Each file must be less than 5MB.',
            ]);

            $file = $requ->file('avatar');
            $ext = strtolower($file->extension());
            $allowed = ['jpg','jpeg','png','webp','avif'];
            if (!in_array($ext, $allowed, true)) { $ext = 'jpg'; }
            $newName = rand() . date('Ymd') . '.' . $ext;

            $dest = public_path('upload/image/admin');
            if (!is_dir($dest)) {
                @mkdir($dest, 0755, true);
            }

            // Remove old avatar if exists
            if (!empty($admin->avatar) && File::exists($dest.DIRECTORY_SEPARATOR.$admin->avatar)) {
                File::delete($dest.DIRECTORY_SEPARATOR.$admin->avatar);
            }
            // Resize to square thumbnail and encode with quality
            try {
                $image = Image::read($file)->cover(200, 200);
                $binary = $image->encodeByExtension($ext, quality: 85);
                file_put_contents($dest.DIRECTORY_SEPARATOR.$newName, (string)$binary);
            } catch (\Throwable $e) {
                // Fallback to raw move if processing fails
                $file->move($dest, $newName);
            }
            $admin->avatar = $newName;
        }

        if ($admin->save()) {
            return back()->with('success', 'Profile photo updated successfully');
        }
        return back()->with('error', 'Failed to update profile photo');
    }

    public function delAdminPhoto($id)
    {
        $admin = CultivationAdmin::find($id);
        if (!$admin) {
            return back()->with('error', 'Sorry! No data found');
        }
        $dest = public_path('upload/image/admin');
        if (!empty($admin->avatar) && File::exists($dest.DIRECTORY_SEPARATOR.$admin->avatar)) {
            File::delete($dest.DIRECTORY_SEPARATOR.$admin->avatar);
        }
        $admin->avatar = '';
        $admin->save();
        return back()->with('success', 'Profile photo deleted successfully');
    }
}
