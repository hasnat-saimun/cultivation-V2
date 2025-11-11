@extends('cultivation.include')
@section('backTitle') Attendance @endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-md-10 col-12 mx-auto">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Attendance - Select Filters</h5>
            </div>
            <div class="card-body">
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                <form method="POST" action="{{ route('attendanceFetch') }}" class="row">
                    @csrf
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date *</label>
                        <input type="date" name="date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Class *</label>
                        <select name="classId" class="form-select form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Session</label>
                        <select name="sessionId" class="form-select form-control">
                            <option value="">Select</option>
                            @foreach($sessions as $sess)
                                <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Section / Group</label>
                        <select name="sectionId" class="form-select form-control">
                            <option value="">Select</option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <button type="submit" class="btn btn-primary">Load Students</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection