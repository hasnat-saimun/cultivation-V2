@php
    $config = \App\Models\ServerConfig::orderBy('id','DESC')->first();
@endphp

<div class="report-header d-flex align-items-center mb-3 pb-3 border-bottom">
    @if(!empty($config?->logo))
        @php
            $appBase = rtrim(config('app.url'), '/').'/public';
            $logoFile = $config->logo;
            $logoSrc = preg_match('~^https?://~i', $logoFile) ? $logoFile : $appBase.'/upload/image/cultivation/'.$logoFile;
        @endphp
        <div class="me-3" style="flex:0 0 auto;">
            <img src="{{ $logoSrc }}" alt="Institute Logo" style="height:60px;width:auto;object-fit:contain;">
        </div>
    @endif
    <div class="flex-grow-1 text-center text-md-start">
        <h4 class="mb-0" style="font-weight:700;">{{ $config->instituteName ?? 'Institute Name' }}</h4>
        <div class="small text-muted">{{ $config->address ?? '' }}</div>
        <div class="small">
            @if(!empty($config?->officeMobile))
                <span><i class="fa fa-phone"></i> {{ $config->officeMobile }}</span>
            @endif
            @if(!empty($config?->officeEmail))
                <span class="ms-3"><i class="fa fa-envelope-o"></i> {{ $config->officeEmail }}</span>
            @endif
        </div>
    </div>
</div>
