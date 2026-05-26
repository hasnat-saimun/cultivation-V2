@props([
    'label' => 'Metric',
    'value' => 0,
    'hint' => null,
    'tone' => 'neutral',
])

<article class="am-stat am-tone-{{ $tone }}">
    <p class="am-stat-label">{{ $label }}</p>
    <p class="am-stat-value">{{ $value }}</p>
    @if($hint)
        <p class="am-stat-hint">{{ $hint }}</p>
    @endif
</article>
