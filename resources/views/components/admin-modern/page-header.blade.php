@props([
    'title' => 'Dashboard',
    'subtitle' => null,
    'breadcrumb' => [],
])

<section class="am-page-header">
    <div>
        <h1 class="am-page-title">{{ $title }}</h1>
        @if($subtitle)
            <p class="am-page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if(!empty($breadcrumb))
        <ol class="am-breadcrumb" aria-label="Breadcrumb">
            @foreach($breadcrumb as $crumb)
                <li @if($loop->last) aria-current="page" @endif>{{ $crumb }}</li>
            @endforeach
        </ol>
    @endif
</section>
