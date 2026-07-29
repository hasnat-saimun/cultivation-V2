<div class="report-header">
    @if(!empty($header['institute']['logoUrl']))
        <img src="{{ $header['institute']['logoUrl'] }}" alt="Institute Logo" class="hdr-logo">
    @endif
    <h2>{{ $header['institute']['name'] }}</h2>
    @if(!empty($header['institute']['address']))<p>{{ $header['institute']['address'] }}</p>@endif
    @if(!empty($header['institute']['mobile']) || !empty($header['institute']['email']))
        <p>{{ $header['institute']['mobile'] }}{{ !empty($header['institute']['mobile']) && !empty($header['institute']['email']) ? ' | ' : '' }}{{ $header['institute']['email'] }}</p>
    @endif
</div>

<div class="title">
    <h3>{{ $header['title'] }}</h3>
    <p>{{ $header['examName'] }}</p>
</div>
