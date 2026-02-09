@extends('result.include')
@section('backTitle')
Final Result Publish
@endsection
@section('backIndex')
@php
    $publishedMap = [];
    foreach ($publishedList as $row) {
        $key = implode(':', [$row->examId, $row->sessionId, $row->classId, $row->groupId ?? '']);
        $publishedMap[$key] = $row;
    }
@endphp
<div class="row gutters-20">
    <div class="col-12">
        @if(session()->has('success'))
            <div class="alert alert-success">{{ session()->get('success') }}</div>
        @endif
        @if(session()->has('error'))
            <div class="alert alert-danger">{{ session()->get('error') }}</div>
        @endif
    </div>
</div>
<div class="row gutters-20">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Publish Final Result</h4>
                <form method="POST" action="{{ route('result.final.publish.store') }}">
                    @csrf
                    <div class="form-group mb-2">
                        <label>Exam *</label>
                        <select class="select2" name="examId" required>
                            <option value="">Select *</option>
                            @foreach($examList as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->examName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-2">
                        <label>Session *</label>
                        <select class="select2" name="sessionId" required>
                            <option value="">Select *</option>
                            @foreach($sessionList as $sess)
                                <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-2">
                        <label>Class *</label>
                        <select class="select2" name="classId" required>
                            <option value="">Select *</option>
                            <option value="all">All Classes</option>
                            @foreach($classList as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label>Section/Group (optional)</label>
                        <select class="select2" name="groupId">
                            <option value="">All Sections</option>
                            @foreach($sectionList as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="publish" class="btn btn-success">Publish</button>
                        <button type="submit" name="action" value="unpublish" class="btn btn-outline-danger">Unpublish</button>
                    </div>
                    <small class="text-muted d-block mt-2">If Section is empty, the publish applies to all sections for the class.</small>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Published Results</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Session</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Published</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($publishedList->count() > 0)
                                @foreach($publishedList as $pub)
                                <tr>
                                    <td>{{ $examNames[$pub->examId] ?? $pub->examId }}</td>
                                    <td>{{ $sessionNames[$pub->sessionId] ?? $pub->sessionId }}</td>
                                    <td>{{ $classNames[$pub->classId] ?? $pub->classId }}</td>
                                    <td>{{ $sectionNames[$pub->groupId] ?? ($pub->groupId ? $pub->groupId : 'All') }}</td>
                                    <td>{{ $pub->published_at ? $pub->published_at->format('Y-m-d H:i') : '-' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('result.final.publish.store') }}">
                                            @csrf
                                            <input type="hidden" name="examId" value="{{ $pub->examId }}">
                                            <input type="hidden" name="sessionId" value="{{ $pub->sessionId }}">
                                            <input type="hidden" name="classId" value="{{ $pub->classId }}">
                                            <input type="hidden" name="groupId" value="{{ $pub->groupId }}">
                                            <button type="submit" name="action" value="unpublish" class="btn btn-sm btn-outline-danger">Unpublish</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6">No published results found.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
