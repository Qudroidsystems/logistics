{{--
    <x-cb.hero title="My Payments" icon="ri-wallet-3-line"
               subtitle="View your bills…" :back="route('dashboard')" back-label="Dashboard">
        <x-slot:pills> <span class="cb-meta-pill">…</span> </x-slot:pills>
        <x-slot:actions> <a class="cb-hero-btn" href="…">Download</a> </x-slot:actions>
    </x-cb.hero>
--}}
@props(['title', 'subtitle' => null, 'icon' => null, 'back' => null, 'backLabel' => 'Back'])

<div {{ $attributes->merge(['class' => 'cb-hero']) }}>
    @if($back)
        <a href="{{ $back }}" class="cb-back"><i class="ri-arrow-left-line"></i>{{ $backLabel }}</a>
    @endif
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h1>@if($icon)<i class="{{ $icon }} me-2"></i>@endif{{ $title }}</h1>
            @if($subtitle)<p>{{ $subtitle }}</p>@endif
        </div>
        @isset($actions)
            <div class="cb-hero-actions">{{ $actions }}</div>
        @endisset
    </div>
    @isset($pills)
        <div class="meta-pills">{{ $pills }}</div>
    @endisset
</div>
