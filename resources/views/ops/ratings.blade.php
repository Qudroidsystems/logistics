@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Ratings" icon="ri-star-line" subtitle="Hide unfair or abusive ratings. A hidden rating no longer counts toward the provider's score." :back="route('ops.dashboard')" back-label="Operations" />
    @include('ops._flash')
    <x-cb.card title="Ratings" icon="ri-list-check" :count="count($rows)" :flush="true">
        <x-slot:tools>
            <form method="GET" class="d-flex gap-2">
                <select name="max_score" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Any score</option>
                    @foreach([2 => '2 or lower', 3 => '3 or lower'] as $k => $v)<option value="{{ $k }}" @selected(($f['max_score'] ?? '') == $k)>{{ $v }}</option>@endforeach
                </select>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Any status</option>
                    @foreach(['approved', 'hidden'] as $s)<option value="{{ $s }}" @selected(($f['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
            </form>
        </x-slot:tools>
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Score</th><th>Comment</th><th>From</th><th>Delivery</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="fw-semibold">{{ $r['score'] }}/5</td>
                    <td style="max-width:360px">{{ $r['comment'] ?: '—' }}@if($r['tags'])<div class="small text-muted">{{ is_array($r['tags']) ? implode(', ', $r['tags']) : $r['tags'] }}</div>@endif</td>
                    <td>{{ $r['rater_type'] }}</td>
                    <td class="small text-muted">{{ $r['tracking_code'] }}</td>
                    <td><span class="badge bg-{{ $r['moderation_status'] === 'hidden' ? 'danger' : 'light text-dark' }}">{{ $r['moderation_status'] }}</span></td>
                    <td class="text-end">
                        @can('Moderate rating')
                        <form method="POST" action="{{ route('ops.rating.moderate', $r['id']) }}" class="d-inline">@csrf
                            @if($r['moderation_status'] === 'hidden')<button name="status" value="approved" class="btn btn-sm btn-outline-success">Restore</button>
                            @else<button name="status" value="hidden" class="btn btn-sm btn-outline-danger">Hide</button>@endif
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No ratings.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>
@endsection
