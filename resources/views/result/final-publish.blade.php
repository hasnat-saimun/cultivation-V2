@extends('result.include')
@section('backTitle')
Final Result Publish
@endsection
@section('backIndex')
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
                <form method="POST" action="{{ route('result.publish') }}">
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
                        <button type="submit" class="btn btn-success">Publish Ready Scope(s)</button>
                    </div>
                    <small class="text-muted d-block mt-2">If Section is empty, actual sections are validated and published atomically as separate scopes.</small>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Publication States</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Session</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Published</th>
                                <th>Status / Revision</th>
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
                                        <span class="badge {{ $pub->isPublished() ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($pub->status) }}
                                        </span>
                                        r{{ $pub->revision }}
                                        @if($pub->legacyImported)<small class="d-block text-muted">Legacy import</small>@endif
                                    </td>
                                    <td>
                                        @if($pub->isPublished())
                                        <form method="POST" action="{{ route('result.unpublish') }}">
                                            @csrf
                                            <input type="hidden" name="examId" value="{{ $pub->examId }}">
                                            <input type="hidden" name="sessionId" value="{{ $pub->sessionId }}">
                                            <input type="hidden" name="classId" value="{{ $pub->classId }}">
                                            <input type="hidden" name="groupId" value="{{ $pub->groupId }}">
                                            <input type="hidden" name="publication_revision" value="{{ $pub->revision }}">
                                            <input type="hidden" name="exact_scope" value="1">
                                            <input type="text" name="reason" maxlength="500" required
                                                class="form-control form-control-sm mb-1" placeholder="Unpublish reason">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Unpublish</button>
                                        </form>
                                        @else
                                        <form method="POST" action="{{ route('result.publish') }}">
                                            @csrf
                                            <input type="hidden" name="examId" value="{{ $pub->examId }}">
                                            <input type="hidden" name="sessionId" value="{{ $pub->sessionId }}">
                                            <input type="hidden" name="classId" value="{{ $pub->classId }}">
                                            <input type="hidden" name="groupId" value="{{ $pub->groupId }}">
                                            <input type="hidden" name="publication_revision" value="{{ $pub->revision }}">
                                            <input type="hidden" name="exact_scope" value="1">
                                            <button type="submit" class="btn btn-sm btn-success">Republish</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7">No publication states found.</td>
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
