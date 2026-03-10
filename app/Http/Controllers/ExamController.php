<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;
use App\Models\ExamRoutine;
use App\Models\ExamRoutineItem;
use App\Models\ClassRoutine;
use App\Models\ClassRoutineItem;
use App\Models\Subject;
use App\Models\newAdmission;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\Department;
use App\Models\sessionManage;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    private const CLASS_ROUTINE_ALLOWED_DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
    private const CLASS_ROUTINE_BREAK_TOKEN = '__BREAK__';
    private const CLASS_ROUTINE_BREAK_LABEL = 'Break/Tiffin Time';

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

        $lookup = $this->buildRoutineLookupMaps($routineList, true);

        return view('result.examRoutineManage', [
            'routineList' => $routineList,
            'lookup' => $lookup,
        ]);
    }

    public function resultClassRoutineManage(){
        $routineList = ClassRoutine::withCount('entries')
            ->orderBy('id', 'DESC')
            ->get();

        $lookup = $this->buildRoutineLookupMaps($routineList, false);

        return view('result.classRoutineManage', [
            'routineList' => $routineList,
            'lookup' => $lookup,
        ]);
    }

    public function saveResultClassRoutine(Request $requ){
        $requ->validate([
            'title' => 'required|string|max:255',
            'assignClass' => 'required',
            'assignSession' => 'required',
        ]);

        DB::beginTransaction();

        try {
            if (empty($requ->itemId)) {
                $item = new ClassRoutine();
            } else {
                $item = ClassRoutine::find($requ->itemId);
            }

            if (empty($item)) {
                DB::rollBack();
                return back()->with('error', 'Routine failed to save');
            }

            $item->title = trim((string)($requ->title ?? '')) ?: 'Class Routine';
            $item->assignClass = $requ->assignClass;
            $item->assignSection = !empty($requ->assignSection) ? (int)$requ->assignSection : null;
            $item->assignDepartment = $requ->assignDepartment;
            $item->assignSession = $requ->assignSession;
            $item->save();

            ClassRoutineItem::where('class_routine_id', $item->id)->delete();

            $days = $requ->input('entry_day', []);
            $startTimes = $requ->input('entry_start_time', []);
            $endTimes = $requ->input('entry_end_time', []);
            $subjectIds = $requ->input('entry_subject_id', []);

            $rows = max(count($days), count($startTimes), count($endTimes), count($subjectIds));
            $savedRows = 0;
            $usedSubjectKeysByDay = [];
            $usedTimeRangesByDay = [];
            $allowedDays = self::CLASS_ROUTINE_ALLOWED_DAYS;

            for ($i = 0; $i < $rows; $i++) {
                $entryDay = trim((string)($days[$i] ?? ''));
                $entryStartTime = trim((string)($startTimes[$i] ?? ''));
                $entryEndTime = trim((string)($endTimes[$i] ?? ''));
                $rawSubjectId = trim((string)($subjectIds[$i] ?? ''));
                $isBreakTime = ($rawSubjectId === self::CLASS_ROUTINE_BREAK_TOKEN);
                $entrySubjectId = (!$isBreakTime && ctype_digit($rawSubjectId)) ? (int)$rawSubjectId : null;
                $subjectData = !empty($entrySubjectId) ? Subject::find($entrySubjectId) : null;
                $entrySubject = $isBreakTime ? self::CLASS_ROUTINE_BREAK_LABEL : ($subjectData->subjectName ?? '');
                $entrySubjectKey = $isBreakTime ? self::CLASS_ROUTINE_BREAK_TOKEN : (string)$entrySubjectId;

                if ($entryDay === '' && $entryStartTime === '' && $entryEndTime === '' && empty($entrySubjectId)) {
                    continue;
                }

                if ($entryDay === '') {
                    DB::rollBack();
                    return back()->with('error', 'Day is required for each routine row.');
                }

                if (!$isBreakTime && empty($entrySubjectId)) {
                    DB::rollBack();
                    return back()->with('error', 'Subject is required for each routine row.');
                }

                $normalizedDay = ucfirst(strtolower($entryDay));
                $dayKey = strtolower($normalizedDay);

                if (!in_array($normalizedDay, $allowedDays, true)) {
                    DB::rollBack();
                    return back()->with('error', 'Day must be between Sunday and Thursday.');
                }

                if (!array_key_exists($dayKey, $usedSubjectKeysByDay)) {
                    $usedSubjectKeysByDay[$dayKey] = [];
                }

                if (!array_key_exists($dayKey, $usedTimeRangesByDay)) {
                    $usedTimeRangesByDay[$dayKey] = [];
                }

                if (in_array($entrySubjectKey, $usedSubjectKeysByDay[$dayKey], true)) {
                    DB::rollBack();
                    return back()->with('error', 'Same subject/break cannot be added multiple times on '.$normalizedDay.'.');
                }

                if ($entryStartTime === '' || $entryEndTime === '') {
                    DB::rollBack();
                    return back()->with('error', 'Start Time and End Time are required for each routine row.');
                }

                $startTs = strtotime($entryStartTime);
                $endTs = strtotime($entryEndTime);

                if ($startTs === false || $endTs === false) {
                    DB::rollBack();
                    return back()->with('error', 'Invalid time format in routine rows.');
                }

                if ($startTs >= $endTs) {
                    DB::rollBack();
                    return back()->with('error', 'Start Time must be earlier than End Time for '.$normalizedDay.'.');
                }

                foreach ($usedTimeRangesByDay[$dayKey] as $range) {
                    $overlaps = ($startTs < $range['end']) && ($range['start'] < $endTs);
                    if ($overlaps) {
                        DB::rollBack();
                        return back()->with('error', 'Overlapping time range found on '.$normalizedDay.'. Please keep separate time slots.');
                    }
                }

                if (!$isBreakTime && !empty($entrySubjectId) && !$this->subjectMatchesClass($subjectData?->assign_class, (int)$requ->assignClass)) {
                    DB::rollBack();
                    return back()->with('error', 'Selected subject does not match the chosen class.');
                }

                $entryTime = '';
                if ($entryStartTime !== '' && $entryEndTime !== '') {
                    $entryTime = date('h:i A', strtotime($entryStartTime)).'-'.date('h:i A', strtotime($entryEndTime));
                }

                ClassRoutineItem::create([
                    'class_routine_id' => $item->id,
                    'class_day' => $normalizedDay,
                    'start_time' => $entryStartTime !== '' ? $entryStartTime : null,
                    'end_time' => $entryEndTime !== '' ? $entryEndTime : null,
                    'class_time' => $entryTime,
                    'subject_id' => $entrySubjectId,
                    'subject_name' => $entrySubject,
                    'sort_order' => $i + 1,
                ]);

                $usedSubjectKeysByDay[$dayKey][] = $entrySubjectKey;
                $usedTimeRangesByDay[$dayKey][] = [
                    'start' => $startTs,
                    'end' => $endTs,
                ];
                $savedRows++;
            }

            if ($savedRows === 0) {
                DB::rollBack();
                return back()->with('error', 'Please add at least one routine row.');
            }

            DB::commit();
            return back()->with('success', 'Class routine successfully saved');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Routine failed to save');
        }
    }

    public function editResultClassRoutine($id){
        $routineList = ClassRoutine::withCount('entries')
            ->orderBy('id', 'DESC')
            ->get();

        $lookup = $this->buildRoutineLookupMaps($routineList, false);

        return view('result.classRoutineManage', [
            'itemId' => $id,
            'routineList' => $routineList,
            'lookup' => $lookup,
        ]);
    }

    public function delResultClassRoutine($id){
        $item = ClassRoutine::find($id);

        if(empty($item)):
            return back()->with('error', 'Item failed to delete');
        else:
            $item->delete();
            return back()->with('success', 'Item deleted successfully');
        endif;
    }

    public function viewResultClassRoutine($id){
        $routine = ClassRoutine::with('entries')->find($id);

        if (empty($routine)) {
            return back()->with('error', 'Sorry! Routine not found.');
        }

        $dayOrder = [
            'Sunday' => 1,
            'Monday' => 2,
            'Tuesday' => 3,
            'Wednesday' => 4,
            'Thursday' => 5,
        ];

        $entries = $routine->entries
            ->sort(function ($a, $b) use ($dayOrder) {
                $aDay = ucfirst(strtolower((string)($a->class_day ?? '')));
                $bDay = ucfirst(strtolower((string)($b->class_day ?? '')));

                $aDayOrder = $dayOrder[$aDay] ?? 99;
                $bDayOrder = $dayOrder[$bDay] ?? 99;

                if ($aDayOrder !== $bDayOrder) {
                    return $aDayOrder <=> $bDayOrder;
                }

                $aStart = (string)($a->start_time ?? '23:59:59');
                $bStart = (string)($b->start_time ?? '23:59:59');

                if ($aStart !== $bStart) {
                    return $aStart <=> $bStart;
                }

                return ((int)$a->sort_order) <=> ((int)$b->sort_order);
            })
            ->values();

        return view('result.classRoutineView', [
            'routine' => $routine,
            'entries' => $entries,
        ]);
    }

    public function printResultClassRoutine($id){
        $routine = ClassRoutine::find($id);

        if (empty($routine)) {
            return back()->with('error', 'Sorry! Routine not found.');
        }

        return redirect()->route('viewResultClassRoutine', ['id' => $id, 'print' => 1]);
    }

    public function downloadResultClassRoutinePdf($id){
        $routine = ClassRoutine::with('entries')->find($id);

        if (empty($routine)) {
            return back()->with('error', 'Sorry! Routine not found.');
        }

        $dayOrder = [
            'Sunday' => 1,
            'Monday' => 2,
            'Tuesday' => 3,
            'Wednesday' => 4,
            'Thursday' => 5,
        ];

        $entries = $routine->entries
            ->sort(function ($a, $b) use ($dayOrder) {
                $aDay = ucfirst(strtolower((string)($a->class_day ?? '')));
                $bDay = ucfirst(strtolower((string)($b->class_day ?? '')));

                $aDayOrder = $dayOrder[$aDay] ?? 99;
                $bDayOrder = $dayOrder[$bDay] ?? 99;

                if ($aDayOrder !== $bDayOrder) {
                    return $aDayOrder <=> $bDayOrder;
                }

                $aStart = (string)($a->start_time ?? '23:59:59');
                $bStart = (string)($b->start_time ?? '23:59:59');

                if ($aStart !== $bStart) {
                    return $aStart <=> $bStart;
                }

                return ((int)$a->sort_order) <=> ((int)$b->sort_order);
            })
            ->values();

        $pdf = \PDF::loadView('exports.class-routine-pdf', [
            'routine' => $routine,
            'entries' => $entries,
        ])->setPaper('a4', 'landscape');

        $className = optional(\App\Models\classManage::find($routine->assignClass))->className ?? 'class';
        $sessionName = optional(\App\Models\sessionManage::find($routine->assignSession))->session ?? 'session';

        $toSlug = function (string $value): string {
            $value = strtolower(trim($value));
            $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
            return trim($value, '-');
        };

        $titlePart = $toSlug((string)($routine->title ?: 'class-routine')) ?: 'class-routine';
        $classPart = $toSlug((string)$className) ?: 'class';
        $sessionPart = $toSlug((string)$sessionName) ?: 'session';

        $fileName = $titlePart.'-'.$classPart.'-'.$sessionPart.'-'.date('Y-m-d').'.pdf';

        return $pdf->download($fileName);
    }

    // Legacy Academic Panel route compatibility (old classRoutine* routes)
    public function classRoutineManage()
    {
        return redirect()->route('resultClassRoutineManage');
    }

    public function saveClassRoutine(Request $requ)
    {
        return $this->saveResultClassRoutine($requ);
    }

    public function editClassRoutine($id)
    {
        return redirect()->route('editResultClassRoutine', ['id' => $id]);
    }

    public function delClassRoutine($id)
    {
        return $this->delResultClassRoutine($id);
    }

    public function delClassRoutineContent($id)
    {
        return redirect()->route('classRoutineManage')->with('error', 'Attachment delete is not applicable in the new Class Routine system.');
    }

    // Legacy Academic Panel route compatibility (old examRoutine* routes)
    public function examRoutineManage()
    {
        return redirect()->route('resultExamRoutineManage');
    }

    public function saveExamRoutine(Request $requ)
    {
        return $this->saveResultExamRoutine($requ);
    }

    public function editExamRoutine($id)
    {
        return redirect()->route('editResultExamRoutine', ['id' => $id]);
    }

    public function delExamRoutine($id)
    {
        return $this->delResultExamRoutine($id);
    }

    public function delExamRoutineContent($id)
    {
        return redirect()->route('examRoutineManage')->with('error', 'Attachment delete is not applicable in the new Exam Routine system.');
    }

    private function buildRoutineLookupMaps($routineList, bool $includeExam = false): array
    {
        $classIds = $routineList->pluck('assignClass')->filter()->unique()->values()->all();
        $sectionIds = $routineList->pluck('assignSection')->filter()->unique()->values()->all();
        $departmentIds = $routineList->pluck('assignDepartment')->filter()->unique()->values()->all();
        $sessionIds = $routineList->pluck('assignSession')->filter()->unique()->values()->all();
        $examIds = $includeExam ? $routineList->pluck('assignExam')->filter()->unique()->values()->all() : [];

        return [
            'classes' => !empty($classIds) ? classManage::whereIn('id', $classIds)->get()->keyBy('id') : collect(),
            'sections' => !empty($sectionIds) ? sectionManage::whereIn('id', $sectionIds)->get()->keyBy('id') : collect(),
            'departments' => !empty($departmentIds) ? Department::whereIn('id', $departmentIds)->get()->keyBy('id') : collect(),
            'sessions' => !empty($sessionIds) ? sessionManage::whereIn('id', $sessionIds)->get()->keyBy('id') : collect(),
            'exams' => ($includeExam && !empty($examIds)) ? Exam::whereIn('id', $examIds)->get()->keyBy('id') : collect(),
        ];
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

        $lookup = $this->buildRoutineLookupMaps($routineList, true);

        return view('result.examRoutineManage', [
            'itemId' => $id,
            'routineList' => $routineList,
            'lookup' => $lookup,
        ]);
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

        $routine = ExamRoutine::with('entries.subject')->where('status', 'result_routine')
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
            $routine = ExamRoutine::with('entries.subject')->where('status', 'result_routine')
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
            $routine = ExamRoutine::with('entries.subject')->where('status', 'result_routine')
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

        $routine = ExamRoutine::with('entries.subject')->where('status', 'result_routine')
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
            $routine = ExamRoutine::with('entries.subject')->where('status', 'result_routine')
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
            $routine = ExamRoutine::with('entries.subject')->where('status', 'result_routine')
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

        $routineRows = collect($routine?->entries ?? [])->sort(function ($a, $b) {
            $aDate = (string)($a->exam_date ?? '');
            $bDate = (string)($b->exam_date ?? '');

            if ($aDate !== $bDate) {
                return strcmp($aDate, $bDate);
            }

            $aStart = (string)($a->start_time ?? '');
            $bStart = (string)($b->start_time ?? '');

            if ($aStart !== $bStart) {
                return strcmp($aStart, $bStart);
            }

            return ((int)$a->id) <=> ((int)$b->id);
        })->values();

        return view('result.getAttendSheet',[
            'studentList' => $studentList,
            'groupId' => $requ->groupId,
            'classId' => $requ->classId,
            'sessionId' => $requ->sessionId,
            'examId' => $requ->examId,
            'departmentId' => $requ->departmentId,
            'routineRows' => $routineRows,
        ]);
    }
}
