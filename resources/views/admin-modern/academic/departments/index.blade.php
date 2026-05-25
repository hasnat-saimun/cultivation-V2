@extends('admin-modern.layouts.app')

@section('title', 'Department List')

@section('content')
    <x-admin-modern.page-header
        title="Department List"
        subtitle="Modern parallel department setup list using existing ERP department data"
        :breadcrumb="['Home', 'Academic', 'Department List']"
    />

    <x-admin-modern.table-shell title="Department List">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('createDepartment') }}" class="am-btn-primary">Create Department</a>
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
                        <td>{{ $item->departmentName }}</td>
                        <td>
                            <div class="am-action-group">
                                <a href="{{ route('editDepartment', ['itemId' => $item->id]) }}" class="am-action-btn is-edit">Edit</a>
                                <a
                                    href="{{ route('delDepartment', ['itemId' => $item->id]) }}"
                                    class="am-action-btn is-delete"
                                    onclick="return confirm('Are you sure you want to delete this item?');"
                                >Delete</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No department data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin-modern.table-shell>
@endsection
