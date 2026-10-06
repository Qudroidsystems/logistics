@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">

    @include('partials.flash')

    <h4 class="mb-3">Control Center</h4>

    <div class="row g-3 mb-3">
        @foreach([['Total users', $stats['users'], 'ri-group-line'], ['Active accounts', $stats['active'], 'ri-user-follow-line'], ['Online now', $stats['online'], 'ri-wifi-line'], ['Roles', $stats['roles'], 'ri-shield-user-line']] as [$label, $value, $icon])
        <div class="col-md-3 col-6"><div class="card"><div class="card-body d-flex justify-content-between align-items-center">
            <div><div class="text-muted small">{{ $label }}</div><h3 class="mb-0">{{ number_format($value) }}</h3></div>
            <i class="{{ $icon }} fs-1 text-primary opacity-50"></i>
        </div></div></div>
        @endforeach
    </div>

    <div class="card"><div class="card-header"><h6 class="mb-0">Recent activity</h6></div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <tbody>
            @forelse($recent as $r)
                <tr><td class="text-muted" style="width:170px">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d M, H:i') }}</td><td style="width:200px">{{ $r->name ?? 'System' }}</td><td>{{ $r->description }}</td></tr>
            @empty
                <tr><td class="text-center text-muted py-3">No activity yet.</td></tr>
            @endforelse
            </tbody></table></div></div>

</div></div></div>
@endsection
