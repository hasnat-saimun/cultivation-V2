@extends('admin-modern.layouts.app')

@section('title', 'Create Subject')

@section('content')
    <x-admin-modern.page-header
        title="Create Subject"
        subtitle="Modern parallel wrapper for subject creation using existing ERP submit flow"
        :breadcrumb="['Home', 'Academic', 'Subject List', 'Create Subject']"
    />

    <x-admin-modern.table-shell title="Add New Subject">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicSubjectsIndex') }}" class="am-btn-outline">Subject List</a>
        </div>

        @include('admin-modern.academic.subjects._form', [
            'actionRoute' => route('confirmSubject'),
            'submitLabel' => 'Save',
            'item' => null,
            'defaultClassIds' => [],
        ])
    </x-admin-modern.table-shell>
@endsection
