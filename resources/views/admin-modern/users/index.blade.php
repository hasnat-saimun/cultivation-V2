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

        <table class="am-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Admin Name</th>
                    <th>User Mobile</th>
                    <th>User Email</th>
                    <th>User Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($userList as $key => $user)
                    @php
                        $roleLabel = 'Super Admin';
                        $roleClass = 'super';

                        if ((int)$user->userType === 1) {
                            $roleLabel = 'Teacher Admin';
                            $roleClass = 'teacher';
                        } elseif ((int)$user->userType === 2) {
                            $roleLabel = 'Cash Admin';
                            $roleClass = 'cash';
                        } elseif ((int)$user->userType === 3) {
                            $roleLabel = 'General Admin';
                            $roleClass = 'general';
                        }
                    @endphp
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $user->adminName }}</td>
                        <td>{{ $user->adminMobile }}</td>
                        <td>{{ $user->adminMail }}</td>
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
                        <td colspan="6" class="text-center">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin-modern.table-shell>
@endsection
