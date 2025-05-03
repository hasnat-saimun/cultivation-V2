@php
    $serverData = \App\Models\ServerConfig::orderBy('id','DESC')->limit(1)->first();
    if(!empty($serverData)):
        $serverId           = $serverData->id;
        $insName            = $serverData->institueName;
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
        $serverId           = "";
        $insName            = "Sonar Bangla College";
        $location           = "Poyat, Burichong, Cumilla";
        $officeMobile       = "01716841785";
        $officeMail         = "";
        $principalSign      = "";
        $logo               = "";
        $favicon            = "";
        $avatar             = "";
        $einNumber          = "434713";
        $establishDate      = "";
    endif;
@endphp
<h3 class="fw-bold mb-0 display-4">{{ $insName }}</h3>
<p class="fw-bold mb-0 h3">{{ $location }}</p>
<p class="fw-bold mb-0">Tel:- {{ $officeMobile }}, EIN:- {{ $einNumber }}</p>