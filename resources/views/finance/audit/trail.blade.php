{{-- resources/views/finance/audit/trail.blade.php --}}
@extends('layouts.master')

@section('content')
@php $naira = fn($v) => $v!==null ? '₦'.number_format((float)$v,2) : '—'; @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Audit Trail" icon="ri-history-line" subtitle="Every financial record created, edited or deleted — with the before/after and who did it." :back="route('finance.audit.dashboard')" back-label="Audit">
        <x-slot name="actions">
            <a href="{{ route('finance.audit.trail.export', request()->query()) }}" class="action-btn btn-primary-cb"><i class="ri-download-2-line"></i>Export CSV</a>
        </x-slot>
    </x-cb.hero>

    <x-cb.card title="Filter" icon="ri-filter-3-line">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Summary, ref or user"></div>
            <div class="col-md-2"><label class="form-label small">Module</label>
                <select name="module" class="form-select"><option value="">All</option>@foreach($modules as $k=>$v)<option value="{{ $k }}" @selected(request('module')===$k)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">Action</label>
                <select name="event" class="form-select"><option value="">All</option>@foreach($events as $k=>$v)<option value="{{ $k }}" @selected(request('event')===$k)>{{ $v[0] }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label small">User</label>
                <select name="user" class="form-select"><option value="">All</option>@foreach($users as $id=>$name)<option value="{{ $id }}" @selected(request('user')==$id)>{{ $name }}</option>@endforeach</select></div>
            <div class="col-md-1"><label class="form-label small">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
            <div class="col-md-1"><label class="form-label small">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
            <div class="col-md-1"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-search-line"></i></button></div>
        </form>
    </x-cb.card>

    <x-cb.card title="Records" icon="ri-list-check-2" :count="$rows->total()" :flush="true">
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-history-line"></i><h6>Nothing recorded</h6><p>No financial changes match these filters.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th class="text-end">Amount</th><th>Changes</th></tr></thead>
                <tbody>
                @foreach($rows as $r) @php [$el,$ecl]=$r->label(); @endphp
                    <tr>
                        <td class="small">{{ optional($r->created_at)->format('d M Y H:i') }}</td>
                        <td class="small">{{ $r->user_name ?: '—' }}</td>
                        <td><span class="status-pill {{ $ecl }}">{{ $el }}</span></td>
                        <td>{{ $r->model_label }} <span class="text-muted small">{{ $r->ref }}</span><div class="small text-muted">{{ $r->summary }}</div></td>
                        <td class="text-end">{{ $naira($r->amount) }}</td>
                        <td style="max-width:320px">
                            @if($r->changes)
                                <details><summary class="text-muted small">{{ count($r->changes) }} field(s)</summary>
                                    <div class="small">@foreach($r->changes as $f=>$pair)<div><b>{{ $f }}</b>: <span class="text-danger">{{ \Illuminate\Support\Str::limit((string)($pair[0] ?? '∅'),24) }}</span> → <span class="text-success">{{ \Illuminate\Support\Str::limit((string)($pair[1] ?? '∅'),24) }}</span></div>@endforeach</div>
                                </details>
                            @else<span class="text-muted small">—</span>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
</div></div></div>
@endsection
