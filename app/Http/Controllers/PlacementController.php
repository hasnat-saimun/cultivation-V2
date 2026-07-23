<?php

namespace App\Http\Controllers;

use App\Models\Marksheet;
use App\Models\Placement;
use App\Models\newAdmission;
use Illuminate\Http\Request;
use App\Services\ResultCalculation\CentralizedPlacementRecalculator;
use App\Services\ResultCalculation\PlacementRecalculationException;

class PlacementController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'sessionId' => $request->input('sessionId'),
            'classId' => $request->input('classId'),
            'groupId' => $request->input('groupId'),
            'examId' => $request->input('examId'),
            'departmentId' => $request->input('departmentId'),
        ];

        $query = Placement::query();
        foreach (['sessionId', 'classId', 'groupId', 'examId'] as $key) {
            $value = $filters[$key] ?? null;
            if (!empty($value)) {
                $query->where($key, $value);
            }
        }
        if (!empty($filters['departmentId'])) {
            $studentIds = newAdmission::query()
                ->where('departmentName', (int)$filters['departmentId'])
                ->pluck('id');
            $query->whereIn('studentId', $studentIds);
        }
        $placements = $query->orderByDesc('gpa')->orderByDesc('totalMarks')->orderBy('position')->paginate(50);

        return view('placement.index', compact('placements', 'filters'));
    }

    public function recalculate(Request $request, CentralizedPlacementRecalculator $centralized)
    {
        $request->validate([
            'sessionId' => 'required',
            'classId' => 'required',
            'examId' => 'required',
            'groupId' => 'nullable',
            'departmentId' => 'nullable',
            'force' => 'nullable|boolean',
        ]);

        $sessionId = (string) $request->input('sessionId');
        $classId = (string) $request->input('classId');
        $groupId = $request->input('groupId') ? (string) $request->input('groupId') : null;
        $examId = (string) $request->input('examId');
        $departmentId = $request->input('departmentId') ? (int)$request->input('departmentId') : null;

        if (config('result_engine.placement_enabled', false)) {
            try {
                $report = $centralized->recalculate(
                    (int) $examId, (int) $classId, (int) $sessionId, $groupId === null ? null : (int) $groupId,
                    $departmentId, false, $request->boolean('force'), session('cultivationAdmin'),
                );
                return redirect()->route('placements.index', [
                    'sessionId' => $sessionId, 'classId' => $classId, 'groupId' => $groupId,
                    'examId' => $examId, 'departmentId' => $departmentId,
                ])->with('success', "Centralized placements recalculated using {$report['rankingMethod']}: {$report['rowsInserted']} rows inserted.");
            } catch (PlacementRecalculationException $exception) {
                $first = $exception->report['blockingErrors'][0]['message'] ?? $exception->getMessage();
                return back()->withInput()->with('error', $first);
            }
        }

        $marksQuery = Marksheet::query()
            ->where('sessionId', $sessionId)
            ->where('classId', $classId)
            ->where('examId', $examId);

        if ($groupId !== null) {
            $marksQuery->where('groupId', $groupId);
        }
        if ($departmentId !== null) {
            $studentIds = newAdmission::query()
                ->where('departmentName', $departmentId)
                ->pluck('id');
            $marksQuery->whereIn('studentId', $studentIds);
        }

        $marks = $marksQuery->get();

        // Wipe existing placements for these filters
        $wipeQuery = Placement::query()
            ->where('sessionId', $sessionId)
            ->where('classId', $classId)
            ->where('examId', $examId);
        if ($groupId !== null) { $wipeQuery->where('groupId', $groupId); }
        if ($departmentId !== null) {
            $wipeStudentIds = newAdmission::query()
                ->where('departmentName', $departmentId)
                ->pluck('id');
            $wipeQuery->whereIn('studentId', $wipeStudentIds);
        }
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
            'departmentId' => $departmentId,
        ])->with('success', 'Placements recalculated');
    }
}
