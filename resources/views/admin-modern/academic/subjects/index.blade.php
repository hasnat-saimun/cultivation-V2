@extends('admin-modern.layouts.app')

@section('title', 'Subject List')

@section('content')
    <x-admin-modern.page-header
        title="Subject List"
        subtitle="Modern parallel subject setup list using existing ERP subject data"
        :breadcrumb="['Home', 'Academic', 'Subject List']"
    />

    <x-admin-modern.table-shell title="Subject List">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicSubjectsCreate') }}" class="am-btn-primary">Create Subject</a>
        </div>

        <table class="am-table">
            <thead>
                <tr>
                    <th>Sl</th>
                    <th>Name</th>
                    <th>CQ</th>
                    <th>MCQ</th>
                    <th>Practical</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itemData as $key => $item)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $item->subjectName }}</td>
                        <td>{{ $item->CQ }}</td>
                        <td>{{ $item->MCQ }}</td>
                        <td>{{ $item->Practical }}</td>
                        <td style="text-align:center; vertical-align:middle;">
                            <div class="am-action-group" style="justify-content:center; gap:0.35rem; flex-wrap:wrap;" aria-label="Subject row actions">
                                <a href="{{ route('adminModernAcademicSubjectsEdit', ['itemId' => $item->id]) }}" class="am-action-btn is-edit" title="Edit subject">Edit</a>
                                <a
                                    href="{{ route('delSubject', ['itemId' => $item->id]) }}"
                                    class="am-action-btn is-delete"
                                    title="Delete subject"
                                    onclick="return confirm('Are you sure you want to delete this item?');"
                                >Delete</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No subject data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin-modern.table-shell>
@endsection
