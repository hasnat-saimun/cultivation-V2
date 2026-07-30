@if(!$examId || !$classId || !$sessionId)
    <div class="result-empty-state alert alert-info no-print">
        <strong>Select result criteria</strong>
        <div>Please select required filters (Exam, Class &amp; Session) to view results.</div>
    </div>
@elseif($studentsLoaded && empty($tabulationRows))
    <div class="result-empty-state alert alert-warning no-print">
        <strong>No result data found</strong>
        <div>No marks were found for the selected exam, class, session, section/group and department.</div>
    </div>
@endif
