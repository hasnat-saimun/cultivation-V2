@extends('cultivation.include')
@section('backTitle')
Add New Designation
@endsection
@section('backIndex')
<style>
    .form-header {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #ffffff;
        padding: 2rem;
        border-radius: 8px 8px 0 0;
        margin-bottom: 2rem;
    }
    
    .form-header h2 {
        margin: 0;
        font-weight: 600;
    }
    .form-header p {
        margin: 0.25rem 0 0;
        color: rgba(255, 255, 255, 0.92);
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
        color: #1f2937; /* slate-800 */
        margin-bottom: 0.75rem;
    }
    
    .form-control, .form-select {
        border: 1.5px solid #dee2e6;
        border-radius: 6px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #4f46e5; /* indigo-600 */
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
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
    
    .alert {
        border-radius: 6px;
        margin-bottom: 1.5rem;
    }

    /* Buttons aligned with header color */
    .btn.btn-primary {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }
    .btn.btn-primary:hover,
    .btn.btn-primary:focus {
        background-color: #4338ca;
        border-color: #4338ca;
    }
    .btn.btn-outline-light {
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.8);
    }
    .btn.btn-outline-light:hover,
    .btn.btn-outline-light:focus {
        color: #111827;
        background-color: #ffffff;
        border-color: #ffffff;
    }
    .btn.btn-secondary {
        background-color: #6b7280; /* gray-500 */
        border-color: #6b7280;
        color: #ffffff;
    }
    .btn.btn-secondary:hover,
    .btn.btn-secondary:focus {
        background-color: #4b5563; /* gray-600 */
        border-color: #4b5563;
        color: #ffffff;
    }
</style>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="form-header d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fa-solid fa-briefcase"></i> Add New Designation</h2>
                <p class="mb-0 mt-1">Create a new designation for your institute</p>
            </div>
            <a href="{{ route('designationsIndex') }}" class="btn btn-outline-light">
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

            <form action="{{ route('designationsStore') }}" method="POST">
                @csrf
                <div class="form-section">
                    <div class="mb-4">
                        <label for="name" class="form-label required-field">Designation Name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" required placeholder="e.g., Senior Teacher, Administrative Officer" value="{{ old('name') }}">
                        @error('name')
                            <div class="invalid-feedback d-block">
                                <i class="fa-solid fa-exclamation-triangle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="type" class="form-label required-field">Designation Type</label>
                        <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="" disabled selected>Select Type</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <div class="invalid-feedback d-block">
                                <i class="fa-solid fa-exclamation-triangle"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-save"></i> Save Designation
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
