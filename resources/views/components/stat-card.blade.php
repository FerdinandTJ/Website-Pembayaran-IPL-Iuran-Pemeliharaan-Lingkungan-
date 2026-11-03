@props(['label', 'value', 'icon' => 'bar-chart', 'hint' => null, 'tone' => 'green'])
<div {{ $attributes->class(['card stat-card h-100', 'stat-tone-'.$tone]) }}>
    <div class="card-body"><div class="stat-heading"><span class="stat-label">{{ $label }}</span><span class="stat-icon"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></span></div>
        <div class="stat-value">{{ $value }}</div>
        @if($hint)<div class="stat-hint">{{ $hint }}</div>@endif
    </div>
</div>
