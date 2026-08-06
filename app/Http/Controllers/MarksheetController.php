<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marksheet;
use App\Models\newAdmission;
use App\Models\ServerConfig;
use App\Models\GradeList;
use App\Models\sessionManage;
use App\Models\sectionManage;
use App\Models\Department;
use App\Models\ResultPublish;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\ReligiousSubjectDefault;
use App\Services\CultivationAdminResolver;
use App\Services\FourthSubjectAssignmentResolver;
use App\Services\MarksEntryAuthorizationService;
use App\Services\MarksEntryContextService;
use App\Services\ReligiousSubjectAssignmentResolver;
use App\Services\ResultCalculation\BoardResultCalculator;
use App\Services\ResultCalculation\TranscriptResultPresenter;
use App\Services\ResultCalculation\ResultCalculationInputBuilder;
use App\Services\ResultCalculation\BulkTranscriptResultBuilder;
use App\Services\ResultCalculation\ResultCalculationBatchBuilder;
use App\Services\ResultCalculation\ResultMeritPositionService;
use App\Services\ResultCalculation\TabulationResultPresenter;
use App\Services\ResultMarksDraftService;
use App\Services\ResultComponentMarksValidationService;
use App\Services\ResultMarksConfirmationService;
use App\Services\ResultMarksReopenService;
use App\Services\ResultMarksScopeService;
use App\Exceptions\ResultLifecycleException;
use App\Exceptions\ResultPublicationException;
use App\Services\ResultPublishService;
use App\Services\ResultUnpublishService;
use App\Services\PublishedResultReadyMarksService;
use App\Services\TranscriptAccessService;
use App\Services\Students\StudentGenderService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MarksheetController extends Controller
{
    private CultivationAdminResolver $adminResolver;
    private FourthSubjectAssignmentResolver $fourthSubjectResolver;
    private MarksEntryAuthorizationService $marksAuth;
    private MarksEntryContextService $marksContext;
    private ReligiousSubjectAssignmentResolver $religiousSubjectResolver;
    private BoardResultCalculator $boardResultCalculator;
    private TranscriptResultPresenter $transcriptResultPresenter;
    private ResultCalculationInputBuilder $resultCalculationInputBuilder;
    private BulkTranscriptResultBuilder $bulkTranscriptResultBuilder;
    private ResultCalculationBatchBuilder $resultCalculationBatchBuilder;
    private TabulationResultPresenter $tabulationResultPresenter;
    private ResultMeritPositionService $meritPositionService;
    private ResultMarksDraftService $draftMarks;
    private ResultComponentMarksValidationService $componentMarksValidation;
    private ResultMarksConfirmationService $marksConfirmation;
    private ResultMarksReopenService $marksReopen;
    private ResultMarksScopeService $marksScopes;
    private ResultPublishService $resultPublisher;
    private ResultUnpublishService $resultUnpublisher;
    private PublishedResultReadyMarksService $publishedMarks;
    private TranscriptAccessService $transcriptAccess;
    private StudentGenderService $studentGender;

    public function __construct(
        CultivationAdminResolver $adminResolver,
        FourthSubjectAssignmentResolver $fourthSubjectResolver,
        MarksEntryAuthorizationService $marksAuth,
        MarksEntryContextService $marksContext,
        ReligiousSubjectAssignmentResolver $religiousSubjectResolver,
        BoardResultCalculator $boardResultCalculator,
        TranscriptResultPresenter $transcriptResultPresenter,
        ResultCalculationInputBuilder $resultCalculationInputBuilder,
        BulkTranscriptResultBuilder $bulkTranscriptResultBuilder,
        ResultCalculationBatchBuilder $resultCalculationBatchBuilder,
        TabulationResultPresenter $tabulationResultPresenter,
        ResultMeritPositionService $meritPositionService,
        ResultMarksDraftService $draftMarks,
        ResultComponentMarksValidationService $componentMarksValidation,
        ResultMarksConfirmationService $marksConfirmation,
        ResultMarksReopenService $marksReopen,
        ResultMarksScopeService $marksScopes,
        ResultPublishService $resultPublisher,
        ResultUnpublishService $resultUnpublisher,
        PublishedResultReadyMarksService $publishedMarks,
        TranscriptAccessService $transcriptAccess,
        StudentGenderService $studentGender
    )
    {
        $this->adminResolver = $adminResolver;
        $this->fourthSubjectResolver = $fourthSubjectResolver;
        $this->marksAuth = $marksAuth;
        $this->marksContext = $marksContext;
        $this->religiousSubjectResolver = $religiousSubjectResolver;
        $this->boardResultCalculator = $boardResultCalculator;
        $this->transcriptResultPresenter = $transcriptResultPresenter;
        $this->resultCalculationInputBuilder = $resultCalculationInputBuilder;
        $this->bulkTranscriptResultBuilder = $bulkTranscriptResultBuilder;
        $this->resultCalculationBatchBuilder = $resultCalculationBatchBuilder;
        $this->tabulationResultPresenter = $tabulationResultPresenter;
        $this->meritPositionService = $meritPositionService;
        $this->draftMarks = $draftMarks;
        $this->componentMarksValidation = $componentMarksValidation;
        $this->marksConfirmation = $marksConfirmation;
        $this->marksReopen = $marksReopen;
        $this->marksScopes = $marksScopes;
        $this->resultPublisher = $resultPublisher;
        $this->resultUnpublisher = $resultUnpublisher;
        $this->publishedMarks = $publishedMarks;
        $this->transcriptAccess = $transcriptAccess;
        $this->studentGender = $studentGender;
    }

    private function classRequiresOptionalGroup(?string $className): bool
    {
        return $this->marksContext->classRequiresOptionalGroup($className);
    }

    private function validatedGenderValue(Request $request): string
    {
        $rawGender = $request->input('gender', 'all');
        if (is_array($rawGender) || is_object($rawGender)) {
            throw ValidationException::withMessages([
                'gender' => ['Invalid gender selection.'],
            ]);
        }

        $gender = (string) $rawGender;
        if ($gender === '') {
            $gender = 'all';
        }

        $allowed = ['all', '1', '2', '3'];
        if (!in_array($gender, $allowed, true)) {
            throw ValidationException::withMessages([
                'gender' => ['Invalid gender selection.'],
            ]);
        }

        return $gender;
    }

    private function normalizeOptionalGroupSelection(Request $request): array
    {
        $classId = (int) $request->input('classId');
        $class = classManage::find($classId);
        if (!$class) {
            throw ValidationException::withMessages([
                'classId' => ['Selected class is invalid.'],
            ]);
        }

        $requiresOptionalGroup = $this->classRequiresOptionalGroup((string) $class->className);
        $optionalGroupRaw = $request->input('optionalGroupId', 0);
        $optionalGroupId = ($optionalGroupRaw === null || $optionalGroupRaw === '' ||
            $optionalGroupRaw === 'all' || (int) $optionalGroupRaw === 0)
            ? null
            : (int) $optionalGroupRaw;

        // For Class 9 and above, null means "All Departments/Groups".
        // For lower classes the field is inactive and any stale value is ignored.
        if (!$requiresOptionalGroup) {
            $optionalGroupId = null;
        }

        return [$class, $requiresOptionalGroup, $optionalGroupId];
    }

    private function validatedSelectionContext(
        $user,
        int $classId,
        ?int $sectionId,
        ?int $optionalGroupId,
        int $sessionId,
        int $subjectId,
        bool $requiresOptionalGroup,
        string $gender = 'all'
    ) {
        $isTeacher = $user && $user->isTeacher();

        if ($isTeacher) {
            $classIds = $this->marksContext->teacherAndAdminClassIds($user);
            if (!in_array($classId, $classIds, true)) {
                return redirect()->route('addMarks')->with('error', 'Unauthorized class selection');
            }
        } else {
            if (!classManage::where('id', $classId)->exists()) {
                return redirect()->route('addMarks')->with('error', 'Invalid class selection');
            }
        }

        if ($sessionId <= 0 || !sessionManage::where('id', $sessionId)->exists()) {
            return redirect()->route('addMarks')->with('error', 'Session not found');
        }

        if ($isTeacher) {
            $allowedSections = collect($this->marksContext->sectionsForContext($user, $classId, $sessionId))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if ($sectionId !== null && !in_array($sectionId, $allowedSections, true)) {
                return redirect()->route('addMarks')->with('error', 'Unauthorized section selection');
            }
        }

        if ($requiresOptionalGroup && $optionalGroupId !== null) {
            if ($isTeacher) {
                $allowedGroups = collect($this->marksContext->groupsForContext($user, $classId, $sectionId, $sessionId))
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (!in_array((int) $optionalGroupId, $allowedGroups, true)) {
                    return redirect()->route('addMarks')->with('error', 'Unauthorized group selection');
                }
            }
        }

        if ($isTeacher) {
            $allowedSubjectIds = $this->marksContext->subjectsForContext($user, $classId, $sectionId, $optionalGroupId, $sessionId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!in_array($subjectId, $allowedSubjectIds, true)) {
                return redirect()->route('addMarks')->with('error', 'Unauthorized subject selection');
            }

            if (!$this->marksAuth->teacherCanSelectGender($user, $classId, $sectionId, $optionalGroupId, $subjectId, $gender, $sessionId)) {
                return redirect()->route('addMarks')->with('error', 'Unauthorized gender selection');
            }
        }

        return true;
    }

    private function isResultPublished(int $examId, int $sessionId, int $classId, $groupId = null): bool
    {
        return ResultPublish::where('examId', $examId)
            ->where('sessionId', $sessionId)
            ->where('classId', $classId)
            ->where('status', ResultPublish::STATUS_PUBLISHED)
            ->where(function($q) use ($groupId){
                $q->whereNull('groupId');
                if($groupId){
                    $q->orWhere('groupId', $groupId);
                }
            })
            ->exists();
    }
    public function addMarks(){
        $user = $this->adminResolver->current();
        $isTeacher = $user && $user->isTeacher();
        $classIds = $isTeacher ? $this->marksAuth->authorizedClassIds($user) : [];

        if ($isTeacher) {
            $classes = classManage::whereIn('id', $classIds)->orderBy('id', 'DESC')->get();
        } else {
            $classes = classManage::orderBy('id', 'DESC')->get();
        }

        $classGroupRequirementMap = $this->marksContext->classGroupRequirementMap($classes);
        $sessions = sessionManage::orderBy('id', 'DESC')->get(['id', 'session']);

        return view('result.add-marks', [
            'isTeacherAdmin' => $isTeacher,
            'classes' => $classes,
            'sessions' => $sessions,
            'classGroupRequirementMap' => $classGroupRequirementMap,
        ]);
    }

    public function marksEntryClasses(Request $request)
    {
        $requestId = (string) Str::uuid();
        $examId = $request->input('exam_id', $request->input('examId'));
        $sessionId = $request->input('session_id', $request->input('sessionId'));
        $adminId = null;
        $status = 500;
        $classCount = 0;
        $exceptionClass = null;
        $exceptionMessage = null;

        try {
            $request->validate([
                'exam_id' => 'nullable|integer',
                'examId' => 'nullable|integer',
                'session_id' => 'nullable|integer',
                'sessionId' => 'nullable|integer',
            ]);

            $examId = (int) ($examId ?? 0);
            $sessionId = (int) ($sessionId ?? 0);
            $user = $this->adminResolver->current();
            $adminId = $user?->id;
            $isTeacher = $user && $user->isTeacher();

            if ($examId <= 0
                || !Exam::whereKey($examId)->exists()
                || ($isTeacher && $sessionId <= 0)
                || ($sessionId > 0 && !sessionManage::whereKey($sessionId)->exists())) {
                $status = 200;

                return response()->json([
                    'classes' => [],
                    'request_id' => $requestId,
                ])->header('X-Request-ID', $requestId);
            }

            $classes = $this->marksContext
                ->classesForContext($user)
                ->map(function ($class) {
                    $supportsGroup = $this->marksContext
                        ->classRequiresOptionalGroup((string) $class->className);

                    return [
                        'id' => (int) $class->id,
                        'name' => (string) $class->className,
                        'requires_department' => $supportsGroup,
                        'requiresOptionalGroup' => $supportsGroup,
                        'supports_group' => $supportsGroup,
                    ];
                })
                ->values();

            $classCount = $classes->count();
            $status = 200;

            return response()->json([
                'classes' => $classes,
                'request_id' => $requestId,
            ])->header('X-Request-ID', $requestId);
        } catch (\Throwable $exception) {
            $exceptionClass = $exception::class;
            $exceptionMessage = $exception->getMessage();
            $status = $exception instanceof ValidationException ? 422 : 500;

            return response()->json([
                'message' => $exceptionMessage,
                'request_id' => $requestId,
            ], $status)->header('X-Request-ID', $requestId);
        } finally {
            Log::info('marks_entry_classes_diagnostic', [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $requestId,
                'admin_id' => $adminId,
                'exam_id' => $examId,
                'session_id' => $sessionId,
                'http_status' => $status,
                'class_count' => $classCount,
                'exception_class' => $exceptionClass,
                'exception_message' => $exceptionMessage,
                'host' => $request->getHost(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    public function marksEntrySections(Request $request)
    {
        $request->validate([
            'class_id' => 'nullable|integer',
            'classId' => 'nullable|integer',
            'session_id' => 'nullable|integer',
            'sessionId' => 'nullable|integer',
        ]);

        $classId = (int) ($request->input('class_id', $request->input('classId', 0)));
        $sessionId = (int) ($request->input('session_id', $request->input('sessionId', 0)));
        $sessionId = $sessionId > 0 ? $sessionId : null;

        if ($classId <= 0) {
            return response()->json(['sections' => []]);
        }

        $sections = $this->marksContext->sectionsForContext($this->adminResolver->current(), $classId, $sessionId);

        return response()->json([
            'sections' => $sections,
        ]);
    }

    public function marksEntryGroups(Request $request)
    {
        $request->validate([
            'class_id' => 'nullable|integer',
            'classId' => 'nullable|integer',
            'section_id' => 'nullable|integer',
            'sectionId' => 'nullable|integer',
            'session_id' => 'nullable|integer',
            'sessionId' => 'nullable|integer',
        ]);

        $classId = (int) ($request->input('class_id', $request->input('classId', 0)));
        $sectionRaw = $request->input('section_id', $request->input('sectionId'));
        $sectionId = ($sectionRaw === null || $sectionRaw === '') ? null : (int) $sectionRaw;
        $sessionId = (int) ($request->input('session_id', $request->input('sessionId', 0)));
        $sessionId = $sessionId > 0 ? $sessionId : null;

        if ($classId <= 0) {
            return response()->json(['groups' => []]);
        }

        $groups = $this->marksContext->groupsForContext($this->adminResolver->current(), $classId, $sectionId, $sessionId);

        return response()->json([
            'groups' => $groups,
        ]);
    }
    public function getMarks(Request $requ){
        $requ->validate([
            'examId' => 'required|integer',
            'classId' => 'required|integer',
            'subjectId' => 'required|integer|exists:subjects,id',
            'sessionId' => 'required|integer',
            'groupId' => 'nullable|integer',
            'optionalGroupId' => 'nullable|integer',
        ]);

        [$class, $requiresOptionalGroup, $optionalGroupId] = $this->normalizeOptionalGroupSelection($requ);
        $gender = $this->validatedGenderValue($requ);

        $subjectId = (int)$requ->subjectId;
        $subject = Subject::find($subjectId);
        if (!$subject) {
            return redirect()->route('addMarks')->with('error', 'Invalid subject selection');
        }
        $exam = Exam::find((int) $requ->examId);
        if (!$exam) {
            return redirect()->route('addMarks')->with('error', 'Invalid exam selection');
        }

        $groupId = $requ->groupId ?: null;
        $sessionId = (int) $requ->sessionId;
        $user = $this->adminResolver->current();
        $isTeacherAdmin = $user && $user->isTeacher();
        $academicContext = [
            'class_id' => (int) $requ->classId,
            'section_id' => $groupId ? (int) $groupId : null,
            'department_id' => $optionalGroupId,
            'session_id' => $sessionId,
        ];

        $selection = $this->validatedSelectionContext(
            $user,
            (int) $requ->classId,
            $groupId ? (int) $groupId : null,
            $optionalGroupId,
            $sessionId,
            $subjectId,
            $requiresOptionalGroup,
            $gender
        );
        if ($selection instanceof \Illuminate\Http\RedirectResponse) {
            return $selection;
        }

        $studentBaseQuery = newAdmission::where('className', (int)$requ->classId)
            ->when($groupId, function($q) use ($groupId){
                return $q->where('sectionName', (int)$groupId);
            })
            ->when($optionalGroupId, function($q) use ($optionalGroupId){
                return $q->where('departmentName', (int)$optionalGroupId);
            });

        $this->religiousSubjectResolver->applyStudentReligiousSubjectFilter($studentBaseQuery, $subject);
        $this->fourthSubjectResolver->applyStudentFourthSubjectFilter($studentBaseQuery, $subject, $academicContext);

        if ($isTeacherAdmin) {
            $authorized = $this->marksAuth->applyTeacherStudentAuthorizationFilters(
                $studentBaseQuery,
                $user,
                (int) $requ->classId,
                $groupId ? (int) $groupId : null,
                $optionalGroupId,
                $subjectId,
                $gender,
                $sessionId
            );

            if (!$authorized) {
                return redirect()->route('addMarks')->with('error', 'No authorized student scope found for this assignment');
            }
        } elseif ($gender !== 'all') {
            $studentBaseQuery->where('gender', $gender);
        }

        $studentSessionValue = (string) $sessionId;
        $sessionData = sessionManage::find($sessionId);
        $sessionText = $sessionData?->session;

        if(!$sessionId){
            return redirect()->route('addMarks')->with('error','Session not found');
        }
        $isFinalPublished = $this->isResultPublished((int)$requ->examId, (int)$sessionId, (int)$requ->classId, $groupId);
        // Fetch students class-wise along with session and section filters
        $studentQuery = clone $studentBaseQuery;
        if($studentSessionValue){
            $studentQuery->where(function ($q) use ($studentSessionValue, $sessionText) {
                $q->where('sessName', $studentSessionValue);
                if (!empty($sessionText)) {
                    $q->orWhere('sessName', (string) $sessionText);
                }
            });
        }

        $studentList = $studentQuery->professionalOrder()->get();

        // Fallback for legacy data where sessName stores id/text inconsistently
        if($studentList->isEmpty() && $studentSessionValue && is_numeric($studentSessionValue)){
            $sessionText = sessionManage::where('id', (int)$studentSessionValue)->value('session');
            if($sessionText){
                $studentList = (clone $studentBaseQuery)
                    ->where('sessName', $sessionText)
                    ->professionalOrder()
                    ->get();
            }
        }

        $studentIds = $studentList->pluck('id')->map(fn ($id) => (int) $id)->all();
        $candidateMarks = Marksheet::query()
            ->where('classId', (int) $requ->classId)
            ->where('examId', (int) $requ->examId)
            ->where('subjectId', $subjectId)
            ->whereIn('studentId', $studentIds)
            ->get()
            ->groupBy(fn (Marksheet $mark) => (int) $mark->studentId);

        $marksByStudent = $studentList->mapWithKeys(function (newAdmission $student) use (
            $candidateMarks,
            $sessionId,
            $sessionText,
            $groupId
        ) {
            $studentSectionId = (int) ($student->sectionName ?? 0);
            $selected = $candidateMarks->get((int) $student->id, collect())
                ->sort(function (Marksheet $left, Marksheet $right) use ($sessionId, $sessionText, $groupId, $studentSectionId) {
                    $rank = static function (Marksheet $mark) use ($sessionId, $sessionText, $groupId, $studentSectionId): array {
                        $sessionRank = (string) $mark->sessionId === (string) $sessionId
                            ? 0
                            : (!empty($sessionText) && (string) $mark->sessionId === (string) $sessionText ? 1 : 2);
                        if ($groupId !== null) {
                            $groupRank = (string) $mark->groupId === (string) $groupId ? 0 : 1;
                        } elseif ($studentSectionId > 0) {
                            $groupRank = (string) $mark->groupId === (string) $studentSectionId
                                ? 0
                                : ($mark->groupId === null || $mark->groupId === '' ? 2 : 1);
                        } else {
                            $groupRank = 0;
                        }

                        return [$sessionRank, $groupRank, -(int) $mark->id];
                    };

                    return $rank($left) <=> $rank($right);
                })
                ->first();

            return [(int) $student->id => $selected];
        });

        $actorIds = $marksByStudent
            ->filter()
            ->flatMap(fn (Marksheet $mark) => [$mark->entered_by ?? $mark->teacher_id, $mark->updated_by])
            ->filter()
            ->unique()
            ->values();
        $actorNames = CultivationAdmin::whereIn('id', $actorIds)->pluck('adminName', 'id');
        $actualSectionIds = $studentList->map(fn (newAdmission $student) => (int) ($student->sectionName ?? 0))
            ->unique()->values();
        $scopeStates = \App\Models\MarksScopeState::query()
            ->where('sessionId', (string) $sessionId)
            ->where('classId', (string) $requ->classId)
            ->where('examId', (string) $requ->examId)
            ->where('subjectId', (string) $subjectId)
            ->where(function ($query) use ($actualSectionIds) {
                if ($actualSectionIds->contains(0)) {
                    $query->whereNull('groupId');
                    $positive = $actualSectionIds->filter(fn ($id) => $id > 0)->all();
                    if ($positive !== []) $query->orWhereIn('groupId', $positive);
                } else {
                    $query->whereIn('groupId', $actualSectionIds->all());
                }
            })
            ->get()
            ->keyBy(fn ($state) => $state->groupId === null ? 'class' : 'section:'.$state->groupId);
        $scopeRevisions = [];
        $scopeStatuses = [];
        foreach ($actualSectionIds as $actualSectionId) {
            $key = $actualSectionId > 0 ? 'section:'.$actualSectionId : 'class';
            $scopeRevisions[$key] = (int) ($scopeStates->get($key)?->revision ?? 1);
            $scopeStatuses[$key] = (string) ($scopeStates->get($key)?->status ?? \App\Models\MarksScopeState::STATUS_DRAFT);
        }
        $hasConfirmedScope = collect($scopeStatuses)->contains(\App\Models\MarksScopeState::STATUS_CONFIRMED);
        $singleScopeRevision = count($scopeRevisions) === 1 ? reset($scopeRevisions) : null;

        return view('result.get-marks',[
            'studentList'=>$studentList,
            'marksByStudent'=>$marksByStudent,
            'actorNames'=>$actorNames,
            'classData'=>$class,
            'sectionData'=>$groupId ? sectionManage::find((int) $groupId) : null,
            'optionalGroupData'=>$optionalGroupId ? Department::find((int) $optionalGroupId) : null,
            'sessionData'=>$sessionData,
            'examData'=>$exam,
            'subjectData'=>$subject,
            'groupId'=>$groupId,
            'optionalGroupId'=>$optionalGroupId,
            'gender'=>$gender,
            'classRequiresOptionalGroup'=>$requiresOptionalGroup,
            'classId'=>$requ->classId,
            'sessionId'=>$sessionId,
            'examId'=>$requ->examId,
            'subjectId'=>$requ->subjectId,
            'isFinalPublished'=>$isFinalPublished,
            'isTeacherAdmin'=>$isTeacherAdmin,
            'scopeRevisions'=>$scopeRevisions,
            'scopeStatuses'=>$scopeStatuses,
            'singleScopeRevision'=>$singleScopeRevision,
            'hasConfirmedScope'=>$hasConfirmedScope,
            'canReopenMarks'=>$user && !$user->isTeacher() && !$user->isCash(),
        ]);
    }

    public function confirmMarks(Request $requ){
        // Deprecated compatibility endpoint: despite its historical name, this action saves Draft marks.
        return $this->saveDraftMarks($requ, true);

    }

    public function saveDraftMarks(Request $request, bool $legacy = false)
    {
        $this->validateDraftSubmissionPayload($request);
        $actor = $this->adminResolver->current();
        if (!$actor) abort(403);

        try {
            $result = $this->draftMarks->save($request->all(), $actor, $request->ip(), $legacy);
            $message = $result['changed_student_count'] > 0
                ? 'Draft marks saved successfully (Changed: '.$result['changed_student_count'].', Unchanged: '.$result['unchanged_student_count'].').'
                : 'Draft marks are already up to date.';
            return $request->expectsJson()
                ? response()->json($result)
                : redirect()->route('addMarks')->with('success', $message);
        } catch (ResultLifecycleException $exception) {
            return $this->lifecycleFailure($request, $exception);
        }
    }

    public function confirmSubjectMarks(Request $request)
    {
        $request->validate([
            'examId' => 'required|integer',
            'classId' => 'required|integer',
            'subjectId' => 'required|integer',
            'sessionId' => 'required|integer',
            'groupId' => 'nullable|integer',
            'optionalGroupId' => 'nullable|integer',
            'scope_revision' => 'required|integer|min:1',
            'submission_action' => ['nullable', Rule::in(['confirm', 'confirm_with_blanks', 'draft'])],
            'confirm_blank_marks' => 'nullable|boolean',
            'studentId' => 'nullable|array|min:1|max:500',
            'studentId.*' => 'nullable|integer|distinct',
            'cqMarks' => 'nullable|array|max:500',
            'mcqMarks' => 'nullable|array|max:500',
            'practical' => 'nullable|array|max:500',
            'scope_revisions' => 'nullable|array',
            'scope_revisions.*' => 'integer|min:1',
        ]);
        $actor = $this->adminResolver->current();
        if (!$actor) abort(403);
        try {
            $action = (string) ($request->input('submission_action') ?: 'confirm');
            if ($action === 'draft') {
                $this->validateDraftSubmissionPayload($request);
                $result = $this->draftMarks->save($request->all(), $actor, $request->ip());

                if ($request->expectsJson()) {
                    return response()->json($result);
                }

                $message = $result['changed_student_count'] > 0
                    ? 'Draft marks saved successfully (Changed: '.$result['changed_student_count'].', Unchanged: '.$result['unchanged_student_count'].').'
                    : 'Draft marks are already up to date.';

                return redirect()->route('addMarks')->with('success', $message);
            }

            // Keep confirm actions aligned with current form state by persisting submitted rows first.
            if ($request->filled('studentId')) {
                $this->validateDraftSubmissionPayload($request);
                $draftResult = $this->draftMarks->save($request->all(), $actor, $request->ip());
                $scopeRevisions = (array) ($draftResult['current_revisions'] ?? []);
                if ($scopeRevisions !== []) {
                    $singleScopeRevision = count($scopeRevisions) === 1 ? reset($scopeRevisions) : null;
                    if ($singleScopeRevision !== false && $singleScopeRevision !== null) {
                        $request->merge(['scope_revision' => (int) $singleScopeRevision]);
                    }
                    $request->merge(['scope_revisions' => $scopeRevisions]);
                }
            }

            $confirmWithBlanks = $action === 'confirm_with_blanks'
                || (string) $request->input('confirm_blank_marks') === '1';

            $payload = $request->all();
            $payload['confirm_blank_marks'] = $confirmWithBlanks ? 1 : 0;

            $result = $this->marksConfirmation->confirm($payload, $actor, $request->ip());
            return $request->expectsJson()
                ? response()->json($result)
                : redirect()->route('addMarks')->with('success', 'Subject marks confirmed successfully.');
        } catch (ResultLifecycleException $exception) {
            return $this->lifecycleFailure($request, $exception);
        }
    }

    private function validateDraftSubmissionPayload(Request $request): void
    {
        $subjectForValidation = Subject::find((int) $request->input('subjectId'));

        $request->validate(array_merge([
            'examId' => 'required|integer',
            'classId' => 'required|integer',
            'subjectId' => 'required|integer',
            'sessionId' => 'required|integer',
            'groupId' => 'nullable|integer',
            'optionalGroupId' => 'nullable|integer',
            'gender' => 'nullable|string|in:all,1,2,3',
            'studentId' => 'required|array|min:1|max:500',
            'studentId.*' => 'required|integer|distinct',
            'cqMarks' => 'nullable|array|max:500',
            'mcqMarks' => 'nullable|array|max:500',
            'practical' => 'nullable|array|max:500',
            'scope_revision' => 'nullable|integer|min:1',
            'scope_revisions' => 'nullable|array',
            'scope_revisions.*' => 'integer|min:1',
        ], $this->componentMarksValidation->componentRules($subjectForValidation)));
    }

    public function reopenSubjectMarks(Request $request)
    {
        $request->validate([
            'examId' => 'required|integer',
            'classId' => 'required|integer',
            'subjectId' => 'required|integer',
            'sessionId' => 'required|integer',
            'groupId' => 'nullable|integer',
            'scope_revision' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);
        $actor = $this->adminResolver->current();
        if (!$actor) abort(403);
        try {
            $result = $this->marksReopen->reopen($request->all(), $actor, $request->ip());
            return $request->expectsJson()
                ? response()->json($result)
                : redirect()->route('addMarks')->with('success', 'Subject marks reopened as Draft.');
        } catch (ResultLifecycleException $exception) {
            return $this->lifecycleFailure($request, $exception);
        }
    }

    private function lifecycleFailure(Request $request, ResultLifecycleException $exception)
    {
        $this->logLifecycleFailure($request, $exception->failure, $exception->httpStatus);
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => $exception->failure,
                'message' => $exception->getMessage(),
                'details' => $exception->details,
            ], $exception->httpStatus);
        }
        return redirect()->route('addMarks')->with('error', $exception->getMessage());
    }

    public function marksEntrySubjects(Request $request)
    {
        $request->validate([
            'class_id' => 'nullable|integer',
            'classId' => 'nullable|integer',
            'section_id' => 'nullable|integer',
            'sectionId' => 'nullable|integer',
            'optional_group_id' => 'nullable|integer',
            'optionalGroupId' => 'nullable|integer',
            'session_id' => 'nullable|integer',
            'sessionId' => 'nullable|integer',
        ]);

        $classId = (int) ($request->input('class_id', $request->input('classId', 0)));
        if ($classId <= 0) {
            return response()->json([], 200);
        }

        $sectionRaw = $request->input('section_id', $request->input('sectionId'));
        $groupRaw = $request->input('optional_group_id', $request->input('optionalGroupId'));
        $sessionRaw = $request->input('session_id', $request->input('sessionId'));

        $sectionId = ($sectionRaw === null || $sectionRaw === '') ? null : (int) $sectionRaw;
        $optionalGroupId = ($groupRaw === null || $groupRaw === '' || $groupRaw === 'all' || (int) $groupRaw === 0)
            ? null
            : (int) $groupRaw;
        $sessionId = ($sessionRaw === null || $sessionRaw === '') ? null : (int) $sessionRaw;

        $user = $this->adminResolver->current();

        $subjects = $this->marksContext
            ->subjectsForContext($user, $classId, $sectionId, $optionalGroupId, $sessionId)
            ->map(function ($subject) {
                return [
                    'id' => (int) $subject->id,
                    'subjectName' => (string) $subject->subjectName,
                ];
            })
            ->values();

        return response()->json($subjects);
    }

    public function createMarksheet(){
        return view('result.createMarksheet');
    }

    public function finalPublishIndex(){
        $examList = Exam::orderBy('id','DESC')->get();
        $sessionList = sessionManage::orderBy('id','DESC')->get();
        $classList = \App\Models\classManage::orderBy('id','DESC')->get();
        $sectionList = \App\Models\sectionManage::orderBy('id','DESC')->get();
        $publishedList = ResultPublish::orderByDesc('updated_at')->get();

        $examNames = Exam::orderBy('id','DESC')->pluck('examName','id');
        $sessionNames = sessionManage::orderBy('id','DESC')->pluck('session','id');
        $classNames = \App\Models\classManage::orderBy('id','DESC')->pluck('className','id');
        $sectionNames = \App\Models\sectionManage::orderBy('id','DESC')->pluck('section','id');

        return view('result.final-publish', compact(
            'examList',
            'sessionList',
            'classList',
            'sectionList',
            'publishedList',
            'examNames',
            'sessionNames',
            'classNames',
            'sectionNames'
        ));
    }

    public function finalPublishStore(Request $requ){
        $requ->validate(['action' => 'required|in:publish,unpublish']);
        return $requ->input('action') === 'publish'
            ? $this->publishResult($requ)
            : $this->unpublishResult($requ);
    }

    public function publishResult(Request $request)
    {
        $this->validatePublicationRequest($request, false);
        $actor = $this->adminResolver->current();
        if (!$actor) abort(403);
        try {
            $result = $this->resultPublisher->publish($request->all(), $actor, $request->ip());
            return $request->expectsJson()
                ? response()->json($result)
                : back()->with('success', $result['idempotent']
                    ? 'Result scope was already Published.'
                    : 'Final result published successfully.');
        } catch (ResultPublicationException $exception) {
            return $this->publicationFailure($request, $exception);
        }
    }

    public function unpublishResult(Request $request)
    {
        $this->validatePublicationRequest($request, true);
        $actor = $this->adminResolver->current();
        if (!$actor) abort(403);
        try {
            $result = $this->resultUnpublisher->unpublish($request->all(), $actor, $request->ip());
            return $request->expectsJson()
                ? response()->json($result)
                : back()->with('success', $result['idempotent']
                    ? 'Result scope was already Unpublished.'
                    : 'Final result unpublished successfully.');
        } catch (ResultPublicationException $exception) {
            return $this->publicationFailure($request, $exception);
        }
    }

    private function validatePublicationRequest(Request $request, bool $unpublish): void
    {
        $request->validate([
            'examId' => 'required|integer',
            'sessionId' => 'required|integer',
            'classId' => 'required',
            'classIds' => 'nullable|array|max:100',
            'classIds.*' => 'integer|min:1|distinct',
            'groupId' => 'nullable|integer',
            'publication_revision' => 'nullable|integer|min:1',
            'publication_revisions' => 'nullable|array',
            'publication_revisions.*' => 'integer|min:1',
            'exact_scope' => 'nullable|boolean',
            'reason' => $unpublish ? 'required|string|max:500' : 'nullable|string|max:500',
            'confirm_anyway' => $unpublish ? 'nullable' : 'nullable|boolean',
        ]);
        $classId = $request->input('classId');
        if ($classId !== 'all' && (!is_numeric($classId) || (int) $classId <= 0)) {
            throw ValidationException::withMessages(['classId' => ['Invalid class selection.']]);
        }
    }

    private function publicationFailure(Request $request, ResultPublicationException $exception)
    {
        $this->logLifecycleFailure($request, $exception->failure, $exception->httpStatus);
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => $exception->failure,
                'message' => $exception->getMessage(),
                'details' => $exception->details,
            ], $exception->httpStatus);
        }
        return back()->withInput()->with('error', $exception->getMessage())
            ->with('publication_failure', $exception->failure)
            ->with('publication_errors', $exception->details);
    }

    private function logLifecycleFailure(Request $request, string $category, int $status): void
    {
        $actor = $this->adminResolver->current();
        $context = [
            'action' => (string) ($request->route()?->getName() ?? 'result.lifecycle'),
            'actor_id' => $actor?->id,
            'actor_role' => $actor?->user_type_label,
            'scope' => array_filter([
                'sessionId' => $request->input('sessionId'),
                'classId' => $request->input('classId'),
                'groupId' => $request->input('groupId'),
                'examId' => $request->input('examId'),
                'subjectId' => $request->input('subjectId'),
            ], fn ($value) => $value !== null && $value !== ''),
            'error_category' => $category,
            'revision' => $request->input('scope_revision', $request->input('publication_revision')),
            'http_status' => $status,
        ];
        if ($status === 403) {
            Log::warning('Result lifecycle authorization denied.', $context);
        } elseif ($status === 409) {
            Log::notice('Result lifecycle concurrency conflict.', $context);
        } else {
            Log::info('Result lifecycle operation rejected.', $context);
        }
    }

    public function allMarksheet(Request $request)
    {
        $examId = $request->integer('examId');
        $classId = $request->integer('classId');
        $sessionId = $request->integer('sessionId');
        $sectionId = $request->filled('sectionId') ? $request->integer('sectionId') : null;
        $departmentId = $request->filled('departmentId') ? $request->integer('departmentId') : null;
        $gender = $this->studentGender->normalize($request->input('gender', 'all'));

        if ($examId > 0 && $classId > 0 && $sessionId > 0) {
            try {
                return $this->centralizedTabulation($request, $examId, $classId, $sessionId, $sectionId, $departmentId, $gender);
            } catch (\Throwable $exception) {
                Log::error('Centralized marksheet calculation failed.', [
                    'exam_id'=>$examId,'class_id'=>$classId,'session_id'=>$sessionId,
                    'section_id'=>$sectionId,'department_id'=>$departmentId,
                    'exception'=>get_class($exception),
                ]);
                return back()->with('error', 'Result calculation failed safely. No partial result was rendered.');
            }
        }

        $viewName = $request->routeIs('atGlanceResult') ? 'result.atGlanceResult' : 'result.allMarksheet';
        return view($viewName, [
            'subjects'=>collect(),'passResults'=>[],'failResults'=>[],'incompleteResults'=>[],
            'passResultsCompact'=>[],'failResultsCompact'=>[],'incompleteResultsCompact'=>[],
            'compactMode'=>(bool)$request->get('compact'),'examId'=>$examId ?: null,
            'classId'=>$classId ?: null,'sessionId'=>$sessionId ?: null,'sectionId'=>$sectionId,
            'departmentId'=>$departmentId,'studentsLoaded'=>false,'exam'=>null,
            'gender'=>$gender,
            'usingCentralizedTabulation'=>true,
        ] + $this->resultPresentationContext($examId ?: null, $classId ?: null, $sessionId ?: null, $sectionId, $departmentId, null, $gender));
    }

    private function centralizedTabulation(Request $request, int $examId, int $classId, int $sessionId, ?int $sectionId, ?int $departmentId, string $gender)
    {
        $batch = $this->resultCalculationBatchBuilder->buildForGender($examId, $classId, $sessionId, $sectionId, $departmentId, $gender);
        $presented = $this->tabulationResultPresenter->present($batch['entries']);
        $passResults = array_values(array_filter($presented['sections']['Complete'], fn ($row) => $row['status'] === 'Pass'));
        $failResults = array_values(array_filter($presented['sections']['Complete'], fn ($row) => $row['status'] === 'Fail'));
        $incompleteResults = $presented['sections']['Incomplete'];
        $absentResults = $presented['sections']['Absent'];
        $compactMode = (bool) $request->get('compact');
        $viewName = $request->routeIs('atGlanceResult') ? 'result.atGlanceResult' : 'result.allMarksheet';
        return view($viewName, [
            'subjects' => $presented['subjects'], 'passResults' => $passResults, 'failResults' => $failResults,
            'incompleteResults' => $incompleteResults, 'passResultsCompact' => $passResults,
            'failResultsCompact' => $failResults, 'incompleteResultsCompact' => $incompleteResults,
            'absentResults' => $absentResults,
            'compactMode' => $compactMode, 'examId' => $examId, 'classId' => $classId, 'sessionId' => $sessionId,
            'sectionId' => $sectionId, 'departmentId' => $departmentId, 'studentsLoaded' => true,
            'gender' => $gender,
            'exam' => $batch['exam'], 'usingCentralizedTabulation' => true,
            'tabulationRows' => $presented['rows'], 'tabulationSections' => $presented['sections'],
            'glanceRows' => $presented['glanceRows'], 'reportSections' => $presented['reportSections'],
            'failedGroups' => $presented['failedGroups'],
            'failureBuckets' => $presented['failureBuckets'],
            'tabulationPages' => $presented['tabulationPages'],
            'subjectWisePages' => $presented['subjectWisePages'], 'glancePages' => $presented['glancePages'],
        ] + $this->resultPresentationContext($examId, $classId, $sessionId, $sectionId, $departmentId, $batch['exam'], $gender));
    }

    private function centralizedSummary(Request $request)
    {
        $examId = (int) $request->get('examId'); $classId = (int) $request->get('classId');
        $sessionId = (int) $request->get('sessionId');
        $sectionId = $request->get('sectionId') ? (int) $request->get('sectionId') : null;
        $departmentId = $request->get('departmentId') ? (int) $request->get('departmentId') : null;
        $gender = $this->studentGender->normalize($request->input('gender', 'all'));
        $batch = $this->resultCalculationBatchBuilder->buildForGender($examId, $classId, $sessionId, $sectionId, $departmentId, $gender);
        $presented = $this->tabulationResultPresenter->present($batch['entries']);
        $summary = $this->tabulationResultPresenter->summarize($presented['rows'], $presented['subjects']);
        return view('result.result-summary', [
            'examId' => $examId, 'classId' => $classId, 'sessionId' => $sessionId, 'sectionId' => $sectionId,
            'departmentId' => $departmentId, 'studentsLoaded' => true,
            'gender' => $gender,
            'overallSummary' => $summary['overallSummary'], 'subjectStats' => $summary['subjectStats'],
            'failureBuckets' => $summary['failureBuckets'], 'gpaDistribution' => $summary['gpaDistribution'],
            'gradeDistribution' => $summary['gradeDistribution'], 'hasData' => count($presented['rows']) > 0,
            'summaryView' => $summary,
            'usingCentralizedSummary' => true,
        ] + $this->resultPresentationContext($examId, $classId, $sessionId, $sectionId, $departmentId, $batch['exam'], $gender));
    }

    public function resultSummary(Request $request)
    {
        if ($request->filled('examId') && $request->filled('classId') && $request->filled('sessionId')) {
            try {
                return $this->centralizedSummary($request);
            } catch (\Throwable $exception) {
                Log::error('Centralized result summary failed.', [
                    'exam_id'=>$request->integer('examId'),'class_id'=>$request->integer('classId'),
                    'session_id'=>$request->integer('sessionId'),'exception'=>get_class($exception),
                ]);
                return back()->with('error', 'Result summary calculation failed safely.');
            }
        }

        return view('result.result-summary', [
            'examId'=>$request->get('examId'),'classId'=>$request->get('classId'),
            'sessionId'=>$request->get('sessionId'),'sectionId'=>$request->get('sectionId'),
            'departmentId'=>$request->get('departmentId'),'studentsLoaded'=>false,
            'gender'=>$this->studentGender->normalize($request->input('gender', 'all')),
            'overallSummary'=>['total'=>0,'present'=>0,'absent'=>0,'pass'=>0,'fail'=>0,'incomplete'=>0,'passRate'=>0],
            'subjectStats'=>[],'failureBuckets'=>[],'gpaDistribution'=>[],'gradeDistribution'=>[],
            'hasData'=>false,'usingCentralizedSummary'=>true,
            'summaryView'=>['subjectPages'=>[[]],'failureSummaryLine'=>'No failed-subject bucket found.'],
        ] + $this->resultPresentationContext(
            $request->integer('examId') ?: null,
            $request->integer('classId') ?: null,
            $request->integer('sessionId') ?: null,
            $request->integer('sectionId') ?: null,
            $request->integer('departmentId') ?: null,
            null,
            $this->studentGender->normalize($request->input('gender', 'all')),
        ));
    }

    private function resultPresentationContext(
        ?int $examId,
        ?int $classId,
        ?int $sessionId,
        ?int $sectionId,
        ?int $departmentId,
        ?Exam $exam = null,
        string $gender = StudentGenderService::ALL,
    ): array {
        $serverConfig = ServerConfig::orderBy('id', 'DESC')->first();
        $class = $classId ? classManage::find($classId) : null;
        $session = $sessionId ? sessionManage::find($sessionId) : null;
        $section = $sectionId ? sectionManage::find($sectionId) : null;
        $department = $departmentId ? Department::find($departmentId) : null;
        $exam ??= $examId ? Exam::find($examId) : null;
        return [
            'filterOptions' => [
                'exams' => Exam::orderBy('id', 'DESC')->get(),
                'classes' => classManage::orderBy('id')->get(),
                'sessions' => sessionManage::orderBy('id', 'DESC')->get(),
                'sections' => sectionManage::orderBy('id')->get(),
                'departments' => Department::orderBy('id')->get(),
            ],
            'scopeLabels' => [
                'exam' => $exam?->examName ?? '-',
                'class' => $class?->className ?? '-',
                'session' => $session?->session ?? '-',
                'section' => $section?->section ?? 'N/A',
                'department' => $department?->departmentName ?? 'All',
                'gender' => $this->studentGender->label($gender),
            ],
            'resultHeader' => [
                'examName' => $exam?->examName ?? '-',
                'className' => $class?->className ?? '-',
                'sessionName' => $session?->session ?? '-',
                'sectionName' => $section?->section ?? '-',
                'departmentName' => $department?->departmentName ?? 'All Departments',
                'genderName' => $this->studentGender->label($gender),
                'printedAt' => now()->format('d M Y, h:i A'),
            ],
            'preloadedInstituteConfig' => $serverConfig,
            'instituteConfigPreloaded' => true,
            'signatureView' => [
                'roles' => ['Class Teacher', 'Principal/Head Master'],
                'principalSignatureUrl' => empty($serverConfig?->principalSign) ? null : (
                    preg_match('~^https?://~i', $serverConfig->principalSign)
                        ? $serverConfig->principalSign
                        : asset('public/upload/image/cultivation/'.ltrim($serverConfig->principalSign, '/'))
                ),
            ],
            'preloadedCultivationAdmin' => $this->adminResolver->current(),
            'cultivationAdminPreloaded' => true,
        ];
    }

    private function scopedTranscriptMarks(newAdmission $student, int $examId)
    {
        $sessionRaw = (string) ($student->sessName ?? '');
        $sessionAlternates = array_values(array_unique(array_filter([
            $sessionRaw,
            is_numeric($sessionRaw)
                ? (string) (sessionManage::find((int) $sessionRaw)?->session ?? '')
                : '',
        ])));
        $classId = (int) ($student->className ?? 0);
        $sectionId = is_numeric($student->sectionName ?? null) ? (int) $student->sectionName : null;

        return Marksheet::query()
            ->where('studentId', (int) $student->id)
            ->where('examId', $examId)
            ->when($classId > 0, fn ($query) => $query->where('classId', $classId))
            ->when($sessionAlternates !== [], function ($query) use ($sessionAlternates) {
                $query->where(function ($sessionQuery) use ($sessionAlternates) {
                    $sessionQuery->whereIn('sessionId', $sessionAlternates)
                        ->orWhereNull('sessionId')
                        ->orWhere('sessionId', '');
                });
            })
            ->when($sectionId !== null, function ($query) use ($sectionId) {
                $query->where(function ($groupQuery) use ($sectionId) {
                    $groupQuery->where('groupId', $sectionId)
                        ->orWhereNull('groupId')
                        ->orWhere('groupId', '');
                });
            })
            ->orderBy('subjectId')
            ->get();
    }

    public function generateMarksheet(Request $request)
    {
        $examId = $request->integer('examId');
        $studentIdInput = trim((string)($request->studentId ?? ''));
        $stdIdInput = trim((string)($request->stdId ?? $request->studentId ?? $request->id ?? ''));

        $studentQuery = newAdmission::query();
        if ($studentIdInput !== '' && ctype_digit($studentIdInput)) {
            $studentQuery->whereKey((int)$studentIdInput);
        } elseif ($stdIdInput !== '') {
            $studentQuery->where(function ($query) use ($stdIdInput) {
                $query->where('stdId',$stdIdInput)->orWhereRaw('TRIM(stdId) = ?',[$stdIdInput]);
                if (ctype_digit($stdIdInput)) $query->orWhere('id', (int) $stdIdInput);
            });
        } else {
            $studentQuery->whereRaw('1 = 0');
        }

        $student = $studentQuery->with(['marksheet'=>fn ($query) =>
            $query->where('examId',$examId)->orderBy('subjectId')
        ])->first();
        if (!$student) return back()->with('error','Student not found.');
        if ($examId <= 0) return back()->with('error','Selected exam is required.');

        $exam = Exam::findOrFail($examId);
        $this->transcriptAccess->authorize($this->adminResolver->current(), $student, $exam);

        try {
            $scopedMarks = $this->scopedTranscriptMarks($student, (int) $exam->id);
            $sessionIdForMarks = is_numeric($student->sessName ?? null)
                ? (int) $student->sessName
                : (int) (sessionManage::where('session', (string) ($student->sessName ?? ''))->value('id') ?? 0);
            if ($sessionIdForMarks > 0) {
                $scopedMarks = $this->publishedMarks->filter(
                    $scopedMarks,
                    (int) $exam->id,
                    $sessionIdForMarks,
                    (int) $student->className,
                    is_numeric($student->sectionName ?? null) ? (int) $student->sectionName : null,
                );
            }
            $student->setRelation('marksheet', $scopedMarks);
            $subjects = $this->resultCalculationInputBuilder->subjectsForStudent($student);
            $meritBatch = null;
            $calculated = null;
            $scopeSectionId = is_numeric($student->sectionName ?? null) ? (int) $student->sectionName : null;
            $scopeDepartmentId = is_numeric($student->departmentName ?? null) ? (int) $student->departmentName : null;
            if ($sessionIdForMarks > 0) {
                $meritBatch = $this->resultCalculationBatchBuilder->build(
                    (int) $exam->id,
                    (int) $student->className,
                    $sessionIdForMarks,
                    $scopeSectionId,
                    $scopeDepartmentId,
                );
                $entry = $meritBatch['entries'][(int) $student->id] ?? null;
                if ($entry !== null) {
                    $subjects = $entry['subjects'];
                    $calculated = $entry['result'];
                }
            }
            if ($calculated === null) {
                $calculated = $this->boardResultCalculator->calculate($student, $exam, $scopedMarks, $subjects);
            }
            $transcriptResult = $this->transcriptResultPresenter->present(
                $calculated, $subjects, $scopedMarks
            );
            $meritRank = null;
            $sessionId = is_numeric($student->sessName ?? null)
                ? (int) $student->sessName
                : (int) (sessionManage::where('session', (string) ($student->sessName ?? ''))->value('id') ?? 0);
            if ($sessionId > 0) {
                $meritBatch ??= $this->resultCalculationBatchBuilder->build(
                    (int) $exam->id,
                    (int) $student->className,
                    $sessionId,
                    $scopeSectionId,
                    $scopeDepartmentId,
                );
                $meritRank = $this->meritPositionService->positions($meritBatch['entries'])[(int) $student->id] ?? null;
            }
            $transcriptResult['curriculumStatus'] = [
                'configured' => (bool) ($student->curriculum_main_subjects_configured ?? false),
                'reason' => (bool) ($student->curriculum_main_subjects_configured ?? false)
                    ? null
                    : 'Curriculum main subjects are not configured for this scope.',
                'scope' => [
                    'sessionId' => $student->sessName ?? null,
                    'classId' => $student->className ?? null,
                    'sectionId' => $student->sectionName ?? null,
                    'departmentId' => $student->departmentName ?? null,
                ],
            ];
            $serverConfig = ServerConfig::first();
            $sessionName = is_numeric($student->sessName)
                ? (sessionManage::find($student->sessName)?->session ?? '-')
                : ((string) ($student->sessName ?? '-') ?: '-');
            $className = is_numeric($student->className)
                ? (classManage::find($student->className)?->className ?? '-')
                : ((string) ($student->className ?? '-') ?: '-');
            $sectionName = is_numeric($student->sectionName)
                ? (sectionManage::find($student->sectionName)?->section ?? '-')
                : ((string) ($student->sectionName ?? '-') ?: '-');
            $departmentName = is_numeric($student->departmentName)
                ? (Department::find($student->departmentName)?->departmentName ?? '-')
                : ((string) ($student->departmentName ?? '-') ?: '-');
            $publicAsset = fn (?string $path, string $directory): ?string =>
                empty($path) ? null : (
                    preg_match('~^https?://~i', $path)
                        ? $path
                        : asset('public/'.$directory.'/'.ltrim($path, '/'))
                );
            $transcriptView = [
                'studentId' => $student->stdId ?? $student->id,
                'studentName' => trim(($student->fullName ?? '').' '.($student->sureName ?? '')),
                'fatherName' => $student->fatherName ?? $student->father ?? '',
                'motherName' => $student->motherName ?? $student->mother ?? '',
                'rollNumber' => is_numeric($student->rollNumber)
                    ? str_pad((string) ((int) $student->rollNumber), 2, '0', STR_PAD_LEFT)
                    : (string) ($student->rollNumber ?? ''),
                'sessionName' => $sessionName,
                'className' => $className,
                'sectionName' => $sectionName,
                'departmentName' => $departmentName,
                'examName' => (string) $exam->examName,
                'meritRank' => $meritRank,
                'title' => $serverConfig?->transcript_title ?? 'Academic Transcript',
                'institute' => [
                    'name' => $serverConfig?->instituteName ?? 'Jahanara Ayub Academy',
                    'address' => $serverConfig?->address ?? '',
                    'mobile' => $serverConfig?->officeMobile ?? '',
                    'email' => $serverConfig?->officeEmail ?? '',
                    'logoUrl' => $publicAsset($serverConfig?->logo, 'upload/image/cultivation'),
                ],
                'principalSignatureUrl' => $publicAsset($serverConfig?->principalSign, 'upload/image/cultivation'),
                'gradeLegend' => GradeList::orderBy('gradePoint', 'DESC')->get()
                    ->map(fn ($grade) => [
                        'range' => $grade->minMark.' - '.$grade->maxMark,
                        'grade' => $grade->gradeName,
                        'point' => $grade->gradePoint,
                    ])->all(),
            ];
        } catch (\Throwable $exception) {
            Log::error('Centralized single marksheet calculation failed.', [
                'student_id'=>(int)$student->id,'exam_id'=>$examId,'exception'=>get_class($exception),
            ]);
            return back()->with('error','Result calculation failed safely.');
        }

        return view('result.marksheetGenerate',[
            'studentDetails'=>$student,'examId'=>$examId,'config'=>$serverConfig,
            'maxMarkedSubjects'=>count($calculated->subjectResults),
            'studentMarkedSubjects'=>count($calculated->subjectResults),
            'hideForMaxRule'=>$calculated->status === 'Incomplete','meritRank'=>$meritRank,
            'usingNewResultEngine'=>true,'transcriptResult'=>$transcriptResult,
            'transcriptView'=>$transcriptView,
            'preloadedCultivationAdmin'=>$this->adminResolver->current(),
            'cultivationAdminPreloaded'=>true,
        ]);
    }
    public function internalResult(){
        return view('frontend.result.internalResult');
    }


    public function individualResult(){
        return view('frontend.result.individualResult');
    }
    //front web site end

    // Transcript generation: class/section student list and open per-student transcripts
    public function transcriptList(Request $request)
    {
        $examId    = $request->get('examId');
        $classId   = $request->get('classId');
        $sessionId = $request->get('sessionId');
        $sectionId = $request->get('sectionId');
        $departmentId = $request->get('departmentId');
        $gender = $this->studentGender->normalize($request->get('gender', StudentGenderService::ALL));

        $students = collect();
        $studentsLoaded = false;
        if ($classId) {
            $q = newAdmission::query()->where('className', (int)$classId);
            if ($sessionId) { $q->where('sessName', (int)$sessionId); }
            if ($sectionId) { $q->where('sectionName', (int)$sectionId); }
            if ($departmentId) { $q->where('departmentName', (int)$departmentId); }
            $students = $this->studentGender->apply($q, $gender)->professionalOrder()->get();
            $studentsLoaded = true;
        }

        $genderOptions = [
            StudentGenderService::ALL => 'All',
            StudentGenderService::MALE => 'Male',
            StudentGenderService::FEMALE => 'Female',
        ];
        if ($this->studentGender->apply(newAdmission::query(), StudentGenderService::OTHER)->exists()) {
            $genderOptions[StudentGenderService::OTHER] = 'Other/Unknown';
        }

        return view('result.transcriptList', [
            'examId' => $examId,
            'classId' => $classId,
            'sessionId' => $sessionId,
            'sectionId' => $sectionId,
            'departmentId' => $departmentId,
            'gender' => $gender,
            'genderLabel' => $this->studentGender->label($gender),
            'genderOptions' => $genderOptions,
            'students' => $students,
            'studentsLoaded' => $studentsLoaded,
        ]);
    }

    public function bulkTranscriptPdf(Request $request)
    {
        $request->validate([
            'examId' => 'required|integer',
            'classId' => 'required|integer',
            'sessionId' => 'nullable|integer',
            'sectionId' => 'nullable|integer',
            'departmentId' => 'nullable|integer',
            'gender' => 'nullable|string',
            'stdIds' => 'required|array|min:1',
            'stdIds.*' => 'required',
        ]);

        $examId = (int)$request->input('examId');
        $classId = (int)$request->input('classId');
        $sessionId = $request->filled('sessionId') ? (int)$request->input('sessionId') : null;
        $sectionId = $request->filled('sectionId') ? (int)$request->input('sectionId') : null;
        $departmentId = $request->filled('departmentId') ? (int)$request->input('departmentId') : null;
        $gender = $this->studentGender->normalize($request->input('gender', StudentGenderService::ALL));
        $rawIds = collect($request->input('stdIds', []))
            ->map(fn($v) => trim((string)$v))
            ->filter()
            ->unique()
            ->values();

        if ($rawIds->isEmpty()) {
            return back()->with('error', 'No students selected for PDF.');
        }

        $numericIds = $rawIds->filter(fn($v) => ctype_digit($v))->map(fn($v) => (int)$v)->values();

        $studentQuery = newAdmission::query()
            ->where('className', $classId)
            ->when($sessionId, fn ($query) => $query->where('sessName', $sessionId))
            ->when($sectionId, fn ($query) => $query->where('sectionName', $sectionId))
            ->when($departmentId, fn ($query) => $query->where('departmentName', $departmentId));
        $students = $this->studentGender->apply($studentQuery, $gender)
            ->where(function($q) use ($rawIds, $numericIds){
                $q->whereIn('stdId', $rawIds);
                if ($numericIds->isNotEmpty()) {
                    $q->orWhereIn('id', $numericIds);
                }
            })
            ->professionalOrder()
            ->get();

        if ($students->count() !== $rawIds->count()) {
            abort(403, 'One or more selected students are outside the requested transcript scope.');
        }

        if ($students->isEmpty()) {
            return back()->with('error', 'No matching students found for selected IDs.');
        }

        $exam = Exam::find($examId);
        if (!$exam) {
            return back()->with('error', 'Selected exam not found.');
        }

        $students->load(['marksheet' => function($q) use ($examId){
            $q->where('examId', $examId)->orderBy('subjectId', 'ASC');
        }]);
        $students->each(function (newAdmission $student) use ($examId) {
            $student->setRelation('marksheet', $this->scopedTranscriptMarks($student, $examId));
        });

        try {
            $gradeRows = GradeList::orderBy('maxMark', 'DESC')->orderBy('gradePoint', 'DESC')->get();
            $transcripts = $this->bulkTranscriptResultBuilder->buildWithGradeRows($students, $exam, $gradeRows, $gender);
        } catch (\Throwable $exception) {
            Log::warning('Centralized bulk transcript calculation failed.', [
                'exam_id' => $examId,
                'student_count' => $students->count(),
                'exception' => get_class($exception),
            ]);
            return back()->with('error', 'Transcripts could not be calculated safely.');
        }

        if (collect($transcripts)->contains(fn ($item) => !($item['usingBulkResultEngine'] ?? false))) {
            return back()->with('error', 'Transcripts could not be calculated safely.');
        }

        $isHttpRuntime = ! in_array(PHP_SAPI, ['cli', 'phpdbg'], true);
        $previousTimeLimit = ini_get('max_execution_time');
        $previousMemoryLimit = ini_get('memory_limit');

        try {
            if ($isHttpRuntime) {
                @set_time_limit(180);
                @ini_set('memory_limit', '512M');
            }

            $config = ServerConfig::first();
            $publicAsset = fn (?string $path, string $directory): ?string =>
                empty($path) ? null : (
                    preg_match('~^https?://~i', $path)
                        ? $path
                        : asset('public/'.$directory.'/'.ltrim($path, '/'))
                );
            $bulkView = [
                'title' => $config?->transcript_title ?? 'Academic Transcript',
                'examName' => (string) $exam->examName,
                'genderLabel' => $this->studentGender->label($gender),
                'institute' => [
                    'name' => $config?->instituteName ?? 'Jahanara Ayub Academy',
                    'address' => $config?->address ?? '',
                    'mobile' => $config?->officeMobile ?? '',
                    'email' => $config?->officeEmail ?? '',
                    'logoUrl' => $publicAsset($config?->logo, 'upload/image/cultivation'),
                ],
                'principalSignatureUrl' => $publicAsset($config?->principalSign, 'upload/image/cultivation'),
                'gradeLegend' => $gradeRows->map(fn ($grade) => [
                        'range' => $grade->minMark.' - '.$grade->maxMark,
                        'grade' => $grade->gradeName,
                        'point' => number_format((float) $grade->gradePoint, 2),
                    ])->all(),
            ];
            $html = view('result.bulk-transcript-pdf', [
                'exam' => $exam,
                'transcripts' => $transcripts,
                'config' => $config,
                'bulkView' => $bulkView,
            ])->render();

            $fileName = 'bulk-transcripts-exam-'.$examId.'-'.date('Y-m-d').'.pdf';

            if (class_exists(Dompdf::class) && class_exists(Options::class)) {
                $options = new Options();
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);

                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                return response($dompdf->output(), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                ]);
            }

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
                return $pdf->download($fileName);
            }

            throw new \RuntimeException('No PDF engine available (Dompdf classes missing).');
        } catch (\Throwable $e) {
            Log::error('Bulk transcript PDF generation failed', [
                'exam_id' => $examId,
                'student_count' => count($transcripts),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', 'PDF generation failed on server. Please contact admin.');
        } finally {
            if ($isHttpRuntime) {
                if ($previousTimeLimit !== false) {
                    @set_time_limit((int) $previousTimeLimit);
                }
                if ($previousMemoryLimit !== false) {
                    @ini_set('memory_limit', (string) $previousMemoryLimit);
                }
            }
        }
    }

}
