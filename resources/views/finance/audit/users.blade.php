{{-- resources/views/finance/audit/users.blade.php --}}
@extends('layouts.master')

@section('content')
@php $naira = fn($v) => '₦'.number_format((float)$v,0); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Who did what (financial)" icon="ri-group-line" subtitle="Segregation of duties: who records, edits and approves money — a core audit check." :back="route('finance.audit.dashboard')" back-label="Audit" />

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-auto"><label class="form-label small">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
        <div class="col-auto"><label class="form-label small">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
        <div class="col-auto"><button class="action-btn btn-primary-cb"><i class="ri-filter-3-line"></i>Apply</button></div>
    </form>

    <x-cb.card title="Financial activity by user" icon="ri-user-settings-line" :count="$activity->count()" :flush="true">
        @if($activity->isEmpty())
            <div class="empty-state"><i class="ri-user-line"></i><h6>No activity</h6><p>No financial changes recorded in this period.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>User</th><th class="text-end">Created</th><th class="text-end">Edited</th><th class="text-end">Deleted</th><th class="text-end">Total</th><th class="text-end">Value touched</th></tr></thead>
                <tbody>
                @foreach($activity as $a)
                    <tr>
                        <td>{{ $a->user_name ?: ('User #'.$a->user_id) }}</td>
                        <td class="text-end text-success">{{ $a->created }}</td>
                        <td class="text-end text-info">{{ $a->updated }}</td>
                        <td class="text-end {{ $a->deleted>0?'text-danger fw-semibold':'' }}">{{ $a->deleted }}</td>
                        <td class="text-end">{{ $a->total }}</td>
                        <td class="text-end">{{ $naira($a->amount) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>

    @if(!empty($sod['requested']) || !empty($sod['approved']))
    <div class="row g-3">
        <div class="col-lg-6">
            <x-cb.card title="Expense vouchers — requested by" icon="ri-file-add-line" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>User</th><th class="text-end">Count</th><th class="text-end">Total</th></tr></thead><tbody>
                    @forelse($sod['requested'] as $r)<tr><td>{{ $r->name }}</td><td class="text-end">{{ $r->n }}</td><td class="text-end">{{ $naira($r->total) }}</td></tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-3">None</td></tr>@endforelse
                </tbody></table></div>
            </x-cb.card>
        </div>
        <div class="col-lg-6">
            <x-cb.card title="Expense vouchers — approved by" icon="ri-checkbox-circle-line" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>User</th><th class="text-end">Count</th><th class="text-end">Total</th></tr></thead><tbody>
                    @forelse($sod['approved'] as $r)<tr><td>{{ $r->name }}</td><td class="text-end">{{ $r->n }}</td><td class="text-end">{{ $naira($r->total) }}</td></tr>
                    @empty<tr><td colspan="3" class="text-center text-muted py-3">None</td></tr>@endforelse
                </tbody></table></div>
            </x-cb.card>
        </div>
    </div>
    <div class="cb-banner info"><i class="ri-information-line"></i><div>A healthy control environment has <strong>different people</strong> requesting and approving spending. Names appearing high in both lists warrant a closer look (also see the "Self-approved vouchers" exception).</div></div>
    @endif
</div></div></div>
@endsection
