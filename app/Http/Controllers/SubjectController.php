<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\classManage;
use App\Models\Subject;
use App\Models\ReligiousSubjectDefault;
use App\Services\{SubjectClassScopeService, SubjectScopeSplitPreviewService, SubjectScopeSplitService};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubjectController extends Controller
{
    public function __construct(
        private SubjectClassScopeService $scopeService,
        private SubjectScopeSplitPreviewService $splitPreview,
        private SubjectScopeSplitService $splitter,
    ) {}
    
    
    public function createSubject(){
        $classList = classManage::orderBy('id','ASC')->get();
        return view('result.new-subject',['classList'=>$classList]);
    }

    public function confirmSubject(Request $requ){
        $validated = $this->validateSubjectPayload($requ);
        [$requestedClasses, $allClasses] = $this->requestedClasses($validated, $requ);
        $classIds = $this->scopeService->validate($validated['subjectName'], $requestedClasses, $allClasses);

        DB::transaction(function () use ($requ, $validated, $allClasses, $classIds) {
            $subject = new Subject();
            $aliasCreate = str_replace(' ','_',$validated['subjectName']);
            $alias = strtolower($aliasCreate);

            $subject->subjectName   = $validated['subjectName'];
            $subject->subjectType   = $validated['subjectType'];
            $subject->passingSystem = $requ->passingSystem;
            $subject->assign_class  = null;
            $subject->CQ            = $validated['cqValue'] ?? null;
            $subject->MCQ           = $validated['mcqValue'] ?? null;
            $subject->Practical     = $validated['practicalValue'] ?? null;
            $subject->isReligious   = $requ->has('isReligious') ? 1 : 0;
            $subject->alias         = $alias;
            $subject->save();
            $this->scopeService->sync($subject, $classIds, $allClasses);

            // Map defaults for selected classes (for all classes support)
            if ($subject->isReligious) {
                $defaultClasses = array_filter(array_map('intval', (array) $requ->input('defaultReligiousClasses', [])));
                if ($requ->has('defaultReligiousForAllClass')) {
                    $defaultClasses = classManage::orderBy('id','ASC')->pluck('id')->toArray();
                }
                foreach ($defaultClasses as $classId) {
                    ReligiousSubjectDefault::updateOrCreate(
                        ['classId' => $classId],
                        ['subjectId' => $subject->id]
                    );
                }
            }
        });

        return back()->with('success','Record successfully saved');
    }

    public function allSubject(){
        $itemData = Subject::orderBy('id','DESC')->get();
        $classNames = classManage::pluck('className', 'id');
        $subjectScopeLabels = $itemData->mapWithKeys(function (Subject $subject) use ($classNames) {
            if ($this->scopeService->isAllClasses($subject)) {
                return [$subject->id => 'All Classes'];
            }
            return [$subject->id => collect($this->scopeService->selectedClassIds($subject))->map(fn ($id) => $classNames[$id] ?? '#'.$id)->implode(', ') ?: 'Not assigned'];
        });
        return view('result.subjectList', compact('itemData', 'subjectScopeLabels'));
    }
    
    public function editSubject($item){
        $itemData = Subject::find($item);
        $classList = classManage::orderBy('id','ASC')->get();
        $defaultClassIds = ReligiousSubjectDefault::where('subjectId', $itemData->id)->pluck('classId')->toArray();
        return view('result.edit-subject',['item'=>$itemData, 'classList'=>$classList, 'defaultClassIds'=>$defaultClassIds,
            'selectedClassIds'=>$this->scopeService->selectedClassIds($itemData), 'allClasses'=>$this->scopeService->isAllClasses($itemData)]);
    }
    

    public function updateSubject(Request $requ){
        $validated = $this->validateSubjectPayload($requ, true);
        $subject = Subject::find($validated['itemId']);
        if(!empty($subject) && $subject->exists()):
            [$requestedClasses, $allClasses] = $this->requestedClasses($validated, $requ);
            $classIds = $this->scopeService->validate($validated['subjectName'], $requestedClasses, $allClasses, $subject->id);
            DB::transaction(function () use ($requ, $validated, $subject, $allClasses, $classIds) {
            $aliasCreate = str_replace(' ','_',$validated['subjectName']);
            $alias = strtolower($aliasCreate);

            $subject->subjectName   = $validated['subjectName'];
            $subject->subjectType   = $validated['subjectType'];
            $subject->passingSystem = $requ->passingSystem;
            $subject->CQ            = $validated['cqValue'] ?? null;
            $subject->MCQ           = $validated['mcqValue'] ?? null;
            $subject->Practical     = $validated['practicalValue'] ?? null;
            $subject->isReligious   = $requ->has('isReligious') ? 1 : 0;
            $subject->alias         = $alias;
            $subject->save();
            $this->scopeService->sync($subject, $classIds, $allClasses);

            // Update defaults mapping for selected classes
            if ($subject->isReligious) {
                $selected = array_filter(array_map('intval', (array) $requ->input('defaultReligiousClasses', [])));
                if ($requ->has('defaultReligiousForAllClass')) {
                    $selected = classManage::orderBy('id','ASC')->pluck('id')->toArray();
                }
                // Add/update selected
                foreach ($selected as $classId) {
                    ReligiousSubjectDefault::updateOrCreate(
                        ['classId' => $classId],
                        ['subjectId' => $subject->id]
                    );
                }
                // Remove mappings pointing to this subject for classes not selected
                $existing = ReligiousSubjectDefault::where('subjectId', $subject->id)->pluck('classId')->toArray();
                $toRemove = array_diff($existing, $selected);
                if (!empty($toRemove)) {
                    ReligiousSubjectDefault::where('subjectId', $subject->id)->whereIn('classId', $toRemove)->delete();
                }
            }
            });
            return back()->with('success','Record successfully updated');
        else:
            return back()->with('error','No alias found for update');
        endif;
    }

    private function validateSubjectPayload(Request $requ, bool $isUpdate = false): array
    {
        $subjectId = $isUpdate && $requ->filled('itemId') ? (int) $requ->input('itemId') : null;

        return $requ->validate([
            'itemId' => [$isUpdate ? 'required' : 'nullable', 'integer', 'exists:subjects,id'],
            'subjectName' => [
                'required',
                'string',
                'max:255',
            ],
            'subjectType' => ['required', 'string', 'max:255'],
            'allClasses' => ['nullable', 'boolean'],
            'classIds' => ['nullable', 'array'],
            'classIds.*' => ['integer', 'exists:class_manages,id'],
            'classId' => ['nullable', 'integer', function (string $attribute, $value, $fail) {
                if ((int) $value !== 0 && !classManage::whereKey((int) $value)->exists()) {
                    $fail('The selected class is invalid.');
                }
            }],
            'cqValue' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mcqValue' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'practicalValue' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'defaultReligiousClasses' => ['nullable', 'array'],
            'defaultReligiousClasses.*' => ['integer', 'exists:class_manages,id'],
        ]);
    }

    private function requestedClasses(array $validated, Request $request): array
    {
        $legacyAll = array_key_exists('classId', $validated) && (string) $validated['classId'] === '0';
        $allClasses = $request->boolean('allClasses') || $legacyAll;
        $classIds = $validated['classIds'] ?? [];
        if (!$classIds && isset($validated['classId']) && (int) $validated['classId'] > 0) {
            $classIds = [(int) $validated['classId']];
        }
        return [$classIds, $allClasses];
    }

    public function splitScopeForm(int $itemId)
    {
        $source = Subject::findOrFail($itemId);
        return view('result.subject-scope-split', $this->splitViewData($source));
    }

    public function previewScopeSplit(Request $request, int $itemId)
    {
        $source = Subject::findOrFail($itemId);
        $payload = $this->validateSplitPayload($request, $source);

        try {
            $preview = $this->splitPreview->preview($source->id, $payload['destination_id'], $payload['remain'], $payload['migrate'], $payload['create_destination']);
        } catch (Throwable $exception) {
            return back()->withInput()->withErrors(['split' => $exception->getMessage()]);
        }

        return view('result.subject-scope-split', $this->splitViewData($source) + compact('preview', 'payload'));
    }

    public function applyScopeSplit(Request $request, int $itemId)
    {
        $source = Subject::findOrFail($itemId);
        $payload = $this->validateSplitPayload($request, $source);
        $request->validate(['confirmation' => ['required', 'in:APPLY']]);
        if ($payload['legacy_unresolved']) {
            throw ValidationException::withMessages(['legacy_scope_resolution' => 'Every legacy/non-academic scope must be explicitly kept with the source before Apply.']);
        }

        try {
            $result = $this->splitter->execute(
                $source->id, $payload['destination_id'], $payload['remain'], $payload['migrate'], true,
                'admin:'.session('cultivationAdmin'), $payload['create_destination'], $payload['teacher_resolutions']
            );
        } catch (Throwable $exception) {
            return back()->withInput()->withErrors(['split' => $exception->getMessage()]);
        }

        return redirect()->route('subject.scope.split', ['itemId' => $source->id])
            ->with('success', 'Scope migration applied. Audit operation: '.$result['operation_uuid'])
            ->with('migrationResult', [
                'operation_uuid' => $result['operation_uuid'],
                'destination_id' => $result['destinationId'],
                'counts' => $result['counts'],
            ]);
    }

    private function splitViewData(Subject $source): array
    {
        $sourceClassIds = $this->scopeService->selectedClassIds($source);
        $scopeClasses = classManage::whereIn('id', $sourceClassIds)->orderBy('id')->get();
        $legacyClassList = $scopeClasses->filter(fn ($class) => $this->isLegacyNonAcademicClass($class->className))->values();
        $classList = $scopeClasses->reject(fn ($class) => $this->isLegacyNonAcademicClass($class->className))->values();
        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', trim($source->subjectName)));
        $destinations = Subject::whereKeyNot($source->id)->get()->filter(
            fn (Subject $subject) => mb_strtolower(preg_replace('/\s+/u', ' ', trim($subject->subjectName))) === $normalized
        );

        return [
            'source' => $source,
            'classList' => $classList,
            'legacyClassList' => $legacyClassList,
            'allClassNames' => classManage::pluck('className', 'id'),
            'sourceClassIds' => $sourceClassIds,
            'destinations' => $destinations,
        ];
    }

    private function validateSplitPayload(Request $request, Subject $source): array
    {
        $validated = $request->validate([
            'remain' => ['nullable', 'array'],
            'remain.*' => ['integer', 'exists:class_manages,id'],
            'destination_mode' => ['required', 'in:existing,create'],
            'destination_id' => ['nullable', 'integer', 'exists:subjects,id', Rule::notIn([$source->id])],
            'legacy_scope_resolution' => ['nullable', 'array'],
            'legacy_scope_resolution.*' => ['nullable', 'in:keep_source'],
            'teacher_resolution' => ['nullable', 'array'],
            'teacher_resolution.*' => ['in:keep,move,both,scoped'],
        ]);
        $remain = collect($validated['remain'] ?? [])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
        $current = $this->scopeService->selectedClassIds($source);
        $scopeClasses = classManage::whereIn('id', $current)->get(['id', 'className']);
        $legacyIds = $scopeClasses->filter(fn ($class) => $this->isLegacyNonAcademicClass($class->className))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $academicCurrent = array_values(array_diff($current, $legacyIds));
        if (array_diff($remain, $academicCurrent)) {
            throw ValidationException::withMessages(['remain' => 'Remaining classes must belong to the current source scope.']);
        }
        $migrate = array_values(array_diff($academicCurrent, $remain));
        if (!$remain || !$migrate) {
            throw ValidationException::withMessages(['remain' => 'Select at least one academic class to remain and leave at least one academic class to migrate.']);
        }
        $legacyResolutions = collect((array) $request->input('legacy_scope_resolution', []))->mapWithKeys(fn ($action, $id) => [(int) $id => $action])->all();
        $legacyUnresolved = array_values(array_filter($legacyIds, fn ($id) => ($legacyResolutions[$id] ?? null) !== 'keep_source'));
        $remain = array_values(array_unique(array_merge($remain, $legacyIds)));

        $create = $validated['destination_mode'] === 'create';
        if (!$create && empty($validated['destination_id'])) {
            throw ValidationException::withMessages(['destination_id' => 'Select the existing destination subject.']);
        }

        return ['remain' => $remain, 'migrate' => $migrate, 'create_destination' => $create,
            'destination_id' => $create ? null : (int) $validated['destination_id'],
            'legacy_scope_resolutions' => $legacyResolutions, 'legacy_unresolved' => $legacyUnresolved,
            'teacher_resolutions' => (array) $request->input('teacher_resolution', [])];
    }

    private function isLegacyNonAcademicClass(string $name): bool
    {
        return preg_match('/^no\s*class$/iu', trim($name)) === 1;
    }

    public function delSubject($id){
        $itemData = Subject::find($id);
        if(empty($itemData)):
            return back()->with('error','Sorry! Alias failed to delete');
        else:
            $itemData->delete();
            return back()->with('success','Success! Alias successfully delete');
        endif;
    }
}
