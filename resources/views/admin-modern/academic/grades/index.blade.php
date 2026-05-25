@extends('admin-modern.layouts.app')

@section('title', 'Grade List')

@section('content')
    <x-admin-modern.page-header
        title="Grade List"
        subtitle="Modern parallel grade setup list using existing ERP grade data"
        :breadcrumb="['Home', 'Academic', 'Grade List']"
    />

    <x-admin-modern.table-shell title="Grade List">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicGradesCreate') }}" class="am-btn-primary">Create Grade</a>
        </div>

        <table class="am-table">
            <thead>
                <tr>
                    <th>Sl</th>
                    <th>Name</th>
                    <th>Grade Point</th>
                    <th>Min Marks</th>
                    <th>Max Marks</th>
                    <th>Min Point</th>
                    <th>Max Point</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itemData as $key => $item)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $item->gradeName }}</td>
                        <td>{{ $item->gradePoint }}</td>
                        <td>{{ $item->minMark }}</td>
                        <td>{{ $item->maxMark }}</td>
                        <td>{{ $item->minGp }}</td>
                        <td>{{ $item->maxGp }}</td>
                        <td>
                            <div class="am-action-group">
                                <a href="{{ route('adminModernAcademicGradesEdit', ['itemId' => $item->id]) }}" class="am-action-btn is-edit">Edit</a>
                                <a
                                    href="{{ route('delGrade', ['itemId' => $item->id]) }}"
                                    class="am-action-btn is-delete"
                                    onclick="return confirm('Are you sure you want to delete this item?');"
                                >Delete</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No grade data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin-modern.table-shell>
@endsection
