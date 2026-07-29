@php
    use App\Services\ResultCalculation\SubjectHeaderFormatter;

    $mode = strtolower((string) ($tableMode ?? ''));
    if ($mode === '' && !empty($tableRows)) {
        $status = strtolower((string) ($tableRows[0]['reportStatus'] ?? $tableRows[0]['classification'] ?? ''));
        $mode = match ($status) {
            'pass' => 'pass',
            'fail' => 'fail',
            'incomplete' => 'incomplete',
            'absent' => 'absent',
            default => 'pass',
        };
    }
    $isPass = $mode === 'pass';
    $isFail = $mode === 'fail';
    $isIncomplete = $mode === 'incomplete';
    $isAbsent = $mode === 'absent';
    $slStart = max(1, (int) ($slStart ?? 1));
@endphp

<table class="result-table">
    <thead>
    <tr>
        <th class="sl-col">SL</th><th class="roll-col">Roll</th><th class="id-col">Student ID</th><th class="name-col">Student Name</th>
        @if($isPass)
            @foreach($subjects as $subject)
                <th title="{{ $subject->full_name }}">{{ $subject->display_name }}{{ $subject->is_fourth_subject ? ' (4th)' : '' }}</th>
            @endforeach
            <th>Total Marks</th><th>Optional Marks</th><th>GPA</th><th>Grade</th><th>Merit Position</th><th class="d-print-none">Transcript</th>
        @elseif($isFail)
            @foreach($subjects as $subject)
                <th title="{{ $subject->full_name }}">{{ $subject->display_name }}{{ $subject->is_fourth_subject ? ' (4th)' : '' }}</th>
            @endforeach
            <th>Total Marks</th><th>Optional Marks</th><th>GPA</th><th>Grade</th><th>Merit Position</th><th class="d-print-none">Transcript</th>
        @elseif($isIncomplete)
            <th>Missing Subject(s)</th>
        @elseif($isAbsent)
            @php /* No extra columns for absent mode. */ @endphp
        @endif
    </tr>
    </thead>
    <tbody>
    @foreach($tableRows as $row)
        @php
            $missingRequiredSubjects = collect($row['missingSubjectNames'] ?? [])->values();
            $missingRequiredFullNames = $missingRequiredSubjects
                ->map(fn ($name) => SubjectHeaderFormatter::normalizeName((string) $name))
                ->values();
            $missingRequiredShortNames = $missingRequiredFullNames
                ->map(fn ($name) => SubjectHeaderFormatter::shortLabel((string) $name))
                ->values();
            $transcriptHref = route('marksheetGenerate', [
                'stdId' => ($row['student']->stdId ?? $row['studentIdentity']['id']),
                'studentId' => $row['studentIdentity']['id'],
                'examId' => request('examId'),
                'classId' => request('classId'),
                'sessionId' => request('sessionId'),
                'sectionId' => request('sectionId'),
                'departmentId' => request('departmentId'),
            ]);
        @endphp
        <tr>
            <td class="sl-col">{{ $slStart + $loop->index }}</td><td class="roll-col">{{ $row['studentIdentity']['roll'] }}</td>
            <td class="id-col">{{ $row['student']->stdId ?? $row['studentIdentity']['id'] }}</td>
            <td class="name-col">{{ $row['studentIdentity']['name'] }}</td>
            @if($isPass)
                @foreach($subjects as $subject)
                    <td>
                        @php $cell = $row['cells'][$subject->cellKey] ?? null; @endphp
                        @if($cell)
                            {{ $cell['total'] }}
                            <span class="subject-outcome">({{ $cell['grade'] }}/{{ $cell['gradePoint'] }})</span>
                        @else
                            -
                        @endif
                    </td>
                @endforeach
                <td>{{ $row['totalMarks'] }}</td><td>{{ number_format($row['optionalBonus'], 2) }}</td>
                <td>{{ $row['finalGpa'] ?? $row['classification'] }}</td><td>{{ $row['finalLetter'] }}</td>
                <td>{{ $row['meritPosition'] ?? '-' }}</td>
                <td class="d-print-none">
                    <a class="btn btn-outline-primary btn-sm d-print-none transcript-print-btn" target="_blank" href="{{ $transcriptHref }}">View</a>
                </td>
            @elseif($isFail)
                @foreach($subjects as $subject)
                    @php
                        $cell = $row['cells'][$subject->cellKey] ?? null;
                        $isFailedRequiredCell = $cell
                            && ($cell['status'] ?? '') === 'Fail'
                            && strtolower((string) ($cell['type'] ?? '')) !== 'optional';
                    @endphp
                    <td class="{{ $isFailedRequiredCell ? 'failed-subject-cell' : '' }}">
                        @if($cell)
                            {{ $cell['total'] }}
                            <span class="subject-outcome">({{ $cell['grade'] }}/{{ $cell['gradePoint'] }})</span>
                        @else
                            -
                        @endif
                    </td>
                @endforeach
                <td>{{ $row['totalMarks'] }}</td><td>{{ number_format($row['optionalBonus'], 2) }}</td>
                <td>{{ $row['finalGpa'] ?? $row['classification'] }}</td><td>{{ $row['finalLetter'] }}</td>
                <td>{{ $row['meritPosition'] ?? '-' }}</td>
                <td class="d-print-none">
                    <a class="btn btn-outline-primary btn-sm d-print-none transcript-print-btn" target="_blank" href="{{ $transcriptHref }}">View</a>
                </td>
            @elseif($isIncomplete)
                <td title="{{ $missingRequiredFullNames->implode(', ') }}">
                    {{ $missingRequiredShortNames->isEmpty() ? '-' : $missingRequiredShortNames->implode(', ') }}
                </td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>
