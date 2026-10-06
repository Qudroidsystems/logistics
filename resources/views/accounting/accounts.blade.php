{{-- resources/views/accounting/accounts.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); $T = ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expenses']; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Chart of Accounts" icon="ri-list-settings-line" subtitle="The accounts every transaction is posted to, with today's balance." />
    @include('accounting._nav', ['section' => 'accounts'])
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif
    <div class="row g-3">
        <div class="col-xl-8">
            @foreach($T as $type => $label)
                @if(isset($accounts[$type]))
                <x-cb.card :title="$label" icon="ri-folder-2-line" :count="$accounts[$type]->count()" :flush="true">
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                        <tbody>
                        @foreach($accounts[$type] as $a)
                            <tr class="{{ $a->is_active ? '' : 'text-muted' }}">
                                <td style="width:80px"><code>{{ $a->account_code }}</code></td>
                                <td class="{{ $a->parent_id ? 'ps-4' : 'fw-bold' }}">{{ $a->account_name }} @if($a->is_bank_account)<i class="ri-bank-line text-muted" title="Bank account"></i>@endif @unless($a->is_active)<span class="status-pill st-muted">inactive</span>@endunless</td>
                                <td class="text-end {{ $a->balance < 0 ? 'text-danger' : '' }}">{{ $a->balance != 0 ? $m($a->balance) : '—' }}</td>
                                <td class="text-end text-nowrap"><a href="{{ route('accounting.ledger', ['account' => $a->id]) }}" class="action-btn btn-open" title="Ledger"><i class="ri-file-list-3-line"></i></a>
                                    @can('Manage chart of accounts')<button type="button" class="action-btn btn-open" title="Edit" data-acc="{{ json_encode($a->only(['id', 'account_code', 'account_name', 'account_type', 'bank_name', 'bank_account_no', 'description', 'is_bank_account', 'is_active'])) }}" onclick="editAcc(JSON.parse(this.dataset.acc))"><i class="ri-edit-line"></i></button>@endcan</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                </x-cb.card>
                @endif
            @endforeach
        </div>
        <div class="col-xl-4">
            @can('Manage chart of accounts')
            <x-cb.card title="Add / edit account" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('accounting.accounts.save') }}" class="small" id="accForm">@csrf
                    <input type="hidden" name="id">
                    <div class="row g-2 mb-2"><div class="col-4"><input name="account_code" class="form-control form-control-sm" placeholder="Code" required></div><div class="col-8"><input name="account_name" class="form-control form-control-sm" placeholder="Name" required></div></div>
                    <select name="account_type" class="form-select form-select-sm mb-2">@foreach($T as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_bank_account" value="1" id="iba"><label for="iba" class="form-check-label">This is a bank account</label></div>
                    <div class="row g-2 mb-2"><div class="col-6"><input name="bank_name" class="form-control form-control-sm" placeholder="Bank"></div><div class="col-6"><input name="bank_account_no" class="form-control form-control-sm" placeholder="Account no."></div></div>
                    <input name="description" class="form-control form-control-sm mb-2" placeholder="Description">
                    <div class="form-check mb-2"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="iact" checked><label for="iact" class="form-check-label">Active</label></div>
                    <button class="action-btn btn-go w-100 justify-content-center">Save account</button>
                </form>
                <div class="small text-muted mt-2">Codes: 1xxx assets, 2xxx liabilities, 3xxx equity, 4xxx income, 5xxx expenses. Add one account per bank account the school operates.</div>
            </x-cb.card>
            @endcan
        </div>
    </div>
</div>
</div>
</div>
<script>
function editAcc(a) {
    const f = document.getElementById('accForm');
    ['id', 'account_code', 'account_name', 'account_type', 'bank_name', 'bank_account_no', 'description'].forEach(k => f.elements[k].value = a[k] ?? '');
    f.elements['is_bank_account'].checked = !!a.is_bank_account; f.querySelector('#iact').checked = !!a.is_active;
    f.scrollIntoView({behavior: 'smooth'});
}
</script>
@endsection
