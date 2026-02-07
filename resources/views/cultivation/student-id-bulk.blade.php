@extends('cultivation.include')
@section('backTitle')
Bulk Student ID Cards
@endsection
@section('backIndex')
<div class="row gutters-20 mt-4">
    <div class="col-12">
        <div class="card card-default shadow-sm">
            <div class="card-header bg-gradient-info text-white d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h3 class="mb-0"><i class="fa-solid fa-id-card"></i> Bulk Student ID Cards</h3>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('studentList') }}" class="btn btn-light btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
                    @if($students->count() > 0)
                        <button type="button" class="btn btn-success btn-sm" onclick="printCards()"><i class="fa-solid fa-print"></i> Print All</button>
                    @endif
                    @php $fmt = request()->get('format','landscape'); $qsL = $filters; $qsL['format']='landscape'; $qsP = $filters; $qsP['format']='portrait'; @endphp
                    <div class="btn-group" role="group" aria-label="Format">
                        <a href="{{ route('student.idcards.bulk', $qsL) }}" class="btn btn-outline-light btn-sm {{ $fmt==='landscape' ? 'active' : '' }}">Landscape</a>
                        <a href="{{ route('student.idcards.bulk', $qsP) }}" class="btn btn-outline-light btn-sm {{ $fmt==='portrait' ? 'active' : '' }}">Portrait</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info d-flex align-items-start" role="alert">
                    <i class="fa-solid fa-circle-info me-2 mt-1"></i>
                    <div>
                        <strong>Filter and print professional student ID cards.</strong> Choose class, session, section, or department to load matching students. All loaded cards appear below and are ready for one-click printing.
                    </div>
                </div>

                <form method="get" action="{{ route('student.idcards.bulk') }}" class="filter-panel row g-3 align-items-end mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold"><i class="fa-solid fa-calendar-days me-1 text-primary"></i> Session</label>
                        <select name="sessionId" class="form-select form-select-lg shadow-sm">
                            <option value="">All Sessions</option>
                            @foreach($sessionDetails as $session)
                                <option value="{{ $session->id }}" {{ ($filters['sessionId'] ?? '') == $session->id ? 'selected' : '' }}>{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold"><i class="fa-solid fa-layer-group me-1 text-primary"></i> Class</label>
                        <select name="classId" class="form-select form-select-lg shadow-sm">
                            <option value="">All Classes</option>
                            @foreach($classDetails as $class)
                                <option value="{{ $class->id }}" {{ ($filters['classId'] ?? '') == $class->id ? 'selected' : '' }}>{{ $class->className }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold"><i class="fa-solid fa-people-roof me-1 text-primary"></i> Section</label>
                        <select name="sectionId" class="form-select form-select-lg shadow-sm">
                            <option value="">All Sections</option>
                            @foreach($sectionDetails as $section)
                                <option value="{{ $section->id }}" {{ ($filters['sectionId'] ?? '') == $section->id ? 'selected' : '' }}>{{ $section->section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold"><i class="fa-solid fa-diagram-project me-1 text-primary"></i> Department</label>
                        <select name="departmentId" class="form-select form-select-lg shadow-sm">
                            <option value="">All Departments</option>
                            @foreach($departmentDetails as $dept)
                                <option value="{{ $dept->id }}" {{ ($filters['departmentId'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->departmentName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 filter-actions">
                        <a href="{{ route('student.idcards.bulk') }}" class="btn btn-outline-secondary btn-lg"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-magnifying-glass"></i> Load ID Cards</button>
                    </div>
                </form>

                @if(($filters['classId'] ?? null) || ($filters['sessionId'] ?? null) || ($filters['sectionId'] ?? null) || ($filters['departmentId'] ?? null))
                    @if($students->count() === 0)
                        <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> No students found for the selected filters.</div>
                    @else
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div class="badge bg-success text-wrap">Loaded {{ $students->count() }} student{{ $students->count() > 1 ? 's' : '' }}</div>
                            <button type="button" class="btn btn-success btn-sm" onclick="printCards()"><i class="fa-solid fa-print"></i> Print All</button>
                        </div>
                        <div id="printArea" class="id-card-grid {{ $fmt === 'portrait' ? 'print-portrait' : '' }}">
                            @foreach($students as $student)
                                @php $card = $cardData[$student->id] ?? null; @endphp
                                @if($card)
                                    @if(request()->get('format','landscape') === 'portrait')
                                        @include('cultivation.partials.student-id-card-portrait', ['card' => $card, 'branding' => $branding])
                                    @else
                                        @include('cultivation.partials.student-id-card', ['card' => $card, 'branding' => $branding])
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="alert alert-light border">Select at least one filter above to load ID cards.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
:root {
    --id-primary: #0f172a;
    --id-accent: #2563eb;
    --id-muted: #6b7280;
    --id-border: #e5e7eb;
    --id-bg: #f8fafc;
    --card-w-land: 85.60mm;
    --card-h-land: 53.98mm;
    --card-w-port: 53.98mm;
    --card-h-port: 85.60mm;
    --ratio-land: 1.586;
    --ratio-port: 0.631;
    --face-gap: 8px;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.id-card-grid {
    --card-w: var(--card-w-land);
    --card-h: var(--card-h-land);
    --shell-w: var(--card-w);
    --shell-h: calc(var(--card-h) * 2 + var(--face-gap));
    display: grid;
    grid-template-columns: repeat(2, var(--shell-w));
    gap: 24px;
    align-items: start;
    justify-content: center;
}

.id-card-grid.print-portrait {
    --card-w: var(--card-w-port);
    --card-h: var(--card-h-port);
}

/* Responsive: single column on narrow screens */
@media (max-width: 768px) {
    .id-card-grid {
        grid-template-columns: 1fr;
        --shell-w: min(100%, var(--shell-w));
        gap: 16px;
    }
}

.filter-panel {
    background: #fff;
    border: 1px solid var(--id-border);
    border-radius: 12px;
    padding: 16px 16px 8px;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.filter-actions {
    margin-top: 6px;
    padding-top: 4px;
}

.filter-panel .form-select-lg {
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border-radius: 12px;
    border: 1px solid var(--id-border);
    transition: all 0.2s ease;
}

.filter-panel .form-select-lg:focus {
    border-color: var(--id-accent);
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
}

.id-card-shell {
    width: var(--shell-w);
    height: var(--shell-h);
    margin: 0 auto;
    color: var(--id-primary);
    font-family: 'Segoe UI', Tahoma, sans-serif;
    display: flex;
    flex-direction: column;
    gap: var(--face-gap);
}

.id-face {
    width: var(--card-w);
    height: var(--card-h);
    margin: 0 auto;
    background: #ffffff;
    border: 1px solid #d7dbe2;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1);
}

/* New ID card design (portrait + landscape) */
.id-front-top {
    background: linear-gradient(135deg, #0a4aa6 0%, #0b6bd6 55%, #0a3f8a 100%);
    color: #ffffff;
    padding: 8px 10px 10px;
    position: relative;
}

.id-front-top::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 3px;
    background: #e11d48;
}

.id-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.id-brand {
    display: flex;
    align-items: center;
    gap: 8px;
}

.id-logo {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.id-logo img {
    max-height: 30px;
    width: auto;
}

.logo-fallback {
    font-weight: 800;
    font-size: 0.85rem;
    color: #ffffff;
}

.id-school-name {
    font-weight: 800;
    font-size: 0.9rem;
    line-height: 1.1;
}

.id-school-tag {
    font-size: 0.7rem;
    opacity: 0.85;
}

.id-card-label {
    font-weight: 700;
    font-size: 0.68rem;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 8px;
    border-radius: 999px;
}

.id-front-body {
    display: grid;
    gap: 10px;
    padding: 10px;
}

.id-front-body.landscape {
    grid-template-columns: 74px 1fr 66px;
    align-items: center;
}

.id-front-body.portrait {
    grid-template-columns: 1fr;
}

.id-front-body.portrait .id-info {
    text-align: center;
}

.id-photo-wrap {
    width: 68px;
    height: 78px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #b0c8f0;
    background: #f1f5f9;
}

.id-photo-wrap.portrait {
    width: 78px;
    height: 92px;
    margin: 0 auto;
}

.id-photo-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.id-info {
    font-size: 0.72rem;
    color: #111827;
}

.id-info .id-name {
    font-weight: 800;
    font-size: 0.86rem;
    margin-bottom: 4px;
}

.id-info .id-row {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px solid #e5e7eb;
    padding: 2px 0;
    gap: 6px;
}

.id-info .id-row span {
    font-weight: 700;
}

.id-portrait-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 10px;
    margin-top: 6px;
    font-size: 0.72rem;
}

.id-portrait-grid div {
    display: flex;
    justify-content: space-between;
    gap: 6px;
    border-bottom: 1px solid #e5e7eb;
    padding: 2px 0;
}

.id-portrait-qr {
    display: flex;
    justify-content: center;
    margin-top: 6px;
}

.id-qr {
    width: 62px;
    height: 62px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    overflow: hidden;
    background: #fff;
}

.id-qr img {
    width: 100%;
    height: 100%;
}

.id-front-footer {
    background: #0f172a;
    color: #ffffff;
    font-size: 0.72rem;
    padding: 6px 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.id-front-footer span {
    font-weight: 700;
}

.id-back-top {
    background: #0f172a;
    height: 14px;
}

.id-back-body {
    padding: 8px 10px 10px;
    display: grid;
    gap: 10px;
}

.id-back-body.landscape {
    grid-template-columns: 1fr 120px;
    align-items: start;
}

.id-back-title {
    font-weight: 800;
    font-size: 0.82rem;
    color: #1f2937;
    margin-bottom: 4px;
}

.id-back-list {
    font-size: 0.72rem;
    color: #374151;
    padding-left: 16px;
    margin: 0;
}

.id-back-note {
    font-size: 0.7rem;
    color: #1f2937;
    margin-top: 6px;
    border-top: 1px solid #e5e7eb;
    padding-top: 6px;
}

.id-sign {
    text-align: center;
    font-size: 0.7rem;
    color: #6b7280;
    margin-top: 6px;
    border-top: 1px solid #e5e7eb;
    padding-top: 4px;
}

.id-barcode-box {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 6px;
    text-align: center;
}

.id-barcode {
    height: 26px;
    background: repeating-linear-gradient(
        90deg,
        #111827 0,
        #111827 2px,
        #ffffff 2px,
        #ffffff 4px
    );
    margin-bottom: 6px;
}

.id-card-no {
    font-size: 0.72rem;
    color: #111827;
}

.id-back-footer-bar {
    background: #0f172a;
    color: #ffffff;
    font-size: 0.7rem;
    padding: 6px 10px;
    display: flex;
    justify-content: space-between;
    gap: 6px;
}

.id-back-footer-bar span {
    font-weight: 700;
}

@media print {
    :root {
        --page-w: 210mm;
        --page-h: 297mm;
        --page-m: 8mm;
        --grid-gap: 6mm;
        --face-gap: 4mm;
    }
    body { background: #fff; }
    body * { visibility: hidden !important; }
    #printArea, #printArea * { visibility: visible !important; }
    #printArea { position: absolute; left: 0; top: 0; }
    #printArea {
        display: grid !important;
        gap: var(--grid-gap) !important;
        justify-content: center !important;
        align-content: start !important;
        width: var(--page-w) !important;
        height: var(--page-h) !important;
        padding: var(--page-m) !important;
        box-sizing: border-box !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        --shell-w: calc((var(--page-w) - (var(--page-m) * 2) - var(--grid-gap)) / 2);
        --shell-h: calc((var(--page-h) - (var(--page-m) * 2) - var(--grid-gap)) / 2);
        grid-template-columns: repeat(2, var(--shell-w)) !important;
        grid-auto-rows: var(--shell-h) !important;
    }
    #printArea.print-portrait {
        --card-ratio: var(--ratio-port);
    }
    #printArea:not(.print-portrait) {
        --card-ratio: var(--ratio-land);
    }
    #printArea {
        --card-w: min(var(--shell-w), calc((var(--shell-h) - var(--face-gap)) / 2 * var(--card-ratio)));
        --card-h: calc(var(--card-w) / var(--card-ratio));
    }
    .id-card-shell {
        width: var(--shell-w) !important;
        height: var(--shell-h) !important;
        max-width: none !important;
        page-break-inside: avoid;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        gap: var(--face-gap);
    }
    .id-face {
        width: var(--card-w) !important;
        height: var(--card-h) !important;
        border-radius: 3.18mm !important;
        margin-left: auto !important;
        margin-right: auto !important;
        box-shadow: none;
    }
    #printArea .id-card-shell:nth-child(4n) {
        page-break-after: always;
    }
    @page {
        size: A4 portrait;
        margin: 0;
    }
}
</style>
@endpush

@push('scripts')
<script>
    function printCards() {
        const printArea = document.getElementById('printArea');
        if (!printArea) return;
        window.print();
    }
</script>
@endpush
