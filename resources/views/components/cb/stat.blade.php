{{-- <x-cb.stat label="Outstanding" :value="$amount" icon="ri-alarm-warning-line" accent="rose" /> --}}
@props(['label', 'value', 'icon' => null, 'accent' => 'teal', 'hint' => null])

<div {{ $attributes->merge(['class' => 'cb-stat accent-' . $accent]) }}>
    <div class="stat-accent"></div>
    @if($icon)<div class="stat-ico"><i class="{{ $icon }}"></i></div>@endif
    <div class="stat-value">{{ $value }}</div>
    <div class="stat-label">{{ $label }}@if($hint) <span class="d-block">{{ $hint }}</span>@endif</div>
</div>
