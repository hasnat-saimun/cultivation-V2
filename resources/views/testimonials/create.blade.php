@extends('cultivation.include')
@section('backTitle')
{{ isset($testimonial) ? 'Edit Testimonial' : 'Create Testimonial' }}
@endsection
@section('backIndex')
<div class="row gutters-20 mt-4 justify-content-center">
    <div class="col-lg-8">
        <div class="card card-default">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h3 class="mb-0">{{ isset($testimonial) ? 'Edit' : 'Create' }} Testimonial for {{ $admission->fullName ?? $admission->sureName ?? 'Student' }}</h3>
                <div class="d-print-none">
                    @if(isset($testimonial))
                        <a href="{{ route('testimonials.show', $testimonial->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    @else
                        <a href="{{ route('studentList') }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="p-2 alert-info mb-4">
                    <strong>Student Details</strong> (read-only; from database)
                    <div class="row mt-2">
                        <div class="col-md-6 small">Name: <strong>{{ $admission->fullName ?? '' }} {{   $admission->sureName ?? '' }}</strong></div>
                        <div class="col-md-6 small">DOB: <strong>{{ isset($admission->dob) ? date('d M Y', strtotime($admission->dob)) : 'N/A' }}</strong></div>
                        <div class="col-md-6 small">Father: <strong>{{ $admission->father ?? 'N/A' }}</strong></div>
                        <div class="col-md-6 small">Mother: <strong>{{ $admission->mother ?? 'N/A' }}</strong></div>
                        <div class="col-12 small">Address: <strong>{{ $admission->address ?? 'N/A' }}</strong></div>
                    </div>
                </div>
                <form method="POST" action="{{ isset($testimonial) ? route('testimonials.update') : route('testimonials.store') }}">
                    @csrf
                    @if(isset($testimonial))
                        <input type="hidden" name="id" value="{{ $testimonial->id }}">
                    @else
                        <input type="hidden" name="admission_id" value="{{ $admission->id }}">
                    @endif
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SSC/HSC Year</label>
                            <input type="text" name="ssc_year" class="form-control" value="{{ old('ssc_year', $testimonial->ssc_year ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SSC/HSC Roll No</label>
                            <input type="text" name="roll_no" class="form-control" value="{{ old('roll_no', $testimonial->roll_no ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SSC/HSC Registration No</label>
                            <input type="text" name="reg_no" class="form-control" value="{{ old('reg_no', $testimonial->reg_no ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">GPA</label>
                            <input type="text" name="gpa" class="form-control" value="{{ old('gpa', $testimonial->gpa ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Grade</label>
                            <input type="text" name="grade" class="form-control" value="{{ old('grade', $testimonial->grade ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject/Department</label>
                            <input type="text" name="subject" class="form-control" value="{{ old('subject', $testimonial->subject ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Education Board</label>
                            <input type="text" name="education_board" class="form-control" placeholder="e.g., Cumilla/Sylhet/Dhaka/BTEB" value="{{ old('education_board', $testimonial->education_board ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Exam Name</label>
                            <input type="text" name="exam_name" class="form-control" placeholder="e.g., S.S.C/H.S.C/Vocational" value="{{ old('exam_name', $testimonial->exam_name ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ref No</label>
                            <input type="text" name="ref_no" class="form-control" value="{{ old('ref_no', $testimonial->ref_no ?? '') }}" placeholder="Will be generated automatically" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Issue Date</label>
                            <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', isset($testimonial->issue_date) ? date('Y-m-d', strtotime($testimonial->issue_date)) : '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Composed By</label>
                            <input type="text" name="composed_by" class="form-control" value="{{ old('composed_by', $testimonial->composed_by ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Composed Date</label>
                            <input type="date" name="composed_date" class="form-control" value="{{ old('composed_date', isset($testimonial->composed_date) ? date('Y-m-d', strtotime($testimonial->composed_date)) : '') }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control" value="{{ old('remarks', $testimonial->remarks ?? '') }}">
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-success px-4">{{ isset($testimonial) ? 'Update' : 'Create' }} Testimonial</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
