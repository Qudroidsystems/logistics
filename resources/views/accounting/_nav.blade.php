{{-- Accounting section tabs. Needs $section --}}
@php $tabs = [
    'dashboard' => ['Overview', 'ri-dashboard-3-line', 'accounting.dashboard'], 'journals' => ['Journal', 'ri-book-2-line', 'accounting.journals'],
    'ledger' => ['Ledger', 'ri-file-list-3-line', 'accounting.ledger'], 'trial' => ['Trial balance', 'ri-scales-line', 'accounting.trial-balance'],
    'income' => ['Income & expenditure', 'ri-line-chart-line', 'accounting.income-statement'], 'balance' => ['Balance sheet', 'ri-bank-line', 'accounting.balance-sheet'],
    'cash' => ['Cash flow', 'ri-exchange-dollar-line', 'accounting.cash-flow'], 'accounts' => ['Accounts', 'ri-list-settings-line', 'accounting.accounts'],
]; @endphp
<div class="cb-card mb-3 d-print-none"><div class="cb-toolbar"><div class="term-chips flex-wrap">
    @foreach($tabs as $k => [$l, $i, $r])<a class="term-chip {{ $section === $k ? 'active' : '' }}" href="{{ route($r) }}"><i class="{{ $i }} me-1"></i>{{ $l }}</a>@endforeach
</div></div></div>
