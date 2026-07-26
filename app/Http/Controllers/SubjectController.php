<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\classManage;
use App\Models\Subject;
use App\Models\ReligiousSubjectDefault;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    
    
    public function createSubject(){
        $classList = classManage::orderBy('id','ASC')->get();
        return view('result.new-subject',['classList'=>$classList]);
    }

    public function confirmSubject(Request $requ){
        $validated = $this->validateSubjectPayload($requ);
        $chk = Subject::where(['subjectName'=>$validated['subjectName']]);
        if($chk->exists()):
            return back()->withInput()->with('error','Alias already exist');
        else:
            $subject = new Subject();
            $aliasCreate = str_replace(' ','_',$validated['subjectName']);
            $alias = strtolower($aliasCreate);

            $subject->subjectName   = $validated['subjectName'];
            $subject->subjectType   = $validated['subjectType'];
            $subject->passingSystem = $requ->passingSystem;
            $subject->assign_class  = $validated['classId'] ?? null;
            $subject->CQ            = $validated['cqValue'] ?? null;
            $subject->MCQ           = $validated['mcqValue'] ?? null;
            $subject->Practical     = $validated['practicalValue'] ?? null;
            $subject->isReligious   = $requ->has('isReligious') ? 1 : 0;
            $subject->alias         = $alias;
            $subject->save();

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
            return back()->with('success','Record successfully saved');
        endif;
    }

    public function allSubject(){
        $itemData = Subject::orderBy('id','DESC')->get();
        return view('result.subjectList',['itemData'=>$itemData]);
    }
    
    public function editSubject($item){
        $itemData = Subject::find($item);
        $classList = classManage::orderBy('id','ASC')->get();
        $defaultClassIds = ReligiousSubjectDefault::where('subjectId', $itemData->id)->pluck('classId')->toArray();
        return view('result.edit-subject',['item'=>$itemData, 'classList'=>$classList, 'defaultClassIds'=>$defaultClassIds]);
    }
    

    public function updateSubject(Request $requ){
        $validated = $this->validateSubjectPayload($requ, true);
        $subject = Subject::find($validated['itemId']);
        if(!empty($subject) && $subject->exists()):
            $aliasCreate = str_replace(' ','_',$validated['subjectName']);
            $alias = strtolower($aliasCreate);

            $subject->subjectName   = $validated['subjectName'];
            $subject->subjectType   = $validated['subjectType'];
            $subject->passingSystem = $requ->passingSystem;
            $subject->assign_class  = $validated['classId'] ?? null;
            $subject->CQ            = $validated['cqValue'] ?? null;
            $subject->MCQ           = $validated['mcqValue'] ?? null;
            $subject->Practical     = $validated['practicalValue'] ?? null;
            $subject->isReligious   = $requ->has('isReligious') ? 1 : 0;
            $subject->alias         = $alias;
            $subject->save();

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
                Rule::unique('subjects', 'subjectName')->ignore($subjectId),
            ],
            'subjectType' => ['required', 'string', 'max:255'],
            'classId' => [
                'nullable',
                function (string $attribute, $value, $fail) {
                    if ($value === null || $value === '' || $value === '0') {
                        return;
                    }

                    if (!is_numeric($value) || !classManage::whereKey((int) $value)->exists()) {
                        $fail('The selected class is invalid.');
                    }
                },
            ],
            'cqValue' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mcqValue' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'practicalValue' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'defaultReligiousClasses' => ['nullable', 'array'],
            'defaultReligiousClasses.*' => ['integer', 'exists:class_manages,id'],
        ]);
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
