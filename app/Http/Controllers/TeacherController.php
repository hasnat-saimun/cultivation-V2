<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherManagement;
use File;

class TeacherController extends Controller
{
    
    
    public function addTeacher(){
        $chk = TeacherManagement::orderBy('id','DESC')->first();
        return view('cultivation.add-teacher',['chk'=>$chk]);
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
            $teacherProfile->designation = $requ->designation;
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
        $matchOptions = ['teacherId' => 'Teacher ID', 'email' => 'Email'];
        $allowedColumns = [
            'teacherId', 'firstName', 'lastName', 'fathersName', 'mothersName', 'gender', 'dob', 'designation',
            'blGroup', 'religion', 'email', 'joinDate', 'mobile', 'address', 'mpoIndex', 'pdsId', 'rank'
        ];
        return view('cultivation.teacher-bulk-upload', compact('matchOptions', 'allowedColumns'));
    }

    public function bulkUploadStore(Request $request)
    {
        $request->validate([
            'match_by' => 'required|in:teacherId,email',
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Unable to read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            return back()->with('error', 'CSV appears to be empty or has no header row.');
        }

        $map = $this->teacherColumnMap();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $matchBy = $request->match_by;

        while (($row = fgetcsv($handle)) !== false) {
            $mapped = $this->mapCsvRow($header, $row, $map);

            // Skip completely empty rows
            if ($this->isRowEmpty($mapped)) {
                continue;
            }

            $matchValue = $mapped[$matchBy] ?? '';
            if ($matchValue === '') {
                $skipped++;
                continue;
            }

            $profile = TeacherManagement::where($matchBy, $matchValue)->first();

            if ($profile) {
                foreach ($mapped as $key => $val) {
                    if ($val !== '') {
                        $profile->$key = $val;
                    }
                }
                $profile->save();
                $updated++;
            } else {
                // Require teacherId and firstName to create a new record
                if (($mapped['teacherId'] ?? '') === '' || ($mapped['firstName'] ?? '') === '') {
                    $skipped++;
                    continue;
                }

                $profile = new TeacherManagement();
                foreach ($mapped as $key => $val) {
                    if ($val !== '') {
                        $profile->$key = $val;
                    }
                }
                $profile->save();
                $created++;
            }
        }

        fclose($handle);

        $message = "Bulk upload complete. Updated: $updated, Created: $created, Skipped: $skipped.";
        return redirect()->route('teacherBulkUpload')->with('success', $message);
    }

    public function downloadSample()
    {
        $path = public_path('samples/teacher_bulk_sample.csv');
        if (!file_exists($path)) {
            return back()->with('error', 'Sample file missing.');
        }
        return response()->download($path, 'teacher_bulk_sample.csv');
    }

    private function teacherColumnMap(): array
    {
        return [
            'teacherid'      => 'teacherId',
            'teacher_id'     => 'teacherId',
            'id'             => 'teacherId',
            'firstname'      => 'firstName',
            'first_name'     => 'firstName',
            'lastname'       => 'lastName',
            'last_name'      => 'lastName',
            'fathersname'    => 'fathersName',
            'fathername'     => 'fathersName',
            'father'         => 'fathersName',
            'mothersname'    => 'mothersName',
            'mothername'     => 'mothersName',
            'mother'         => 'mothersName',
            'gender'         => 'gender',
            'dob'            => 'dob',
            'designation'    => 'designation',
            'blgroup'        => 'blGroup',
            'bloodgroup'     => 'blGroup',
            'religion'       => 'religion',
            'email'          => 'email',
            'mail'           => 'email',
            'join'           => 'joinDate',
            'joindate'       => 'joinDate',
            'join_date'      => 'joinDate',
            'mobile'         => 'mobile',
            'phone'          => 'mobile',
            'contact'        => 'mobile',
            'address'        => 'address',
            'mpo'            => 'mpoIndex',
            'mpoindex'       => 'mpoIndex',
            'pds'            => 'pdsId',
            'pdsid'          => 'pdsId',
            'rank'           => 'rank',
        ];
    }

    private function mapCsvRow(array $header, array $row, array $map): array
    {
        $mapped = [];
        foreach ($header as $idx => $col) {
            $norm = strtolower(trim((string) $col));
            $norm = str_replace([' ', '-'], '_', $norm);
            if (!isset($map[$norm])) {
                continue;
            }
            $mapped[$map[$norm]] = trim((string) ($row[$idx] ?? ''));
        }
        return $mapped;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $val) {
            if (trim((string) $val) !== '') {
                return false;
            }
        }
        return true;
    }
    
    public function viewTeacher($id){
        $singleData= TeacherManagement::find($id);
        return view('cultivation.viewTeacher',['singleData'=>$singleData]);
    }
    public function editTeacher($id){
        $profileData = TeacherManagement::find($id);
        return view('cultivation.edit-teacher',['profileData'=>$profileData]);
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
            $teacherProfile->designation = $requ->designation;
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
}
