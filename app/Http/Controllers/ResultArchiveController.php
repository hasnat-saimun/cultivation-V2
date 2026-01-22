<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResultArchive;
use App\Models\newAdmission;
use App\Models\Exam;

class ResultArchiveController extends Controller
{
    public function transcript($id)
    {
        $archive = ResultArchive::with('student')->findOrFail($id);
        $className = optional(\App\Models\classManage::find($archive->old_class))->className ?? $archive->old_class;
        $sessionName = optional(\App\Models\sessionManage::find($archive->old_session))->session ?? $archive->old_session;
        $sectionName = optional(\App\Models\sectionManage::find($archive->old_section))->section ?? $archive->old_section;

        // Use stored result data (already merged for pair subjects during archiving)
        $resultData = $archive->result_data;
        if (is_string($resultData)) {
            $resultData = json_decode($resultData, true) ?? [];
        }
        $transcriptData = is_array($resultData) ? $resultData : [];

        // Load grade list for display
        $gradeList = \App\Models\GradeList::orderBy('gradePoint','DESC')->get();
        
        return view('result.archiveTranscript', compact('archive', 'className', 'sessionName', 'sectionName', 'resultData', 'transcriptData', 'gradeList'));
    }

    public function index(Request $request)
    {
        $query = ResultArchive::query();

        if ($request->filled('className')) {
            $query->where('old_class', $request->input('className'));
        }
        if ($request->filled('sessionId')) {
            $query->where('old_session', $request->input('sessionId'));
        }
        if ($request->filled('sectionId')) {
            $query->where('old_section', $request->input('sectionId'));
        }
        if ($request->filled('roll')) {
            $query->where('old_roll', $request->input('roll'));
        }
        if ($request->filled('archive_year')) {
            $query->whereYear('created_at', $request->input('archive_year'));
        }
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->input('exam_id'));
        }
        $archives = $query->orderByRaw('CAST(old_roll as UNSIGNED) ASC')->get();

        // For filter dropdowns, load all possible values
        $classIds = ResultArchive::distinct()->pluck('old_class')->unique()->filter();
        $sessionIds = ResultArchive::distinct()->pluck('old_session')->unique()->filter();
        $sectionIds = ResultArchive::distinct()->pluck('old_section')->unique()->filter();

        $classNames = \App\Models\classManage::whereIn('id', $classIds)->pluck('className', 'id');
        $sessionNames = \App\Models\sessionManage::whereIn('id', $sessionIds)->pluck('session', 'id');
        $sectionNames = \App\Models\sectionManage::whereIn('id', $sectionIds)->pluck('section', 'id');

        $examList = Exam::pluck('examName', 'id');

        return view('result.resultArchive', compact('archives', 'classNames', 'sessionNames', 'sectionNames', 'examList'));
    }

}
