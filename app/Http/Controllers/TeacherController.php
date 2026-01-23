<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherManagement;
use App\Models\Designation;
use App\Imports\TeachersImport;
use Maatwebsite\Excel\Facades\Excel;
use File;

class TeacherController extends Controller
{
    
    
    public function addTeacher(){
        $chk = TeacherManagement::orderBy('id','DESC')->first();
        $designations = Designation::teacherDesignations();
        return view('cultivation.add-teacher',['chk'=>$chk, 'designations'=>$designations]);
    }
    public function confirmTeacher(Request $requ){
        $chk = TeacherManagement::where(['email'=>$requ->email])->orWhere(['teacherId'=>$requ->teacherId])->get();
        if(!$chk->isEmpty()):
            return back()->with('error','Opps Sorry! Profile already created');
        else:
            $teacherProfile    = new TeacherManagement();

            if(!empty($requ->file('avatar'))):
                $teacherProfileAvatar = $requ->file('avatar');
            $validated = $requ->validate([
                    'avatar' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'avatar.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'avatar.max'    => 'Each file must be less than 5MB.'
                ]);
                $newTeacherAvatar   = rand().date('Ymd').'.'.$teacherProfileAvatar->getClientOriginalExtension();
                $teacherProfileAvatar->move(public_path('upload/image/teacher'),$newTeacherAvatar);
                $teacherProfile->avatar        = $newTeacherAvatar; 
            endif;

            $teacherProfile->teacherId = $requ->teacherId;
            $teacherProfile->firstName = $requ->firstName;
            $teacherProfile->lastName = $requ->lastName;
            $teacherProfile->fathersName = $requ->fathersName;
            $teacherProfile->mothersName = $requ->mothersName;
            $teacherProfile->gender = $requ->gender;
            $teacherProfile->dob = $requ->dob;
            $teacherProfile->designation_id = $requ->designation;
            $teacherProfile->designation = $this->getDesignationName($requ->designation);
            $teacherProfile->blGroup = $requ->blGroup;
            $teacherProfile->religion = $requ->religion;
            $teacherProfile->email = $requ->email;
            $teacherProfile->joinDate = $requ->joinDate;
            $teacherProfile->mobile = $requ->mobile;
            $teacherProfile->address = $requ->address;
            $teacherProfile->mpoIndex = $requ->mpoIndex;
            $teacherProfile->pdsId = $requ->pdsId;
            $teacherProfile->rank = $requ->rank;

            $teacherProfile->save();

            return back()->with('success','Owo Success! Profile created successfully');
        endif;
    }

    public function teacherList(){
        $profileData = TeacherManagement::all();
        return view('cultivation.teacherList',['profileData'=>$profileData]);
    }

    public function bulkUploadForm()
    {
        return view('cultivation.teacher-bulk-upload');
    }

    public function bulkUploadStore(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('csv_file');
            $import = new TeachersImport();
            Excel::import($import, $file);

            $created = $import->getCreated();
            $updated = $import->getUpdated();
            $message = "Import completed! Created: {$created}, Updated: {$updated}";

            return redirect()->route('teacherBulkUpload')->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadSample()
    {
        $headers = [
            'teacher_id',
            'first_name',
            'last_name',
            'fathers_name',
            'mothers_name',
            'gender',
            'dob',
            'designation',
            'blood_group',
            'religion',
            'email',
            'join_date',
            'mobile',
            'address',
            'mpo_index',
            'pds_id',
            'rank'
        ];

        $sampleData = [
            [
                'T202400001',
                'John',
                'Doe',
                'Mr. Father Doe',
                'Mrs. Mother Doe',
                'Male',
                '1985-06-15',
                'Senior Teacher',
                'O+',
                'Islam',
                'john.doe@school.com',
                '2020-01-15',
                '01712345678',
                '123 Main Street, City',
                'MPO123',
                'PDS456',
                '1'
            ],
            [
                'T202400002',
                'Jane',
                'Smith',
                'Mr. Father Smith',
                'Mrs. Mother Smith',
                'Female',
                '1990-03-22',
                'Assistant Teacher',
                'A+',
                'Hindu',
                'jane.smith@school.com',
                '2021-06-01',
                '01798765432',
                '456 Park Avenue, City',
                'MPO789',
                'PDS012',
                '2'
            ],
        ];

        $callback = function() use ($headers, $sampleData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="teacher_bulk_sample.csv"',
        ]);
    }

    public function downloadTemplate()
    {
        $headers = [
            'teacher_id',
            'first_name',
            'last_name',
            'fathers_name',
            'mothers_name',
            'gender',
            'dob',
            'designation',
            'blood_group',
            'religion',
            'email',
            'join_date',
            'mobile',
            'address',
            'mpo_index',
            'pds_id',
            'rank'
        ];

        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="teacher_template.csv"',
        ]);
    }
    
    public function viewTeacher($id){
        $singleData= TeacherManagement::find($id);
        return view('cultivation.viewTeacher',['singleData'=>$singleData]);
    }
    public function editTeacher($id){
        $profileData = TeacherManagement::find($id);
        $designations = Designation::teacherDesignations();
        return view('cultivation.edit-teacher',['profileData'=>$profileData, 'designations'=>$designations]);
    }

    public function updateTeacher(Request $requ){
        $teacherProfile = TeacherManagement::find($requ->teacherId);
        if(empty($teacherProfile)):
            return back()->with('error','Opps Sorry! Profile not found for update');
        else:
            $teacherProfile->firstName = $requ->firstName;
            $teacherProfile->lastName = $requ->lastName;
            $teacherProfile->fathersName = $requ->fathersName;
            $teacherProfile->mothersName = $requ->mothersName;
            $teacherProfile->gender = $requ->gender;
            $teacherProfile->dob = $requ->dob;
            $teacherProfile->designation_id = $requ->designation;
            $teacherProfile->designation = $this->getDesignationName($requ->designation);
            $teacherProfile->blGroup = $requ->blGroup;
            $teacherProfile->religion = $requ->religion;
            $teacherProfile->email = $requ->email;
            $teacherProfile->joinDate = $requ->joinDate;
            $teacherProfile->mobile = $requ->mobile;
            $teacherProfile->address = $requ->address;
            $teacherProfile->mpoIndex = $requ->mpoIndex;
            $teacherProfile->pdsId = $requ->pdsId;
            $teacherProfile->rank = $requ->rank;
            $teacherProfile->save();

            return back()->with('success','Owo Success! Profile updated successfully');
        endif;
    }

    public function delTeacher($id){
        $teacherProfileData = TeacherManagement::find($id);
        if(empty($teacherProfileData)):
            return back()->with('error','Sorry! Profile failed to delete');
        else:
            $teacherProfileData->delete();
            return back()->with('success','Success! Profile successfully delete');
        endif;
    }

    public function delTeacherPhoto($id){
        $teacherProfileData = TeacherManagement::find($id);
        if(empty($teacherProfileData)):
            // return public_path('uploads/image/teacher/'.$teacherProfileData->avatar);
            return back()->with('error','Sorry! Profile picture failed to delete');
        else:
            if (File::exists(public_path('upload/image/teacher/'.$teacherProfileData->avatar))) {
                File::delete(public_path('upload/image/teacher/'.$teacherProfileData->avatar));
            }
            // return public_path('upload/image/teacher/'.$teacherProfileData->avatar);
            $teacherProfileData->avatar        = "";
            $teacherProfileData->save();
            return back()->with('success','Success! Profile picture deleted successfully');
        endif;
    }

    public function updateTeacherPhoto(Request $requ){
        $teacherProfileData = TeacherManagement::find($requ->profileId);
        if($teacherProfileData):
            if(!empty($requ->avatar)):
                $teacherAvatar = $requ->file('avatar');
            $validated = $requ->validate([
                    'avatar' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'avatar.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'avatar.max'    => 'Each file must be less than 5MB.'
                ]);
                $newTeacherAvatar = rand().date('Ymd').'.'.$teacherAvatar->getClientOriginalExtension();
                $teacherAvatar->move(public_path('upload/image/teacher/'),$newTeacherAvatar);

                $teacherProfileData->avatar = $newTeacherAvatar;
            endif;
                
            if($teacherProfileData->save()):
                return back()->with("success",'data update success');
            else:
                return back()->with("error",'data update failed');
            endif;
        else:
            return back()->with('error','Data update failed');
        endif;
    }

    public function teacherBulkDelete(Request $request)
    {
        try {
            $ids = json_decode($request->input('ids'), true);
            
            if (!is_array($ids) || empty($ids)) {
                return back()->with('error', 'No records selected');
            }

            $deleted = TeacherManagement::whereIn('id', $ids)->delete();

            if ($deleted > 0) {
                return back()->with('success', "Successfully deleted $deleted teacher(s)");
            } else {
                return back()->with('error', 'No records found to delete');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    private function getDesignationName($designationId)
    {
        if (!$designationId) {
            return null;
        }
        $designation = Designation::find($designationId);
        return $designation ? $designation->name : null;
    }

    public function bulkPhotoUploadForm()
    {
        $teachers = TeacherManagement::select('id', 'teacherId', 'firstName', 'lastName', 'avatar')
            ->orderBy('firstName')
            ->get();
        
        return view('cultivation.teacher-bulk-photo-upload', compact('teachers'));
    }

    public function bulkPhotoUploadStore(Request $request)
    {
        $request->validate([
            'teacher_ids' => 'array',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:5120',
        ]);

        $teacherIds = $request->input('teacher_ids', []);
        $updated = 0;
        $skipped = 0;

        foreach ($teacherIds as $tid) {
            $fileKey = "photos.$tid";
            if (!$request->hasFile($fileKey)) {
                $skipped++;
                continue;
            }

            $teacher = TeacherManagement::find($tid);
            if (!$teacher) {
                $skipped++;
                continue;
            }

            $photo = $request->file($fileKey);
            $newAvatar = rand() . date('Ymd') . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('upload/image/teacher/'), $newAvatar);

            if (!empty($teacher->avatar) && File::exists(public_path('upload/image/teacher/' . $teacher->avatar))) {
                File::delete(public_path('upload/image/teacher/' . $teacher->avatar));
            }

            $teacher->avatar = $newAvatar;
            $teacher->save();
            $updated++;
        }

        $message = "Updated $updated photo(s).";
        if ($skipped > 0) {
            $message .= " Skipped $skipped without files.";
        }

        return redirect()->route('teacherBulkPhotoUpload')->with('success', $message);
    }

    /**
     * Get list of teachers as JSON for API
     */
    public function getTeachersList()
    {
        $teachers = TeacherManagement::select('id', 'firstName', 'lastName')
            ->orderBy('firstName')
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'teacher_id' => $teacher->id,
                    // Keep snake_case keys for API compatibility, read camelCase fields
                    'first_name' => $teacher->firstName,
                    'last_name' => $teacher->lastName,
                    'full_name' => $teacher->firstName . ' ' . $teacher->lastName
                ];
            });

        return response()->json($teachers);
    }

    /**
     * Export teacher list as PDF
     */
    public function exportPDF()
    {
        $teachers = TeacherManagement::orderBy('firstName')->get();
        $pdf = \PDF::loadView('exports.teacher-list-pdf', ['teachers' => $teachers]);
        return $pdf->download('teacher-list-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Show bulk update form for teachers
     */
    public function bulkUpdateForm()
    {
        $teachers = TeacherManagement::select('id', 'teacherId', 'firstName', 'lastName', 'designation', 'designation_id', 'email', 'mobile', 'address', 'gender', 'dob', 'joinDate')
            ->orderBy('firstName')->get();
        $designations = Designation::teacherDesignations();
        return view('cultivation.teacher-bulk-update', compact('teachers', 'designations'));
    }

    /**
     * Store bulk updates for teachers (per-row editing)
     */
    public function bulkUpdateStore(Request $request)
    {
        $request->validate([
            'teachers' => 'required|array',
            'teachers.*.id' => 'required|exists:teacher_management,id',
            'teachers.*.firstName' => 'nullable|string|max:255',
            'teachers.*.lastName' => 'nullable|string|max:255',
            'teachers.*.designation' => 'nullable|integer|exists:designations,id',
            'teachers.*.email' => 'nullable|email|max:255',
            'teachers.*.mobile' => 'nullable|string|max:20',
            'teachers.*.address' => 'nullable|string',
            'teachers.*.gender' => 'nullable|in:Male,Female,Others',
            'teachers.*.dob' => 'nullable|date',
            'teachers.*.joinDate' => 'nullable|date',
        ]);

        $updated = 0;
        foreach ($request->input('teachers', []) as $teacherData) {
            if (!empty($teacherData['id'])) {
                $teacher = TeacherManagement::find($teacherData['id']);
                if ($teacher) {
                    $teacher->firstName = $teacherData['firstName'] ?? $teacher->firstName;
                    $teacher->lastName = $teacherData['lastName'] ?? $teacher->lastName;
                    $teacher->email = $teacherData['email'] ?? $teacher->email;
                    $teacher->mobile = $teacherData['mobile'] ?? $teacher->mobile;
                    $teacher->address = $teacherData['address'] ?? $teacher->address;
                    $teacher->gender = $teacherData['gender'] ?? $teacher->gender;
                    $teacher->dob = $teacherData['dob'] ?? $teacher->dob;
                    $teacher->joinDate = $teacherData['joinDate'] ?? $teacher->joinDate;
                    
                    // Handle designation exactly like single profile update
                    if (!empty($teacherData['designation'])) {
                        $teacher->designation_id = $teacherData['designation'];
                        $teacher->designation = $this->getDesignationName($teacherData['designation']);
                    }
                    
                    $teacher->save();
                    $updated++;
                }
            }
        }

        return redirect()->route('teacherBulkUpdate')->with('success', "Successfully updated {$updated} teacher(s)");
    }
}
