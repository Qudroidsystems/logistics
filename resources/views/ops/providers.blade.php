@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Providers" icon="ri-team-line" subtitle="Companies, riders and shoppers, best score first." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')
    <x-cb.card title="Providers" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools>
            <form method="GET" class="d-flex gap-2">
                <input name="q" value="{{ $f['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Name">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Any status</option>
                    @foreach(['active', 'pending', 'suspended'] as $s)<option value="{{ $s }}" @selected(($f['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </x-slot:tools>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Provider</th><th>Status</th><th>Tier</th><th class="text-end">Score</th><th class="text-end">Rating</th><th class="text-end">Jobs</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="fw-semibold">{{ $r['display_name'] }}<div class="small text-muted fw-normal">{{ str_replace('_', ' ', $r['type']) }}{{ $r['listed'] ? '' : ' · not listed' }}</div></td>
                    <td><span class="badge bg-{{ $r['status'] === 'active' ? 'success' : ($r['status'] === 'suspended' ? 'danger' : 'secondary') }}">{{ $r['status'] }}</span></td>
                    <td>{{ $r['tier'] }}</td>
                    <td class="text-end">{{ $r['score'] !== null ? round($r['score']) : '—' }}</td>
                    <td class="text-end">{{ $r['rating_count'] ? number_format($r['rating_avg'], 1).' ('.$r['rating_count'].')' : '—' }}</td>
                    <td class="text-end">{{ $r['jobs_completed'] }}</td>
                    <td class="text-end text-nowrap">
                        <form method="POST" action="{{ route('ops.provider.refresh', $r['operator_id']) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary" title="Recalculate score"><i class="ri-refresh-line"></i></button></form>
                        @if($r['status'] === 'active')
                            @if(auth()->user()->hasAnyPermission(['Suspend vendor', 'Suspend driver', 'Suspend shopper']))
                            <form method="POST" action="{{ route('ops.provider.suspend', $r['operator_id']) }}" class="d-inline" onsubmit="var r=prompt('Reason for suspending {{ addslashes($r['display_name']) }}?'); if(!r){return false;} this.querySelector('[name=reason]').value=r;">@csrf<input type="hidden" name="reason"><button class="btn btn-sm btn-outline-danger">Suspend</button></form>
                            @endif
                        @elseif($r['status'] === 'suspended' && auth()->user()->hasAnyPermission(['Suspend vendor', 'Suspend driver', 'Suspend shopper']))
                            <form method="POST" action="{{ route('ops.provider.reinstate', $r['operator_id']) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Reinstate</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No providers.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
