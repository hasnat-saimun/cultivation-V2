@extends('admin-modern.layouts.app')

@section('title', 'Section List')

@section('content')
    <x-admin-modern.page-header
        title="Section List"
        subtitle="Modern parallel section setup list using existing ERP section data"
        :breadcrumb="['Home', 'Academic', 'Section List']"
    />

    <x-admin-modern.table-shell title="Section List">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicSectionsCreate') }}" class="am-btn-primary">Create Section</a>
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
                        <td>{{ $item->section }}</td>
                        <td style="text-align:center; vertical-align:middle;">
                            <div class="am-action-group" style="justify-content:center; gap:0.35rem; flex-wrap:wrap;" aria-label="Section row actions">
                                <a href="{{ route('adminModernAcademicSectionsEdit', ['itemId' => $item->id]) }}" class="am-action-btn is-edit" title="Edit section">Edit</a>
                                <a
                                    href="{{ route('delSection', ['itemId' => $item->id]) }}"
                                    class="am-action-btn is-delete"
                                    title="Delete section"
                                    onclick="return confirm('Are you sure you want to delete this item?');"
                                >Delete</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No section data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin-modern.table-shell>
@endsection
