<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StaffManagement;
use App\Models\Designation;
use App\Imports\StaffImport;
use Maatwebsite\Excel\Facades\Excel;
use File;

class StaffController extends Controller
{
    
    public function addStaff(){
        $chk = StaffManagement::orderBy('id','DESC')->first();
        $designations = Designation::staffDesignations();
        return view('cultivation.add-staff',['chk'=>$chk, 'designations'=>$designations]);
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

            $staffProfile->designation   = $this->getDesignationName($requ->designation);
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
            'staff_id', 'first_name', 'last_name', 'fathers_name', 'mothers_name', 'gender', 'dob', 'designation',
            'blood_group', 'religion', 'email', 'join_date', 'mobile', 'address', 'rank'
        ];
        return view('cultivation.staff-bulk-upload', compact('matchOptions', 'allowedColumns'));
    }

    public function bulkUploadStore(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('csv_file');
            $import = new StaffImport();
            Excel::import($import, $file);

            $created = $import->getCreated();
            $updated = $import->getUpdated();
            $message = "Import completed! Created: {$created}, Updated: {$updated}";

            return redirect()->route('staffBulkUpload')->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadSample()
    {
        $path = public_path('samples/staff_bulk_sample.csv');
        if (!file_exists($path)) {
            return back()->with('error', 'Sample file missing.');
        }
        return response()->download($path, 'staff_bulk_sample.csv');
    }

    public function downloadTemplate()
    {
        $headers = [
            'staff_id',
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
            'rank'
        ];

        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="staff_template.csv"',
        ]);
    }

    // Legacy CSV helpers removed; using StaffImport via Excel now.
    
    
    public function viewStaff($id){
        $singleData= StaffManagement::find($id);
        return view('cultivation.viewStaff',['singleData'=>$singleData]);
    }
    public function editStaff($id){
        $profileData = StaffManagement::find($id);
        $designations = Designation::staffDesignations();
        return view('cultivation.edit-staff',['profileData'=>$profileData, 'designations'=>$designations]);
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
            $staffProfile->designation_id = $requ->designation;
            $staffProfile->designation_id = $requ->designation;
            $staffProfile->designation   = $this->getDesignationName($requ->designation);
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

    public function staffBulkDelete(Request $request)
    {
        try {
            $ids = json_decode($request->input('ids'), true);
            
            if (!is_array($ids) || empty($ids)) {
                return back()->with('error', 'No records selected');
            }

            $deleted = StaffManagement::whereIn('id', $ids)->delete();

            if ($deleted > 0) {
                return back()->with('success', "Successfully deleted $deleted staff member(s)");
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
        $staff = StaffManagement::select('id', 'staffId', 'firstName', 'lastName', 'avatar')
            ->orderBy('firstName')
            ->get();
        
        return view('cultivation.staff-bulk-photo-upload', compact('staff'));
    }

    public function bulkPhotoUploadStore(Request $request)
    {
        $request->validate([
            'staff_ids' => 'array',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:5120',
        ]);

        $staffIds = $request->input('staff_ids', []);
        $updated = 0;
        $skipped = 0;

        foreach ($staffIds as $sid) {
            $fileKey = "photos.$sid";
            if (!$request->hasFile($fileKey)) {
                $skipped++;
                continue;
            }

            $member = StaffManagement::find($sid);
            if (!$member) {
                $skipped++;
                continue;
            }

            $photo = $request->file($fileKey);
            $newAvatar = rand() . date('Ymd') . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('upload/image/staff/'), $newAvatar);

            if (!empty($member->avatar) && File::exists(public_path('upload/image/staff/' . $member->avatar))) {
                File::delete(public_path('upload/image/staff/' . $member->avatar));
            }

            $member->avatar = $newAvatar;
            $member->save();
            $updated++;
        }

        $message = "Updated $updated photo(s).";
        if ($skipped > 0) {
            $message .= " Skipped $skipped without files.";
        }

        return redirect()->route('staffBulkPhotoUpload')->with('success', $message);
    }

    /**
     * Get list of staff as JSON for API
     */
    public function getStaffList()
    {
        $staff = StaffManagement::select('id', 'firstName', 'lastName')
            ->orderBy('firstName')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'staff_id' => $member->id,
                    // Keep snake_case keys for API compatibility, read camelCase fields
                    'first_name' => $member->firstName,
                    'last_name' => $member->lastName,
                    'full_name' => $member->firstName . ' ' . $member->lastName
                ];
            });

        return response()->json($staff);
    }

    /**
     * Export staff list as PDF
     */
    public function exportPDF()
    {
        $staff = StaffManagement::orderBy('firstName')->get();
        $pdf = \PDF::loadView('exports.staff-list-pdf', ['staff' => $staff]);
        return $pdf->download('staff-list-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Show bulk update form for staff
     */
    public function bulkUpdateForm()
    {
        $staff = StaffManagement::select('id', 'staffId', 'firstName', 'lastName', 'designation', 'designation_id', 'email', 'mobile', 'address', 'gender', 'dob', 'joinDate')
            ->orderBy('firstName')->get();
        $designations = Designation::staffDesignations();
        return view('cultivation.staff-bulk-update', compact('staff', 'designations'));
    }

    /**
     * Store bulk updates for staff (per-row editing)
     */
    public function bulkUpdateStore(Request $request)
    {
        $request->validate([
            'staff' => 'required|array',
            'staff.*.id' => 'required|exists:staff_management,id',
            'staff.*.firstName' => 'nullable|string|max:255',
            'staff.*.lastName' => 'nullable|string|max:255',
            'staff.*.designation' => 'nullable|integer|exists:designations,id',
            'staff.*.email' => 'nullable|email|max:255',
            'staff.*.mobile' => 'nullable|string|max:20',
            'staff.*.address' => 'nullable|string',
            'staff.*.gender' => 'nullable|in:Male,Female,Others',
            'staff.*.dob' => 'nullable|date',
            'staff.*.joinDate' => 'nullable|date',
        ]);

        $updated = 0;
        foreach ($request->input('staff', []) as $staffData) {
            if (!empty($staffData['id'])) {
                $member = StaffManagement::find($staffData['id']);
                if ($member) {
                    $member->firstName = $staffData['firstName'] ?? $member->firstName;
                    $member->lastName = $staffData['lastName'] ?? $member->lastName;
                    $member->email = $staffData['email'] ?? $member->email;
                    $member->mobile = $staffData['mobile'] ?? $member->mobile;
                    $member->address = $staffData['address'] ?? $member->address;
                    $member->gender = $staffData['gender'] ?? $member->gender;
                    $member->dob = $staffData['dob'] ?? $member->dob;
                    $member->joinDate = $staffData['joinDate'] ?? $member->joinDate;
                    
                    // Handle designation exactly like single profile update
                    if (!empty($staffData['designation'])) {
                        $member->designation_id = $staffData['designation'];
                        $member->designation = $this->getDesignationName($staffData['designation']);
                    }
                    
                    $member->save();
                    $updated++;
                }
            }
        }

        return redirect()->route('staffBulkUpdate')->with('success', "Successfully updated {$updated} staff member(s)");
    }
}
