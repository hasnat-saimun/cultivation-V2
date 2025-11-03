<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Exports\StudentTemplateExport;
use App\Models\newAdmission;

class StudentController extends Controller
{
    public function bulkUploadStudents(Request $request)
    {
        $request->validate([
            'student_file' => 'required|mimes:csv,xlsx,xls|max:2048'
        ]);

        try {
            Excel::import(new StudentsImport, $request->file('student_file'));
            
            return redirect()->back()->with('success', 'Students uploaded successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error uploading file: ' . $e->getMessage());
        }
    }

    public function downloadStudentTemplate()
    {
        return Excel::download(new StudentTemplateExport, 'student_template.xlsx');
    }
}