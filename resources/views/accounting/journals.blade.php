{{-- resources/views/accounting/journals.blade.php --}}
@extends('layouts.master')

@section('content')
@php $m = fn ($x) => '₦' . number_format((float) $x, 2); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Journal" icon="ri-book-2-line" subtitle="Every entry in the books — automatic and manual.">
        <x-slot:actions>@can('Post journal entries')<a href="{{ route('accounting.journals.create') }}" class="cb-hero-btn"><i class="ri-add-line"></i>New entry</a>@endcan</x-slot:actions>
    </x-cb.hero>
    @include('accounting._nav', ['section' => 'journals'])
    <x-cb.card title="Entries" icon="ri-list-check-2" :count="$entries->total()" :flush="true">
        <form class="cb-toolbar gap-2 flex-wrap" method="GET">
            <input type="search" name="q" class="cb-search" placeholder="Entry no. or description" value="{{ request('q') }}">
            <select name="type" class="cb-select"><option value="">All types</option>@foreach(\App\Models\JournalEntry::TYPES as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select>
            <select name="status" class="cb-select"><option value="">Any status</option>@foreach(['draft' => 'Draft', 'posted' => 'Posted', 'reversed' => 'Reversed'] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
            <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm" style="width:150px"><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm" style="width:150px">
            <button class="btn btn-sm btn-light">Filter</button>
        </form>
        @if($entries->isEmpty())
            <div class="empty-state"><i class="ri-book-2-line"></i><h6>No entries in this period</h6><p>Automatic entries appear as payroll, expenses and fees are processed.</p></div>
        @else
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Date</th><th>Entry</th><th>Accounts</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($entries as $e)
                <tr class="{{ $e->status === 'reversed' ? 'text-muted' : '' }}">
                    <td class="text-nowrap">{{ $e->entry_date->format('d M Y') }}</td>
                    <td><a href="{{ route('accounting.journals.show', $e) }}" class="fw-bold">{{ $e->entry_no }}</a> <span class="small text-muted">{{ $e->typeLabel() }}</span><div class="small">{{ \Illuminate\Support\Str::limit($e->description, 90) }}</div></td>
                    <td class="small">@foreach($e->lines->take(4) as $l)<div>{{ $l->debit > 0 ? 'Dr' : 'Cr' }} {{ $l->account->account_code ?? '' }} {{ \Illuminate\Support\Str::limit($l->account->account_name ?? '', 28) }}</div>@endforeach @if($e->lines->count() > 4)<div class="text-muted">+{{ $e->lines->count() - 4 }} more</div>@endif</td>
                    <td class="text-end fw-bold">{{ $m($e->total()) }}</td>
                    <td><span class="status-pill {{ ['posted' => 'st-paid', 'draft' => 'st-pending', 'reversed' => 'st-muted'][$e->status] ?? 'st-muted' }}">{{ ucfirst($e->status) }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <div class="p-3">{{ $entries->links() }}</div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
@endsection
