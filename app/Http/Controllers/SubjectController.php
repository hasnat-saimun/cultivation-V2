<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\classManage;
use App\Models\Subject;
use App\Models\ReligiousSubjectDefault;

class SubjectController extends Controller
{
    
    
    public function createSubject(){
        $classList = classManage::orderBy('id','ASC')->get();
        return view('result.new-subject',['classList'=>$classList]);
    }

    public function confirmSubject(Request $requ){
        $chk = Subject::where(['subjectName'=>$requ->subjectName]);
        if($chk->exists()):
            return back()->with('error','Alias already exist');
        else:
            $subject = new Subject();
            $aliasCreate = str_replace(' ','_',$requ->subjectName);
            $alias = strtolower($aliasCreate);

            $subject->subjectName   = $requ->subjectName;
            $subject->subjectType   = $requ->subjectType;
            $subject->passingSystem = $requ->passingSystem;
            $subject->assign_class  = $requ->classId;
            $subject->CQ            = $requ->cqValue;
            $subject->MCQ           = $requ->mcqValue;
            $subject->Practical     = $requ->practicalValue;
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
        $subject = Subject::find($requ->itemId);
        if(!empty($subject) && $subject->exists()):
            $aliasCreate = str_replace(' ','_',$requ->subjectName);
            $alias = strtolower($aliasCreate);

            $subject->subjectName   = $requ->subjectName;
            $subject->subjectType   = $requ->subjectType;
            $subject->passingSystem = $requ->passingSystem;
            $subject->CQ            = $requ->cqValue;
            $subject->MCQ           = $requ->mcqValue;
            $subject->Practical     = $requ->practicalValue;
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
