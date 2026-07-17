<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use App\Exports\StudentTemplateExport;
use App\Models\newAdmission;

class StudentController extends Controller
{
    public function bulkUploadStudents(Request $request)
    {
        $request->validate([
            'student_file' => 'required_without:file|mimes:csv,xlsx,xls|max:2048',
            'file' => 'required_without:student_file|mimes:csv,xlsx,xls|max:2048'
        ]);

        try {
            $upload = $request->file('file') ?? $request->file('student_file');
            Excel::import(new StudentsImport(), $upload);
            
            return redirect()->back()->with('success', 'Students uploaded successfully!');
        } catch (\Exception $e) {
            Log::error('Student import failed', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Student import failed. Please try again or contact support.');
        }
    }

    public function downloadStudentTemplate()
    {
        return Excel::download(new StudentTemplateExport, 'student_template.xlsx');
    }
}