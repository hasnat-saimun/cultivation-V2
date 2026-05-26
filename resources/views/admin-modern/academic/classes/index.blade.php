@extends('admin-modern.layouts.app')

@section('title', 'Class List')

@section('content')
    <x-admin-modern.page-header
        title="Class List"
        subtitle="Modern parallel class setup list using existing ERP class data"
        :breadcrumb="['Home', 'Academic', 'Class List']"
    />

    <x-admin-modern.table-shell title="Class List">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicClassesCreate') }}" class="am-btn-primary">Create Class</a>
        </div>

        <table class="am-table">
            <thead>
                <tr>
                    <th>Sl</th>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itemData as $key => $item)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $item->className }}</td>
                        <td style="text-align:center; vertical-align:middle;">
                            <div class="am-action-group" style="justify-content:center; gap:0.35rem; flex-wrap:wrap;" aria-label="Class row actions">
                                <a href="{{ route('adminModernAcademicClassesEdit', ['itemId' => $item->id]) }}" class="am-action-btn is-edit" title="Edit class">Edit</a>
                                <a
                                    href="{{ route('delClass', ['itemId' => $item->id]) }}"
                                    class="am-action-btn is-delete"
                                    title="Delete class"
                                    onclick="return confirm('Are you sure you want to delete this item?');"
                                >Delete</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No class data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin-modern.table-shell>
@endsection
