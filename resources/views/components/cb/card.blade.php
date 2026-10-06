{{--
    <x-cb.card title="Payment History" icon="ri-history-line" :count="$rows->count()" :flush="true">
        <x-slot:tools> …buttons… </x-slot:tools>
        …body…
    </x-cb.card>
    flush = true removes the body padding (for tables / toolbars).
--}}
@props(['title' => null, 'icon' => null, 'count' => null, 'flush' => false])

<div {{ $attributes->merge(['class' => 'cb-card']) }}>
    @if($title || isset($tools))
        <div class="cb-card-header">
            <h5>@if($icon)<i class="{{ $icon }}"></i>@endif{{ $title }}
                @if(!is_null($count))<span class="cb-count">{{ $count }}</span>@endif
            </h5>
            @isset($tools)<div class="d-flex gap-2 align-items-center flex-wrap">{{ $tools }}</div>@endisset
        </div>
    @endif
    <div class="{{ $flush ? '' : 'cb-card-body' }}">
        {{ $slot }}
    </div>
</div>
