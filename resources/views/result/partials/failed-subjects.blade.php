@if(($result['failedSubjectCount'] ?? 0) > 0)
    <div class="{{ $containerClass ?? '' }} failed-subjects">
        <h4 class="{{ $headingClass ?? '' }}">Failed Subjects ({{ $result['failedSubjectCount'] }})</h4>
        <table class="failed-subject-grid">
            <tbody>
                @foreach($result['failedSubjectRows'] as $rowIndex => $failedSubjectRow)
                    <tr data-failed-subject-row="{{ $rowIndex + 1 }}">
                        @foreach($failedSubjectRow as $failedSubject)
                            <td data-failed-subject-item>{{ $failedSubject }}</td>
                        @endforeach
                        @for($emptyColumn = count($failedSubjectRow); $emptyColumn < 3; $emptyColumn++)
                            <td class="failed-subject-empty" aria-hidden="true"></td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
