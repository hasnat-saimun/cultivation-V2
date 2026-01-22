                <script>
                $(document).ready(function() {
                    // Auto-submit form when exam or archive year changes
                    $('select[name="exam_id"], select[name="archive_year"]').on('change', function() {
                        $(this).closest('form').submit();
                    });
                });
                </script>
@extends('result.singleinclude')
@section('backTitle')
Result Archive
@endsection
@section('backIndex')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<!-- jQuery CDN (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<div class="main-website">
    <div class="main-content p-4">
        <div class="container-fluid mb-4">
            <form method="GET" action="{{ route('resultArchive') }}" class="row px-4 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Exam</label>
                    <select name="exam_id" class="form-control">
                        <option value="">All</option>
                        @foreach($examList as $id => $name)
                            <option value="{{ $id }}" {{ request('exam_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Archive Year</label>
                    <select name="archive_year" class="form-control">
                        <option value="">All</option>
                        @php
                            $years = $archives->pluck('created_at')->map(fn($dt) => \Carbon\Carbon::parse($dt)->format('Y'))->unique()->sort();
                        @endphp
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('archive_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="className" class="form-control">
                        <option value="">All</option>
                        @foreach($classNames as $id => $name)
                            <option value="{{ $id }}" {{ request('className') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Session</label>
                    <select name="sessionId" class="form-control">
                        <option value="">All</option>
                        @foreach($sessionNames as $id => $name)
                            <option value="{{ $id }}" {{ request('sessionId') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Section</label>
                    <select name="sectionId" class="form-control">
                        <option value="">All</option>
                        @foreach($sectionNames as $id => $name)
                            <option value="{{ $id }}" {{ request('sectionId') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Roll</label>
                    <input type="text" name="roll" class="form-control" value="{{ request('roll') }}" placeholder="Old Roll">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-success w-100">Filter</button>
                </div>
                @if(request()->hasAny(['className','sessionId','sectionId','roll','exam_id','archive_year']))
                <div class="col-md-1">
                    <a href="{{ route('resultArchive') }}" class="btn btn-warning w-100">Reset</a>
                </div>
                @endif
            </form>
        </div>

        <table id="archiveTable" class="table table-bordered table-striped align-middle display responsive">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Old Roll</th>
                    <th>Class</th>
                    <th>Session</th>
                    <th>Section</th>
                    <th>Archived At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($archives as $archive)
                <tr data-bs-toggle="collapse" data-bs-target="#result-{{ $archive->id }}" class="archive-row">
                    <td>{{ $archive->id }}</td>
                    <td>{{ optional($archive->student)->fullName ?? $archive->student_id }}</td>
                    <td>{{ $archive->old_roll }}</td>
                    <td>{{ $classNames[$archive->old_class] ?? $archive->old_class }}</td>
                    <td>{{ $sessionNames[$archive->old_session] ?? $archive->old_session }}</td>
                    <td>{{ $sectionNames[$archive->old_section] ?? $archive->old_section }}</td>
                    <td>{{ $archive->created_at }}</td>
                    <td>
                        <button class="btn btn-sm btn-info view-details-btn" type="button" data-archive-id="{{ $archive->id }}">View</button>
                        <a class="btn btn-sm btn-primary" target="_blank" href="{{ route('resultArchive.transcript', ['id' => $archive->id]) }}@if(request('exam_id'))?exam_id={{ request('exam_id') }}@endif">Transcript</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            @foreach($archives as $archive)
            <tr class="collapse bg-light archive-details" id="result-{{ $archive->id }}">
                <td colspan="8">
                    @include('result.transcriptArchive', [
                        'archive' => $archive,
                        'student' => $archive->student,
                        'className' => $classNames[$archive->old_class] ?? $archive->old_class,
                        'sessionName' => $sessionNames[$archive->old_session] ?? $archive->old_session,
                        'sectionName' => $sectionNames[$archive->old_section] ?? $archive->old_section,
                        'gradeList' => \App\Models\GradeList::orderBy('gradePoint','DESC')->get(),
                    ])
                </td>
            </tr>
            @endforeach
        </table>
        </div>
    </div>
</div>
<!-- DataTables JS (placed at end of body for reliability) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery) {
        $('#archiveTable').DataTable({
            order: [[2, 'asc']], // Roll number ASC
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                paginate: { previous: 'Prev', next: 'Next' }
            }
        });
        // Custom view button toggle for details row
        $('.view-details-btn').on('click', function(e) {
            e.preventDefault();
            var archiveId = $(this).data('archive-id');
            var detailsRow = $('#result-' + archiveId);
            if (detailsRow.length) {
                detailsRow.toggleClass('show');
                // Optionally scroll into view
                if (detailsRow.hasClass('show')) {
                    $('html,body').animate({ scrollTop: detailsRow.offset().top - 100 }, 300);
                }
            }
        });
    } else {
        console.error('jQuery not loaded for DataTables');
    }
});
</script>
    </div>
</div>
        </div>
    </div>
</div>
@endsection
