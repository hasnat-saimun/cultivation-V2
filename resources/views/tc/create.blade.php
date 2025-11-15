@extends('cultivation.include')
@section('backTitle')
{{ isset($tc) ? 'Edit Transfer Certificate' : 'Create Transfer Certificate' }}
@endsection
@section('backIndex')
<div class="row gutters-20 mt-4 justify-content-center">
    <div class="col-lg-8">
        <div class="card card-default">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h3 class="mb-0">{{ isset($tc) ? 'Edit' : 'Create' }} Transfer Certificate for {{ $admission->fullName ?? $admission->sureName ?? 'Student' }}</h3>
                <div class="d-print-none">
                    @if(isset($tc))
                        <a href="{{ route('tc.show', $tc->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
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
                <form method="POST" action="{{ isset($tc) ? route('tc.update') : route('tc.store') }}">
                    @csrf
                    @if(isset($tc))
                        <input type="hidden" name="id" value="{{ $tc->id }}">
                    @else
                        <input type="hidden" name="admission_id" value="{{ $admission->id }}">
                    @endif
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ref No</label>
                            <input type="text" name="ref_no" class="form-control" value="{{ old('ref_no', $tc->ref_no ?? '') }}" placeholder="Auto if empty">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Issue Date</label>
                            <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', isset($tc->issue_date) ? date('Y-m-d', strtotime($tc->issue_date)) : '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Leaving Class</label>
                            <input type="text" name="leaving_class" class="form-control" placeholder="e.g., Nine / Ten" value="{{ old('leaving_class', $tc->leaving_class ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Leaving Date</label>
                            <input type="date" name="leaving_date" class="form-control" value="{{ old('leaving_date', isset($tc->leaving_date) ? date('Y-m-d', strtotime($tc->leaving_date)) : '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reason for Leaving</label>
                            <input type="text" name="reason" class="form-control" placeholder="e.g., Family shifting / Admission elsewhere" value="{{ old('reason', $tc->reason ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Conduct</label>
                            <input type="text" name="conduct" class="form-control" placeholder="e.g., Good / Satisfactory" value="{{ old('conduct', $tc->conduct ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Character</label>
                            <input type="text" name="character" class="form-control" placeholder="e.g., Good / Excellent" value="{{ old('character', $tc->character ?? '') }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Optional notes for office use" value="{{ old('remarks', $tc->remarks ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Composed By</label>
                            <input type="text" name="composed_by" class="form-control" placeholder="e.g., Office Assistant" value="{{ old('composed_by', $tc->composed_by ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Composed Date</label>
                            <input type="date" name="composed_date" class="form-control" value="{{ old('composed_date', isset($tc->composed_date) ? date('Y-m-d', strtotime($tc->composed_date)) : '') }}">
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-success px-4">{{ isset($tc) ? 'Update' : 'Create' }} Transfer Certificate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
