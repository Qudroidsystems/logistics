{{-- resources/views/accounting/ledger.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); $z = fn ($x) => $x > 0 ? number_format((float) $x, 2) : ''; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="General Ledger" icon="ri-file-list-3-line" :subtitle="$data ? $data['account']->account_code . ' · ' . $data['account']->account_name : 'Choose an account'" />
    @include('accounting._nav', ['section' => 'ledger'])
    <x-cb.card title="Account statement" icon="ri-file-list-3-line" :flush="true">
        <x-slot:tools>
            <div class="d-flex flex-wrap gap-2">
                <form method="GET" class="d-print-none"><input type="hidden" name="from" value="{{ $from }}"><input type="hidden" name="to" value="{{ $to }}">
                    <select name="account" class="cb-select" onchange="this.form.submit()">@foreach($accounts as $a)<option value="{{ $a->id }}" @selected($data && $data['account']->id === $a->id)>{{ $a->account_code }} {{ $a->account_name }}</option>@endforeach</select></form>
                @include('accounting._range', ['mode' => 'range', 'from' => $from, 'to' => $to])
            </div>
        </x-slot:tools>
        @if($data)
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Date</th><th>Entry</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
                <tr class="table-light"><td>{{ \Carbon\Carbon::parse($from)->format('d M Y') }}</td><td></td><td><b>Opening balance</b></td><td></td><td></td><td class="text-end fw-bold">{{ $m($data['opening']) }}</td></tr>
                @forelse($data['rows'] as $r)
                    <tr><td class="text-nowrap">{{ \Carbon\Carbon::parse($r->entry_date)->format('d M Y') }}</td><td><a href="{{ route('accounting.journals.show', $r->entry_id) }}">{{ $r->entry_no }}</a></td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($r->description, 90) }}@if($r->narration)<div class="text-muted">{{ $r->narration }}</div>@endif</td>
                        <td class="text-end">{{ $z($r->debit) }}</td><td class="text-end">{{ $z($r->credit) }}</td><td class="text-end">{{ $m($r->balance) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted p-3">No movements in this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><th colspan="3" class="text-end">Totals / closing</th><th class="text-end">{{ number_format($data['debit'], 2) }}</th><th class="text-end">{{ number_format($data['credit'], 2) }}</th><th class="text-end">{{ $m($data['closing']) }}</th></tr></tfoot>
        </table></div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
@endsection
