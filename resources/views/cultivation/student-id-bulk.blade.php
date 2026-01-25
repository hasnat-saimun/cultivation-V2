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
                        <div id="printArea" class="id-card-grid">
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
    /* ISO/IEC 7810 ID-1 (CR80) physical size (exact) */
    --card-w: 85.60mm;
    --card-h: 53.98mm;
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.id-card-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(320px, 1fr));
    gap: 24px;
    align-items: start;
}

/* Responsive: single column on narrow screens */
@media (max-width: 768px) {
    .id-card-grid {
        grid-template-columns: 1fr;
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
    max-width: 440px;
    margin: 0 auto;
    color: var(--id-primary);
    font-family: 'Segoe UI', Tahoma, sans-serif;
}

.id-face {
    background: #ffffff;
    border: 1px solid var(--id-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 16px;
}

.id-face.back {
    margin-top: 12px;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
}

.id-header {
    margin: -16px -16px 0 -16px;
    padding: 14px 16px;
    background: linear-gradient(115deg, var(--id-primary) 0%, var(--id-accent) 100%);
    color: #fff;
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-wrap {
    height: 58px;
    width: 58px;
    background: rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-img {
    max-height: 50px;
    width: auto;
    object-fit: contain;
}

.logo-fallback {
    font-weight: 700;
    font-size: 22px;
}

.ins-meta {
    flex: 1;
    min-width: 0;
}

.ins-name {
    font-weight: 700;
    font-size: 1.05rem;
    line-height: 1.2;
}

.ins-sub {
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.3;
}

.badge-chip {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    padding: 8px 12px;
    border-radius: 999px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    font-size: 0.78rem;
}

.id-body {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 14px;
    align-items: center;
}

.photo-box {
    height: 140px;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid var(--id-border);
    background: var(--id-bg);
}

.photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.info .student-name {
    font-weight: 800;
    font-size: 1.1rem;
}

.info .meta {
    color: var(--id-muted);
    font-size: 0.9rem;
    margin-bottom: 6px;
}

.info .meta.muted {
    margin-top: 6px;
}

.pill-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 6px;
}

.pill {
    background: var(--id-bg);
    border: 1px solid var(--id-border);
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 0.82rem;
    color: var(--id-primary);
}

.id-footer {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    border-top: 1px dashed var(--id-border);
    padding-top: 10px;
}

.foot-col .label {
    font-size: 0.75rem;
    color: var(--id-muted);
    letter-spacing: 0.3px;
}

.foot-col .value {
    font-weight: 700;
    font-size: 0.95rem;
}

.back-title {
    font-weight: 700;
    font-size: 1rem;
    color: var(--id-primary);
}

.back-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
}

.back-grid .label {
    font-size: 0.8rem;
    color: var(--id-muted);
}

.back-grid .value {
    font-weight: 700;
    font-size: 0.95rem;
}

.id-signatures {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 10px;
}

.sig-line {
    flex: 1;
    text-align: center;
    border-top: 1px solid var(--id-border);
    padding-top: 8px;
    font-size: 0.85rem;
    color: var(--id-muted);
}

.id-back-footer {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.8rem;
    color: var(--id-muted);
    border-top: 1px dashed var(--id-border);
    padding-top: 8px;
}

@media print {
    body { background: #fff; }
    .card, .btn, .alert, form, .card-header { display: none !important; }
    /* Standard ID card physical layout: 2 per row */
    #printArea {
        display: grid !important;
        grid-template-columns: repeat(2, var(--card-w)) !important;
        gap: 10mm !important;
        justify-content: start !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .id-card-shell {
        width: var(--card-w) !important;
        page-break-inside: avoid;
    }
    .id-face {
        width: var(--card-w) !important;
        height: var(--card-h) !important;
        border-radius: 3.18mm !important; /* ~1/8" corner radius typical for ID-1 */
    }
    @page {
        size: A4;
        margin: 10mm;
    }
}
</style>
@endpush

@push('scripts')
<script>
    function printCards() {
        const printArea = document.getElementById('printArea');
        if (!printArea) return;
        const original = document.body.innerHTML;
        document.body.innerHTML = printArea.innerHTML;
        window.print();
        document.body.innerHTML = original;
    }
</script>
@endpush
