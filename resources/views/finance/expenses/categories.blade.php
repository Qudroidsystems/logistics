{{-- resources/views/finance/expenses/categories.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Expense Categories" icon="ri-price-tag-3-line" subtitle="Each category posts to an expense account in the chart of accounts." :back="route('finance.expenses')" back-label="Expenses" />
    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Categories" icon="ri-list-check-2" :count="$categories->count()" :flush="true">
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Code</th><th>Name</th><th>Posts to</th><th>Active</th><th></th></tr></thead>
                    <tbody>
                    @foreach($categories as $c) @php $f = 'cat' . $c->id; @endphp
                        <tr>
                            <td><input form="{{ $f }}" name="code" value="{{ $c->code }}" class="form-control form-control-sm" style="width:110px"></td>
                            <td><input form="{{ $f }}" name="name" value="{{ $c->name }}" class="form-control form-control-sm"></td>
                            <td><select form="{{ $f }}" name="account_id" class="form-select form-select-sm"><option value="">— General (5000s) —</option>@foreach($accounts as $a)<option value="{{ $a->id }}" @selected($c->account_id == $a->id)>{{ $a->account_code }} {{ $a->account_name }}</option>@endforeach</select></td>
                            <td><input form="{{ $f }}" type="hidden" name="is_active" value="0"><input form="{{ $f }}" type="checkbox" class="form-check-input" name="is_active" value="1" @checked($c->is_active)></td>
                            <td><button form="{{ $f }}" class="action-btn btn-open"><i class="ri-save-line"></i></button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            </x-cb.card>
            @foreach($categories as $c)<form id="cat{{ $c->id }}" method="POST" action="{{ route('finance.expense-categories.save') }}">@csrf<input type="hidden" name="id" value="{{ $c->id }}"></form>@endforeach
        </div>
        <div class="col-xl-4">
            <x-cb.card title="Add category" icon="ri-add-circle-line">
                <form method="POST" action="{{ route('finance.expense-categories.save') }}" class="small">@csrf
                    <input name="code" class="form-control form-control-sm mb-2" placeholder="Code e.g. EXAM-001" required>
                    <input name="name" class="form-control form-control-sm mb-2" placeholder="Name" required>
                    <select name="account_id" class="form-select form-select-sm mb-2"><option value="">Posts to…</option>@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->account_code }} {{ $a->account_name }}</option>@endforeach</select>
                    <button class="action-btn btn-go w-100 justify-content-center">Add</button>
                </form>
            </x-cb.card>
            <x-cb.card title="Controls" icon="ri-shield-check-line">
                <form method="POST" action="{{ route('finance.expense-settings') }}" class="small">@csrf
                    <label class="form-label">Second approver needed above (₦)</label><input type="number" name="second_approval_above" min="0" step="1000" class="form-control form-control-sm mb-2" value="{{ $settings['second_approval_above'] }}">
                    <label class="form-label">Receipt required above (₦)</label><input type="number" name="receipt_required_above" min="0" step="1000" class="form-control form-control-sm mb-2" value="{{ $settings['receipt_required_above'] }}">
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="block_over_budget" value="1" id="bob" @checked($settings['block_over_budget'])><label for="bob" class="form-check-label">Stop vouchers that go over budget (otherwise just warn)</label></div>
                    <button class="action-btn btn-open w-100 justify-content-center">Save controls</button>
                </form>
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
