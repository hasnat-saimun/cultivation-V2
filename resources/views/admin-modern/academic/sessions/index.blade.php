@extends('admin-modern.layouts.app')

@section('title', 'Session List')

@section('content')
    <x-admin-modern.page-header
        title="Session List"
        subtitle="Modern parallel session setup list using existing ERP session data"
        :breadcrumb="['Home', 'Academic', 'Session List']"
    />

    <x-admin-modern.table-shell title="Session List">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('createSession') }}" class="am-btn-primary">Create Session</a>
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
                        <td>{{ $item->session }}</td>
                        <td>
                            <div class="am-action-group">
                                <a href="{{ route('editSession', ['itemId' => $item->id]) }}" class="am-action-btn is-edit">Edit</a>
                                <a
                                    href="{{ route('delSession', ['itemId' => $item->id]) }}"
                                    class="am-action-btn is-delete"
                                    onclick="return confirm('Are you sure you want to delete this item?');"
                                >Delete</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No session data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin-modern.table-shell>
@endsection
