@extends('cultivation.include')
@section('backTitle')
Bulk Photo Update
@endsection
@section('backIndex')
<div class="row gutters-20 mt-4">
    <div class="col-12 col-md-12 mx-auto">
        <div class="card card-default">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Classwise Bulk Photo Update</h3>
                <a href="{{ route('studentList') }}" class="btn btn-secondary btn-sm">Back to Student List</a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success flash-alert">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="sticky-alert sticky-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="mb-4" method="get" action="{{ route('studentPhotoBulk') }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Session</label>
                            <select name="sessionId" class="form-control" required>
                                <option value="">Select Session</option>
                                @foreach($sessionDetails as $session)
                                    <option value="{{ $session->id }}" {{ ($filters['sessionId'] ?? '') == $session->id ? 'selected' : '' }}>{{ $session->session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Class</label>
                            <select name="classId" class="form-control" required>
                                <option value="">Select Class</option>
                                @foreach($classDetails as $class)
                                    <option value="{{ $class->id }}" {{ ($filters['classId'] ?? '') == $class->id ? 'selected' : '' }}>{{ $class->className }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Section</label>
                            <select name="sectionId" class="form-control" required>
                                <option value="">Select Section</option>
                                @foreach($sectionDetails as $section)
                                    <option value="{{ $section->id }}" {{ ($filters['sectionId'] ?? '') == $section->id ? 'selected' : '' }}>{{ $section->section }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Load Students</button>
                        </div>
                    </div>
                </form>

                @if(($filters['classId'] ?? false) && ($filters['sessionId'] ?? false) && ($filters['sectionId'] ?? false))
                    @if($students->count() > 0)
                        <form method="post" action="{{ route('studentPhotoBulkUpload') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="classId" value="{{ $filters['classId'] }}">
                            <input type="hidden" name="sessionId" value="{{ $filters['sessionId'] }}">
                            <input type="hidden" name="sectionId" value="{{ $filters['sectionId'] }}">
                            <div class="sticky-alert sticky-info">Only rows with a selected file will be updated. Max size 2MB; supported types: jpg, jpeg, png, gif, webp, avif.</div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th>Roll</th>
                                            <th>Name</th>
                                            <th>Current Photo</th>
                                            <th>Upload New</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($students as $student)
                                            <tr>
                                                <td>{{ $student->rollNumber }}</td>
                                                <td>{{ $student->fullName }} {{ $student->sureName }}</td>
                                                <td>
                                                    @if(!empty($student->avatar))
                                                        <img src="{{ asset('public/upload/image/student/'.$student->avatar) }}" alt="Current photo" style="height:60px;width:60px;object-fit:cover;">
                                                    @else
                                                        <span class="text-muted">No photo</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="hidden" name="student_ids[]" value="{{ $student->id }}">
                                                    <input type="file" name="photos[{{ $student->id }}]" accept="image/*" class="form-control" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-success">Update Selected Photos</button>
                            </div>
                        </form>
                    @else
                        <div class="sticky-alert sticky-warning mb-0">No students found for this filter.</div>
                    @endif
                @else
                    <div class="sticky-alert sticky-secondary mb-0">Select session, class, and section to load students.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        const alerts = document.querySelectorAll('.flash-alert');
        if(alerts.length){
            setTimeout(() => {
                alerts.forEach(el => {
                    el.classList.add('fade');
                    el.classList.remove('show');
                    setTimeout(() => { el.remove(); }, 400);
                });
            }, 3500);
        }
    })();
</script>
@endpush

@push('styles')
<style>
    .sticky-alert { padding: 12px 16px; border: 1px solid transparent; border-radius: 4px; margin-bottom: 16px; }
    .sticky-danger { background: #f8d7da; border-color: #f5c2c7; color: #842029; }
    .sticky-info { background: #cff4fc; border-color: #b6effb; color: #055160; }
    .sticky-warning { background: #fff3cd; border-color: #ffecb5; color: #664d03; }
    .sticky-secondary { background: #e2e3e5; border-color: #d3d6d8; color: #41464b; }
</style>
@endpush
