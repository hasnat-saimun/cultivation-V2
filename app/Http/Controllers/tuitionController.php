<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\sectionManage;
use App\Models\classManage;
use App\Models\sessionManage;
use App\Models\tuitionFee;
use App\Models\newAdmission;
use App\Models\feesManager;
use App\Models\ClassWiseFeeSetup;
use App\Models\CultivationAdmin;
use Carbon\Carbon;



class tuitionController extends Controller
{
    private function currentAdmin(): ?CultivationAdmin
    {
        $adminId = session('cultivationAdmin');
        return $adminId ? CultivationAdmin::find($adminId) : null;
    }

    private function allowedClassIds(?CultivationAdmin $user): array
    {
        if (!$user || !$user->isTeacher()) {
            return [];
        }

        if (!empty($user->primary_class_id)) {
            return [(int) $user->primary_class_id];
        }

        return array_map('intval', $user->access_class_array ?? []);
    }

    private function allowedSectionIds(?CultivationAdmin $user): array
    {
        if (!$user || !$user->isTeacher()) {
            return [];
        }

        if (!empty($user->primary_section_id)) {
            return [(int) $user->primary_section_id];
        }

        return array_map('intval', $user->access_section_array ?? []);
    }

    private function applyTeacherStudentScope($query, ?CultivationAdmin $user): void
    {
        if (!$user || !$user->isTeacher()) {
            return;
        }

        $classIds = $this->allowedClassIds($user);
        if (empty($classIds)) {
            $query->whereRaw('1 = 0');
            return;
        }
        $query->whereIn('className', $classIds);

        $sectionIds = $this->allowedSectionIds($user);
        if (!empty($sectionIds)) {
            $query->whereIn('sectionName', $sectionIds);
        }
    }

    private function canCollectStudent(newAdmission $student, ?CultivationAdmin $user): bool
    {
        if (!$user || !$user->isTeacher()) {
            return true;
        }

        $studentClassId = (int) ($student->className ?? 0);
        $studentSectionId = (int) ($student->sectionName ?? 0);

        $classIds = $this->allowedClassIds($user);
        if (empty($classIds) || !in_array($studentClassId, $classIds, true)) {
            return false;
        }

        $sectionIds = $this->allowedSectionIds($user);
        if (!empty($sectionIds) && !in_array($studentSectionId, $sectionIds, true)) {
            return false;
        }

        return true;
    }

    private function resolveStatus(float $due, float $paid): string
    {
        if ($paid <= 0) {
            return 'unpaid';
        }
        if ($paid < $due) {
            return 'partial';
        }
        return 'paid';
    }

    // tuition form page
    public function tuitionFee(){
        $user = $this->currentAdmin();
        $isTeacher = $user && $user->isTeacher();

        if ($isTeacher) {
            $classIds = $this->allowedClassIds($user);
            $sectionIds = $this->allowedSectionIds($user);

            $classDetails = !empty($classIds)
                ? classManage::whereIn('id', $classIds)->orderBy('id', 'ASC')->get()
                : collect();

            $sectionDetails = !empty($sectionIds)
                ? sectionManage::whereIn('id', $sectionIds)->orderBy('id', 'ASC')->get()
                : sectionManage::orderBy('id', 'ASC')->get();
        } else {
            $sectionDetails = sectionManage::orderBy('id', 'ASC')->get();
            $classDetails = classManage::orderBy('id', 'ASC')->get();
        }

        $sessionDetails = sessionManage::orderBy('id', 'ASC')->get();

        return view('account.tuition.tuitionFeesFrom',[
            'sectionData' => $sectionDetails,
            'classData' => $classDetails,
            'sessionData' => $sessionDetails,
            'isTeacher' => $isTeacher,
        ]);
    }

    // get tution admission student on form page
    public function getStudentForTutionFee($stdId){
        $user = $this->currentAdmin();
        $studentQuery = newAdmission::where('stdId', $stdId);
        $this->applyTeacherStudentScope($studentQuery, $user);
        $studentList = $studentQuery->first();

        $feesDetails = feesManager::all();
        $classWiseSetupMap = [];
        if (!empty($studentList) && !empty($studentList->className)) {
            $classWiseSetupMap = ClassWiseFeeSetup::where('class_id', (int) $studentList->className)
                ->pluck('setup_amount', 'fees_type_id')
                ->map(function ($amount) {
                    return (float) $amount;
                })
                ->toArray();
        }

        $feeMonthInput = request()->input('feeMonth', now()->format('Y-m'));
        try {
            $feeMonthDate = Carbon::createFromFormat('Y-m', $feeMonthInput)->startOfMonth()->toDateString();
        } catch (\Throwable $e) {
            $feeMonthInput = now()->format('Y-m');
            $feeMonthDate = now()->startOfMonth()->toDateString();
        }

        $monthCollection = collect();
        if (!empty($studentList)) {
            $monthCollection = tuitionFee::where('stdId', $studentList->stdId)
                ->whereDate('fee_month', $feeMonthDate)
                ->orderBy('id', 'DESC')
                ->get();
        }

        return view('account.tuition.getTutionStudentList',[
            'studentData' => $studentList,
            'feesData' => $feesDetails,
            'classWiseSetupMap' => $classWiseSetupMap,
            'feeMonthInput' => $feeMonthInput,
            'monthCollection' => $monthCollection,
        ]);
    }

    public function classWiseFeeSetup()
    {
        $user = $this->currentAdmin();
        if ($user && $user->isTeacher()) {
            return back()->with('error', 'Teachers are not allowed to configure class-wise fee setup.');
        }

        $classData = classManage::orderBy('id', 'ASC')->get();
        $feesData = feesManager::orderBy('feesName', 'ASC')->get();
        $setupRows = ClassWiseFeeSetup::query()
            ->join('class_manages as cm', 'cm.id', '=', 'class_wise_fee_setups.class_id')
            ->join('fees_managers as fm', 'fm.id', '=', 'class_wise_fee_setups.fees_type_id')
            ->select([
                'class_wise_fee_setups.id',
                'class_wise_fee_setups.class_id',
                'class_wise_fee_setups.fees_type_id',
                'class_wise_fee_setups.setup_amount',
                'cm.className as class_name',
                'fm.feesName as fee_name',
            ])
            ->orderBy('cm.id', 'ASC')
            ->orderBy('fm.feesName', 'ASC')
            ->get();

        $existingByClass = ClassWiseFeeSetup::query()
            ->select(['class_id', 'fees_type_id', 'setup_amount'])
            ->orderBy('fees_type_id', 'ASC')
            ->get()
            ->groupBy('class_id')
            ->map(function ($rows) {
                return $rows->map(function ($row) {
                    return [
                        'fees_type_id' => (int) $row->fees_type_id,
                        'setup_amount' => (float) $row->setup_amount,
                    ];
                })->values();
            });

        return view('account.tuition.classWiseFeeSetup', [
            'classData' => $classData,
            'feesData' => $feesData,
            'setupRows' => $setupRows,
            'existingByClass' => $existingByClass,
        ]);
    }

    public function saveClassWiseFeeSetup(Request $requ)
    {
        $user = $this->currentAdmin();
        if ($user && $user->isTeacher()) {
            return back()->with('error', 'Teachers are not allowed to configure class-wise fee setup.');
        }

        $validated = $requ->validate([
            'classId' => 'required|integer|exists:class_manages,id',
            'feesType' => 'required|array|min:1',
            'feesType.*' => 'required|distinct|integer|exists:fees_managers,id',
            'setupAmount' => 'required|array|min:1',
            'setupAmount.*' => 'required|numeric|min:0.01',
        ]);

        $classId = (int) $validated['classId'];
        $feeTypes = $validated['feesType'];
        $amounts = $validated['setupAmount'];

        $created = 0;
        $updated = 0;
        foreach ($feeTypes as $index => $feeType) {
            $setupAmount = isset($amounts[$index]) ? (float) $amounts[$index] : 0;
            if ($setupAmount <= 0) {
                continue;
            }

            $setup = ClassWiseFeeSetup::firstOrNew([
                'class_id' => $classId,
                'fees_type_id' => (int) $feeType,
            ]);

            $isNew = !$setup->exists;
            $setup->setup_amount = $setupAmount;
            $setup->save();

            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        if ($created <= 0 && $updated <= 0) {
            return back()->with('error', 'No class-wise setup saved.');
        }

        $messageParts = [];
        if ($created > 0) {
            $messageParts[] = $created . ' created';
        }
        if ($updated > 0) {
            $messageParts[] = $updated . ' updated';
        }

        return back()->with('success', 'Class-wise setup saved: ' . implode(', ', $messageParts) . '.');
    }

    public function deleteClassWiseFeeSetup($id)
    {
        $user = $this->currentAdmin();
        if ($user && $user->isTeacher()) {
            return back()->with('error', 'Teachers are not allowed to manage class-wise fee setup.');
        }

        $row = ClassWiseFeeSetup::find($id);
        if (!$row) {
            return back()->with('error', 'Class-wise setup record not found.');
        }

        $row->delete();
        return back()->with('success', 'Class-wise setup record deleted successfully.');
    }

    public function getClassWiseFeeSetupData(Request $requ)
    {
        $user = $this->currentAdmin();
        if ($user && $user->isTeacher()) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthorized',
                'rows' => [],
            ], 403);
        }

        $validated = $requ->validate([
            'classId' => 'required|integer|exists:class_manages,id',
        ]);

        $rows = ClassWiseFeeSetup::where('class_id', (int) $validated['classId'])
            ->orderBy('fees_type_id', 'ASC')
            ->get(['fees_type_id', 'setup_amount'])
            ->map(function ($row) {
                return [
                    'fees_type_id' => (int) $row->fees_type_id,
                    'setup_amount' => (float) $row->setup_amount,
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'rows' => $rows,
        ]);
    }

    public function getStudentsForTutionFeeFilter(Request $requ){
        $user = $this->currentAdmin();
        $classId = $requ->input('classId');
        $sessionId = $requ->input('sessionId');
        $sectionId = $requ->input('sectionId');
        $search = trim((string)$requ->input('search', ''));

        $query = newAdmission::query();
        $this->applyTeacherStudentScope($query, $user);

        if($classId){ $query->where('className', (int)$classId); }
        if($sessionId){ $query->where('sessName', (int)$sessionId); }
        if($sectionId){ $query->where('sectionName', (int)$sectionId); }
        if($search !== ''){
            $query->where(function($q) use ($search){
                $q->where('stdId','like',"%{$search}%")
                    ->orWhere('fullName','like',"%{$search}%")
                    ->orWhere('sureName','like',"%{$search}%")
                    ->orWhere('rollNumber','like',"%{$search}%");
            });
        }

        $students = $query->orderBy('className','ASC')
            ->orderBy('rollNumber','ASC')
            ->limit(100)
            ->get();

        return view('account.tuition.getTutionStudentFilterList', [
            'students' => $students,
        ]);
    }

    public function saveTuitionfee(Request $requ){
        $user = $this->currentAdmin();
        if (!$user) {
            return redirect()->route('adminLogin')->with('error', 'Please login to continue');
        }

        $validated = $requ->validate([
            'stdId' => 'required|string',
            'feeMonth' => 'required|date_format:Y-m',
            'feesType' => 'required|array|min:1',
            'feesType.*' => 'distinct|required|integer|exists:fees_managers,id',
            'amount' => 'required|array|min:1',
            'amount.*' => 'required|numeric|min:0.01',
            'totalAmount' => 'nullable|array',
            'totalAmount.*' => 'nullable|numeric|min:0.01',
        ]);

        $student = newAdmission::where('stdId', $validated['stdId'])->first();
        if (!$student) {
            return back()->with('error', 'Student not found');
        }
        if (!$this->canCollectStudent($student, $user)) {
            return back()->with('error', 'Unauthorized to collect fees for this student.');
        }

        $stdId = $student->stdId;
        $feeMonth = Carbon::createFromFormat('Y-m', $validated['feeMonth'])->startOfMonth()->toDateString();
        $types = $validated['feesType'];
        $paidRows = $validated['amount'];
        $setupRows = $validated['totalAmount'] ?? [];
        $classSetupMap = ClassWiseFeeSetup::where('class_id', (int) ($student->className ?? 0))
            ->pluck('setup_amount', 'fees_type_id')
            ->map(function ($amount) {
                return (float) $amount;
            })
            ->toArray();

        $created = 0;
        $updated = 0;
        $skipped = [];

        foreach($types as $idx => $t){
            $feeType = (int) $t;
            $feeInfo = feesManager::find($feeType);
            $feeName = $feeInfo->feesName ?? ('ID '.$feeType);

            $paidNow = isset($paidRows[$idx]) ? (float) $paidRows[$idx] : 0.0;
            if ($paidNow <= 0) {
                $skipped[] = $feeName.' (invalid paid amount)';
                continue;
            }

            $classWiseDefault = isset($classSetupMap[$feeType]) ? (float) $classSetupMap[$feeType] : 0.0;
            $defaultDue = $classWiseDefault > 0
                ? $classWiseDefault
                : (is_numeric($feeInfo->feesAmount ?? null) ? (float) $feeInfo->feesAmount : 0.0);
            $submittedDue = isset($setupRows[$idx]) && $setupRows[$idx] !== null ? (float) $setupRows[$idx] : 0.0;
            $dueAmount = $submittedDue > 0 ? $submittedDue : ($defaultDue > 0 ? $defaultDue : $paidNow);

            $existing = tuitionFee::where('stdId', $stdId)
                ->where('feesType', $feeType)
                ->whereDate('fee_month', $feeMonth)
                ->first();

            if ($existing) {
                $targetDue = max((float) ($existing->due_amount ?? 0), $dueAmount);
                $newPaid = (float) ($existing->paid_amount ?? 0) + $paidNow;

                if ($newPaid - $targetDue > 0.00001) {
                    $remaining = max(0, $targetDue - (float) ($existing->paid_amount ?? 0));
                    $skipped[] = $feeName.' (amount exceeds due, remaining '.number_format($remaining, 2).')';
                    continue;
                }

                $existing->due_amount = $targetDue;
                $existing->paid_amount = $newPaid;
                $existing->amount = $newPaid;
                $existing->payment_status = $this->resolveStatus($targetDue, $newPaid);
                $existing->class_id = (int) ($student->className ?? 0) ?: null;
                $existing->section_id = (int) ($student->sectionName ?? 0) ?: null;
                $existing->session_id = (int) ($student->sessName ?? 0) ?: null;
                $existing->collected_by = (int) $user->id;
                $existing->save();
                $updated++;
                continue;
            }

            if ($paidNow - $dueAmount > 0.00001) {
                $skipped[] = $feeName.' (paid amount cannot be greater than setup amount)';
                continue;
            }

            tuitionFee::create([
                'stdId' => $stdId,
                'feesType' => $feeType,
                'fee_month' => $feeMonth,
                'due_amount' => $dueAmount,
                'paid_amount' => $paidNow,
                'amount' => $paidNow,
                'payment_status' => $this->resolveStatus($dueAmount, $paidNow),
                'class_id' => (int) ($student->className ?? 0) ?: null,
                'section_id' => (int) ($student->sectionName ?? 0) ?: null,
                'session_id' => (int) ($student->sessName ?? 0) ?: null,
                'collected_by' => (int) $user->id,
                'note' => null,
            ]);
            $created++;
        }

        if ($created === 0 && $updated === 0) {
            return back()->with('error', 'No fee was saved. '.(!empty($skipped) ? implode(' | ', $skipped) : ''));
        }

        $parts = [];
        if ($created > 0) {
            $parts[] = $created.' new saved';
        }
        if ($updated > 0) {
            $parts[] = $updated.' updated (partial collection added)';
        }
        if (!empty($skipped)) {
            $parts[] = 'Skipped: '.implode(', ', $skipped);
            return back()->with('warning', implode(' | ', $parts));
        }

        return back()->with('success', implode(' | ', $parts));
    }
    
    // tution Fee List
    public function tuitionFeeList(){
        $user = $this->currentAdmin();
        $query = tuitionFee::query();

        if ($user && $user->isTeacher()) {
            $studentQuery = newAdmission::query()->select('stdId');
            $this->applyTeacherStudentScope($studentQuery, $user);
            $allowedStdIds = $studentQuery->pluck('stdId')->all();

            if (empty($allowedStdIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('stdId', $allowedStdIds);
            }
        }

        $tutionfeeData = $query->orderBy('id', 'DESC')->get();
        return view('account.tuition.tuitionFeesList',['tfd'=>$tutionfeeData]);
    }

    public function tuitionFeeView($id){
        $user = $this->currentAdmin();
        $singleData = tuitionFee::find($id);

        if (!$singleData) {
            return back()->with('error', 'Tuition fee record not found');
        }

        if ($user && $user->isTeacher()) {
            $student = newAdmission::where('stdId', $singleData->stdId)->first();
            if (!$student || !$this->canCollectStudent($student, $user)) {
                return back()->with('error', 'Unauthorized fee record');
            }
        }

        return view('account.tuition.viewTutionFee',['singleView'=>$singleData]);
    }

    //edit tiutionfee
    public function editTuitionFee($id){
        $user = $this->currentAdmin();
        $tuitionFeeData = tuitionFee::find($id);
        if(!$tuitionFeeData){
            return back()->with('error','Sorry! Tuition fee not found');
        }

        if ($user && $user->isTeacher()) {
            $student = newAdmission::where('stdId', $tuitionFeeData->stdId)->first();
            if (!$student || !$this->canCollectStudent($student, $user)) {
                return back()->with('error', 'Unauthorized fee record');
            }
        }

        $feesDetails = feesManager::all();
        $classDetails = classManage::orderBy('id', 'ASC')->get();
        $sessionDetails = sessionManage::orderBy('id', 'ASC')->get();
        $sectionDetails = sectionManage::orderBy('id', 'ASC')->get();
        $currentStudent = newAdmission::where('stdId', $tuitionFeeData->stdId)->first();

        return view('account.tuition.editTuitionFee',[
            'editData'=>$tuitionFeeData,
            'feesData'=>$feesDetails,
            'classData'=>$classDetails,
            'sessionData'=>$sessionDetails,
            'sectionData'=>$sectionDetails,
            'currentStudent'=>$currentStudent,
        ]);
    }

    //update tiutionfee 
    public function updateTuitionFee(Request $requ){
        $user = $this->currentAdmin();
        $id = $requ->input('tuitionFeeId');
        $updateData = tuitionFee::find($id);
        if(!$updateData){
            return back()->with('error','Tuition fee record not found');
        }

        if ($user && $user->isTeacher()) {
            $student = newAdmission::where('stdId', $updateData->stdId)->first();
            if (!$student || !$this->canCollectStudent($student, $user)) {
                return back()->with('error', 'Unauthorized fee record');
            }
        }

        // Admin/general can edit full record. Teacher users should use due collection flow only.
        if ($user && $user->isTeacher()) {
            return back()->with('error', 'Teachers cannot edit full tuition records. Use Collect Due instead.');
        }

        $validated = $requ->validate([
            'stdId'    => 'required|string',
            'feesType' => 'required|integer|exists:fees_managers,id',
            'feeMonth' => 'required|date_format:Y-m',
            'dueAmount' => 'required|numeric|min:0.01',
            'paidAmount' => 'required|numeric|min:0',
        ]);

        $student = newAdmission::where('stdId', $validated['stdId'])->first();
        if (!$student) {
            return back()->with('error', 'Student not found');
        }

        $feeMonth = Carbon::createFromFormat('Y-m', $validated['feeMonth'])->startOfMonth()->toDateString();
        $dueAmount = (float) $validated['dueAmount'];
        $paidAmount = (float) $validated['paidAmount'];

        if ($paidAmount - $dueAmount > 0.00001) {
            return back()->with('warning', 'Collected amount cannot be greater than setup amount.');
        }

        $sameMonthDup = tuitionFee::where('stdId', $validated['stdId'])
            ->where('feesType', (int) $validated['feesType'])
            ->whereDate('fee_month', $feeMonth)
            ->where('id','!=',$updateData->id)
            ->exists();

        if($sameMonthDup){
            $feeName = feesManager::find((int) $validated['feesType'])->feesName ?? 'selected fee type';
            return back()->with('warning', "Cannot update: a '$feeName' record already exists for this student in selected month.");
        }

        $updateData->stdId = $validated['stdId'];
        $updateData->feesType = (int) $validated['feesType'];
        $updateData->fee_month = $feeMonth;
        $updateData->due_amount = $dueAmount;
        $updateData->paid_amount = $paidAmount;
        $updateData->amount = $paidAmount;
        $updateData->payment_status = $this->resolveStatus($dueAmount, $paidAmount);
        $updateData->class_id = (int) ($student->className ?? 0) ?: null;
        $updateData->section_id = (int) ($student->sectionName ?? 0) ?: null;
        $updateData->session_id = (int) ($student->sessName ?? 0) ?: null;
        $updateData->collected_by = $user ? (int) $user->id : null;

        if($updateData->save()){
            return redirect(route('tuitionFeeList'))->with('success','Tuition fee record updated successfully');
        }
        return back()->with('error','Data update failed');
    }

    public function collectDueForm($id)
    {
        $user = $this->currentAdmin();
        $feeData = tuitionFee::find($id);
        if (!$feeData) {
            return back()->with('error', 'Tuition fee record not found');
        }

        $student = newAdmission::where('stdId', $feeData->stdId)->first();
        if (!$student) {
            return back()->with('error', 'Student not found');
        }

        if (!$this->canCollectStudent($student, $user)) {
            return back()->with('error', 'Unauthorized fee record');
        }

        $feesDetails = feesManager::all();
        return view('account.tuition.collectDue', [
            'editData' => $feeData,
            'feesData' => $feesDetails,
        ]);
    }

    public function collectDueSubmit(Request $requ)
    {
        $user = $this->currentAdmin();
        $validated = $requ->validate([
            'tuitionFeeId' => 'required|integer|exists:tuition_fees,id',
            'collectAmount' => 'required|numeric|min:0.01',
        ]);

        $updateData = tuitionFee::find((int) $validated['tuitionFeeId']);
        if (!$updateData) {
            return back()->with('error', 'Tuition fee record not found');
        }

        $student = newAdmission::where('stdId', $updateData->stdId)->first();
        if (!$student) {
            return back()->with('error', 'Student not found');
        }

        if (!$this->canCollectStudent($student, $user)) {
            return back()->with('error', 'Unauthorized to collect dues for this student.');
        }

        $dueAmount = (float) ($updateData->due_amount ?? $updateData->amount ?? 0);
        $paidAmount = (float) ($updateData->paid_amount ?? $updateData->amount ?? 0);
        $collectAmount = (float) $validated['collectAmount'];
        $remainingDue = max(0, $dueAmount - $paidAmount);

        if ($remainingDue <= 0) {
            return back()->with('warning', 'This fee is already fully paid.');
        }

        if ($collectAmount - $remainingDue > 0.00001) {
            return back()->with('warning', 'Collect amount cannot be greater than due amount.');
        }

        $newPaidAmount = $paidAmount + $collectAmount;
        $updateData->paid_amount = $newPaidAmount;
        $updateData->amount = $newPaidAmount;
        $updateData->payment_status = $this->resolveStatus($dueAmount, $newPaidAmount);
        $updateData->class_id = (int) ($student->className ?? 0) ?: null;
        $updateData->section_id = (int) ($student->sectionName ?? 0) ?: null;
        $updateData->session_id = (int) ($student->sessName ?? 0) ?: null;
        $updateData->collected_by = $user ? (int) $user->id : null;

        if ($updateData->save()) {
            return redirect(route('duesDashboard'))->with('success', 'Dues collected successfully');
        }

        return back()->with('error', 'Data update failed');
    }

    //delelte 
    public function dltTuitionFee($id){
        $user = $this->currentAdmin();
        $dltData = tuitionFee::find($id);

        if (!$dltData) {
            return back()->with('error', 'Fee record not found');
        }

        if ($user && $user->isTeacher()) {
            $student = newAdmission::where('stdId', $dltData->stdId)->first();
            if (!$student || !$this->canCollectStudent($student, $user)) {
                return back()->with('error', 'Unauthorized fee record');
            }
        }

        if($dltData->delete()):
            return back()->with('success','data entry successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }

     // bulk delete
     public function bulkDeleteTuitionFees(Request $requ){
        $user = $this->currentAdmin();
        $ids = $requ->input('feeIds', []);
        if(empty($ids) || !is_array($ids)){
            return back()->with('error','No items selected');
        }

        $query = tuitionFee::whereIn('id', $ids);

        if ($user && $user->isTeacher()) {
            $studentQuery = newAdmission::query()->select('stdId');
            $this->applyTeacherStudentScope($studentQuery, $user);
            $allowedStdIds = $studentQuery->pluck('stdId')->all();
            if (empty($allowedStdIds)) {
                return back()->with('error', 'Unauthorized fee records');
            }
            $query->whereIn('stdId', $allowedStdIds);
        }

        $deleted = $query->delete();
        if($deleted>0){
            return back()->with('success', $deleted.' record(s) deleted');
        }
        return back()->with('error','Failed to delete selected records');
     }

     //report
     
    public function tuitionReport($id){
        $user = $this->currentAdmin();
        $singleData = tuitionFee::find($id);

        if (!$singleData) {
            return back()->with('error', 'Fee record not found');
        }

        if ($user && $user->isTeacher()) {
            $student = newAdmission::where('stdId', $singleData->stdId)->first();
            if (!$student || !$this->canCollectStudent($student, $user)) {
                return back()->with('error', 'Unauthorized fee record');
            }
        }

        return view('account.tuition.report',['singleView'=>$singleData]);
    }

    public function feesReport(){
        $user = $this->currentAdmin();
        $isTeacher = $user && $user->isTeacher();

        if ($isTeacher) {
            $classIds = $this->allowedClassIds($user);
            $sectionIds = $this->allowedSectionIds($user);

            $classDetails = !empty($classIds)
                ? classManage::whereIn('id', $classIds)->orderBy('id', 'ASC')->get()
                : collect();

            $sectionDetails = !empty($sectionIds)
                ? sectionManage::whereIn('id', $sectionIds)->orderBy('id', 'ASC')->get()
                : sectionManage::orderBy('id', 'ASC')->get();
        } else {
            $classDetails = classManage::orderBy('id', 'ASC')->get();
            $sectionDetails = sectionManage::orderBy('id', 'ASC')->get();
        }

        $sessionDetails = sessionManage::orderBy('id', 'ASC')->get();

        return view('account.tuition.feesReport', [
            'classData' => $classDetails,
            'sectionData' => $sectionDetails,
            'sessionData' => $sessionDetails,
            'isTeacher' => $isTeacher,
        ]);
    }

    public function duesDashboard(Request $requ)
    {
        $user = $this->currentAdmin();
        $isTeacher = $user && $user->isTeacher();

        if ($isTeacher) {
            $classIds = $this->allowedClassIds($user);
            $sectionIds = $this->allowedSectionIds($user);

            $classDetails = !empty($classIds)
                ? classManage::whereIn('id', $classIds)->orderBy('id', 'ASC')->get()
                : collect();

            $sectionDetails = !empty($sectionIds)
                ? sectionManage::whereIn('id', $sectionIds)->orderBy('id', 'ASC')->get()
                : sectionManage::orderBy('id', 'ASC')->get();
        } else {
            $classDetails = classManage::orderBy('id', 'ASC')->get();
            $sectionDetails = sectionManage::orderBy('id', 'ASC')->get();
        }

        $sessionDetails = sessionManage::orderBy('id', 'ASC')->get();

        $classId = $requ->input('classId');
        $sessionId = $requ->input('sessionId');
        $sectionId = $requ->input('sectionId');
        $feeMonthInput = trim((string) $requ->input('feeMonth', ''));
        $search = trim((string) $requ->input('search', ''));

        $query = DB::table('tuition_fees as tf')
            ->join('new_admissions as na', 'na.stdId', '=', 'tf.stdId')
            ->leftJoin('fees_managers as fm', 'fm.id', '=', 'tf.feesType')
            ->leftJoin('class_manages as cm', 'cm.id', '=', 'na.className')
            ->leftJoin('section_manages as sm', 'sm.id', '=', 'na.sectionName')
            ->leftJoin('session_manages as sess', 'sess.id', '=', 'na.sessName');

        if ($isTeacher) {
            $allowedClasses = $this->allowedClassIds($user);
            $allowedSections = $this->allowedSectionIds($user);

            if (empty($allowedClasses)) {
                $query->whereRaw('1=0');
            } else {
                $query->whereIn('na.className', $allowedClasses);
            }

            if (!empty($allowedSections)) {
                $query->whereIn('na.sectionName', $allowedSections);
            }
        }

        if (!empty($classId)) {
            $query->where('na.className', (int) $classId);
        }
        if (!empty($sessionId)) {
            $query->where('na.sessName', (int) $sessionId);
        }
        if (!empty($sectionId)) {
            $query->where('na.sectionName', (int) $sectionId);
        }

        if ($feeMonthInput !== '') {
            try {
                $feeMonth = Carbon::createFromFormat('Y-m', $feeMonthInput)->startOfMonth()->toDateString();
                $query->whereDate('tf.fee_month', $feeMonth);
            } catch (\Throwable $e) {
                // Ignore invalid month format and keep default dataset.
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('na.stdId', 'like', "%{$search}%")
                    ->orWhere('na.fullName', 'like', "%{$search}%")
                    ->orWhere('na.sureName', 'like', "%{$search}%")
                    ->orWhere('na.rollNumber', 'like', "%{$search}%");
            });
        }

        $dueExpr = 'COALESCE(tf.due_amount, tf.amount, 0)';
        $paidExpr = 'COALESCE(tf.paid_amount, tf.amount, 0)';

        // Dues dashboard should show only partial records (paid > 0 and still due remaining).
        $query->whereRaw("{$paidExpr} > 0 AND {$dueExpr} > {$paidExpr}");

        $summary = (clone $query)
            ->selectRaw("SUM({$dueExpr}) as total_due, SUM({$paidExpr}) as total_paid")
            ->first();

        $rows = (clone $query)
            ->select([
                'tf.id',
                'tf.stdId',
                'tf.feesType',
                'tf.fee_month',
                'tf.payment_status',
                'na.fullName',
                'na.sureName',
                'na.rollNumber',
                'cm.className as class_name',
                'sm.section as section_name',
                'sess.session as session_name',
                'fm.feesName as fee_name',
                DB::raw("{$dueExpr} as due_amount"),
                DB::raw("{$paidExpr} as paid_amount"),
                DB::raw("({$dueExpr} - {$paidExpr}) as balance_amount"),
            ])
            ->orderBy('na.className', 'ASC')
            ->orderByRaw('CAST(NULLIF(na.rollNumber, "") AS UNSIGNED) ASC')
            ->orderBy('tf.id', 'DESC')
            ->limit(500)
            ->get();

        return view('account.tuition.duesDashboard', [
            'rows' => $rows,
            'classData' => $classDetails,
            'sectionData' => $sectionDetails,
            'sessionData' => $sessionDetails,
            'isTeacher' => $isTeacher,
            'filters' => [
                'classId' => $classId,
                'sessionId' => $sessionId,
                'sectionId' => $sectionId,
                'feeMonth' => $feeMonthInput,
                'search' => $search,
            ],
            'summary' => [
                'total_due' => (float) ($summary->total_due ?? 0),
                'total_paid' => (float) ($summary->total_paid ?? 0),
                'total_balance' => max(0, (float) ($summary->total_due ?? 0) - (float) ($summary->total_paid ?? 0)),
            ],
        ]);
    }

    public function getFeesReport(Request $requ){
        $reportType = $requ->input('reportType','monthly');

        // Basic validation for stdId
        $requ->validate([
            'stdId' => 'required'
        ]);

        $student = newAdmission::where(['stdId'=>$requ->stdId])->first();
        if (!$student) {
            return back()->with('error', 'Student not found');
        }

        $feesQuery = tuitionFee::where('stdId', $requ->stdId)->orderBy('created_at', 'ASC');

        $user = $this->currentAdmin();
        if ($user && $user->isTeacher()) {
            if (!$student || !$this->canCollectStudent($student, $user)) {
                return back()->with('error', 'Unauthorized student for fee report');
            }
        }

        if($reportType === 'daily'){
            $validated = $requ->validate([
                'dailyDate' => 'required|date'
            ]);
            $date = Carbon::parse($validated['dailyDate'])->toDateString();
            $feesQuery = $feesQuery->whereDate('created_at', $date);
        } elseif($reportType === 'monthly'){
            $validated = $requ->validate([
                'reportMonth' => 'required|date_format:Y-m',
            ]);
            $reportMonth = Carbon::createFromFormat('Y-m', $validated['reportMonth'])->startOfMonth()->toDateString();
            $feesQuery = $feesQuery->whereDate('fee_month', $reportMonth);
        } else { // custom range
            $validated = $requ->validate([
                'fromDate' => 'required|date',
                'toDate' => 'required|date|after_or_equal:fromDate'
            ]);
            $from = Carbon::parse($validated['fromDate'])->toDateString();
            $to   = Carbon::parse($validated['toDate'])->toDateString();
            $feesQuery = $feesQuery->whereBetween('created_at', [$from." 00:00:00", $to." 23:59:59"]);
        }

        $feesList = $feesQuery->get();

        if($feesList->isNotEmpty()){
            $viewData = [
                'feesList'=>$feesList,
                'student'=>$student,
                'reportType'=>$reportType,
            ];
            if($reportType === 'daily'){
                $viewData['dailyDate'] = $date;
            } elseif($reportType === 'monthly') {
                $viewData['reportMonth'] = $reportMonth;
            } else {
                $viewData['fromDate'] = $from;
                $viewData['toDate'] = $to;
            }
            return view('account.tuition.generateStudentReport', $viewData);
        }
        return back()->with('error','Sorry! No data found with your query');
    }

}
