@extends('admin-modern.layouts.app')

@section('title', 'Exam List')

@section('content')
    <x-admin-modern.page-header
        title="Exam List"
        subtitle="Modern parallel exam setup list using existing ERP exam data"
        :breadcrumb="['Home', 'Academic', 'Exam List']"
    />

    <x-admin-modern.table-shell title="Exam List">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('createExam') }}" class="am-btn-primary">Create Exam</a>
        </div>

        <table class="am-table">
            <thead>
                <tr>
                    <th>Sl</th>
                    <th>Name</th>
                    <th>Exam Class</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Base Mark</th>
                    <th>Passing System</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itemData as $key => $item)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $item->examName }}</td>
                        @if($item->className > 0)
                            @php
                                $classItem = \App\Models\classManage::find($item->className);
                            @endphp
                            <td>{{ $classItem?->className }}</td>
                        @else
                            <td>All Class</td>
                        @endif
                        <td>{{ $item->examDate }}</td>
                        <td>{{ $item->closeDate }}</td>
                        <td>{{ $item->baseMark }}</td>
                        <td>{{ (int) $item->passingSystem === 1 ? 'Feature Wise' : 'Total Marks' }}</td>
                        <td>
                            <div class="am-action-group">
                                <a href="{{ route('editExam', ['itemId' => $item->id]) }}" class="am-action-btn is-edit">Edit</a>
                                <a
                                    href="{{ route('delExam', ['itemId' => $item->id]) }}"
                                    class="am-action-btn is-delete"
                                    onclick="return confirm('Are you sure you want to delete this item?');"
                                >Delete</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No exam data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin-modern.table-shell>
@endsection
