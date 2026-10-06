{{-- resources/views/accounting/dashboard.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); $y = $d['ytd']; $max = max(1, collect($d['months'])->flatMap(fn ($r) => [$r['income'], $r['expenses']])->max()); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Accounting" icon="ri-book-2-line" subtitle="Double-entry books fed automatically by fees, payroll, expenses, loans and assets.">
        <x-slot:actions>
            @can('Post journal entries')<a href="{{ route('accounting.journals.create') }}" class="cb-hero-btn"><i class="ri-add-line"></i>Journal entry</a>@endcan
        </x-slot:actions>
        <x-slot:pills>
            <span class="cb-meta-pill"><i class="ri-calendar-line"></i>Year from {{ \Carbon\Carbon::parse($d['fy_start'])->format('d M Y') }}</span>
            @if($settings['books_closed_until'])<span class="cb-meta-pill"><i class="ri-lock-line"></i>Books closed to {{ \Carbon\Carbon::parse($settings['books_closed_until'])->format('d M Y') }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>
    @include('accounting._nav', ['section' => 'dashboard'])

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
    @if($unposted['payroll'] || $unposted['fees'])
        <div class="cb-banner warning"><i class="ri-refresh-line"></i><div class="flex-grow-1">Not yet in the books: {{ $unposted['payroll'] ? $unposted['payroll'] . ' approved payroll month(s)' : '' }}{{ $unposted['payroll'] && $unposted['fees'] ? ' and ' : '' }}{{ $unposted['fees'] ? $unposted['fees'] . ' fee payment(s)' : '' }}. Fees post automatically each night.</div>
            @can('Post journal entries')<form method="POST" action="{{ route('accounting.catch-up') }}">@csrf<button class="action-btn btn-go">Post now</button></form>@endcan
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Cash & bank" :value="$m($d['cash_total'])" icon="ri-bank-line" accent="teal" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Income this year" :value="$m($y['total_income'])" icon="ri-arrow-up-circle-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Expenses this year" :value="$m($y['total_expenses'])" icon="ri-arrow-down-circle-line" accent="rose" :hint="'Staff costs ' . $m($y['total_staff'])" /></div>
        <div class="col-md-3 col-6"><x-cb.stat :label="$y['surplus'] >= 0 ? 'Surplus' : 'Deficit'" :value="$m(abs($y['surplus']))" icon="ri-scales-3-line" :accent="$y['surplus'] >= 0 ? 'green' : 'rose'" :hint="$y['total_income'] > 0 ? round($y['surplus'] / $y['total_income'] * 100) . '% margin' : null" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Last 6 months" icon="ri-bar-chart-2-line">
                <div class="d-flex align-items-end gap-3" style="height:180px">
                    @foreach($d['months'] as $r)
                        <div class="flex-fill text-center">
                            <div class="d-flex align-items-end justify-content-center gap-1" style="height:150px">
                                <div title="Income {{ $m($r['income']) }}" style="width:16px;height:{{ max(2, round($r['income'] / $max * 150)) }}px;background:#10b981;border-radius:4px 4px 0 0"></div>
                                <div title="Expenses {{ $m($r['expenses']) }}" style="width:16px;height:{{ max(2, round($r['expenses'] / $max * 150)) }}px;background:#f43f5e;border-radius:4px 4px 0 0"></div>
                            </div>
                            <div class="small text-muted">{{ $r['label'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="small mt-2"><span style="display:inline-block;width:10px;height:10px;background:#10b981"></span> Income &nbsp; <span style="display:inline-block;width:10px;height:10px;background:#f43f5e"></span> Expenses</div>
            </x-cb.card>
            <x-cb.card title="Recent entries" icon="ri-book-2-line" :flush="true">
                <x-slot:tools><a href="{{ route('accounting.journals') }}" class="action-btn btn-open">All</a></x-slot:tools>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <tbody>@forelse($recent as $e)<tr><td class="small text-muted">{{ $e->entry_date->format('d M') }}</td><td><a href="{{ route('accounting.journals.show', $e) }}">{{ $e->entry_no }}</a> <span class="small text-muted">{{ $e->typeLabel() }}</span><div class="small">{{ \Illuminate\Support\Str::limit($e->description, 80) }}</div></td><td class="text-end fw-bold">{{ $m($e->total()) }}</td></tr>@empty<tr><td class="text-muted p-3">No entries yet.</td></tr>@endforelse</tbody>
                </table></div>
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            <x-cb.card title="Cash & bank" icon="ri-bank-line" :flush="true">
                <ul class="list-group list-group-flush small">
                    @foreach($d['cash'] as $c)<li class="list-group-item d-flex justify-content-between"><a href="{{ route('accounting.ledger', ['account' => $c['account']->id]) }}">{{ $c['account']->account_code }} {{ $c['account']->account_name }}</a><b class="{{ $c['balance'] < 0 ? 'text-danger' : '' }}">{{ $m($c['balance']) }}</b></li>@endforeach
                </ul>
            </x-cb.card>
            @can('Post journal entries')
            <x-cb.card title="Automatic posting" icon="ri-robot-2-line">
                <div class="small text-muted mb-2">Payroll posts when a month is approved; salary transfers, expenses, loans, cooperative and depreciation post as they happen; fees post nightly.</div>
                <form method="POST" action="{{ route('accounting.sync-fees') }}" class="mb-2">@csrf<button class="action-btn btn-open w-100 justify-content-center"><i class="ri-refresh-line"></i>Post fee receipts now</button></form>
                <form method="POST" action="{{ route('accounting.catch-up') }}">@csrf<button class="action-btn btn-open w-100 justify-content-center"><i class="ri-history-line"></i>Post anything missed</button></form>
            </x-cb.card>
            @endcan
            @can('Close accounting period')
            <x-cb.card title="Settings" icon="ri-settings-3-line">
                <form method="POST" action="{{ route('accounting.settings') }}" class="small">@csrf
                    <label class="form-label">Close the books up to</label><input type="date" name="books_closed_until" value="{{ $settings['books_closed_until'] }}" max="{{ now()->toDateString() }}" class="form-control form-control-sm mb-2">
                    <label class="form-label">Financial year starts (MM-DD)</label><input type="text" name="financial_year_start" value="{{ $settings['financial_year_start'] }}" pattern="\d{2}-\d{2}" class="form-control form-control-sm mb-2">
                    <label class="form-label">Main bank account</label><select name="bank_account" class="form-select form-select-sm mb-2">@foreach($accounts as $a)<option value="{{ $a->account_code }}" @selected($settings['bank_account'] === $a->account_code)>{{ $a->account_code }} {{ $a->account_name }}</option>@endforeach</select>
                    <label class="form-label">Cash / petty cash account</label><select name="cash_account" class="form-select form-select-sm mb-2">@foreach($accounts as $a)<option value="{{ $a->account_code }}" @selected($settings['cash_account'] === $a->account_code)>{{ $a->account_code }} {{ $a->account_name }}</option>@endforeach</select>
                    <button class="action-btn btn-go w-100 justify-content-center">Save</button>
                    <div class="text-muted mt-1">Closing stops anyone posting on or before that date — use it after the auditors sign off a year or term.</div>
                </form>
            </x-cb.card>
            @endcan
        </div>
    </div>
</div>
</div>
</div>
@endsection
