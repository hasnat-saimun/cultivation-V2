<?php

namespace App\Http\Controllers;

use App\Models\Marksheet;
use App\Models\Placement;
use App\Models\newAdmission;
use Illuminate\Http\Request;

class PlacementController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'sessionId' => $request->input('sessionId'),
            'classId' => $request->input('classId'),
            'groupId' => $request->input('groupId'),
            'examId' => $request->input('examId'),
        ];

        $query = Placement::query();
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $query->where($key, $value);
            }
        }
        $placements = $query->orderByDesc('gpa')->orderByDesc('totalMarks')->orderBy('position')->paginate(50);

        return view('placement.index', compact('placements', 'filters'));
    }

    public function recalculate(Request $request)
    {
        $request->validate([
            'sessionId' => 'required',
            'classId' => 'required',
            'examId' => 'required',
            'groupId' => 'nullable',
        ]);

        $sessionId = (string) $request->input('sessionId');
        $classId = (string) $request->input('classId');
        $groupId = $request->input('groupId') ? (string) $request->input('groupId') : null;
        $examId = (string) $request->input('examId');

        $marksQuery = Marksheet::query()
            ->where('sessionId', $sessionId)
            ->where('classId', $classId)
            ->where('examId', $examId);

        if ($groupId !== null) {
            $marksQuery->where('groupId', $groupId);
        }

        $marks = $marksQuery->get();

        // Wipe existing placements for these filters
        $wipeQuery = Placement::query()
            ->where('sessionId', $sessionId)
            ->where('classId', $classId)
            ->where('examId', $examId);
        if ($groupId !== null) { $wipeQuery->where('groupId', $groupId); }
        $wipeQuery->delete();

        // Group marks by studentId and compute GPA, totals
        $grouped = $marks->groupBy('studentId');

        $rows = [];
        foreach ($grouped as $studentId => $items) {
            $subjectsCount = $items->count();
            $totalGradePoints = $items->reduce(function ($carry, $item) {
                return $carry + (float) ($item->gradePoint ?? 0);
            }, 0.0);
            $totalMarks = $items->reduce(function ($carry, $item) {
                return $carry + (int) ($item->totalMarks ?? 0);
            }, 0);
            $gpa = $subjectsCount > 0 ? round($totalGradePoints / $subjectsCount, 2) : 0.0;

            // Optional status: fail if any subject has 0 gradePoint
            $hasFail = $items->contains(function ($item) {
                return (float) ($item->gradePoint ?? 0) <= 0.0;
            });
            $status = $hasFail ? 'Fail' : 'Pass';

            // Grab roll for tiebreakers if available
            $admission = newAdmission::query()->find($studentId);
            $roll = $admission?->rollNumber ?? null;

            $rows[] = [
                'studentId' => (string) $studentId,
                'sessionId' => $sessionId,
                'classId' => $classId,
                'groupId' => $groupId,
                'examId' => $examId,
                'subjectsCount' => $subjectsCount,
                'totalGradePoints' => round($totalGradePoints, 2),
                'gpa' => $gpa,
                'totalMarks' => $totalMarks,
                'status' => $status,
                '_roll' => $roll,
            ];
        }

        // Rank: GPA desc, totalMarks desc, roll asc
        usort($rows, function ($a, $b) {
            if ($a['gpa'] === $b['gpa']) {
                if ($a['totalMarks'] === $b['totalMarks']) {
                    return ($a['_roll'] ?? PHP_INT_MAX) <=> ($b['_roll'] ?? PHP_INT_MAX);
                }
                return $b['totalMarks'] <=> $a['totalMarks'];
            }
            return $b['gpa'] <=> $a['gpa'];
        });

        $position = 1;
        foreach ($rows as $row) {
            Placement::create([
                'studentId' => $row['studentId'],
                'sessionId' => $row['sessionId'],
                'classId' => $row['classId'],
                'groupId' => $row['groupId'],
                'examId' => $row['examId'],
                'subjectsCount' => $row['subjectsCount'],
                'totalGradePoints' => $row['totalGradePoints'],
                'gpa' => $row['gpa'],
                'totalMarks' => $row['totalMarks'],
                'position' => $position++,
                'status' => $row['status'],
            ]);
        }

        return redirect()->route('placements.index', [
            'sessionId' => $sessionId,
            'classId' => $classId,
            'groupId' => $groupId,
            'examId' => $examId,
        ])->with('success', 'Placements recalculated');
    }
}
