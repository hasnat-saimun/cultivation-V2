@php
    // Attempt to reuse existing server config for institute data
    $serverData = \App\Models\ServerConfig::latest('id')->first();
    $insName      = $serverData->instituteName ?? ($institute['name'] ?? '');
    $address      = $serverData->address ?? ($institute['address'] ?? '');
    $phone        = $serverData->officeMobile ?? ($institute['phone'] ?? '');
    $logoFile     = $serverData->logo ?? null;
    // Build logo URL explicitly from APP_URL (config('app.url')) to avoid relative path issues
    $appUrl = rtrim(config('app.url'), '/');
    $logoUrl = null;
    if($logoFile){
        if(preg_match('~^https?://~i', $logoFile)){
            $logoUrl = $logoFile; // already full URL
        } else {
            // Detect actual public path; avoid duplicate /public if APP_URL already points to public
            $publicSegmentEnds = preg_match('~/public$~i', $appUrl);
            $relativePath = 'upload/image/cultivation/'.$logoFile;
            // Check existence at standard public_path
            $fullFsPath = public_path($relativePath);
            if(!file_exists($fullFsPath)){
                // Try alternative with explicit public/ prefix if first attempt fails
                $altRelative = 'public/upload/image/cultivation/'.$logoFile;
                if(file_exists(public_path($altRelative))){
                    $relativePath = $altRelative;
                }
            }
            // Build URL (avoid double public)
            $logoUrl = $appUrl.'/'.ltrim($relativePath,'/');
            if($publicSegmentEnds && str_contains($relativePath,'public/upload/')){
                // Remove duplicate public if APP_URL already ends with /public
                $logoUrl = rtrim($appUrl,'/').'/'.ltrim(preg_replace('~^public/~','',$relativePath),'/' );
            }
        }
    } else {
        $instLogo = $institute['logo'] ?? null;
        if($instLogo){
            if(preg_match('~^https?://~i', $instLogo)){
                $logoUrl = $instLogo;
            } else {
                $logoUrl = $appUrl.'/'.ltrim($instLogo,'/');
            }
        }
    }
@endphp
<div class="att-print-header" style="display:flex;align-items:center;gap:12px;border-bottom:2px solid #444;padding-bottom:8px;">
    @if($logoUrl)
        <div style="width:70px;flex:0 0 70px;">
            <img src="{{ $logoUrl }}" alt="Logo" style="max-width:100%;height:auto;">
        </div>
    @endif
    <div style="flex:1;">
        <h1 style="margin:0;font-size:20px;line-height:1.2;">{{ $insName ?: 'Institution Name' }}</h1>
        @if($address)<div style="font-size:12px;">{{ $address }}</div>@endif
        <div style="font-size:12px;">
            @if($phone) <strong>Phone:</strong> {{ $phone }} @endif
            @if(($filters['date'] ?? null)) &nbsp; | <strong>Date:</strong> {{ $filters['date'] }} @endif
        </div>
    </div>
    <div style="text-align:right;font-size:11px;">
        <div><strong>Generated:</strong> {{ date('Y-m-d H:i') }}</div>
        <div><strong>Class:</strong> {{ $filters['className'] ?? ($filters['classId'] ?? '-') }}</div>
        <div><strong>Session:</strong> {{ $filters['sessionName'] ?? ($filters['sessionId'] ?? '-') }}</div>
        <div><strong>Section:</strong> {{ $filters['sectionName'] ?? ($filters['sectionId'] ?? '-') }}</div>
        @if(!empty($filters['teacherName']))
        <div><strong>Teacher:</strong> {{ $filters['teacherName'] }}</div>
        @endif
    </div>
</div>
