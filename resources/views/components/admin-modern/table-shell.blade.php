@props([
    'title' => 'Table',
])

<section class="am-panel">
    <div class="am-panel-head">
        <h2>{{ $title }}</h2>
    </div>
    <div class="am-table-wrap">
        {{ $slot }}
    </div>
</section>
