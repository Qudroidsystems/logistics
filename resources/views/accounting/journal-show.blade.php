{{-- resources/views/accounting/journal-show.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => $x > 0 ? '₦' . number_format((float) $x, 2) : ''; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="$e->entry_no" icon="ri-book-2-line" :subtitle="$e->description" :back="route('accounting.journals')" back-label="Journal">
        <x-slot:actions>
            <button type="button" class="cb-hero-btn" onclick="window.print()"><i class="ri-printer-line"></i>Print</button>
            @can('Post journal entries')
                @if($e->status === 'draft')<form method="POST" action="{{ route('accounting.journals.post', $e) }}" class="d-inline">@csrf<button class="cb-hero-btn"><i class="ri-check-double-line"></i>Post</button></form>@endif
            @endcan
        </x-slot:actions>
        <x-slot:pills>
            <span class="cb-meta-pill"><span class="status-pill {{ ['posted' => 'st-paid', 'draft' => 'st-pending', 'reversed' => 'st-muted'][$e->status] ?? 'st-muted' }}">{{ ucfirst($e->status) }}</span></span>
            <span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $e->entry_date->format('d M Y') }}</span>
            <span class="cb-meta-pill"><i class="ri-price-tag-3-line"></i>{{ $e->typeLabel() }}</span>
            <span class="cb-meta-pill"><i class="ri-user-line"></i>{{ $e->creator->name ?? 'Automatic' }}</span>
            @if($source)<span class="cb-meta-pill"><a class="text-reset" href="{{ $source[1] }}"><i class="ri-links-line"></i>{{ $source[0] }}</a></span>@endif
        </x-slot:pills>
    </x-cb.hero>
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($e->status === 'reversed')<div class="cb-banner warning"><i class="ri-arrow-go-back-line"></i><div>Reversed{{ $e->reversal_reason ? ': ' . $e->reversal_reason : '' }}@if($reversal) — see <a href="{{ route('accounting.journals.show', $reversal) }}">{{ $reversal->entry_no }}</a>@endif</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Lines" icon="ri-list-check-2" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Account</th><th>Narration</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                    <tbody>@foreach($e->lines->sortByDesc('debit') as $l)<tr><td><a href="{{ route('accounting.ledger', ['account' => $l->account_id]) }}"><code>{{ $l->account->account_code ?? '' }}</code> {{ $l->account->account_name ?? '' }}</a></td><td class="small">{{ $l->narration }}</td><td class="text-end">{{ $m($l->debit) }}</td><td class="text-end">{{ $m($l->credit) }}</td></tr>@endforeach</tbody>
                    <tfoot><tr><th colspan="2" class="text-end">Total</th><th class="text-end">{{ $m($e->lines->sum('debit')) }}</th><th class="text-end">{{ $m($e->lines->sum('credit')) }}</th></tr></tfoot>
                </table></div>
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            @can('Post journal entries')
                @if($e->status === 'posted' && $e->entry_type !== 'reversal')
                <x-cb.card title="Reverse" icon="ri-arrow-go-back-line">
                    <form method="POST" action="{{ route('accounting.journals.reverse', $e) }}" onsubmit="return confirm('Post a reversing entry dated today?')">@csrf
                        <input name="reason" class="form-control form-control-sm mb-2" placeholder="Why?" required>
                        <button class="action-btn btn-open w-100 justify-content-center">Reverse entry</button>
                    </form>
                    <div class="small text-muted mt-1">Posted entries are never edited or deleted; a reversing entry cancels them so the audit trail stays complete.</div>
                </x-cb.card>
                @endif
            @endcan
            <div class="small text-muted">Created {{ $e->created_at->format('d M Y H:i') }}@if($e->approver) · posted by {{ $e->approver->name }} {{ $e->approved_at?->format('d M Y H:i') }}@endif</div>
        </div>
    </div>
</div>
</div>
</div>
@endsection
