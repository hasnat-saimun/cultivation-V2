@extends('cultivation.include')
@section('backTitle')
Edit Designation
@endsection
@section('backIndex')
<style>
    .form-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 2rem;
        border-radius: 8px 8px 0 0;
        margin-bottom: 2rem;
    }
    
    .form-header h2 {
        margin: 0;
        font-weight: 600;
    }
    
    .form-card {
        border: none;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .form-section {
        padding: 2rem;
    }
    
    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.75rem;
    }
    
    .form-control, .form-select {
        border: 1.5px solid #dee2e6;
        border-radius: 6px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #f5576c;
        box-shadow: 0 0 0 0.2rem rgba(245, 87, 108, 0.25);
    }
    
    .required-field::after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }
    
    .form-actions {
        padding: 1.5rem 2rem;
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        display: flex;
        gap: 0.75rem;
    }
    
    .status-section {
        padding: 1.5rem;
        background-color: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 1.5rem;
    }
    
    .form-check {
        padding-left: 0;
    }
    
    .form-check-input {
        width: 1.5em;
        height: 1.5em;
        margin-top: 0.25em;
        margin-right: 0.75rem;
        cursor: pointer;
    }
    
    .form-check-label {
        cursor: pointer;
        font-weight: 500;
    }
    
    .alert {
        border-radius: 6px;
        margin-bottom: 1.5rem;
    }
    
    .status-badge {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
        border-radius: 4px;
        display: inline-block;
        margin-top: 0.5rem;
    }
</style>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="form-header d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fa-solid fa-briefcase"></i> Edit Designation</h2>
                <p class="mb-0 mt-1 opacity-75">Update designation details and settings</p>
            </div>
            <a href="{{ route('designationsIndex') }}" class="btn btn-light">
                <i class="fa-solid fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="form-card">
            @if(session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                    <i class="fa-solid fa-check-circle"></i> {{ session()->get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                    <i class="fa-solid fa-exclamation-circle"></i> {{ session()->get('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('designationsUpdate', $designation->id) }}" method="POST">
                @csrf
                <div class="form-section">
                    <div class="mb-4">
                        <label for="name" class="form-label required-field">Designation Name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" required placeholder="Enter designation name" value="{{ old('name', $designation->name) }}">
                        @error('name')
                            <div class="invalid-feedback d-block">
                                <i class="fa-solid fa-exclamation-triangle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="type" class="form-label required-field">Designation Type</label>
                        <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" {{ old('type', $designation->type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <div class="invalid-feedback d-block">
                                <i class="fa-solid fa-exclamation-triangle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="status-section">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', $designation->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <i class="fa-solid fa-toggle-on"></i> Mark as Active
                            </label>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fa-solid fa-info-circle"></i>
                                Status:
                                <span class="status-badge {{ old('is_active', $designation->is_active) ? 'bg-success text-white' : 'bg-secondary text-white' }}">
                                    {{ old('is_active', $designation->is_active) ? 'Active' : 'Inactive' }}
                                </span>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fa-solid fa-save"></i> Update Designation
                    </button>
                    <a href="{{ route('designationsIndex') }}" class="btn btn-secondary btn-lg">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
