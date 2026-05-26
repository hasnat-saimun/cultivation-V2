@extends('admin-modern.layouts.app')

@section('title', 'Create Session')

@section('content')
    <x-admin-modern.page-header
        title="Create Session"
        subtitle="Modern parallel wrapper for session creation using existing ERP submit flow"
        :breadcrumb="['Home', 'Academic', 'Session List', 'Create Session']"
    />

    <x-admin-modern.table-shell title="Add New Session">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicSessionsIndex') }}" class="am-btn-outline">Session List</a>
        </div>

        <form action="{{ route('confirmSession') }}" method="POST">
            @csrf
            <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                <div>
                    <label for="session" style="display:block; font-weight:600; margin-bottom:0.35rem;">Session Name *</label>
                    <input id="session" type="text" name="session" placeholder="Enter session name" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>
            </div>

            <div class="am-btn-row">
                <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Save</button>
                <button type="reset" class="am-btn-outline" style="cursor:pointer;">Reset</button>
            </div>
        </form>
    </x-admin-modern.table-shell>
@endsection
