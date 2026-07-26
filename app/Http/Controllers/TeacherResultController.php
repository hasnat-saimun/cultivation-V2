<?php

namespace App\Http\Controllers;

use App\Exceptions\ResultLifecycleException;
use App\Models\CultivationAdmin;
use App\Services\ResultMarksConfirmationService;
use App\Services\ResultMarksDraftService;
use App\Services\TeacherDashboardService;
use App\Services\TeacherResultWorkspaceService;
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
        private ResultMarksConfirmationService $confirmations,
        private TeacherDashboardService $dashboard,
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

    private function validateMarks(Request $request): void
    {
        $request->validate([
            'studentId' => ['required', 'array', 'min:1', 'max:500'],
            'studentId.*' => ['required', 'integer', 'distinct'],
            'cqMarks' => ['nullable', 'array', 'max:500'],
            'cqMarks.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mcqMarks' => ['nullable', 'array', 'max:500'],
            'mcqMarks.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'practical' => ['nullable', 'array', 'max:500'],
            'practical.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scope_revision' => ['required', 'integer', 'min:1'],
            'scope_revisions' => ['nullable', 'array'],
            'scope_revisions.*' => ['integer', 'min:1'],
        ]);
    }

    private function failure(Request $request, ResultLifecycleException $exception): RedirectResponse
    {
        $message = match ($exception->failure) {
            'ScopeRevisionConflict' => 'This workspace is stale. Reload it before submitting again.',
            'ScopeAlreadyConfirmed' => 'Confirmed marks are read-only.',
            'ScopePublished' => 'Published results are read-only.',
            'BlankMarksConfirmationRequired' => 'Some mark fields are blank. Choose Confirm Anyway or save as Draft.',
            'ScopeIncomplete' => 'The scope is incomplete and cannot be confirmed.',
            default => 'The result operation could not be completed for this assigned scope.',
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
