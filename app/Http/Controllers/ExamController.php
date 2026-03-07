<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;
use App\Models\ExamRoutine;
use App\Models\ExamRoutineItem;
use App\Models\Subject;
use App\Models\newAdmission;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    protected function validateExamRequest(Request $requ, $isUpdate = false)
    {
        $rules = [
            'examName' => 'required|string|max:255',
            'examClass' => 'required',
            'examDate' => 'required|date',
            'closeDate' => 'required|date|after_or_equal:examDate',
            'baseMark' => 'required|numeric|min:0',
            'passingSystem' => 'required|in:1,2',
        ];

        if ($isUpdate) {
            $rules['itemId'] = 'required|exists:exams,id';
        }

        return $requ->validate($rules);
    }

    
    public function createExam(){
        return view('result.new-exam');
    }

    public function confirmExam(Request $requ){
        $validated = $this->validateExamRequest($requ);
        $chk = Exam::where(['examName'=>$requ->examName]);
        if($chk->exists()):
            return back()->with('error','Alias already exist');
        else:
            $exam = new Exam();
            $aliasCreate = str_replace(' ','_',$validated['examName']);
            $alias = strtolower($aliasCreate);

            $exam->examName     = $validated['examName'];
            $exam->className    = $validated['examClass'];
            $exam->examDate     = $validated['examDate'];
            $exam->closeDate    = $validated['closeDate'];
            $exam->baseMark     = $validated['baseMark'];
            $exam->passingSystem = $validated['passingSystem'];
            $exam->alias        = $alias;
            $exam->save();
            return back()->with('success','Record successfully saved');
        endif;
    }

    public function allExam(){
        $itemData = Exam::orderBy('id','DESC')->get();
        return view('result.examList',['itemData'=>$itemData]);
    }
    
    public function editExam($item){
        $itemData = Exam::find($item);
        return view('result.edit-exam',['item'=>$itemData]);
    }
    

    public function updateExam(Request $requ){
        $validated = $this->validateExamRequest($requ, true);
        $exam = Exam::find($validated['itemId']);
        if(!empty($exam) && $exam->exists()):
            $aliasCreate = str_replace(' ','_',$validated['examName']);
            $alias = strtolower($aliasCreate);

            $exam->examName     = $validated['examName'];
            $exam->className    = $validated['examClass'];
            $exam->examDate     = $validated['examDate'];
            $exam->closeDate    = $validated['closeDate'];
            $exam->baseMark     = $validated['baseMark'];
            $exam->passingSystem = $validated['passingSystem'];
            $exam->alias        = $alias;
            $exam->save();
            return back()->with('success','Record successfully updated');
        else:
            return back()->with('error','No alias found for update');
        endif;
    }

    public function delExam($id){
        $itemData = Exam::find($id);
        if(empty($itemData)):
            return back()->with('error','Sorry! Alias failed to delete');
        else:
            $itemData->delete();
            return back()->with('success','Success! Alias successfully delete');
        endif;
    }

    // admit card controller

    public function admitCard(){
        return view('result.admitCard');
    }

    public function getAdmitCard(Request $requ){
        $studentList = newAdmission::where('sessName', $requ->sessionId)
            ->where('className', $requ->classId)
            ->when($requ->groupId, function($q) use ($requ){
                return $q->where('sectionName', (int)$requ->groupId);
            })
            ->when($requ->departmentId, function($q) use ($requ){
                return $q->where('departmentName', (int)$requ->departmentId);
            })
            ->orderByRaw('CAST(NULLIF(rollNumber, "") AS UNSIGNED) ASC')
            ->orderBy('id', 'ASC')
            ->get();
        return view('result.get-admitCard',['studentList'=>$studentList,'groupId'=>$requ->groupId,'classId'=>$requ->classId,'sessionId'=>$requ->sessionId,'examId'=>$requ->examId]);
    }

    public function resultExamRoutineManage(){
        $routineList = ExamRoutine::where('status', 'result_routine')
            ->withCount('entries')
            ->orderBy('id', 'DESC')
            ->get();

        return view('result.examRoutineManage', ['routineList' => $routineList]);
    }

    public function saveResultExamRoutine(Request $requ){
        DB::beginTransaction();

        try {
            if (empty($requ->itemId)) {
                $item = new ExamRoutine();
            } else {
                $item = ExamRoutine::where('status', 'result_routine')->find($requ->itemId);
            }

            if (empty($item)) {
                DB::rollBack();
                return back()->with('error', 'Routine failed to save');
            }

            $item->assignClass = $requ->assignClass;
            $item->assignSection = !empty($requ->assignSection) ? (int)$requ->assignSection : null;
            $item->assignDepartment = $requ->assignDepartment;
            $item->assignSession = $requ->assignSession;
            $item->status = 'result_routine';
            $item->assignExam = $requ->assignExam;

            $examData = Exam::find($requ->assignExam);
            $item->title = (!empty($examData) ? $examData->examName : 'Exam').' Routine';
            $item->save();

            ExamRoutineItem::where('exam_routine_id', $item->id)->delete();

            $dates = $requ->input('entry_date', []);
            $days = $requ->input('entry_day', []);
            $startTimes = $requ->input('entry_start_time', []);
            $endTimes = $requ->input('entry_end_time', []);
            $subjectIds = $requ->input('entry_subject_id', []);

            $rows = max(count($dates), count($days), count($startTimes), count($endTimes), count($subjectIds));
            $usedSubjectIds = [];
            $usedDates = [];
            for ($i = 0; $i < $rows; $i++) {
                $entryDate = $dates[$i] ?? null;
                $entryDay = trim((string)($days[$i] ?? ''));
                $entryStartTime = trim((string)($startTimes[$i] ?? ''));
                $entryEndTime = trim((string)($endTimes[$i] ?? ''));
                $entrySubjectId = !empty($subjectIds[$i]) ? (int)$subjectIds[$i] : null;
                $subjectData = !empty($entrySubjectId) ? Subject::find($entrySubjectId) : null;
                $entrySubject = $subjectData->subjectName ?? '';

                $entryTime = '';
                if ($entryStartTime !== '' && $entryEndTime !== '') {
                    $entryTime = date('h:i A', strtotime($entryStartTime)).'-'.date('h:i A', strtotime($entryEndTime));
                }

                if (empty($entryDate) && $entryDay === '' && $entryStartTime === '' && $entryEndTime === '' && empty($entrySubjectId)) {
                    continue;
                }

                if (!empty($entryDate) && in_array($entryDate, $usedDates, true)) {
                    DB::rollBack();
                    return back()->with('error', 'Same exam date cannot be added multiple times in one routine.');
                }

                if (!empty($entryDate)) {
                    $usedDates[] = $entryDate;
                }

                if (!empty($entrySubjectId) && in_array($entrySubjectId, $usedSubjectIds, true)) {
                    DB::rollBack();
                    return back()->with('error', 'Same subject cannot be added multiple times in one routine.');
                }

                if (!empty($entrySubjectId) && !$this->subjectMatchesClass($subjectData?->assign_class, (int)$requ->assignClass)) {
                    DB::rollBack();
                    return back()->with('error', 'Selected subject does not match the chosen class.');
                }

                if (!empty($entrySubjectId)) {
                    $usedSubjectIds[] = $entrySubjectId;
                }

                ExamRoutineItem::create([
                    'exam_routine_id' => $item->id,
                    'exam_date' => $entryDate,
                    'exam_day' => $entryDay,
                    'start_time' => $entryStartTime !== '' ? $entryStartTime : null,
                    'end_time' => $entryEndTime !== '' ? $entryEndTime : null,
                    'exam_time' => $entryTime,
                    'subject_id' => $entrySubjectId,
                    'subject_name' => $entrySubject,
                    'sort_order' => $i + 1,
                ]);
            }

            DB::commit();
            return back()->with('success', 'Routine successfully saved');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Routine failed to save');
        }
    }

    private function subjectMatchesClass(?string $assignClass, int $classId): bool
    {
        if ($classId <= 0) {
            return true;
        }

        $assignClass = trim((string)$assignClass);
        if ($assignClass === '' || $assignClass === '0') {
            return true;
        }

        if (ctype_digit($assignClass)) {
            return (int)$assignClass === $classId;
        }

        preg_match_all('/\d+/', $assignClass, $matches);
        $classIds = array_map('intval', $matches[0] ?? []);

        if (empty($classIds)) {
            return true;
        }

        return in_array($classId, $classIds, true);
    }

    public function editResultExamRoutine($id){
        $routineList = ExamRoutine::where('status', 'result_routine')
            ->withCount('entries')
            ->orderBy('id', 'DESC')
            ->get();

        return view('result.examRoutineManage', ['itemId' => $id, 'routineList' => $routineList]);
    }

    public function delResultExamRoutine($id){
        $item = ExamRoutine::where('status', 'result_routine')->find($id);

        if(empty($item)):
            return back()->with('error', 'Item failed to delete');
        else:
            $item->delete();
            return back()->with('success', 'Item deleted successfully');
        endif;
    }

    public function admitCardRoutine(){
        return view('result.admitCardRoutine');
    }

    public function getAdmitCardRoutine(Request $requ){
        $studentList = newAdmission::where('sessName', $requ->sessionId)
            ->where('className', $requ->classId)
            ->when($requ->groupId, function($q) use ($requ){
                return $q->where('sectionName', (int)$requ->groupId);
            })
            ->when($requ->departmentId, function($q) use ($requ){
                return $q->where('departmentName', (int)$requ->departmentId);
            })
            ->orderByRaw('CAST(NULLIF(rollNumber, "") AS UNSIGNED) ASC')
            ->orderBy('id', 'ASC')
            ->get();

        $routine = ExamRoutine::with('entries')->where('status', 'result_routine')
            ->where('assignClass', $requ->classId)
            ->where('assignSession', $requ->sessionId)
            ->where('assignExam', $requ->examId)
            ->when($requ->groupId, function($q) use ($requ){
                return $q->where('assignSection', (int)$requ->groupId);
            }, function($q){
                return $q->where(function($sq){
                    $sq->whereNull('assignSection')->orWhere('assignSection', '');
                });
            })
            ->when($requ->departmentId, function($q) use ($requ){
                return $q->where('assignDepartment', $requ->departmentId);
            })
            ->latest('id')
            ->first();

        if (empty($routine) && !empty($requ->departmentId)) {
            $routine = ExamRoutine::with('entries')->where('status', 'result_routine')
                ->where('assignClass', $requ->classId)
                ->where(function($q) use ($requ){
                    if (!empty($requ->groupId)) {
                        $q->where('assignSection', (int)$requ->groupId)
                            ->orWhereNull('assignSection')
                            ->orWhere('assignSection', '');
                    } else {
                        $q->whereNull('assignSection')->orWhere('assignSection', '');
                    }
                })
                ->where('assignSession', $requ->sessionId)
                ->where('assignExam', $requ->examId)
                ->where(function($q){
                    $q->whereNull('assignDepartment')
                      ->orWhere('assignDepartment', '');
                })
                ->latest('id')
                ->first();
        }

        if (empty($routine)) {
            $routine = ExamRoutine::with('entries')->where('status', 'result_routine')
                ->where('assignClass', $requ->classId)
                ->where(function($q) use ($requ){
                    $q->whereNull('assignSection')
                      ->orWhere('assignSection', '')
                      ->when(!empty($requ->groupId), function($sq) use ($requ){
                          $sq->orWhere('assignSection', (int)$requ->groupId);
                      });
                })
                ->where('assignSession', $requ->sessionId)
                ->where('assignExam', $requ->examId)
                ->when($requ->departmentId, function($q) use ($requ){
                    return $q->where('assignDepartment', $requ->departmentId);
                })
                ->latest('id')
                ->first();
        }

        return view('result.get-admitCard-routine',[
            'studentList' => $studentList,
            'groupId' => $requ->groupId,
            'classId' => $requ->classId,
            'sessionId' => $requ->sessionId,
            'examId' => $requ->examId,
            'departmentId' => $requ->departmentId,
            'routine' => $routine,
        ]);
    }

    public function attendSheet(){
        return view('result.attendSheet');
    }

    public function getAttendSheet(Request $requ){
        $studentList = newAdmission::where('sessName', $requ->sessionId)
            ->where('className', $requ->classId)
            ->when($requ->groupId, function($q) use ($requ){
                return $q->where('sectionName', (int)$requ->groupId);
            })
            ->when($requ->departmentId, function($q) use ($requ){
                return $q->where('departmentName', (int)$requ->departmentId);
            })
            ->get();
        return view('result.getAttendSheet',['studentList'=>$studentList,'groupId'=>$requ->groupId,'classId'=>$requ->classId,'sessionId'=>$requ->sessionId,'examId'=>$requ->examId]);
    }
}
