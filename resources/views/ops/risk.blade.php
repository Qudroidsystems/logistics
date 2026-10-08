@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Risk events" icon="ri-alarm-warning-line" subtitle="Things the system flagged for a person to look at." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')
    <x-cb.card title="Events" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools>
            <form method="GET"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(['open' => 'Open', 'reviewed' => 'Reviewed', 'actioned' => 'Actioned', 'dismissed' => 'Dismissed', 'all' => 'All'] as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
            </select></form>
        </x-slot:tools>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Severity</th><th>What</th><th>About</th><th>Details</th><th>When</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td><span class="badge bg-{{ ['high' => 'danger', 'medium' => 'warning'][$r['severity']] ?? 'secondary' }}">{{ $r['severity'] }}</span></td>
                    <td>{{ str_replace('_', ' ', $r['type']) }}</td>
                    <td class="small">{{ $r['subject_type'] }} #{{ $r['subject_id'] }}</td>
                    <td class="small text-muted" style="max-width:320px">{{ is_array($r['evidence']) ? json_encode($r['evidence']) : $r['evidence'] }}</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($r['created_at'])->diffForHumans() }}</td>
                    <td class="text-end text-nowrap">
                        @if($r['status'] === 'open')
                        <form method="POST" action="{{ route('ops.risk.review', $r['id']) }}" class="d-inline">@csrf
                            <button name="status" value="actioned" class="btn btn-sm btn-outline-danger">Acted on it</button>
                            <button name="status" value="dismissed" class="btn btn-sm btn-outline-secondary">Dismiss</button>
                        </form>
                        @else <span class="badge bg-light text-dark">{{ $r['status'] }}</span> @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Nothing to review.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
