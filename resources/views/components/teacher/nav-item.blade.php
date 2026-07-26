@props(['label', 'icon', 'href' => null, 'active' => false])

@if ($href)
    <a href="{{ $href }}" class="tp-nav-item {{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif>
        <span class="tp-nav-icon" aria-hidden="true">{{ $icon }}</span>
        <span>{{ $label }}</span>
    </a>
@else
    <span class="tp-nav-item disabled" aria-disabled="true">
        <span class="tp-nav-icon" aria-hidden="true">{{ $icon }}</span>
        <span>{{ $label }}</span>
        <span class="tp-coming">Coming Soon</span>
    </span>
@endif
