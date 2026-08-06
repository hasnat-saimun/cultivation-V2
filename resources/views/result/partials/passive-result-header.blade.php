@php
    $headerVariant = $headerVariant ?? 'default';
    $logoUrl = !empty($preloadedInstituteConfig?->logo)
        ? (preg_match('~^https?://~i', $preloadedInstituteConfig->logo)
            ? $preloadedInstituteConfig->logo
            : asset('public/upload/image/cultivation/'.ltrim($preloadedInstituteConfig->logo, '/')))
        : null;
    $contactParts = array_values(array_filter([
        trim((string) ($preloadedInstituteConfig->address ?? '')),
        trim((string) ($preloadedInstituteConfig->officeMobile ?? '')),
        trim((string) ($preloadedInstituteConfig->officeEmail ?? '')),
    ]));
@endphp

@if(in_array($headerVariant, ['subject-wise', 'at-a-glance'], true))
    @php
        $reportLabel = $headerVariant === 'at-a-glance' ? 'Result Report' : 'Subject-wise Result';
        $reportTitle = $headerVariant === 'at-a-glance'
            ? 'At-a-Glance Result'
            : 'Tabulation Sheet for '.$resultHeader['examName'];
    @endphp
    <section class="result-page-header">
        <div class="header-surface">
            <div class="header-logo-wrap">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Institute Logo" class="header-logo-image">
                @else
                    <div class="header-logo-fallback">Logo</div>
                @endif
            </div>
            <div class="header-identity">
                <div class="header-kicker">Institution Profile</div>
                <h1 class="header-institute-name">{{ $preloadedInstituteConfig->instituteName ?? 'Jahanara Ayub Academy' }}</h1>
                @if($contactParts !== [])
                    <div class="header-contact">{{ implode(' • ', $contactParts) }}</div>
                @endif
            </div>
            <div class="header-title-block">
                <div class="header-report-label">{{ $reportLabel }}</div>
                <h2 class="header-report-title">{{ $reportTitle }}</h2>
                <div class="header-report-timestamp d-none d-print-inline"><strong>Printed:</strong> {{ $resultHeader['printedAt'] }}</div>
            </div>
        </div>

        <div class="header-meta-row">
            <div class="header-meta-grid">
                <div class="header-meta-item">
                    <div class="header-meta-label">Exam</div>
                    <div class="header-meta-value">{{ $scopeLabels['exam'] ?? ($resultHeader['examName'] ?? '-') }}</div>
                </div>
                <div class="header-meta-item">
                    <div class="header-meta-label">Class</div>
                    <div class="header-meta-value">{{ $resultHeader['className'] }}</div>
                </div>
                <div class="header-meta-item">
                    <div class="header-meta-label">Session</div>
                    <div class="header-meta-value">{{ $resultHeader['sessionName'] }}</div>
                </div>
                <div class="header-meta-item">
                    <div class="header-meta-label">Section/Group</div>
                    <div class="header-meta-value">{{ $scopeLabels['section'] ?? $resultHeader['sectionName'] }}</div>
                </div>
                <div class="header-meta-item">
                    <div class="header-meta-label">Department</div>
                    <div class="header-meta-value">{{ $scopeLabels['department'] ?? 'All' }}</div>
                </div>
                @if(in_array($headerVariant, ['subject-wise', 'at-a-glance'], true))
                    <div class="header-meta-item">
                        <div class="header-meta-label">Gender</div>
                        <div class="header-meta-value">{{ $scopeLabels['gender'] ?? 'All' }}</div>
                    </div>
                @endif
            </div>
            <div class="header-actions d-print-none">
                <button type="button" class="btn btn-warning btn-sm" onclick="window.print()">Print</button>
            </div>
        </div>
    </section>
@else
    <div class="container-fluid mb-3">
        <div class="report-header text-center">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Institute Logo" style="height:60px;width:60px;object-fit:contain">
            @endif
            <h4 class="fw-bold">{{ $preloadedInstituteConfig->instituteName ?? 'Jahanara Ayub Academy' }}</h4>
            <div>{{ $preloadedInstituteConfig->address ?? '' }}</div>
            <div>{{ $preloadedInstituteConfig->officeMobile ?? '' }} {{ $preloadedInstituteConfig->officeEmail ?? '' }}</div>
        </div>
        <h4 class="fw-bold text-center my-3">Tabulation Sheet for - {{ $resultHeader['examName'] }}</h4>
        <div class="p-2 border rounded d-flex justify-content-between">
            <div><strong>Class:</strong> {{ $resultHeader['className'] }} &nbsp; <strong>Section/Group:</strong> {{ $resultHeader['sectionName'] }} &nbsp; <strong>Session:</strong> {{ $resultHeader['sessionName'] }}@if($headerVariant === 'result-summary') &nbsp; <strong>Department:</strong> {{ $resultHeader['departmentName'] ?? 'All Departments' }} &nbsp; <strong>Gender:</strong> {{ $resultHeader['genderName'] ?? 'All' }}@endif</div>
            <div><span class="d-none d-print-inline"><strong>Printed:</strong> {{ $resultHeader['printedAt'] }}</span>
                <button type="button" class="btn btn-warning btn-sm d-print-none" onclick="window.print()">Print</button>
            </div>
        </div>
    </div>
@endif
