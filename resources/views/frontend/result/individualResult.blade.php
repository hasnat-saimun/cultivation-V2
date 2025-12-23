@extends('layouts.app')
@section('title', 'Individual Result')

@push('styles')
<style>
  /* Ensure printer uses A4 with comfortable margins */
  @page { size: A4; margin: 12mm; }

  /* Print-specific clean layout */
  @media print {
    html, body { background: #fff !important; }
    .cultivation-header, .d-print-none { display: none !important; }
    .card { box-shadow: none !important; border: none !important; }
    .container { max-width: none; width: 186mm; } /* A4 width (210mm) minus margins */
  }

  /* Screen layout remains neat and centered */
  .result-wrapper { background: #fff; border: 1px solid #e5e7eb; padding: 16px; }
</style>
@endpush

@section('content')
<div class="card">
  <div class="card-body result-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Individual Result</h3>
      <button class="btn btn-warning btn-sm d-print-none" onclick="window.print()">
        <i class="fas fa-print"></i> Print
      </button>
    </div>

    <!-- Place your result content here -->
    <p class="text-muted mb-0">A4 print layout is enabled for this page.</p>
  </div>
</div>
@endsection
