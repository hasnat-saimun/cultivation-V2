<?php

namespace App\Http\Controllers;

use App\Exceptions\ResultLifecycleException;
use App\Models\CultivationAdmin;
use App\Models\Subject;
use App\Services\ResultComponentMarksValidationService;
use App\Services\ResultMarksConfirmationService;
use App\Services\ResultMarksDraftService;
use App\Services\TeacherDashboardService;
use App\Services\TeacherLoginBrandingService;
use App\Services\TeacherResultWorkspaceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherResultController extends Controller
{
    public function __construct(
        private TeacherResultWorkspaceService $workspace,
        private ResultMarksDraftService $drafts,
        private ResultComponentMarksValidationService $componentMarksValidation,
        private ResultMarksConfirmationService $confirmations,
        private TeacherDashboardService $dashboard,
        private TeacherLoginBrandingService $branding,
    ) {}

    public function index(): View
    {
        $teacher = $this->teacher();
        return view('teacher.results.index', [
            'teacher' => $teacher,
            'assignments' => $this->workspace->assignments($teacher),
            'activities' => $this->workspace->recentActivity($teacher),
        ] + $this->dashboard->build($teacher));
    }

    public function load(Request $request): RedirectResponse
    {
        try {
            $authorized = $this->workspace->authorize($this->teacher(), $request->all());
            return redirect()->route('teacher.results.workspace', $this->query($authorized['scope']));
        } catch (ResultLifecycleException) {
            return back()->with('error', 'The selected result scope is not available for your account.');
        }
    }

    public function workspace(Request $request): View|RedirectResponse
    {
        try {
            $data = $this->workspace->workspace($this->teacher(), $request->all());
            return view('teacher.results.workspace', $data + [
                'teacher' => $this->teacher(),
            ] + $this->dashboard->build($this->teacher()));
        } catch (ResultLifecycleException) {
            return redirect()->route('teacher.results.index')
                ->with('error', 'The selected result scope is not available for your account.');
        }
    }

    public function draft(Request $request): RedirectResponse
    {
        $this->validateMarks($request);
        try {
            $authorized = $this->workspace->authorize($this->teacher(), $request->all());
            $input = $this->workspace->serviceInput($request->all(), $authorized['scope']);
            $result = $this->drafts->save($input, $this->teacher(), $request->ip());
            return redirect()->route('teacher.results.workspace', $this->query($authorized['scope']))
                ->with('success', $result['changed_student_count'] > 0
                    ? 'Draft marks saved successfully.'
                    : 'Draft marks are already up to date.');
        } catch (ResultLifecycleException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'scope_revision' => ['required', 'integer', 'min:1'],
            'submission_action' => ['nullable', Rule::in(['confirm', 'confirm_with_blanks', 'draft'])],
            'confirm_blank_marks' => ['nullable', 'boolean'],
        ]);
        try {
            $authorized = $this->workspace->authorize($this->teacher(), $request->all());
            $input = $this->workspace->serviceInput($request->all(), $authorized['scope']);
            $action = (string) ($request->input('submission_action') ?: 'confirm');

            if ($action === 'draft') {
                $this->validateMarks($request);
                $result = $this->drafts->save($input, $this->teacher(), $request->ip());
                return redirect()->route('teacher.results.workspace', $this->query($authorized['scope']))
                    ->with('success', $result['changed_student_count'] > 0
                        ? 'Draft marks saved successfully.'
                        : 'Draft marks are already up to date.');
            }

            if ($request->filled('studentId')) {
                $this->validateMarks($request);
                $draftResult = $this->drafts->save($input, $this->teacher(), $request->ip());
                $scopeRevisions = (array) ($draftResult['current_revisions'] ?? []);
                if ($scopeRevisions !== []) {
                    $singleScopeRevision = count($scopeRevisions) === 1 ? reset($scopeRevisions) : null;
                    if ($singleScopeRevision !== false && $singleScopeRevision !== null) {
                        $input['scope_revision'] = (int) $singleScopeRevision;
                    }
                    $input['scope_revisions'] = $scopeRevisions;
                }
            }

            $input['confirm_blank_marks'] = ($action === 'confirm_with_blanks' || (string) $request->input('confirm_blank_marks') === '1') ? 1 : 0;

            $this->confirmations->confirm($input, $this->teacher(), $request->ip());
            return redirect()->route('teacher.results.workspace', $this->query($authorized['scope']))
                ->with('success', 'Subject marks confirmed successfully.');
        } catch (ResultLifecycleException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function subjectMarksheetPrint(Request $request): View|RedirectResponse
    {
        try {
            return view('teacher.results.subject-marksheet', $this->subjectMarksheetData($request, false));
        } catch (ResultLifecycleException $exception) {
            abort($exception->httpStatus, $exception->getMessage());
        }
    }

    public function subjectMarksheetPdf(Request $request): mixed
    {
        try {
            $data = $this->subjectMarksheetData($request, true);
            $filename = 'subject-marksheet-'.str($data['labels']['subject'])->slug().'.pdf';

            return Pdf::loadView('teacher.results.subject-marksheet', $data)
                ->setPaper('a4', 'landscape')
                ->download($filename);
        } catch (ResultLifecycleException $exception) {
            abort($exception->httpStatus, $exception->getMessage());
        }
    }

    /** @return array<string,mixed> */
    private function subjectMarksheetData(Request $request, bool $pdfMode): array
    {
        $teacher = $this->teacher();
        $data = $this->workspace->workspace($teacher, $request->all());
        $branding = $this->branding->resolve();
        $rows = $data['students']->values()->map(function ($student, int $index) use ($data) {
            $mark = $data['marks']->get((int) $student->id);
            $preview = $data['calculatedResults']->get((int) $student->id);

            return [
                'sl' => $index + 1,
                'roll' => $student->rollNumber,
                'student_id' => $student->stdId,
                'name' => trim($student->fullName.' '.$student->sureName),
                'cq' => $mark?->subjectMarks,
                'mcq' => $mark?->objectMarks,
                'practical' => $mark?->practicalMarks,
                'total' => $preview?->obtainedMarks,
            ];
        });

        return $data + [
            'teacher' => $teacher,
            'instituteName' => $branding['instituteName'],
            'instituteLogoUrl' => $pdfMode
                ? $this->reportLogoSource($branding['instituteLogoUrl'])
                : $branding['instituteLogoUrl'],
            'printedAt' => now(),
            'pdfMode' => $pdfMode,
            'reportPages' => $rows->chunk(15)->values(),
            'subjectCode' => $data['subject']->getAttribute('subjectCode')
                ?: $data['subject']->getAttribute('code'),
        ];
    }

    private function reportLogoSource(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return $url;
        }

        $relative = preg_replace('~^/public/~', '', '/'.ltrim(rawurldecode($path), '/'));
        $file = public_path(ltrim((string) $relative, '/'));
        if (! is_file($file)) {
            return $url;
        }

        $mime = mime_content_type($file) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file));
    }

    private function validateMarks(Request $request): void
    {
        $subject = Subject::find((int) $request->input('subjectId'));

        $request->validate(array_merge([
            'studentId' => ['required', 'array', 'min:1', 'max:500'],
            'studentId.*' => ['required', 'integer', 'distinct'],
            'cqMarks' => ['nullable', 'array', 'max:500'],
            'mcqMarks' => ['nullable', 'array', 'max:500'],
            'practical' => ['nullable', 'array', 'max:500'],
            'scope_revision' => ['required', 'integer', 'min:1'],
            'scope_revisions' => ['nullable', 'array'],
            'scope_revisions.*' => ['integer', 'min:1'],
        ], $this->componentMarksValidation->componentRules($subject)));
    }

    private function failure(Request $request, ResultLifecycleException $exception): RedirectResponse
    {
        $message = match ($exception->failure) {
            'ScopeRevisionConflict' => 'This workspace is stale. Reload it before submitting again.',
            'ScopeAlreadyConfirmed' => 'Confirmed marks are read-only.',
            'ScopePublished' => 'Published results are read-only.',
            'BlankMarksConfirmationRequired' => 'Some mark fields are blank. Choose Confirm Anyway or save as Draft.',
            'ScopeIncomplete' => 'The scope is incomplete and cannot be confirmed.',
            default => $exception->getMessage(),
        };
        return back()->withInput($request->except(['studentId', 'cqMarks', 'mcqMarks', 'practical']))
            ->with('error', $message);
    }

    private function query(array $scope): array
    {
        return array_filter([
            'sessionId' => $scope['sessionId'],
            'classId' => $scope['classId'],
            'groupId' => $scope['groupId'],
            'optionalGroupId' => $scope['optionalGroupId'],
            'subjectId' => $scope['subjectId'],
            'examId' => $scope['examId'],
            'gender' => $scope['gender'] ?? 'all',
        ], fn ($value) => $value !== null);
    }

    private function teacher(): CultivationAdmin
    {
        /** @var CultivationAdmin $teacher */
        $teacher = Auth::guard('teacher')->user();
        return $teacher;
    }
}
