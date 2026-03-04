@php
    $config = \App\Models\ServerConfig::orderBy('id','DESC')->first();
@endphp
<style>
    .report-header{gap:12px;}
    .report-header .hdr-logo{height:120px;width:120px;object-fit:contain}
    .report-header .name{font-weight:700;margin-bottom:0}
    .report-header .subline{font-size:12px;color:#6b7280}
    .report-header .contacts{font-size:12px}
    @media print{
        .report-header{margin-bottom:8px !important;padding-bottom:6px !important;border-bottom:1px solid #e5e7eb !important}
        .report-header .hdr-logo{height:56px !important;width:56px !important}
    }
</style>

<div class="report-header d-flex flex-wrap align-items-center justify-content-center text-center mb-3 pb-3 border-bottom w-100" style="width:100%">
    <div class="text-center">
        @if(!empty($config?->logo))
            @php
                $appBase = rtrim(config('app.url'), '/').'/public';
                $logoFile = $config->logo;
                $logoSrc = preg_match('~^https?://~i', $logoFile) ? $logoFile : $appBase.'/upload/image/cultivation/'.$logoFile;
            @endphp
            <div class="me-3" style="flex:0 0 auto;">
                <img class="hdr-logo" src="{{ $logoSrc }}" alt="Institute Logo">
            </div>
        @endif
        <h4 class="name">{{ $config->instituteName ?? 'Jahanara Ayub Academy' }}</h4>
        <div class="subline">{{ $config->address ?? '' }}</div>
        <div class="contacts">
            @if(!empty($config?->officeMobile))
                <span><i class="fa fa-phone"></i> {{ $config->officeMobile ?? '' }}</span>
            @endif
            @if(!empty($config?->officeEmail))
                <span class="ms-3"><i class="fa fa-envelope-o"></i> {{ $config->officeEmail ?? '' }}</span>
            @endif
        </div>
    </div>
</div>
