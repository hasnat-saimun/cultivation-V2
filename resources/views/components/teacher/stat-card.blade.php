@props(['label', 'value' => null])

<article class="tp-card">
    <span class="tp-card-label">{{ $label }}</span>
    @if ($value === null)
        <strong class="tp-card-value soon">Coming Soon</strong>
    @else
        <strong class="tp-card-value">{{ $value }}</strong>
    @endif
</article>
