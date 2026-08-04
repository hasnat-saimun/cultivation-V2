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
            <a href="{{ route('adminModernAcademicSessionsCreate') }}" class="am-btn-primary">Create Session</a>
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
                        <td style="text-align:center; vertical-align:middle;">
                            <div class="am-action-group" style="justify-content:center; gap:0.35rem; flex-wrap:wrap;" aria-label="Session row actions">
                                <a href="{{ route('adminModernAcademicSessionsEdit', ['itemId' => $item->id]) }}" class="am-action-btn is-edit" title="Edit session">Edit</a>
                                <x-delete-action :action="route('delSession', ['itemId' => $item->id])" class="am-action-btn is-delete" title="Delete session">Delete</x-delete-action>
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
