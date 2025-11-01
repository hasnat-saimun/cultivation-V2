@php
    $serverData = \App\Models\ServerConfig::latest('id')->first();
    if($serverData->count()>0):
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
        $serverId           = "";
        $insName            = "Jahanara Ayub Academy";
        $location           = "North Shampur, Burichong, Cumilla";
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
<h3 class="fw-bold mb-0">{{ $insName }}</h3>
<p class="fw-bold mb-0 small">{{ $location }}</p>
<p class="fw-bold mb-0 small">Tel:- {{ $officeMobile }}, EIN:- {{ $einNumber }}</p>