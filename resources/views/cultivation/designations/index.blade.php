@extends('cultivation.include')
@section('backTitle')
Designation Management
@endsection
@section('backIndex')
<style>
    .designation-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .designation-header h2 {
        margin: 0;
        font-weight: 600;
    }
    /* Ensure header text has strong contrast */
    .designation-header p {
        opacity: 1;
        color: rgba(255, 255, 255, 0.92);
    }
    
    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .table thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        padding: 1rem;
        color: #212529;
    }
    
    .table tbody td {
        padding: 1rem;
        vertical-align: middle;
        color: #212529;
    }
    
    .badge {
        padding: 0.5rem 0.75rem;
        font-weight: 500;
        font-size: 0.85rem;
    }
    /* High-contrast soft badge variants for readability */
    .badge-soft-primary { background-color: #e7f1ff; color: #0b5ed7; }
    .badge-soft-info { background-color: #e6fbff; color: #087990; }
    .badge-soft-success { background-color: #eaf7ed; color: #198754; }
    .badge-soft-secondary { background-color: #eef0f2; color: #495057; }
    
    .btn-action {
        padding: 0.35rem 0.6rem;
        font-size: 0.85rem;
        margin-right: 0.25rem;
    }
    
    .btn-action:last-child {
        margin-right: 0;
    }
    
    .alert {
        border-radius: 6px;
        margin-bottom: 1.5rem;
    }
</style>

<div class="designation-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="fa-solid fa-list"></i> Designation Management</h2>
        <p class="mb-0 mt-1">Manage all designations for teachers, staff, and governing body members</p>
    </div>
    <a href="{{ route('designationsCreate') }}" class="btn btn-light btn-lg">
        <i class="fa-solid fa-plus"></i> Add New Designation
    </a>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                @if(session()->has('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-check-circle"></i> {{ session()->get('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session()->has('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-exclamation-circle"></i> {{ session()->get('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="designationsTable" class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><i class="fa-solid fa-tag"></i> Name</th>
                                <th><i class="fa-solid fa-layer-group"></i> Type</th>
                                <th><i class="fa-solid fa-toggle-on"></i> Status</th>
                                <th><i class="fa-solid fa-arrow-up-down"></i> Order</th>
                                <th><i class="fa-solid fa-cog"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($designations as $designation)
                            <tr>
                                <td><strong>{{ $designation->name }}</strong></td>
                                <td>
                                    @if($designation->type === 'teacher')
                                        <span class="badge badge-soft-primary"><i class="fa-solid fa-chalkboard-user"></i> Teacher</span>
                                    @elseif($designation->type === 'staff')
                                        <span class="badge badge-soft-info"><i class="fa-solid fa-users"></i> Staff</span>
                                    @else
                                        <span class="badge badge-soft-success"><i class="fa-solid fa-handshake"></i> Governing Body</span>
                                    @endif
                                </td>
                                <td>
                                    @if($designation->is_active)
                                        <span class="badge badge-soft-success"><i class="fa-solid fa-circle-check"></i> Active</span>
                                    @else
                                        <span class="badge badge-soft-secondary"><i class="fa-solid fa-circle-xmark"></i> Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $designation->sort_order }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('designationsEdit', $designation->id) }}" class="btn btn-sm btn-warning btn-action" title="Edit">
                                        <i class="fa-solid fa-pencil"></i> Edit
                                    </a>
                                    <a href="{{ route('designationsToggle', $designation->id) }}" class="btn btn-sm btn-info btn-action" title="Toggle Status">
                                        <i class="fa-solid fa-power-off"></i> Toggle
                                    </a>
                                    <a href="{{ route('designationsDelete', $designation->id) }}" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Are you sure? This action cannot be undone.');" title="Delete">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fa-solid fa-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                    <p class="mt-3 text-muted">No designations found. <a href="{{ route('designationsCreate') }}">Create one now</a></p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($designations->hasPages())
                <div class="d-flex justify-content-end mt-4 server-pagination">
                    {{ $designations->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    $(function () {
        // Hide server-side pagination when DataTable is active
        $('.server-pagination').hide();

        const dt = $('#designationsTable').DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[1, 'asc'], [3, 'asc']], // Type, then Order
            columnDefs: [
                { targets: 4, orderable: false, searchable: false }, // Actions
                { targets: 3, type: 'num' } // Order column numeric sort
            ]
        });
    });
</script>
@endpush
@endsection
