@extends('admin-modern.layouts.app')

@section('title', 'Create Department')

@section('content')
    <x-admin-modern.page-header
        title="Create Department"
        subtitle="Modern parallel wrapper for department creation using existing ERP submit flow"
        :breadcrumb="['Home', 'Academic', 'Department List', 'Create Department']"
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

    <x-admin-modern.table-shell title="Add New Department">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicDepartmentsIndex') }}" class="am-btn-outline">Department List</a>
        </div>

        <form action="{{ route('confirmDepartment') }}" method="POST">
            @csrf
            <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                <div>
                    <label for="departmentName" style="display:block; font-weight:600; margin-bottom:0.35rem;">Department Name *</label>
                    <input id="departmentName" type="text" name="departmentName" placeholder="Enter department name" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>
            </div>

            <div class="am-btn-row">
                <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Save</button>
                <button type="reset" class="am-btn-outline" style="cursor:pointer;">Reset</button>
            </div>
        </form>
    </x-admin-modern.table-shell>
@endsection
