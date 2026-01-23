@extends('cultivation.include')
@section('backIndex')
<div class="row gutters-20 mt-4">
    <div class="col-12 col-md-12 mx-auto">
        <div class="card card-default">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Governing Body Bulk Photo Upload</h3>
                <a href="{{ route('managingCommittee') }}" class="btn btn-secondary btn-sm">Back to Members List</a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-exclamation-circle"></i> <strong>Upload failed!</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($members->count() > 0)
                    <form method="post" action="{{ route('governingBodyBulkPhotoUploadStore') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-info-circle"></i> Only rows with a selected file will be updated. Max size 5MB; supported types: jpg, jpeg, png, gif, webp, avif.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>Member ID</th>
                                        <th>Name</th>
                                        <th>Designation</th>
                                        <th>Current Photo</th>
                                        <th>Upload New</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($members as $member)
                                        <tr>
                                            <td>{{ $member->id }}</td>
                                            <td>{{ $member->full_name }}</td>
                                            <td>{{ $member->designation }}</td>
                                            <td>
                                                @if(!empty($member->avatar))
                                                    <img src="{{ asset('public/upload/image/cultivation/'.$member->avatar) }}" alt="Current photo" style="height:60px;width:60px;object-fit:cover;border-radius:4px;">
                                                @else
                                                    <span class="text-muted">No photo</span>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="hidden" name="member_ids[]" value="{{ $member->id }}">
                                                <input type="file" name="photos[{{ $member->id }}]" accept="image/*" class="form-control" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa-solid fa-upload"></i> Update Selected Photos
                            </button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-warning" role="alert">
                        <i class="fa-solid fa-exclamation-triangle"></i> No members found.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if(alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 4000);
            }
        });
    })();
</script>
@endpush
