@extends('admin-modern.layouts.app')

@section('title', 'Edit Subject')

@section('content')
    <x-admin-modern.page-header
        title="Edit Subject"
        subtitle="Modern parallel wrapper for subject update using existing ERP submit flow"
        :breadcrumb="['Home', 'Academic', 'Subject List', 'Edit Subject']"
    />

    <x-admin-modern.table-shell title="Update Subject">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicSubjectsIndex') }}" class="am-btn-outline">Subject List</a>
        </div>

        @if(isset($item))
            @include('admin-modern.academic.subjects._form', [
                'actionRoute' => route('updateSubject'),
                'submitLabel' => 'Save',
                'item' => $item,
                'defaultClassIds' => $defaultClassIds ?? [],
            ])
        @else
            <div class="am-flash is-info" role="status">
                <span>Opps! Sorry, No data found for update</span>
            </div>
        @endif
    </x-admin-modern.table-shell>
@endsection
