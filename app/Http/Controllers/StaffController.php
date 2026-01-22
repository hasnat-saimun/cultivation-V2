<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StaffManagement;
use File;

class StaffController extends Controller
{
    
    public function addStaff(){
        $chk = StaffManagement::orderBy('id','DESC')->first();
        return view('cultivation.add-staff',['chk'=>$chk]);
    }
    public function confirmStaff(Request $requ){
        $chk = StaffManagement::where(['email'=>$requ->staffMail])->orWhere(['staffId'=>$requ->staffId])->get();
        if(count($chk)>0):
            // return 0;
            return back()->with('error','Opps Sorry! Profile already created');
        else:
            // return 1;
            $staffProfile    = new StaffManagement();

            if(!empty($requ->file('avatar'))):
                $staffProfileAvatar = $requ->file('avatar');
            $validated = $requ->validate([
                    'avatar' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'avatar.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'avatar.max'    => 'Each file must be less than 5MB.'
                ]);
                $newStaffAvatar   = rand().date('Ymd').'.'.$staffProfileAvatar->getClientOriginalExtension();
                $staffProfileAvatar->move(public_path('upload/image/staff'),$newStaffAvatar);
                $staffProfile->avatar        = $newStaffAvatar; 
            endif;

            $staffProfile->firstName     = $requ->firstName;
            $staffProfile->lastName      = $requ->lastName;
            $staffProfile->fathersName   = $requ->fathersName;
            $staffProfile->mothersName   = $requ->mothersName;
            $staffProfile->address       = $requ->address;
            $staffProfile->gender        = $requ->gender;
            $staffProfile->dob           = $requ->dob;
            $staffProfile->joinDate      = $requ->joinDate;
            $staffProfile->email         = $requ->staffMail;
            $staffProfile->mobile        = $requ->mobile;
            $staffProfile->blGroup       = $requ->blGroup;
            $staffProfile->designation   = $requ->designation;
            $staffProfile->religion      = $requ->religion;
            $staffProfile->rank           = $requ->rank;
            $staffProfile->staffId        = $requ->staffId;
            $staffProfile->save();

            return back()->with('success','Owo Success! Profile created successfully');
        endif;
    }

    public function staffList(){
        $profileData = StaffManagement::all();
        return view('cultivation.staffList',['profileData'=>$profileData]);
    }

    public function bulkUploadForm()
    {
        $matchOptions = ['staffId' => 'Staff ID', 'email' => 'Email'];
        $allowedColumns = [
            'staffId', 'firstName', 'lastName', 'fathersName', 'mothersName', 'gender', 'dob', 'designation',
            'blGroup', 'religion', 'email', 'joinDate', 'mobile', 'address', 'rank'
        ];
        return view('cultivation.staff-bulk-upload', compact('matchOptions', 'allowedColumns'));
    }

    public function bulkUploadStore(Request $request)
    {
        $request->validate([
            'match_by' => 'required|in:staffId,email',
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

        $map = $this->staffColumnMap();
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $matchBy = $request->match_by;

        while (($row = fgetcsv($handle)) !== false) {
            $mapped = $this->mapCsvRow($header, $row, $map);

            if ($this->isRowEmpty($mapped)) {
                continue;
            }

            $matchValue = $mapped[$matchBy] ?? '';
            if ($matchValue === '') {
                $skipped++;
                continue;
            }

            $profile = StaffManagement::where($matchBy, $matchValue)->first();

            if ($profile) {
                foreach ($mapped as $key => $val) {
                    if ($val !== '') {
                        $profile->$key = $val;
                    }
                }
                $profile->save();
                $updated++;
            } else {
                if (($mapped['staffId'] ?? '') === '' || ($mapped['firstName'] ?? '') === '') {
                    $skipped++;
                    continue;
                }
                $profile = new StaffManagement();
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
        return redirect()->route('staffBulkUpload')->with('success', $message);
    }

    public function downloadSample()
    {
        $path = public_path('samples/staff_bulk_sample.csv');
        if (!file_exists($path)) {
            return back()->with('error', 'Sample file missing.');
        }
        return response()->download($path, 'staff_bulk_sample.csv');
    }

    private function staffColumnMap(): array
    {
        return [
            'staffid'       => 'staffId',
            'staff_id'      => 'staffId',
            'id'            => 'staffId',
            'firstname'     => 'firstName',
            'first_name'    => 'firstName',
            'lastname'      => 'lastName',
            'last_name'     => 'lastName',
            'fathersname'   => 'fathersName',
            'fathername'    => 'fathersName',
            'father'        => 'fathersName',
            'mothersname'   => 'mothersName',
            'mothername'    => 'mothersName',
            'mother'        => 'mothersName',
            'gender'        => 'gender',
            'dob'           => 'dob',
            'designation'   => 'designation',
            'blgroup'       => 'blGroup',
            'bloodgroup'    => 'blGroup',
            'religion'      => 'religion',
            'email'         => 'email',
            'mail'          => 'email',
            'join'          => 'joinDate',
            'joindate'      => 'joinDate',
            'join_date'     => 'joinDate',
            'mobile'        => 'mobile',
            'phone'         => 'mobile',
            'contact'       => 'mobile',
            'address'       => 'address',
            'rank'          => 'rank',
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
    
    
    public function viewStaff($id){
        $singleData= StaffManagement::find($id);
        return view('cultivation.viewStaff',['singleData'=>$singleData]);
    }
    public function editStaff($id){
        $profileData = StaffManagement::find($id);
        return view('cultivation.edit-staff',['profileData'=>$profileData]);
    }

    public function updateStaff(Request $requ){
         $staffProfile = StaffManagement::find($requ->staffId);
        if(empty($staffProfile)):
            return back()->with('error','Opps Sorry! Profile not found for update');
        else:
            // return 1;
            $staffProfile->firstName     = $requ->firstName;
            $staffProfile->lastName      = $requ->lastName;
            $staffProfile->fathersName   = $requ->fathersName;
            $staffProfile->mothersName   = $requ->mothersName;
            $staffProfile->address       = $requ->address;
            $staffProfile->gender        = $requ->gender;
            $staffProfile->dob           = $requ->dob;
            $staffProfile->joinDate      = $requ->joinDate;
            $staffProfile->email         = $requ->staffMail;
            $staffProfile->mobile        = $requ->mobile;
            $staffProfile->blGroup       = $requ->blGroup;
            $staffProfile->designation   = $requ->designation;
            $staffProfile->religion      = $requ->religion;
            $staffProfile->rank      = $requ->rank;
            $staffProfile->staffId     = $requ->staffId;

            if(!empty($requ->file('avatar'))):
                $staffProfileAvatar = $requ->file('avatar');
            $validated = $requ->validate([
                    'avatar' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'avatar.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'avatar.max'    => 'Each file must be less than 5MB.'
                ]);
                $newStaffAvatar   = rand().date('Ymd').'.'.$staffProfileAvatar->getClientOriginalExtension();
                $staffProfileAvatar->move(public_path('upload/image/staff'),$newStaffAvatar);
                $staffProfile->avatar        = $newStaffAvatar;
            endif;
            $staffProfile->save();

            return back()->with('success','Owo Success! Profile updated successfully');
        endif;
    }

    public function delStaff($id){
        $staffProfileData = StaffManagement::find($id);
        if(empty($staffProfileData)):
            return back()->with('error','Sorry! Profile failed to delete');
        else:
            $staffProfileData->delete();
            return back()->with('success','Success! Profile successfully delete');
        endif;
    }

    public function delStaffPhoto($id){
        $staffProfileData = StaffManagement::find($id);
        if(empty($staffProfileData)):
            // return public_path('uploads/image/staff/'.$staffProfileData->avatar);
            return back()->with('error','Sorry! Profile picture failed to delete');
        else:
            if (File::exists(public_path('upload/image/staff/'.$staffProfileData->avatar))) {
                File::delete(public_path('upload/image/staff/'.$staffProfileData->avatar));
            }
            // return public_path('upload/image/staff/'.$staffProfileData->avatar);
            $staffProfileData->avatar        = "";
            $staffProfileData->save();
            return back()->with('success','Success! Profile picture deleted successfully');
        endif;
    }

    public function updateStaffPhoto(Request $requ){
        $staffProfileData = StaffManagement::find($requ->staffId);
        if($staffProfileData):
            if(!empty($requ->avatar)):
                $staffAvatar = $requ->file('avatar');
                $validated = $requ->validate([
                    'avatar' => 'required|mimes:pdf,jpeg,png,jpg,gif,webp,avif,|max:5120',
                     // max 5 MB
                ],
                [
                    'avatar.mimes'  => 'Allowed formats: PDF, JPEG, PNG, JPG, GIF, WEBP, AVIF.',
                    'avatar.max'    => 'Each file must be less than 5MB.'
                ]);
                $newStaffAvatar = rand().date('Ymd').'.'.$staffAvatar->getClientOriginalExtension();
                $staffAvatar->move(public_path('upload/image/staff/'),$newStaffAvatar);

                $staffProfileData->avatar = $newStaffAvatar;
            endif;
                
            if($staffProfileData->save()):
                return back()->with("success",'data update success');
            else:
                return back()->with("error",'data update failed');
            endif;
        else:
            return back()->with('error','Data update failed');
        endif;
    }
}
