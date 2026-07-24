<div class="container-fluid mb-3">
    <div class="report-header text-center">
        @if(!empty($preloadedInstituteConfig?->logo))
            <img src="{{ preg_match('~^https?://~i', $preloadedInstituteConfig->logo) ? $preloadedInstituteConfig->logo : asset('public/upload/image/cultivation/'.ltrim($preloadedInstituteConfig->logo, '/')) }}" alt="Institute Logo" style="height:60px;width:60px;object-fit:contain">
        @endif
        <h4 class="fw-bold">{{ $preloadedInstituteConfig->instituteName ?? 'Jahanara Ayub Academy' }}</h4>
        <div>{{ $preloadedInstituteConfig->address ?? '' }}</div>
        <div>{{ $preloadedInstituteConfig->officeMobile ?? '' }} {{ $preloadedInstituteConfig->officeEmail ?? '' }}</div>
    </div>
    <h4 class="fw-bold text-center my-3">Tabulation Sheet for - {{ $resultHeader['examName'] }}</h4>
    <div class="p-2 border rounded d-flex justify-content-between">
        <div><strong>Class:</strong> {{ $resultHeader['className'] }} &nbsp; <strong>Section/Group:</strong> {{ $resultHeader['sectionName'] }} &nbsp; <strong>Session:</strong> {{ $resultHeader['sessionName'] }}</div>
        <div><span class="d-none d-print-inline"><strong>Printed:</strong> {{ $resultHeader['printedAt'] }}</span>
            <button type="button" class="btn btn-warning btn-sm d-print-none" onclick="window.print()">Print</button>
        </div>
    </div>
</div>
