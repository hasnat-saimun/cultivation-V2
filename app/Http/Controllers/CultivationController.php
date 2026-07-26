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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\sessionManage;
use App\Services\TeacherSubjectAssignmentAvailabilityService;

class CultivationController extends Controller
{
    private TeacherSubjectAssignmentAvailabilityService $assignmentAvailability;

    public function __construct(TeacherSubjectAssignmentAvailabilityService $assignmentAvailability)
    {
        $this->assignmentAvailability = $assignmentAvailability;
    }

    private function buildAdminFormData(?CultivationAdmin $user = null): array
    {
        $sectionList = SectionModel::orderBy('id', 'ASC')->get();
        $attendanceTakenMap = $this->attendanceTakenMap($user);
        $initialAssignmentSessionId = $this->resolveInitialAssignmentSessionId($user);

        return [
            'subjectList' => $this->availableAdminSubjects($user),
            'classList' => ClassModel::orderBy('id', 'ASC')->get(),
            'sectionList' => $sectionList,
            'groupList' => Department::orderBy('id', 'ASC')->get(),
            'attendanceClassList' => $this->availableAttendanceClasses($user, $sectionList, $attendanceTakenMap),
            'attendanceTakenMap' => $attendanceTakenMap,
            'initialAssignmentSessionId' => $initialAssignmentSessionId,
        ];
    }

    private function resolveInitialAssignmentSessionId(?CultivationAdmin $user = null)
    {
        $oldAssignmentSessionId = $this->normalizeOptionalSessionScalar(old('assignmentSessionId'));
        if ($oldAssignmentSessionId !== null) {
            return $oldAssignmentSessionId;
        }

        $existingAssignmentSessionId = $this->existingAssignmentSessionIdForEdit($user);
        if ($existingAssignmentSessionId !== null) {
            return $existingAssignmentSessionId;
        }

        $requestedAssignmentSessionId = $this->normalizeOptionalSessionScalar(request()->input('assignmentSessionId'));
        if ($requestedAssignmentSessionId !== null) {
            return $requestedAssignmentSessionId;
        }

        return null;
    }

    private function existingAssignmentSessionIdForEdit(?CultivationAdmin $user = null)
    {
        if (!$user || (int) $user->userType !== CultivationAdmin::ROLE_TEACHER) {
            return null;
        }

        if (!Schema::hasColumn('teacher_class_subjects', 'session_id')) {
            return null;
        }

        $sessionIds = DB::table('teacher_class_subjects')
            ->where('teacher_id', $user->id)
            ->whereNotNull('session_id')
            ->distinct()
            ->pluck('session_id')
            ->map(function ($sessionId) {
                return (int) $sessionId;
            })
            ->filter()
            ->unique()
            ->values();

        if ($sessionIds->count() === 1) {
            return $sessionIds->first();
        }

        return null;
    }

    private function normalizeOptionalSessionScalar($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_scalar($value) && ctype_digit((string) $value)) {
            return (int) $value;
        }

        return null;
    }

    private function availableAdminSubjects(?CultivationAdmin $user = null)
    {
        return Subject::query()
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
        $sessionIdRaw = $requ->input('assignmentSessionId', $requ->input('sessionId'));
        $sessionId = null;
        if ($sessionIdRaw !== null && $sessionIdRaw !== '' && ctype_digit((string) $sessionIdRaw)) {
            $sessionId = (int) $sessionIdRaw;
        }

        $rawCls = $requ->input('className', []);
        $rawSec = $requ->input('section', []);
        $rawSub = $requ->input('subject', []);
        $rawGrp = $requ->input('optionalGroup', []);
        $rawGenderScope = $requ->input('genderScope', []);

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
            $genderScope = $this->normalizeGenderScopeValue($rawGenderScope[$i] ?? 'all');

            if ($sectionValue === 'all') {
                foreach ($sectionIds as $sectionId) {
                    $sectionIdPool[] = $sectionId;
                    $assignmentRows[] = [
                        'session_id' => $sessionId,
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'group_id' => $groupId,
                        'subject_id' => $subjectId,
                        'gender_scope' => $genderScope,
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
                'session_id' => $sessionId,
                'class_id' => $classId,
                'section_id' => $normalizedSectionId,
                'group_id' => $groupId,
                'subject_id' => $subjectId,
                'gender_scope' => $genderScope,
            ];
        }

        $dedupedRows = [];
        $seenKeys = [];
        foreach ($assignmentRows as $row) {
            $key = ($row['session_id'] ?? 'n').'-'.$row['class_id'].'-'.($row['section_id'] ?? 'n').'-'.($row['group_id'] ?? 'n').'-'.($row['subject_id'] ?? 'n').'-'.($row['gender_scope'] ?? 'all');
            if (isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;
            $dedupedRows[] = $row;
        }

        $this->assertNoTeacherAssignmentGenderConflicts($dedupedRows);

        return [
            'class_ids' => array_values(array_unique($classIds)),
            'subject_ids' => array_values(array_unique(array_filter($subjectIds))),
            'section_ids' => array_values(array_unique(array_filter($sectionIdPool))),
            'assignment_rows' => $dedupedRows,
        ];
    }

    private function assertNoTeacherAssignmentGenderConflicts(array $assignmentRows): void
    {
        $bucketedByContext = [];
        foreach ($assignmentRows as $row) {
            if (empty($row['subject_id'])) {
                continue;
            }

            $context = $this->assignmentAvailability->normalizeContext([
                'session_id' => $row['session_id'] ?? null,
                'class_id' => $row['class_id'] ?? null,
                'section_id' => $row['section_id'] ?? null,
                'group_id' => $row['group_id'] ?? null,
                'subject_id' => $row['subject_id'] ?? null,
            ]);
            $scope = $this->assignmentAvailability->normalizeGenderScope($row['gender_scope'] ?? 'all');
            if ($scope === null) {
                throw ValidationException::withMessages([
                    'genderScope' => ['Invalid gender scope provided in assignment payload.'],
                ]);
            }

            $bucketKey = implode(':', [
                $context['session_id'] ?? 0,
                $context['class_id'],
                $context['section_id'] ?? 0,
                $context['group_id'] ?? 0,
                $context['subject_id'],
            ]);

            $bucketedByContext[$bucketKey] = $bucketedByContext[$bucketKey] ?? [];
            if (!in_array($scope, $bucketedByContext[$bucketKey], true)) {
                $bucketedByContext[$bucketKey][] = $scope;
            }
        }

        foreach ($bucketedByContext as $scopes) {
            if (in_array('all', $scopes, true) && (in_array('male', $scopes, true) || in_array('female', $scopes, true))) {
                throw ValidationException::withMessages([
                    'genderScope' => ['All cannot be combined with Male or Female in the same assignment context.'],
                ]);
            }
        }
    }

    private function normalizeGenderScopeValue($value): string
    {
        $scope = strtolower(trim((string) $value));

        return in_array($scope, ['all', 'male', 'female'], true) ? $scope : 'all';
    }

    private function ensureSubjectAssignmentsAvailable(array $subjectIds, ?int $ignoreAdminId = null): void
    {
        $subjectIds = array_values(array_unique(array_filter(array_map('intval', $subjectIds))));
        if (empty($subjectIds)) {
            return;
        }

        Subject::query()->whereIn('id', $subjectIds)->lockForUpdate()->get(['id']);
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
        $supportsSession = Schema::hasColumn('teacher_class_subjects', 'session_id');

        $existingRows = DB::table('teacher_class_subjects')
            ->where('teacher_id', $cultivation->id)
            ->get();

        $existingMap = [];
        foreach ($existingRows as $row) {
            $genderScope = $this->normalizeGenderScopeValue($row->gender_scope ?? 'all');
            $sessionKey = $supportsSession ? ($row->session_id ?? 'n') : 'legacy';
            $key = $sessionKey.'-'.$row->class_id.'-'.($row->section_id ?? 'n').'-'.($row->group_id ?? 'n').'-'.($row->subject_id ?? 'n').'-'.$genderScope;
            $existingMap[$key] = $row;
        }

        $desiredMap = [];
        foreach ($assignmentRows as $row) {
            $sessionKey = $supportsSession ? ($row['session_id'] ?? 'n') : 'legacy';
            $key = $sessionKey.'-'.$row['class_id'].'-'.($row['section_id'] ?? 'n').'-'.($row['group_id'] ?? 'n').'-'.($row['subject_id'] ?? 'n').'-'.$this->normalizeGenderScopeValue($row['gender_scope'] ?? 'all');
            $desiredMap[$key] = $row;
        }

        foreach ($existingMap as $key => $existingRow) {
            if (isset($desiredMap[$key])) {
                continue;
            }

            DB::table('teacher_class_subjects')
                ->where('teacher_id', $cultivation->id)
                ->where('class_id', $existingRow->class_id)
                ->when($supportsSession, function ($query) use ($existingRow) {
                    $query->where(function ($sessionQuery) use ($existingRow) {
                        if (($existingRow->session_id ?? null) === null) {
                            $sessionQuery->whereNull('session_id');
                        } else {
                            $sessionQuery->where('session_id', $existingRow->session_id);
                        }
                    });
                })
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
                ->where(function ($query) use ($existingRow) {
                    $genderScope = $this->normalizeGenderScopeValue($existingRow->gender_scope ?? 'all');
                    $query->whereNull('gender_scope')
                        ->orWhere('gender_scope', '')
                        ->orWhere('gender_scope', $genderScope);
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
                'gender_scope' => $this->normalizeGenderScopeValue($row['gender_scope'] ?? 'all'),
                'subject_id' => $row['subject_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($supportsSession) {
                $toInsert[count($toInsert) - 1]['session_id'] = $row['session_id'] ?? null;
            }
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
        $validatedConfig = $requ->validate([
            'ranking_method' => ['nullable', Rule::in(ServerConfig::RANKING_METHODS)],
        ]);

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
        $currentRankingMethod = strtolower(trim((string) ($server->ranking_method ?? '')));
        $server->ranking_method = $validatedConfig['ranking_method']
            ?? (in_array($currentRankingMethod, ServerConfig::RANKING_METHODS, true)
                ? $currentRankingMethod
                : ServerConfig::RANKING_METHOD_GRADING);

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
            'assignmentSessionId' => 'nullable|integer|exists:session_manages,id',
            'primaryClass' => 'nullable|integer|exists:class_manages,id',
            'primarySection' => 'nullable|integer|exists:section_manages,id',
            'className' => 'array',
            'className.*' => 'nullable|integer|exists:class_manages,id',
            'section' => 'array',
            'section.*' => 'nullable',
            'optionalGroup' => 'array',
            'optionalGroup.*' => 'nullable|integer|exists:departments,id',
            'genderScope' => 'array',
            'genderScope.*' => ['nullable', Rule::in(['all', 'male', 'female'])],
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

        if ((int) $requ->userType === CultivationAdmin::ROLE_TEACHER
            && !empty($teacherPayload['assignment_rows'])
            && Schema::hasColumn('teacher_class_subjects', 'session_id')
            && !$requ->filled('assignmentSessionId')) {
            throw ValidationException::withMessages([
                'assignmentSessionId' => ['Please select a session before adding teacher subject assignments.'],
            ]);
        }

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

            if ((int) $requ->userType === CultivationAdmin::ROLE_TEACHER) {
                // Lock and validate requested assignment contexts against overlapping gender scopes.
                foreach ($teacherPayload['assignment_rows'] as $row) {
                    if (empty($row['subject_id'])) {
                        continue;
                    }

                    $context = [
                        'session_id' => $row['session_id'] ?? null,
                        'class_id' => $row['class_id'] ?? null,
                        'section_id' => $row['section_id'] ?? null,
                        'group_id' => $row['group_id'] ?? null,
                        'subject_id' => $row['subject_id'] ?? null,
                    ];

                    $this->assignmentAvailability->lockContextRows($context);
                }

                $rowIdsByKey = [];
                if ($ignoreAdminId) {
                    $currentRowColumns = ['id', 'class_id', 'section_id', 'group_id', 'subject_id', 'gender_scope'];
                    if (Schema::hasColumn('teacher_class_subjects', 'session_id')) {
                        $currentRowColumns[] = 'session_id';
                    }

                    $currentRows = DB::table('teacher_class_subjects')
                        ->where('teacher_id', $ignoreAdminId)
                        ->get($currentRowColumns);

                    foreach ($currentRows as $currentRow) {
                        $key = implode(':', [
                            $currentRow->session_id ?? 0,
                            $currentRow->class_id,
                            $currentRow->section_id ?? 0,
                            $currentRow->group_id ?? 0,
                            $currentRow->subject_id ?? 0,
                            $this->normalizeGenderScopeValue($currentRow->gender_scope ?? 'all'),
                        ]);
                        $rowIdsByKey[$key] = (int) $currentRow->id;
                    }
                }

                foreach ($teacherPayload['assignment_rows'] as $row) {
                    if (empty($row['subject_id'])) {
                        continue;
                    }

                    $context = [
                        'session_id' => $row['session_id'] ?? null,
                        'class_id' => $row['class_id'] ?? null,
                        'section_id' => $row['section_id'] ?? null,
                        'group_id' => $row['group_id'] ?? null,
                        'subject_id' => $row['subject_id'] ?? null,
                    ];

                    $rowKey = implode(':', [
                        $row['session_id'] ?? 0,
                        $row['class_id'],
                        $row['section_id'] ?? 0,
                        $row['group_id'] ?? 0,
                        $row['subject_id'] ?? 0,
                        $this->normalizeGenderScopeValue($row['gender_scope'] ?? 'all'),
                    ]);

                    $excludeRowId = $rowIdsByKey[$rowKey] ?? null;
                    $requestedScope = $this->normalizeGenderScopeValue($row['gender_scope'] ?? 'all');

                    if (!$this->assignmentAvailability->canAssignGender($context, $requestedScope, $excludeRowId)) {
                        if ($requestedScope === 'all') {
                            throw ValidationException::withMessages([
                                'genderScope' => ["This subject is already assigned with partial gender coverage in the selected session, class, section and department."],
                            ]);
                        }

                        $genderLabel = ucfirst($requestedScope);
                        throw ValidationException::withMessages([
                            'genderScope' => ["This subject's {$genderLabel} students are already assigned to another teacher for the selected session, class, section and department."],
                        ]);
                    }
                }
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
                'classes:id,className',
                'sections:id,section',
                'primaryClass:id,className',
                'primarySection:id,section',
            ])
            ->where('id', '!=', $currentUserId)
            ->orderBy('id','ASC')
            ->get();

        $teacherIds = $userList
            ->where('userType', CultivationAdmin::ROLE_TEACHER)
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();

        $compositeRows = collect();
        if (!empty($teacherIds) && Schema::hasTable('teacher_class_subjects')) {
            $columns = ['teacher_id', 'class_id', 'section_id', 'group_id', 'subject_id', 'gender_scope'];
            if (Schema::hasColumn('teacher_class_subjects', 'session_id')) {
                $columns[] = 'session_id';
            }

            $compositeRows = DB::table('teacher_class_subjects')
                ->whereIn('teacher_id', $teacherIds)
                ->get($columns);
        }

        $classNames = ClassModel::query()->pluck('className', 'id');
        $sectionNames = SectionModel::query()->pluck('section', 'id');
        $groupNames = Department::query()->pluck('departmentName', 'id');
        $subjectNames = Subject::query()->pluck('subjectName', 'id');
        $sessionNames = sessionManage::query()->pluck('session', 'id');

        $assignmentBuckets = [];
        foreach ($compositeRows as $row) {
            $teacherId = (int) $row->teacher_id;
            $rowSessionId = property_exists($row, 'session_id') ? $row->session_id : null;
            $sessionName = $rowSessionId ? ($sessionNames[(int) $rowSessionId] ?? ('Session '.$rowSessionId)) : 'Legacy Session';
            $className = $classNames[(int) $row->class_id] ?? ('Class '.$row->class_id);
            $sectionName = $row->section_id ? ($sectionNames[(int) $row->section_id] ?? ('Section '.$row->section_id)) : null;
            $groupName = $row->group_id ? ($groupNames[(int) $row->group_id] ?? ('Group '.$row->group_id)) : null;
            $genderScope = $this->normalizeGenderScopeValue($row->gender_scope ?? 'all');
            $genderLabel = (new \App\Models\TeacherClassSubject(['gender_scope' => $genderScope]))->gender_scope_label;

            $label = $sessionName.' / '.$className;
            if ($sectionName) {
                $label .= ' / '.$sectionName;
            }
            if ($groupName) {
                $label .= ' / '.$groupName;
            }
            $label .= ' / Gender: '.$genderLabel;

            $subjectName = $row->subject_id ? ($subjectNames[(int) $row->subject_id] ?? ('Subject '.$row->subject_id)) : null;
            if (!$subjectName) {
                continue;
            }

            $assignmentBuckets[$teacherId][$label][] = $subjectName;
        }

        foreach ($userList as $user) {
            $teacherId = (int) $user->id;
            $summary = [];

            if (isset($assignmentBuckets[$teacherId])) {
                foreach ($assignmentBuckets[$teacherId] as $label => $subjects) {
                    $subjects = array_values(array_unique(array_filter($subjects)));
                    sort($subjects);
                    $summary[] = [
                        'label' => $label,
                        'subjects' => $subjects,
                    ];
                }
            }

            if (empty($summary) && (int) $user->userType === CultivationAdmin::ROLE_TEACHER) {
                $legacyClasses = $user->classes->pluck('className')->filter()->values()->all();
                $legacySubjects = $user->subjects->pluck('subjectName')->filter()->unique()->values()->all();

                if (!empty($legacyClasses) && !empty($legacySubjects)) {
                    foreach ($legacyClasses as $legacyClassName) {
                        $summary[] = [
                            'label' => $legacyClassName,
                            'subjects' => $legacySubjects,
                        ];
                    }
                } elseif (!empty($legacySubjects)) {
                    $summary[] = [
                        'label' => 'General Assignment',
                        'subjects' => $legacySubjects,
                    ];
                }
            }

            $user->setAttribute('subject_assignment_summary', $summary);
        }

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
        $sessionIdRaw = $requ->input('sessionId');
        $sessionId = ($sessionIdRaw !== null && $sessionIdRaw !== '' && ctype_digit((string) $sessionIdRaw))
            ? (int) $sessionIdRaw
            : null;
        $sectionId = $requ->filled('sectionId') ? (int)$requ->input('sectionId') : null;
        $optionalGroupId = $requ->filled('optionalGroupId') ? (int)$requ->input('optionalGroupId') : null;

        if(!$classId){
            return response()->json(['error'=>'classId required'], 400);
        }

        if($user && $user->isTeacher()){
            $q = \Illuminate\Support\Facades\DB::table('teacher_class_subjects')
                ->where('teacher_id', $user->id)
                ->where('class_id', $classId)
                ->when(Schema::hasColumn('teacher_class_subjects', 'session_id'), function ($query) use ($sessionId) {
                    $query->where(function ($qs) use ($sessionId) {
                        if ($sessionId === null) {
                            $qs->whereNull('session_id');
                        } else {
                            $qs->whereNull('session_id')->orWhere('session_id', $sessionId);
                        }
                    });
                })
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

    /**
     * Return context-aware subject + gender availability for teacher assignment UI.
     */
    public function assignmentAvailability(Request $request)
    {
        $request->validate([
            'sessionId' => 'required|integer|exists:session_manages,id',
            'classId' => 'required|integer|exists:class_manages,id',
            'sectionId' => 'nullable',
            'optionalGroupId' => 'nullable',
        ]);

        $sessionId = (int) $request->input('sessionId');
        $classId = (int) $request->input('classId');
        $sectionRaw = $request->input('sectionId');
        $groupRaw = $request->input('optionalGroupId');

        $sectionId = null;
        if ($sectionRaw !== null && $sectionRaw !== '' && $sectionRaw !== 'none' && $sectionRaw !== 'all' && ctype_digit((string) $sectionRaw)) {
            $sectionId = (int) $sectionRaw;
        }

        $groupId = null;
        if ($groupRaw !== null && $groupRaw !== '' && $groupRaw !== '0' && ctype_digit((string) $groupRaw)) {
            $groupId = (int) $groupRaw;
        }

        $subjects = $this->assignmentAvailability->subjectsWithAvailability([
            'session_id' => $sessionId,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'group_id' => $groupId,
        ]);

        return response()->json([
            'subjects' => $subjects,
        ]);
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
