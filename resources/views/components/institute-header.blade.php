@php
    $config = \App\Models\ServerConfig::orderBy('id','DESC')->first();
@endphp
<style>
   .report-header{
    display:block !important;
        width:100%;
        text-align:center;
        margin:0 auto 8px;
        padding-bottom:6px;
        border-bottom:1px solid #e5e7eb;
    }

    .report-header .hdr-logo{
        height:60px;
        width:60px;
        object-fit:contain;
        display:inline-block;
        margin:0;
    }

    .report-header .logo-wrap{
        width:100%;
        display:block !important;
        text-align:center;
        margin:0 auto 6px;
    }

    .report-header .name{
        font-weight:700;
        margin-bottom:0;
    }

    .report-header .subline{
        font-size:12px;
        color:#6b7280;
    }

    .report-header .contacts{
        font-size:12px;
    }

    @media print{
        .report-header{
            margin-bottom:8px !important;
            padding-bottom:6px !important;
            border-bottom:1px solid #e5e7eb !important;
        }

        .report-header .hdr-logo{
            height:56px !important;
            width:56px !important;
            margin:0 auto !important;
            display:block !important;
        }

        .report-header .logo-wrap{
            width:100% !important;
            text-align:center !important;
            margin:0 auto 6px !important;
        }
    }
</style>

<div class="report-header">
    @if(!empty($config?->logo))
        @php
            $appBase = rtrim(config('app.url'), '/').'/public';
            $logoFile = $config->logo;
            $logoSrc = preg_match('~^https?://~i', $logoFile) ? $logoFile : $appBase.'/upload/image/cultivation/'.$logoFile;
        @endphp
        <div class="logo-wrap">
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
            <span style="margin-left:12px;"><i class="fa fa-envelope-o"></i> {{ $config->officeEmail ?? '' }}</span>
        @endif
    </div>
</div>
