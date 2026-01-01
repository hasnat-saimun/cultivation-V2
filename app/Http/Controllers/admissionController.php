<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\newAdmission;
use App\Models\classManage;
use App\Models\sectionManage;
use App\Models\Department;
use App\Models\Subject;
use Illuminate\Support\Str;
use File;


class AdmissionController extends Controller
{
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

    public function studentList(){
        $stdData = newAdmission::all();
        return view('cultivation.studentList',['studentData'=>$stdData]);
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
                // Check for duplicate record in new class/session/section/roll (new roll if provided, otherwise current roll)
                $newRoll = !empty($requ->rollNum[$x]) ? $requ->rollNum[$x] : $update->rollNumber;
                $duplicate = newAdmission::where([
                    'className'   => $requ->promotId,
                    'sessName'    => $update->sessName,
                    'sectionName' => $update->sectionName,
                    'rollNumber'  => $newRoll,
                ])->where('id', '!=', $update->id)->first();
                if ($duplicate) {
                    $skippedRolls[] = $newRoll;
                    $x++;
                    continue;
                }
                // Archive current result before promotion
                $marks = \App\Models\Marksheet::where('studentId', $update->id)->get();
                if ($marks->count() > 0) {
                    // ...existing code for archiving marks...
                    $subjects = [];
                    $totalMarks = 0;
                    $totalGpa = 0;
                    $subjectCount = 0;
                    foreach ($marks as $mark) {
                        $subject = optional(\App\Models\Subject::find($mark->subjectId));
                        $subjectName = $subject->subjectName ?? ('Subject-'.$mark->subjectId);
                        $marksObtained = $mark->totalMarks ?? 0;
                        $grade = $mark->laterGrade ?? 'N/A';
                        $gpa = $mark->gradePoint ?? 0;
                        $subjects[] = [
                            'name' => $subjectName,
                            'marks' => $marksObtained,
                            'grade' => $grade,
                            'gpa' => $gpa,
                        ];
                        $totalMarks += $marksObtained;
                        $totalGpa += $gpa;
                        $subjectCount++;
                    }
                    $gpa = $subjectCount > 0 ? round($totalGpa / $subjectCount, 2) : 0;
                    $result = $gpa >= 1 ? 'Pass' : 'Fail';
                    $resultData = [
                        'subjects' => $subjects,
                        'total_marks' => $totalMarks,
                        'gpa' => $gpa,
                        'result' => $result,
                    ];
                    // Try to get examId from marks if available
                    $examId = $marks->first()->examId ?? null;
                    $archiveData = [
                        'student_id' => $update->id,
                        'old_class' => $update->className,
                        'old_roll' => $update->rollNumber,
                        'old_session' => $update->sessName,
                        'old_section' => $update->sectionName,
                        'exam_id' => $examId,
                        'result_data' => $resultData,
                    ];
                    \App\Models\ResultArchive::create($archiveData);
                    // Optionally, delete old marks if you want to clear them after archiving
                    // \App\Models\Marksheet::where('studentId', $update->id)->delete();
                }
                $update->className = $requ->promotId;
                $update->rollNumber = $requ->rollNum[$x];
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
        $studentList = newAdmission::where(['sessName'=>$requ->sessionId,'sectionName'=>$requ->groupId,'className'=>$requ->classId])->get();
        return view('cultivation.promotData',['studentList'=>$studentList,'groupId'=>$requ->groupId,'classId'=>$requ->classId,'sessionId'=>$requ->sessionId]);
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
        $stdData = newAdmission::find($id);
        return view('cultivation.stdIdCard',['std'=>$stdData]);
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
}

