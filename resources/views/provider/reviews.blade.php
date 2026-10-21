@extends('layouts.master')

@section('content')
@php $avg = $sum->n ? round((float) $sum->avg, 1) : null; @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Reviews" icon="ri-star-line" subtitle="What customers said after their deliveries. Reviews hidden by the platform's moderators are marked." :back="route('provider.dashboard')" back-label="Dashboard" />

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <x-cb.card title="Your rating" icon="ri-star-smile-line">
                <div class="display-6 fw-semibold">{{ $avg ? number_format($avg, 1) : '—' }}<span class="fs-5 text-muted"> / 5</span></div>
                <div class="small text-muted">{{ $sum->n }} reviews · {{ $sum->n ? round($sum->good * 100 / $sum->n) : 0 }}% gave 4 or 5 stars</div>
                <div class="mt-3">
                    @for($i = 5; $i >= 1; $i--)
                        @php $n = (int) ($spread[$i] ?? 0); $pct = $sum->n ? round($n * 100 / $sum->n) : 0; @endphp
                        <div class="d-flex align-items-center gap-2 small mb-1"><span style="width:28px">{{ $i }}★</span>
                            <div class="progress flex-grow-1" style="height:6px"><div class="progress-bar bg-warning" style="width: {{ $pct }}%"></div></div><span class="text-muted" style="width:28px">{{ $n }}</span></div>
                    @endfor
                </div>
            </x-cb.card>
        </div>
        <div class="col-md-8">
            <x-cb.card title="Latest reviews" icon="ri-chat-quote-line" :flush="true">
                <div class="list-group list-group-flush">
                    @forelse($rows as $r)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span class="text-warning">{{ str_repeat('★', (int) $r->score) }}<span class="text-muted">{{ str_repeat('★', 5 - (int) $r->score) }}</span></span>
                                <span class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('j M Y') }} · {{ $r->order_number }}</span>
                            </div>
                            @if($r->comment)<div class="mt-1">{{ $r->comment }}</div>@endif
                            @php $tags = $r->tags ? (json_decode($r->tags, true) ?: []) : []; @endphp
                            @if($tags)<div class="mt-1">@foreach($tags as $tg)<span class="badge bg-light text-dark me-1">{{ str_replace('_', ' ', $tg) }}</span>@endforeach</div>@endif
                            <div class="small text-muted mt-1">{{ $r->name ? \Illuminate\Support\Str::before($r->name, ' ') : 'Customer' }}@if($r->moderation_status !== 'approved') · <span class="badge bg-secondary">{{ $r->moderation_status === 'hidden' ? 'hidden by moderators' : $r->moderation_status }}</span>@endif</div>
                        </div>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">No reviews yet. Customers can rate you after each delivery.</div>
                    @endforelse
                </div>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
