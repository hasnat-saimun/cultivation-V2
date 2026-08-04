@extends('admin-modern.layouts.app')

@section('title', 'Users')

@section('content')
    <x-admin-modern.page-header
        title="User List"
        subtitle="Modern parallel user management view using existing ERP user data"
        :breadcrumb="['Home', 'Users']"
    />

    <x-admin-modern.table-shell title="Registered Users">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernUsersCreate') }}" class="am-btn-primary">Add New User</a>
        </div>

        <div class="am-table-wrap" style="overflow-x:auto;">
        <table class="am-table" id="adminModernUsersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Admin Name</th>
                    <th>User Mobile</th>
                    <th>User Email</th>
                    <th>Assigned Subjects</th>
                    <th>Attendance Class</th>
                    <th>Type of User</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($userList as $key => $user)
                    @php
                        $subjectAssignments = collect($user->subject_assignment_summary ?? []);
                        $subjectNames = $subjectAssignments
                            ->map(function ($item) {
                                $subjectItems = collect((array) ($item['subject_items'] ?? []));
                                $subjects = $subjectItems->isNotEmpty()
                                    ? $subjectItems->pluck('name')->filter()->implode(', ')
                                    : implode(', ', (array) ($item['subjects'] ?? []));
                                return trim((string) ($item['label'] ?? '')) . ': ' . $subjects;
                            })
                            ->filter()
                            ->implode(' | ');
                        $attendanceClass = $user->primaryClass
                            ? trim($user->primaryClass->className . ($user->primarySection ? ' / ' . $user->primarySection->section : ''))
                            : 'None';

                        $roleLabel = $user->user_type_label;
                        $roleClass = 'unknown';
                        if ($roleLabel === 'Teacher Admin') {
                            $roleClass = 'teacher';
                        } elseif ($roleLabel === 'Cash Admin') {
                            $roleClass = 'cash';
                        } elseif ($roleLabel === 'General Admin') {
                            $roleClass = 'general';
                        } elseif ($roleLabel === 'Super Admin') {
                            $roleClass = 'super';
                        }
                    @endphp
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $user->adminName }}</td>
                        <td>{{ $user->adminMobile }}</td>
                        <td>{{ $user->adminMail }}</td>
                        <td>
                            @if($subjectAssignments->isNotEmpty())
                                @foreach($subjectAssignments as $assignment)
                                    @php
                                        $subjectItems = collect((array) ($assignment['subject_items'] ?? []));
                                        $subjectDisplay = $subjectItems->isNotEmpty()
                                            ? $subjectItems->map(function ($subject) {
                                                return [
                                                    'id' => (int) ($subject['id'] ?? 0),
                                                    'name' => trim((string) ($subject['name'] ?? '')),
                                                    'sources' => implode(',', (array) ($subject['sources'] ?? [])),
                                                ];
                                            })->filter(fn ($subject) => $subject['id'] > 0 && $subject['name'] !== '')->values()
                                            : collect((array) ($assignment['subjects'] ?? []))->map(function ($name) {
                                                return [
                                                    'id' => 0,
                                                    'name' => trim((string) $name),
                                                    'sources' => '',
                                                ];
                                            })->filter(fn ($subject) => $subject['name'] !== '')->values();
                                    @endphp

                                    <div class="assignment-item">
                                        <span class="assignment-label">{{ $assignment['label'] ?? 'Assignment' }}:</span>
                                        @foreach($subjectDisplay as $index => $subject)
                                            <span
                                                class="assignment-subject"
                                                @if(($subject['id'] ?? 0) > 0)
                                                    data-subject-id="{{ $subject['id'] }}"
                                                @endif
                                                @if(($subject['sources'] ?? '') !== '')
                                                    data-subject-sources="{{ $subject['sources'] }}"
                                                @endif
                                            >{{ $subject['name'] }}</span>@if($index < $subjectDisplay->count() - 1), @endif
                                        @endforeach
                                    </div>
                                @endforeach
                            @else
                                None
                            @endif
                        </td>
                        <td>{{ $attendanceClass !== '' ? $attendanceClass : 'None' }}</td>
                        <td>
                            <span class="am-badge {{ $roleClass }}">{{ $roleLabel }}</span>
                        </td>
                        <td>
                            <div class="am-action-group">
                                <a href="{{ route('adminModernUsersEdit', $user->id) }}" class="am-action-btn is-edit">Edit</a>
                                <x-delete-action :action="route('deleteUser', $user->id)" class="am-action-btn is-delete" confirm="Are you sure?">Delete</x-delete-action>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </x-admin-modern.table-shell>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css" />
<style>
    .assignment-item {
        margin-bottom: 0.25rem;
    }

    .assignment-item:last-child {
        margin-bottom: 0;
    }

    .assignment-label {
        font-weight: 700;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
(function(){
    function initAdminModernUsersTable(){
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) { return false; }
        var $ = jQuery;
        var $table = $('#adminModernUsersTable');
        if (!$table.length || $.fn.dataTable.isDataTable($table)) {
            return true;
        }

        var table = $table.DataTable({
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[1, 'asc']],
            columnDefs: [
                { targets: 0, orderable: false, searchable: false },
                { targets: 7, orderable: false, searchable: false }
            ],
            language: {
                emptyTable: 'No users found.',
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries'
            },
            drawCallback: function(){
                var api = this.api();
                var pageInfo = api.page.info();
                api.column(0, { page: 'current' }).nodes().each(function(cell, index){
                    cell.innerHTML = pageInfo.start + index + 1;
                });
            }
        });

        table.draw(false);
        return true;
    }

    if (!initAdminModernUsersTable()) {
        document.addEventListener('DOMContentLoaded', initAdminModernUsersTable);
    }
})();
</script>
@endpush
