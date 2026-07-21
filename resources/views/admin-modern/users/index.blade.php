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
                        $subjectNames = $user->subjects->pluck('subjectName')->filter()->implode(', ');
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
                        <td>{{ $subjectNames !== '' ? $subjectNames : 'None' }}</td>
                        <td>{{ $attendanceClass !== '' ? $attendanceClass : 'None' }}</td>
                        <td>
                            <span class="am-badge {{ $roleClass }}">{{ $roleLabel }}</span>
                        </td>
                        <td>
                            <div class="am-action-group">
                                <a href="{{ route('adminModernUsersEdit', $user->id) }}" class="am-action-btn is-edit">Edit</a>
                                <a
                                    href="{{ route('deleteUser', $user->id) }}"
                                    class="am-action-btn is-delete"
                                    onclick="return confirm('Are you sure?')"
                                >Delete</a>
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
