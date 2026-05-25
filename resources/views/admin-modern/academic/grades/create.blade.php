@extends('admin-modern.layouts.app')

@section('title', 'Create Grade')

@section('content')
    <x-admin-modern.page-header
        title="Create Grade"
        subtitle="Modern parallel wrapper for grade creation using existing ERP submit flow"
        :breadcrumb="['Home', 'Academic', 'Grade List', 'Create Grade']"
    />

    @if(session()->has('success'))
        <div class="am-flash is-success" role="status">
            <span>{{ session()->get('success') }}</span>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="am-flash is-error" role="alert">
            <span>{{ session()->get('error') }}</span>
        </div>
    @endif

    <x-admin-modern.table-shell title="Add New Grade">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicGradesIndex') }}" class="am-btn-outline">Grade List</a>
        </div>

        <form action="{{ route('confirmGrade') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                <div>
                    <label for="gradeName" style="display:block; font-weight:600; margin-bottom:0.35rem;">Grade Name *</label>
                    <input id="gradeName" type="text" name="gradeName" placeholder="Enter the grade name" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>

                <div>
                    <label for="gradePoint" style="display:block; font-weight:600; margin-bottom:0.35rem;">Grade Point *</label>
                    <input id="gradePoint" type="text" name="gradePoint" placeholder="Enter the value of grade point" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>

                <div>
                    <label for="minMark" style="display:block; font-weight:600; margin-bottom:0.35rem;">Minimum Marks *</label>
                    <input id="minMark" type="text" name="minMark" placeholder="Enter the value of minimum marks" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>

                <div>
                    <label for="maxMark" style="display:block; font-weight:600; margin-bottom:0.35rem;">Maximum Marks *</label>
                    <input id="maxMark" type="text" name="maxMark" placeholder="Enter the value of maximum marks" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>

                <div>
                    <label for="minGp" style="display:block; font-weight:600; margin-bottom:0.35rem;">Minimum Point *</label>
                    <input id="minGp" type="text" name="minGp" placeholder="Enter the value of minimum point" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>

                <div>
                    <label for="maxGp" style="display:block; font-weight:600; margin-bottom:0.35rem;">Marximum Point *</label>
                    <input id="maxGp" type="text" name="maxGp" placeholder="Enter the value of maximum point" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>
            </div>

            <div class="am-btn-row">
                <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Save</button>
                <button type="reset" class="am-btn-outline" style="cursor:pointer;">Reset</button>
            </div>
        </form>
    </x-admin-modern.table-shell>
@endsection
