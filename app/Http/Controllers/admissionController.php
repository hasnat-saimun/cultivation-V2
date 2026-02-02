<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\newAdmission;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\sessionManage;
use App\Models\Department;
use App\Models\Subject;
use Illuminate\Support\Str;
use File;


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
            ? $q->orderBy('rollNumber')->get()
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

    public function studentList(Request $request){
        // Build query with optional filters from GET params
        $q = newAdmission::query();
        if ($request->filled('classId')) {
            $q->where('className', $request->get('classId'));
        }
        if ($request->filled('sessionId')) {
            $q->where('sessName', $request->get('sessionId'));
        }
        if ($request->filled('sectionId')) {
            $q->where('sectionName', $request->get('sectionId'));
        }
        if ($request->filled('departmentId')) {
            $q->where('departmentName', $request->get('departmentId'));
        }
        if ($request->filled('search')) {
            $s = $request->get('search');
            $q->where(function($w) use ($s){
                $w->where('fullName','like','%'.$s.'%')
                  ->orWhere('sureName','like','%'.$s.'%')
                  ->orWhere('stdId','like','%'.$s.'%')
                  ->orWhere('phone','like','%'.$s.'%');
            });
        }

        $stdData = $q->orderBy('id','desc')->get();
        return view('cultivation.studentList',['studentData'=>$stdData]);
    }

    /**
     * Export student list as PDF
     */
    public function exportStudentPDF()
    {
        $students = newAdmission::orderBy('id')->get();
        $pdf = \PDF::loadView('exports.student-list-pdf', ['students' => $students]);
        return $pdf->download('student-list-' . date('Y-m-d') . '.pdf');
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
            ])->orderBy('rollNumber')->get();
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
        
        $studentId = $requ->checkbox;
        $totalData = count($studentId);
        $x = 0;
        $skippedRolls = [];
        while($x<$totalData){
            $update = newAdmission::where(['stdId'=>$requ->studentId[$x]])->first();
            if ($update) {
                // Determine target values for class/section/roll (new roll if provided, otherwise current roll)
                $newRoll = !empty($requ->rollNum[$x]) ? $requ->rollNum[$x] : $update->rollNumber;
                // If a promoted section is provided use it, otherwise keep current section for duplicate check
                $targetSection = $requ->filled('promotSection') ? $requ->promotSection : $update->sectionName;
                $duplicate = newAdmission::where([
                    'className'   => $requ->promotId,
                    'sessName'    => $update->sessName,
                    'sectionName' => $targetSection,
                    'rollNumber'  => $newRoll,
                ])->where('id', '!=', $update->id)->first();
                if ($duplicate) {
                    $skippedRolls[] = $newRoll;
                    $x++;
                    continue;
                }
                // Archive current result before promotion with proper pair subject handling
                $marks = \App\Models\Marksheet::where('studentId', $update->id)->get();
                if ($marks->count() > 0) {
                    // Get all subjects and detect pairs
                    $subjectIds = $marks->pluck('subjectId')->unique();
                    $allSubjects = \App\Models\Subject::whereIn('id', $subjectIds)->get();
            
                    // Import MarksheetController methods (detectSubjectPairs, mergeSubjectsForRow)
                    $marksheetCtrl = app(\App\Http\Controllers\MarksheetController::class);
            
                    // Build per-subject output
                    $perSubjectOutput = [];
                    $subjectCache = [];
                    foreach ($marks as $mark) {
                        $subject = optional(\App\Models\Subject::find($mark->subjectId));
                        $subjectName = $subject->subjectName ?? ('Subject-'.$mark->subjectId);
                        if($subject) { $subjectCache[$subject->id] = $subject; }
                
                        $perSubjectOutput[] = [
                            'id' => $mark->subjectId,
                            'name' => $subjectName,
                            'cq' => $mark->subjectMarks ?? 0,
                            'mcq' => $mark->objectMarks ?? 0,
                            'practical' => $mark->practicalMarks ?? 0,
                            'total' => $mark->totalMarks ?? 0,
                            'grade' => $mark->laterGrade ?? 'N/A',
                            'gradePoint' => $mark->gradePoint ?? 0,
                            'type' => $subject->subjectType ?? 'Main',
                            'cqGrade' => '-',
                            'mcqGrade' => '-',
                            'prGrade' => '-',
                        ];
                    }
            
                    // Detect and merge pair subjects
                    $pairGroups = $marksheetCtrl->detectSubjectPairs($allSubjects);
                    $mergedSubjects = $marksheetCtrl->mergeSubjectsForRow($perSubjectOutput, $pairGroups, $subjectCache, false);
            
                    // Calculate final GPA and result from merged subjects
                    $totalMarks = 0;
                    $mainGradePoints = [];
                    $hasFailure = false;
            
                    foreach ($mergedSubjects as $subj) {
                        if(is_numeric($subj['total'])){ $totalMarks += (float)$subj['total']; }
                        if(($subj['grade'] ?? '-') === 'F'){ $hasFailure = true; }
                        $gp = ($subj['grade'] === 'F') ? 0.0 : (is_numeric($subj['gradePoint']) ? (float)$subj['gradePoint'] : null);
                        if($gp !== null && ($subj['type'] ?? 'Main') === 'Main'){ $mainGradePoints[] = $gp; }
                    }
            
                    $finalGpa = count($mainGradePoints) > 0 ? round(array_sum($mainGradePoints) / count($mainGradePoints), 2) : 0;
                    $finalResult = $hasFailure ? 'Fail' : 'Pass';
            
                    $resultData = [
                        'subjects' => $mergedSubjects,
                        'total_marks' => $totalMarks,
                        'gpa' => $finalGpa,
                        'result' => $finalResult,
                    ];
            
                    $archiveData = [
                        'student_id' => $update->id,
                        'old_class' => $update->className,
                        'old_roll' => $update->rollNumber,
                        'old_session' => $update->sessName,
                        'old_section' => $update->sectionName,
                        'result_data' => $resultData,
                    ];
                    \App\Models\ResultArchive::create($archiveData);
                }
                // Apply promotion: class, optionally section, and new roll
                $update->className = $requ->promotId;
                if ($requ->filled('promotSection')) {
                    $update->sectionName = $requ->promotSection;
                }
                $update->rollNumber = $newRoll;
                $update->save();
            }
            $x++;
        }
        if($x>=$totalData){
            if(count($skippedRolls) > 0){
                $msg = 'Student profile promoted successfully. Skipped roll(s) due to duplicate: ' . implode(', ', $skippedRolls);
                return redirect(route('studentPromotion'))->with('error', $msg);
            } else {
                return redirect(route('studentPromotion'))->with('success','Student profile promoted successfully');
            }
        }else{
            return redirect(route('studentPromotion'))->with('error','Student profile promoted failed');
        }
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

        $studentList = $q->get();
        $groupId = $requ->filled('groupId') ? $requ->groupId : null;

        return view('cultivation.promotData',[
            'studentList'=>$studentList,
            'groupId'=>$groupId,
            'classId'=>$requ->classId ?? null,
            'sessionId'=>$requ->sessionId ?? null,
            'type' => $type,
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


    public function stdIdCard($id){
        $std = newAdmission::find($id);
        if (!$std) { return back()->with('error','Student not found'); }

        // Build branding
        $server = \App\Models\ServerConfig::orderBy('id')->first();
        $branding = [
            'name' => $server->instituteName ?? 'Institute',
            'address' => $server->address ?? '',
            'email' => $server->officeEmail ?? '',
            'phone' => $server->officeMobile ?? '',
            'logoUrl' => !empty($server->logo) ? asset('/public/upload/image/cultivation/'.$server->logo) : null,
            'principalSignUrl' => !empty($server->principalSign) ? asset('/public/upload/image/cultivation/'.$server->principalSign) : null,
        ];

        // Lookups
        $class = \App\Models\classManage::find((int)$std->className);
        $section = \App\Models\sectionManage::find((int)$std->sectionName);
        $session = \App\Models\sessionManage::find((int)$std->sessName);
        $dept = \App\Models\Department::find((int)$std->departmentName);
        $className = optional($class)->className ?? '-';
        $sectionName = optional($section)->section ?? '-';
        $deptName = optional($dept)->departmentName ?? '-';
        $sessionText = optional($session)->session ?? '-';

        $validDate = null;
        if ($sessionText && preg_match('/(\d{4})\s*[-–]\s*(\d{4})/', $sessionText, $m)) {
            $endYear = (int)$m[2];
            $validDate = date('d-m-Y', strtotime(($endYear+1).'-06-30'));
        }
        if (!$validDate) { $validDate = date('d-m-Y', strtotime('+1 year')); }

        $photoUrl = !empty($std->avatar)
            ? asset('/public/upload/image/student/'.$std->avatar)
            : asset('/public/back-office/img/avatar.jpeg');

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
        ];

        return view('cultivation.stdIdCard', compact('std','branding','card'));
    }

    public function editStudent($id){
        
        $classDetails = classManage::all();
        $sectionDetails= sectionManage::all();
        $departmentDetails= Department::all();
        $stdDataa= newAdmission::find($id);
        return view('cultivation.edit-student',['classDetails'=>$classDetails,'sectionDatails'=>$sectionDetails,'stdData'=>$stdDataa,'departmentDetails'=>$departmentDetails]);
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
        $students = newAdmission::orderBy('stdName')->get();
        $pdf = \PDF::loadView('exports.student-list-pdf', ['students' => $students]);
        return $pdf->download('student-list-' . date('Y-m-d') . '.pdf');
    }
}

