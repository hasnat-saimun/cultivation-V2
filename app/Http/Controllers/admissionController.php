<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\newAdmission;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Department;
use App\Models\Subject;
use App\Models\Testimonial;
use App\Models\TransferCertificate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use File;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentsExport;
use App\Services\Students\StudentFilterService;


class AdmissionController extends Controller
{
    /**
     * Bulk Student ID Cards (filters + professional card data)
     */
    public function bulkIdCards(Request $request)
    {
        $classDetails = classManage::all();
        $sessionDetails = sessionManage::all();
        $sectionDetails = sectionManage::all();
        $departmentDetails = \App\Models\Department::all();

        $filters = [
            'classId' => $request->get('classId'),
            'sessionId' => $request->get('sessionId'),
            'sectionId' => $request->get('sectionId'),
            'departmentId' => $request->get('departmentId'),
        ];

        $q = newAdmission::query();
        if (!empty($filters['classId'])) { $q->where('className', (int)$filters['classId']); }
        if (!empty($filters['sessionId'])) { $q->where('sessName', (int)$filters['sessionId']); }
        if (!empty($filters['sectionId'])) { $q->where('sectionName', (int)$filters['sectionId']); }
        if (!empty($filters['departmentId'])) { $q->where('departmentName', (int)$filters['departmentId']); }
        $students = (!empty($filters['classId']) || !empty($filters['sessionId']) || !empty($filters['sectionId']) || !empty($filters['departmentId']))
            ? $q->professionalOrder()->get()
            : collect();

        // Branding from ServerConfig (first row)
        $server = \App\Models\ServerConfig::orderBy('id')->first();
        $branding = [
            'name' => $server->instituteName ?? 'Institute',
            'address' => $server->address ?? '',
            'email' => $server->officeEmail ?? '',
            'phone' => $server->officeMobile ?? '',
            'logoUrl' => !empty($server->logo) ? asset('/public/upload/image/cultivation/'.$server->logo) : null,
            'principalSignUrl' => !empty($server->principalSign) ? asset('/public/upload/image/cultivation/'.$server->principalSign) : null,
        ];

        // Preload lookup maps
        $classMap = $classDetails->keyBy('id');
        $sessionMap = $sessionDetails->keyBy('id');
        $sectionMap = $sectionDetails->keyBy('id');
        $deptMap = $departmentDetails->keyBy('id');

        // Build per-student card payloads
        $cardData = [];
        foreach ($students as $s) {
            $className = optional($classMap->get((int)$s->className))->className ?? '-';
            $sectionName = optional($sectionMap->get((int)$s->sectionName))->section ?? '-';
            $deptName = optional($deptMap->get((int)$s->departmentName))->departmentName ?? '-';
            $sessionText = optional($sessionMap->get((int)$s->sessName))->session ?? '-';

            // Compute validity date: 30-June of the last year in session string if parseable
            $validDate = null;
            if ($sessionText && preg_match('/(\d{4})\s*[-–]\s*(\d{4})/', $sessionText, $m)) {
                $endYear = (int)$m[2];
                $validDate = date('d-m-Y', strtotime(($endYear+1).'-06-30'));
            }
            if (!$validDate) { $validDate = date('d-m-Y', strtotime('+1 year')); }

            $photoUrl = !empty($s->avatar)
                ? asset('/public/upload/image/student/'.$s->avatar)
                : asset('/public/back-office/img/avatar.jpeg');

            $cardData[$s->id] = [
                'studentId' => $s->stdId,
                'name' => trim(($s->fullName ?? '').' '.($s->sureName ?? '')),
                'roll' => $s->rollNumber,
                'class' => $className,
                'section' => $sectionName,
                'department' => $deptName,
                'sessionText' => $sessionText,
                'validity' => $validDate,
                'photoUrl' => $photoUrl,
                'guardianName' => $s->gurdian ?? '',
                'guardianRelation' => $s->relationWithStd ?? '',
                'guardianPhone' => $s->gurdianPhone ?? $s->phone ?? '',
            ];
        }

        return view('cultivation.student-id-bulk', compact(
            'classDetails','sessionDetails','sectionDetails','departmentDetails','filters','students','cardData','branding'
        ));
    }

    /**
     * Revert a student's promotion using the latest ResultArchive entry.
     * Restores old_class, old_section, old_roll and old_session if available.
     */
    public function revertPromotion(Request $request, $stdId)
    {
        if (config('result_engine.promotion_revert_enabled', false)) {
            return back()->with('error', 'Legacy revert is disabled while centralized revert is enabled. Use an exact centralized promotion cycle.');
        }
        $student = newAdmission::find($stdId);
        if (!$student) {
            return back()->with('error', 'Student not found');
        }

        $archive = \App\Models\ResultArchive::where('student_id', $student->id)->orderBy('created_at', 'desc')->first();
        if (!$archive) {
            return back()->with('error', 'No archive found to revert');
        }

        // Prepare values from archive (use as-is if present)
        $oldClass = $archive->old_class;
        $oldSection = $archive->old_section;
        $oldRoll = $archive->old_roll;
        $oldSession = $archive->old_session;

        // Update student record
        try {
            $student->className = $oldClass;
            if (!empty($oldSection)) {
                $student->sectionName = $oldSection;
            }
            if (!empty($oldRoll)) {
                $student->rollNumber = $oldRoll;
            }
            if (!empty($oldSession)) {
                $student->sessName = $oldSession;
            }
            $student->save();
        } catch (\Exception $e) {
            return back()->with('error', 'Revert failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Student reverted to previous class/section/roll successfully');
    }

    public function revertCentralizedPromotion(Request $request, string $promotionCycleId)
    {
        $validated = $request->validate([
            'student_id' => 'required|integer|exists:new_admissions,id',
            'reason' => 'nullable|string|max:255',
            'confirm_cycle' => 'required|string|in:'.$promotionCycleId,
        ]);

        try {
            $report = app(\App\Services\ResultCalculation\CentralizedPromotionReverter::class)->process(
                $promotionCycleId,
                [(int)$validated['student_id']],
                false,
                false,
                session('cultivationAdmin'),
                $validated['reason'] ?? null,
            );
            return back()->with(
                'success',
                "Centralized promotion reverted. Revert cycle: {$report['revertCycleId']}."
            );
        } catch (\App\Services\ResultCalculation\PromotionRevertException $exception) {
            $issues = collect($exception->report['blockingErrors'])->take(5)
                ->map(fn ($issue) => $issue['code'].': '.$issue['message'])->implode(' | ');
            return back()->with('error', $issues ?: 'Centralized revert was blocked. No records were modified.');
        }
    }
    public function admitStudent(){
        $classDetails = classManage::all();
        $sectionDetails= sectionManage::all();
        $chk = newAdmission::orderBy('id','DESC')->first();
        return view('cultivation.admit-student',['chk'=>$chk,'classDetails'=>$classDetails,'sectionDatails'=>$sectionDetails]);
    }
    // public function newAdmission(){
    //     $classDetails = classManage::all();
    //     $sectionDetails= sectionManage::all();
    //     return view('cultivation.admit-student',['classDetails'=>$classDetails,'sectionDatails'=>$sectionDetails]);
    // }

    public function studentList(Request $request, ?StudentFilterService $studentFilters = null){
        $studentFilters ??= app(StudentFilterService::class);
        $filters = $studentFilters->filters($request);
        $stdData = $studentFilters->query($filters)->paginate(50)->withQueryString();

        $studentIds = $stdData->pluck('id')->all();
        $latestTestimonialIds = [];
        $latestTransferCertificateIds = [];

        if (!empty($studentIds)) {
            $latestTestimonialIds = Testimonial::query()
                ->whereIn('admission_id', $studentIds)
                ->selectRaw('admission_id, MAX(id) as latest_id')
                ->groupBy('admission_id')
                ->pluck('latest_id', 'admission_id')
                ->toArray();

            $latestTransferCertificateIds = TransferCertificate::query()
                ->whereIn('admission_id', $studentIds)
                ->selectRaw('admission_id, MAX(id) as latest_id')
                ->groupBy('admission_id')
                ->pluck('latest_id', 'admission_id')
                ->toArray();
        }

        $filterOptions = $studentFilters->options($filters);

        return view('cultivation.studentList', [
            'studentData' => $stdData,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'classes' => $filterOptions['classes'],
            'sessions' => $filterOptions['sessions'],
            'sections' => $filterOptions['sections'],
            'departments' => $filterOptions['departments'],
            'genderOptions' => $filterOptions['genderOptions'],
            'latestTestimonialIds' => $latestTestimonialIds,
            'latestTransferCertificateIds' => $latestTransferCertificateIds,
        ]);
    }

    /**
     * Export student list as PDF
     */
    public function exportStudentPDF(Request $request, StudentFilterService $studentFilters)
    {
        $filters = $studentFilters->filters($request);
        $students = $studentFilters->query($filters)->get();
        $instituteName = \App\Models\ServerConfig::query()->value('instituteName') ?: 'Institute';

        $pdf = \PDF::loadView('exports.student-list-pdf', [
            'students' => $students,
            'instituteName' => $instituteName,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('student-list-' . date('Y-m-d') . '.pdf');
    }

    public function exportStudentExcel(Request $request, StudentFilterService $studentFilters)
    {
        $filters = $studentFilters->filters($request);
        $studentsQuery = $studentFilters->query($filters);
        $filename = 'students-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new StudentsExport($studentsQuery), $filename);
    }

    public function bulkPhotoForm(Request $request)
    {
        $classDetails = classManage::all();
        $sessionDetails = sessionManage::all();
        $sectionDetails = sectionManage::all();

        $filters = [
            'classId' => $request->get('classId'),
            'sessionId' => $request->get('sessionId'),
            'sectionId' => $request->get('sectionId'),
        ];

        $students = collect();
        if ($filters['classId'] && $filters['sessionId'] && $filters['sectionId']) {
            $students = newAdmission::where([
                'className' => $filters['classId'],
                'sessName' => $filters['sessionId'],
                'sectionName' => $filters['sectionId'],
            ])->professionalOrder()->get();
        }

        return view('cultivation.student-photo-bulk', compact('classDetails', 'sessionDetails', 'sectionDetails', 'students', 'filters'));
    }

    public function bulkPhotoUpload(Request $request)
    {
        $request->validate([
            'classId' => 'required|integer',
            'sessionId' => 'required|integer',
            'sectionId' => 'required|integer',
            'student_ids' => 'array',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048',
        ]);

        $studentIds = $request->input('student_ids', []);
        $updated = 0;
        $skipped = 0;

        foreach ($studentIds as $sid) {
            $fileKey = "photos.$sid";
            if (!$request->hasFile($fileKey)) {
                $skipped++;
                continue;
            }

            $student = newAdmission::find($sid);
            if (!$student) {
                $skipped++;
                continue;
            }

            $photo = $request->file($fileKey);
            $newAvatar = Str::random(12) . '_' . time() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('upload/image/student/'), $newAvatar);

            if (!empty($student->avatar) && File::exists(public_path('upload/image/student/' . $student->avatar))) {
                File::delete(public_path('upload/image/student/' . $student->avatar));
            }

            $student->avatar = $newAvatar;
            $student->save();
            $updated++;
        }

        $message = "Updated $updated photo(s).";
        if ($skipped > 0) {
            $message .= " Skipped $skipped without files.";
        }

        return redirect()->route('studentPhotoBulk', [
            'classId' => $request->classId,
            'sessionId' => $request->sessionId,
            'sectionId' => $request->sectionId,
        ])->with('success', $message);
    }

    public function studentPromotion(){
        return view('cultivation.promotStd');
    }

    public function confirmPromotData(Request $requ){
        $validated = $requ->validate([
            'sessionId' => 'required|integer|exists:session_manages,id',
            'classId' => 'required|integer|exists:class_manages,id',
            'groupId' => 'nullable|integer|exists:section_manages,id',
            'type' => 'required|in:sectionwise,classwise',
            'promotSession' => 'required|integer|exists:session_manages,id',
            'promotId' => 'required|integer|exists:class_manages,id',
            'promotSection' => 'required|integer|exists:section_manages,id',
            'selected_students' => 'required|array|min:1',
            'selected_students.*' => 'integer|distinct|exists:new_admissions,id',
            'roll_numbers' => 'nullable|array',
            'examId' => 'required|integer|exists:exams,id',
            'submit_token' => 'required|string',
        ]);

        $sessionToken = (string) session('promotion_submit_token', '');
        if ($sessionToken === '' || !hash_equals($sessionToken, (string) $validated['submit_token'])) {
            return redirect(route('studentPromotion'))->with('error', 'Promotion request is invalid or expired. Please load the student list again.');
        }

        // Mark this token as consumed in session so browser refresh/back cannot replay it.
        session()->forget('promotion_submit_token');

        // Prevent duplicate submissions from retries/double-click across concurrent requests.
        $requestLockKey = 'promotion_submit:' . sha1((string) $validated['submit_token']);
        if (!Cache::add($requestLockKey, 1, now()->addMinutes(10))) {
            return redirect(route('studentPromotion'))->with('error', 'Promotion request is already being processed or was processed recently.');
        }

        $selectedIds = array_values(array_unique(array_map('intval', $validated['selected_students'] ?? [])));
        sort($selectedIds);
        $selectedCount = count($selectedIds);
        if ($selectedCount < 1) {
            return back()->withErrors(['selected_students' => 'Please select at least one student.'])->withInput();
        }

        $sourceSession = (int) $validated['sessionId'];
        $sourceClass = (int) $validated['classId'];
        $sourceSection = isset($validated['groupId']) ? (int) $validated['groupId'] : null;
        $sourceType = $validated['type'];

        $targetSession = (int) $validated['promotSession'];
        $targetClass = (int) $validated['promotId'];
        $targetSection = (int) $validated['promotSection'];

        $rollNumbers = $validated['roll_numbers'] ?? [];
        $promotionId = (string) Str::uuid();
        $promoted = 0;
        $skipped = 0;
        $failed = 0;
        $messages = [];

        if (config('result_engine.promotion_enabled', false)) {
            try {
                $report = app(\App\Services\ResultCalculation\CentralizedPromotionProcessor::class)->process(
                    (int)$validated['examId'], $sourceClass, $sourceSession, $targetClass, $targetSession, $targetSection,
                    $sourceSection, null, null, $selectedIds, $rollNumbers, false,
                    session('cultivationAdmin')
                );
                return redirect(route('studentPromotion'))->with(
                    'success',
                    "Centralized promotion completed. Cycle: {$report['promotionCycleId']}; promoted: {$report['studentsPromoted']}; archives: {$report['archivesCreated']}; audits: {$report['auditsCreated']}."
                );
            } catch (\App\Services\ResultCalculation\PromotionProcessingException $exception) {
                $issues = collect($exception->report['blockingErrors'])->take(5)
                    ->map(fn ($issue) => $issue['code'].': '.$issue['message'])->implode(' | ');
                return back()->withInput()->with('error', $issues ?: 'Centralized promotion was blocked. No records were modified.');
            }
        }

        try {
            $legacyBatch = app(\App\Services\ResultCalculation\ResultCalculationBatchBuilder::class)->buildTolerant(
                (int) $validated['examId'],
                $sourceClass,
                $sourceSession,
                $sourceType === 'sectionwise' ? $sourceSection : null,
                null
            );
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', 'The selected exam result could not be calculated. No records were modified.');
        }

        $selectedInScopeIds = collect($legacyBatch['students'])
            ->pluck('id')
            ->map(fn ($studentId) => (int) $studentId)
            ->intersect($selectedIds);
        $missingCalculationIds = $selectedInScopeIds
            ->reject(fn ($studentId) => isset($legacyBatch['entries'][$studentId]))
            ->values();
        if ($missingCalculationIds->isNotEmpty()) {
            return back()->withInput()->with(
                'error',
                'Centralized result calculation failed for selected student IDs: '.$missingCalculationIds->implode(', ').'. No records were modified.'
            );
        }

        try {
            DB::transaction(function () use (
                $selectedIds,
                $sourceSession,
                $sourceClass,
                $sourceSection,
                $sourceType,
                $targetSession,
                $targetClass,
                $targetSection,
                $rollNumbers,
                $promotionId,
                $legacyBatch,
                &$promoted,
                &$skipped,
                &$failed,
                &$messages
            ) {
                $students = newAdmission::whereIn('id', $selectedIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($students->count() !== count($selectedIds)) {
                    throw new \RuntimeException('One or more selected students were not found.');
                }

                // Track requested destination roll numbers in-memory to avoid duplicate roll assignment in one batch.
                $batchRollMap = [];

                foreach ($selectedIds as $studentId) {
                    /** @var \App\Models\newAdmission $student */
                    $student = $students->get($studentId);
                    if (!$student) {
                        $failed++;
                        $messages[] = "Student ID {$studentId} not found.";
                        continue;
                    }

                    if ((int)$student->sessName !== $sourceSession || (int)$student->className !== $sourceClass) {
                        $skipped++;
                        $messages[] = "Skipped {$student->stdId}: source class/session does not match current record.";
                        continue;
                    }
                    if ($sourceType === 'sectionwise' && $sourceSection !== null && (int)$student->sectionName !== $sourceSection) {
                        $skipped++;
                        $messages[] = "Skipped {$student->stdId}: source section does not match current record.";
                        continue;
                    }

                    if ((int)$student->sessName === $targetSession && (int)$student->className === $targetClass && (int)$student->sectionName === $targetSection) {
                        $skipped++;
                        $messages[] = "Skipped {$student->stdId}: already in the target session/class/section.";
                        continue;
                    }

                    $rawRoll = $rollNumbers[$student->id] ?? null;
                    $newRoll = is_string($rawRoll) ? trim($rawRoll) : $rawRoll;
                    if ($newRoll === null || $newRoll === '') {
                        $newRoll = $student->getAttributes()['rollNumber'] ?? $student->rollNumber;
                    }

                    if ($newRoll !== null && $newRoll !== '') {
                        $rollKey = $targetSession . '|' . $targetClass . '|' . $targetSection . '|' . strtolower((string)$newRoll);
                        if (isset($batchRollMap[$rollKey]) && (int)$batchRollMap[$rollKey] !== (int)$student->id) {
                            $skipped++;
                            $messages[] = "Skipped {$student->stdId}: duplicate destination roll {$newRoll} in selected list.";
                            continue;
                        }
                        $batchRollMap[$rollKey] = (int)$student->id;

                        $rollConflict = newAdmission::where('sessName', $targetSession)
                            ->where('className', $targetClass)
                            ->where('sectionName', $targetSection)
                            ->where('rollNumber', $newRoll)
                            ->where('id', '!=', $student->id)
                            ->lockForUpdate()
                            ->exists();

                        if ($rollConflict) {
                            $skipped++;
                            $messages[] = "Skipped {$student->stdId}: destination roll {$newRoll} already exists.";
                            continue;
                        }
                    }

                    // Archive the selected exam's centralized result snapshot before changing academic assignment.
                    $calculationEntry = $legacyBatch['entries'][(int) $student->id];
                    if ($calculationEntry['student']->marksheet->isNotEmpty()) {
                        $studentResult = $calculationEntry['result'];
                        $presentedResult = app(\App\Services\ResultCalculation\TranscriptResultPresenter::class)->present(
                            $studentResult,
                            $calculationEntry['subjects'],
                            $calculationEntry['student']->marksheet
                        );
                        $archiveSubjects = collect(array_merge(
                            $presentedResult['mainRows'],
                            $presentedResult['optionalRows']
                        ))->map(fn (array $row) => [
                            'id' => $row['id'],
                            'sourceIds' => $row['sourceIds'],
                            'name' => $row['name'],
                            'cq' => $row['cq'],
                            'mcq' => $row['mcq'],
                            'practical' => $row['practical'],
                            'total' => $row['total'],
                            'grade' => $row['grade'],
                            'gradePoint' => $row['gradePoint'],
                            'type' => $row['type'],
                            'componentFailures' => $row['componentFailures'],
                        ])->all();

                        $resultData = [
                            'subjects' => $archiveSubjects,
                            'total_marks' => $presentedResult['totalMarks'],
                            'gpa' => $studentResult->gpa,
                            'result' => $studentResult->status,
                            'status' => $studentResult->status,
                            'optional_bonus' => $studentResult->optionalBonus,
                            'compulsory_gp_sum' => $studentResult->compulsoryGpSum,
                            'compulsory_subject_count' => $studentResult->compulsorySubjectCount,
                            'failed_compulsory_subjects' => $studentResult->failedCompulsorySubjects,
                            'missing_compulsory_subjects' => $studentResult->missingCompulsorySubjects,
                            'calculation_version' => $studentResult->calculationVersion,
                        ];

                        \App\Models\ResultArchive::firstOrCreate(
                            [
                                'student_id' => $student->id,
                                'old_class' => $student->className,
                                'old_roll' => $student->getAttributes()['rollNumber'] ?? $student->rollNumber,
                                'old_session' => $student->sessName,
                                'old_section' => $student->sectionName,
                            ],
                            [
                                'result_data' => $resultData,
                            ]
                        );
                    }

                    $oldSessionValue = $student->sessName;
                    $oldClassValue = $student->className;
                    $oldSectionValue = $student->sectionName;
                    $oldRollValue = $student->getAttributes()['rollNumber'] ?? $student->rollNumber;

                    $student->sessName = $targetSession;
                    $student->className = $targetClass;
                    $student->sectionName = $targetSection;
                    $student->rollNumber = $newRoll;
                    $student->save();

                    \App\Models\PromotionAuditLog::create([
                        'promotion_id' => $promotionId,
                        'student_id' => $student->id,
                        'old_session' => $oldSessionValue,
                        'old_class' => $oldClassValue,
                        'old_section' => $oldSectionValue,
                        'old_roll' => $oldRollValue,
                        'new_session' => $targetSession,
                        'new_class' => $targetClass,
                        'new_section' => $targetSection,
                        'new_roll' => $newRoll,
                        'performed_by' => optional(auth()->user())->id,
                        'ip_address' => request()->ip(),
                    ]);

                    $promoted++;
                }
            }, 3);
        } catch (\Throwable $e) {
            return redirect(route('studentPromotion'))->with('error', 'Promotion failed. No changes were saved. Error: ' . $e->getMessage());
        }

        $summary = "Promotion completed. Selected: {$selectedCount}, Promoted: {$promoted}, Skipped: {$skipped}, Failed: {$failed}.";
        if (!empty($messages)) {
            $summary .= ' Details: ' . implode(' | ', $messages);
        }

        return redirect(route('studentPromotion'))->with($failed > 0 ? 'error' : 'success', $summary);
    }

    public function getPromotionData(Request $requ){
        $type = $requ->input('type','sectionwise');

        // Build query dynamically from provided filters. Fields are optional.
        $q = newAdmission::query();
        if ($requ->filled('sessionId')) {
            $q->where('sessName', $requ->sessionId);
        }
        if ($requ->filled('classId')) {
            $q->where('className', $requ->classId);
        }
        if ($requ->filled('groupId') && $type !== 'classwise') {
            // only apply group filter when not doing classwise promotion
            $q->where('sectionName', $requ->groupId);
        }

        $studentList = $q->professionalOrder()->get();
        $groupId = $requ->filled('groupId') ? $requ->groupId : null;
        $submitToken = (string) Str::uuid();
        session()->put('promotion_submit_token', $submitToken);
        $promotionExamList = \App\Models\Exam::orderBy('id', 'DESC')->get();
        $activePromotionAudits = collect();
        $promotionCycleCounts = collect();
        if (config('result_engine.promotion_revert_enabled', false) && $studentList->isNotEmpty()) {
            $activePromotionAudits = \App\Models\PromotionAuditLog::query()
                ->whereIn('student_id', $studentList->pluck('id'))
                ->where('engine', 'centralized')->whereNotNull('promotion_cycle_id')
                ->whereNull('reverted_at')->latest('id')->get()->unique('student_id')->keyBy('student_id');
            $promotionCycleCounts = \App\Models\PromotionAuditLog::query()
                ->whereIn('promotion_cycle_id', $activePromotionAudits->pluck('promotion_cycle_id'))
                ->where('engine', 'centralized')->whereNull('reverted_at')
                ->selectRaw('promotion_cycle_id, COUNT(*) as aggregate')
                ->groupBy('promotion_cycle_id')->pluck('aggregate', 'promotion_cycle_id');
        }

        return view('cultivation.promotData',[
            'studentList'=>$studentList,
            'groupId'=>$groupId,
            'classId'=>$requ->classId ?? null,
            'sessionId'=>$requ->sessionId ?? null,
            'type' => $type,
            'submitToken' => $submitToken,
            'promotionExamList' => $promotionExamList,
            'activePromotionAudits' => $activePromotionAudits,
            'promotionCycleCounts' => $promotionCycleCounts,
        ]);
    }
    
    
    public function confirmAdmit(Request $requ){
        $chk = newAdmission::where(['rollNumber'=>$requ->rollNumber,'className'=>$requ->className,'sessName'=>$requ->sessName,'sectionName'=>$requ->sectionName])->get();
        if(!empty($chk) && count($chk)>0):
            return back()->with('error','Data already exist');
        else:
            $data = new newAdmission();
            
            $data->fullName         = $requ->fullName;
            $data->sureName         = $requ->sureName;
            $data->father           = $requ->fatherName;
            $data->mother           = $requ->motherName;
            $data->gender           = $requ->gender;
            $data->dob              = $requ->dob;
            $data->blGroup          = $requ->blGroup;
            $data->religion         = $requ->religion;
            $data->address          = $requ->address;
            $data->mail             = $requ->mail;
            $data->phone            = $requ->phone;
            $data->sessName         = $requ->sessName;
            $data->className        = $requ->className;
            $data->departmentName   = $requ->departmentName;
            $data->sectionName      = $requ->sectionName;
            // Religious subject selection (single checkbox)
            $data->religiousSubjectId = $requ->religiousSubjectId ? (int) $requ->religiousSubjectId : null;
            $data->fourthSubjectId  = $requ->fourthSubjectId ? (int) $requ->fourthSubjectId : null;
            $data->rollNumber       = $requ->rollNumber;
            $data->gurdianName      = $requ->gurdian;
            $data->gurdianMobile    = $requ->gurdianPhone;
            $data->relationGurdian  = $requ->relationWithStd;
            $data->status           = "newProfile";
            $stdId                  = $requ->stdId;

            $data->stdId = $stdId;

            // Default Religious Subject for the student's class if none provided
            if (empty($data->religiousSubjectId)) {
                $defaultRelSub = self::resolveDefaultReligiousSubject($data->className);
                if ($defaultRelSub) {
                    $data->religiousSubjectId = $defaultRelSub->id;
                }
            }

            if(!empty($requ->avatar)):
                $validated = $requ->validate([
                    'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048',
                     // max 2 MB
                    'avatar' => 'required|image|max:2048'
                     //(simpler: lets Laravel infer common image types)
                ]);
                $stdAvatar = $requ->file('avatar');
                $newAvatar = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
                $stdAvatar->move(public_path('upload/image/student/'),$newAvatar);

                $data->avatar = $newAvatar;
            endif;


            if($data->save()):
                return back()->with('success','Data saved sucessfully');
            else:
                return back()->with('error','An error ocoured! please try later');
            endif;
        endif;
    }
    public function viewAdmission($id){
        $singleData= newAdmission::find($id);
        return view('cultivation.viewStudent',['singleData'=>$singleData]);
    }


    public function stdIdCard(Request $request, $id){
        return view('cultivation.stdIdCard', $this->singleIdCardData($request, (int) $id, false));
    }

    public function stdIdCardPdf(Request $request, $id)
    {
        $data = $this->singleIdCardData($request, (int) $id, true);

        return \PDF::loadView('cultivation.stdIdCard', $data)
            ->setPaper('a4', 'portrait')
            ->download('student-id-card-'.$data['std']->stdId.'.pdf');
    }

    private function singleIdCardData(Request $request, int $id, bool $pdfMode): array
    {
        $std = newAdmission::query()->with(['classInfo', 'sectionInfo', 'sessionInfo', 'departmentInfo'])->findOrFail($id);
        $format = $request->get('format') === 'portrait' ? 'portrait' : 'landscape';
        $server = \App\Models\ServerConfig::orderBy('id')->first();
        $branding = [
            'name' => $server->instituteName ?? 'Institute',
            'address' => $server->address ?? '',
            'email' => $server->officeEmail ?? '',
            'phone' => $server->officeMobile ?? '',
            'logoUrl' => $this->embeddedImage(
                ! empty($server->logo) ? public_path('upload/image/cultivation/'.$server->logo) : null,
                public_path(ltrim(preg_replace('~^public/~', '', (string) config('branding.cultivation_logo')), '/'))
            ),
        ];

        $className = optional($std->classInfo)->className ?? '-';
        $sectionName = optional($std->sectionInfo)->section ?? '-';
        $deptName = optional($std->departmentInfo)->departmentName ?? '-';
        $sessionText = optional($std->sessionInfo)->session ?? '-';

        $validDate = null;
        if ($sessionText && preg_match('/(\d{4})\s*[-–]\s*(\d{4})/', $sessionText, $m)) {
            $endYear = (int)$m[2];
            $validDate = date('d-m-Y', strtotime(($endYear+1).'-06-30'));
        }
        if (!$validDate) { $validDate = date('d-m-Y', strtotime('+1 year')); }

        $photoUrl = $this->embeddedImage(
            ! empty($std->avatar) ? public_path('upload/image/student/'.$std->avatar) : null,
            public_path('back-office/img/avatar.jpeg')
        );

        $card = [
            'studentId' => $std->stdId,
            'name' => trim(($std->fullName ?? '').' '.($std->sureName ?? '')),
            'roll' => $std->rollNumber,
            'class' => $className,
            'section' => $sectionName,
            'department' => $deptName,
            'sessionText' => $sessionText,
            'validity' => $validDate,
            'photoUrl' => $photoUrl,
            'guardianName' => $std->gurdianName ?: $std->father ?: '-',
            'guardianRelation' => $std->relationGurdian ?: '-',
            'guardianPhone' => $std->gurdianMobile ?: $std->phone ?: '-',
            'contact' => $std->phone ?: '-',
        ];

        return compact('std', 'branding', 'card', 'format', 'pdfMode');
    }

    private function embeddedImage(?string ...$paths): ?string
    {
        foreach ($paths as $path) {
            if (! $path || ! is_file($path) || ! is_readable($path)) continue;
            $mime = mime_content_type($path) ?: 'image/png';
            return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
        }
        return null;
    }

    public function editStudent($id){
        
        $classDetails = classManage::all();
        $sectionDetails= sectionManage::all();
        $departmentDetails= Department::all();
        $optionalSubjectList = Subject::where('subjectType', 'Optional')->orderBy('subjectName')->get();
        $stdDataa= newAdmission::find($id);
        return view('cultivation.edit-student',['classDetails'=>$classDetails,'sectionDatails'=>$sectionDetails,'stdData'=>$stdDataa,'departmentDetails'=>$departmentDetails,'optionalSubjectList'=>$optionalSubjectList]);
    }

    //update
    public function updateAdmit(Request $requ){
            $data = newAdmission::find($requ->stdId);
            if($data->count()>0):

                $data->fullName         = $requ->fullName;
                $data->sureName         = $requ->sureName;
                $data->father           = $requ->fatherName;
                $data->mother           = $requ->motherName;
                $data->gender           = $requ->gender;
                $data->dob              = $requ->dob;
                $data->blGroup          = $requ->blGroup;
                $data->religion         = $requ->religion;
                $data->address          = $requ->address;
                $data->mail             = $requ->mail;
                $data->phone            = $requ->phone;
                $data->sessName         = $requ->sessName;
                $data->className        = $requ->className;
                $data->departmentName   = $requ->departmentName;
                $data->sectionName      = $requ->sectionName;
                $data->religiousSubjectId = $requ->religiousSubjectId ? (int) $requ->religiousSubjectId : null;
                $data->fourthSubjectId  = $requ->fourthSubjectId ? (int) $requ->fourthSubjectId : null;
                $data->rollNumber       = $requ->rollNumber;
                $data->gurdianName      = $requ->gurdian;
                $data->gurdianMobile    = $requ->gurdianPhone;
                $data->relationGurdian  = $requ->relationWithStd;
                
                // Default Religious Subject for the student's class if none provided on update
                if (empty($data->religiousSubjectId)) {
                    $defaultRelSub = self::resolveDefaultReligiousSubject($data->className);
                    if ($defaultRelSub) {
                        $data->religiousSubjectId = $defaultRelSub->id;
                    }
                }
                
                if($data->save()):
                    return back()->with("success",'data update success');
                else:
                    return back()->with("error",'data update failed');
                endif;
            else:
                return back()->with('error','Data update failed');
            endif;
     }

     public function delStudentPhoto($id){
        $teacherProfileData = newAdmission::find($id);
        if(empty($teacherProfileData)):
            // return public_path('uploads/image/teacher/'.$teacherProfileData->avatar);
            return back()->with('error','Sorry! Profile picture failed to delete');
        else:
            if (File::exists(public_path('upload/image/student/'.$teacherProfileData->avatar))) {
                File::delete(public_path('upload/image/student/'.$teacherProfileData->avatar));
            }
            // return public_path('upload/image/teacher/'.$teacherProfileData->avatar);
            $teacherProfileData->avatar        = "";
            $teacherProfileData->save();
            return back()->with('success','Success! Profile picture deleted successfully');
        endif;
    }

    public function stdPhotoUpdate(Request $requ){
        $data = newAdmission::find($requ->stdId);
        if($data->count()>0):
            if(!empty($requ->avatar)):
                $validated = $requ->validate([
                    'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048',
                     // max 2 MB
                    'avatar' => 'required|image|max:2048'
                     //(simpler: lets Laravel infer common image types)
                ]);
                $stdAvatar = $requ->file('avatar');
                $newAvatar = rand().date('Ymd').'.'.$stdAvatar->getClientOriginalExtension();
                $stdAvatar->move(public_path('upload/image/student/'),$newAvatar);

                $data->avatar = $newAvatar;
            endif;
                
            if($data->save()):
                return back()->with("success",'data update success');
            else:
                return back()->with("error",'data update failed');
            endif;
        else:
            return back()->with('error','Data update failed');
        endif;
    }
     
    //delelte 
    public function delStudent($id){
        $dltData = newAdmission::find($id);

        if($dltData->delete()):
            return back()->with('success','data entry successfully');
        else:
            return back()->with('error','data deletion failed');
        endif;
    
     }

    public function studentBulkDelete(Request $request)
    {
        try {
            $ids = json_decode($request->input('ids'), true);
            
            if (!is_array($ids) || empty($ids)) {
                return back()->with('error', 'No records selected');
            }

            $deleted = newAdmission::whereIn('id', $ids)->delete();

            if ($deleted > 0) {
                return back()->with('success', "Successfully deleted $deleted student(s)");
            } else {
                return back()->with('error', 'No records found to delete');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    /**
     * Show bulk update form for students
     */
    public function bulkStudentUpdateForm(Request $request, StudentFilterService $studentFilters)
    {
        $classDetails = classManage::orderBy('id')->get();
        $sessionDetails = sessionManage::orderBy('id')->get();
        $sectionDetails = sectionManage::orderBy('id')->get();
        $departmentDetails = Department::orderBy('id')->get();
        $optionalSubjectList = Subject::where('subjectType', 'Optional')->orderBy('subjectName')->get();
        $religiousSubjectList = Subject::where('isReligious', true)->orderBy('subjectName')->get();
        $islamDefaultSubjectId = Subject::where('isReligious', true)
            ->where(function($q){
                $q->whereRaw('LOWER(subjectName) LIKE ?', ['%islam%'])
                  ->orWhereRaw('LOWER(alias) LIKE ?', ['%islam%']);
            })
            ->orderByRaw("CASE WHEN LOWER(subjectName) LIKE '%111%' OR LOWER(alias) LIKE '%111%' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->value('id');

        $filters = $studentFilters->filters($request);
        $students = $studentFilters->query($filters)->get();
        $filterOptions = $studentFilters->options($filters);

        return view('cultivation.student-bulk-update', compact(
            'students',
            'classDetails',
            'sessionDetails',
            'sectionDetails',
            'departmentDetails',
            'optionalSubjectList',
            'religiousSubjectList',
            'islamDefaultSubjectId',
            'filters',
            'filterOptions'
        ));
    }

    /**
     * Store bulk updates for students (per-row editing)
     */
    public function bulkStudentUpdateStore(Request $request)
    {
        $returnFilters = app(StudentFilterService::class)->filters($request);
        $rawStudents = $request->input('students');
        if ($rawStudents === null || $rawStudents === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['students' => 'The students field is required.']);
        }
        if (!is_string($rawStudents)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'students' => 'The students field must contain the complete JSON batch.',
            ]);
        }
        if (strlen($rawStudents) > 2000000) {
            throw \Illuminate\Validation\ValidationException::withMessages(['students' => 'The submitted student batch is too large.']);
        }
        try {
            $decodedStudents = json_decode($rawStudents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'students' => 'The students field contains invalid JSON.',
            ]);
        }
        if (!is_array($decodedStudents) || !array_is_list($decodedStudents)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'students' => 'The students JSON must be an indexed array.',
            ]);
        }
        $request->merge(['students' => $decodedStudents]);

        $rules = [
            'students' => 'required|array|min:1|max:500',
            'students.*.id' => 'required|integer|distinct|exists:new_admissions,id',
            'students.*.fullName' => 'nullable|string|max:255',
            'students.*.sureName' => 'nullable|string|max:255',
            'students.*.father' => 'nullable|string|max:255',
            'students.*.mother' => 'nullable|string|max:255',
            'students.*.gender' => 'nullable|in:1,2,3',
            'students.*.dob' => 'nullable|date',
            'students.*.mail' => 'nullable|email|max:255',
            'students.*.phone' => 'nullable|string|max:20',
            'students.*.address' => 'nullable|string',
            'students.*.sessName' => 'nullable|integer|exists:session_manages,id',
            'students.*.className' => 'nullable|integer|exists:class_manages,id',
            'students.*.departmentName' => 'nullable|integer|exists:departments,id',
            'students.*.sectionName' => 'nullable|integer|exists:section_manages,id',
            'students.*.religiousSubjectId' => 'nullable|integer|exists:subjects,id',
            'students.*.fourthSubjectId' => 'nullable|integer|exists:subjects,id',
            'students.*.rollNumber' => 'nullable|string|max:20',
            'students.*.gurdianName' => 'nullable|string|max:255',
            'students.*.gurdianMobile' => 'nullable|string|max:20',
            'students.*.relationGurdian' => 'nullable|integer',
        ];
        $rowAttributes = [];
        foreach ((array) $request->input('students', []) as $index => $studentData) {
            foreach (array_keys(is_array($studentData) ? $studentData : []) as $field) {
                $rowAttributes['students.'.$index.'.'.$field] = 'Row '.($index + 1).' '.$field;
            }
        }
        $validated = $request->validate($rules, [], $rowAttributes);

        $fields = [
            'fullName',
            'sureName',
            'father',
            'mother',
            'gender',
            'dob',
            'mail',
            'phone',
            'address',
            'sessName',
            'className',
            'departmentName',
            'sectionName',
            'religiousSubjectId',
            'fourthSubjectId',
            'rollNumber',
            'gurdianName',
            'gurdianMobile',
            'relationGurdian',
        ];
        $numericNullableFields = [
            'sessName',
            'className',
            'departmentName',
            'sectionName',
            'religiousSubjectId',
            'fourthSubjectId',
            'relationGurdian',
        ];

        $updated = 0;
        $unchanged = 0;

        DB::transaction(function () use (&$updated, &$unchanged, $validated, $fields, $numericNullableFields) {
            $submittedStudents = $validated['students'];
            $studentIds = collect($submittedStudents)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $studentsById = newAdmission::query()->whereIn('id', $studentIds)->lockForUpdate()->get()->keyBy('id');

            foreach (array_chunk($submittedStudents, 100) as $studentChunk) {
              foreach ($studentChunk as $studentData) {
                $student = $studentsById->get((int) ($studentData['id'] ?? 0));
                if (!$student) {
                    throw new \RuntimeException('Student bulk update failed because a validated row no longer exists.');
                }

                foreach ($fields as $field) {
                    if (!array_key_exists($field, $studentData)) {
                        continue;
                    }

                    $value = $studentData[$field];
                    if (in_array($field, $numericNullableFields, true) && $value === '') {
                        $value = null;
                    }

                    $student->{$field} = $value;
                }

                if (!$student->isDirty()) {
                    $unchanged++;
                    continue;
                }

                if (!$student->save()) {
                    throw new \RuntimeException('Student bulk update failed for ID '.$student->id);
                }

                $updated++;
              }
            }
        });

        if ($updated === 0) {
            return redirect()->route('studentBulkUpdate', array_filter($returnFilters, fn ($value) => $value !== null && $value !== ''))->with(
                'error',
                $unchanged > 0
                    ? 'No changes were saved because all submitted values matched current records.'
                    : 'No student records were updated.'
            );
        }

        return redirect()->route('studentBulkUpdate', array_filter($returnFilters, fn ($value) => $value !== null && $value !== ''))->with(
            'success',
            "Successfully updated {$updated} student(s).".($unchanged > 0 ? " {$unchanged} row(s) unchanged." : '')
        );
    }


    // Helper methods for default religious subject resolution
    private static function resolveDefaultReligiousSubject(?string $className)
    {
        // Prefer explicit per-class default mapping
        if (!empty($className)) {
            $classRow = \App\Models\classManage::where('className', $className)->first();
            if ($classRow) {
                $map = \App\Models\ReligiousSubjectDefault::where('classId', $classRow->id)->first();
                if ($map) {
                    $sub = Subject::find($map->subjectId);
                    if ($sub && ($sub->isReligious ?? false)) return $sub;
                }
            }
        }
        // Otherwise prefer class-assigned religious subject; fallback to any religious subject
        $query = Subject::query()->where('isReligious', true);
        if (!empty($className)) {
            $query = $query->where(function ($q) use ($className) {
                $q->where('assign_class', 'like', '%' . $className . '%');
            });
        }
        $subject = $query->orderBy('id')->first();
        if ($subject) return $subject;
        return Subject::where('isReligious', true)->orderBy('id')->first();
    }

    /**
     * Export student list as PDF
     */
    public function exportPDF()
    {
        $students = newAdmission::query()->professionalOrder()->get();
        $pdf = \PDF::loadView('exports.student-list-pdf', ['students' => $students]);
        return $pdf->download('student-list-' . date('Y-m-d') . '.pdf');
    }
}

