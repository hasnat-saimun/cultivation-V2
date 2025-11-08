@php
    $serverData = \App\Models\ServerConfig::orderBy('id','DESC')->limit(1)->first();
    if(!empty($serverData)):
        $serverId           = $serverData->id;
        $insName            = $serverData->instituteName;
        $location           = $serverData->address;
        $officeMobile       = $serverData->officeMobile;
        $officeMail         = $serverData->officeEmail;
        $principalSign      = $serverData->principalSign;
        $logo               = $serverData->logo;
        $favicon            = $serverData->favicon;
        $avatar             = $serverData->avatar;
        $einNumber          = $serverData->einNumber;
        $establishDate      = $serverData->establishDate;
    else:
        // No server config found; keep variables empty so header stays minimal
        $serverId = $insName = $location = $officeMobile = $officeMail = $principalSign = $logo = $favicon = $avatar = $einNumber = $establishDate = "";
    endif;
@endphp
<style>
    .notice-header{font-family:'Segoe UI',Tahoma,Arial,sans-serif; text-align:center; position:relative;}
    .notice-header .nh-logo{width:70px;height:70px;object-fit:contain;margin:0 auto 10px;border-radius:50%;background:#fff;border:2px solid #ddd;padding:6px;}
    .notice-header h1{font-size:34px;font-weight:600;margin:0 0 6px;letter-spacing:.5px;}
    .notice-header .meta-line{color:#526275;font-size:16px;margin:2px 0;display:flex;align-items:center;justify-content:center;gap:6px;}
    .notice-header .meta-line i{color:#2c4259;}
    /* Date removed as per new requirement */
    .notice-header .date-box{display:none;}
</style>
<div class="notice-header">
    @if(!empty($logo))
        <img src="{{ asset('public') }}/upload/image/cultivation/{{ $logo }}" alt="{{ $insName }}" class="nh-logo">
    @endif
    <h1>{{ $insName }}</h1>
    @if(!empty($location))
    <div class="meta-line"><i class="fa-solid fa-location-dot"></i> <span>{{ $location }}</span></div>
    @endif
    @if(!empty($officeMobile))
    <div class="meta-line"><i class="fa-solid fa-phone"></i> <span>{{ $officeMobile }}</span></div>
    @endif
    @if(!empty($officeMail))
    <div class="meta-line"><i class="fa-solid fa-envelope"></i> <span>{{ $officeMail }}</span></div>
    @endif
    <div class="date-box"></div>
</div>