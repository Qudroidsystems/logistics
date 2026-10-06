{{-- resources/views/certificates/logs.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Certificate Audit Log" icon="ri-history-line" subtitle="Every create, generate, reprint, approval, revocation, download and scan — with who and when." :back="route('certificates.index')" back-label="Certificates" />

    <x-cb.card title="Filter" icon="ri-filter-3-line">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Serial or user"></div>
            <div class="col-md-3"><label class="form-label small">Action</label>
                <select name="action" class="form-select"><option value="">All</option>@foreach($actions as $k=>$v)<option value="{{ $k }}" @selected(request('action')===$k)>{{ $v[0] }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-search-line"></i></button></div>
        </form>
    </x-cb.card>

    <x-cb.card title="Log" icon="ri-file-list-3-line" :count="$rows->total()" :flush="true">
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-history-line"></i><h6>Nothing logged</h6></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>When</th><th>Action</th><th>Serial</th><th>By</th><th>IP</th><th>Note</th></tr></thead>
                <tbody>
                @foreach($rows as $lg) @php [$al,$acl]=$lg->label(); @endphp
                    <tr>
                        <td class="small">{{ optional($lg->created_at)->format('d M Y H:i') }}</td>
                        <td><span class="status-pill {{ $acl }}">{{ $al }}</span></td>
                        <td class="small">{{ $lg->serial }}</td>
                        <td class="small">{{ $lg->user_name ?: '—' }}</td>
                        <td class="small text-muted">{{ $lg->ip }}</td>
                        <td class="small">{{ $lg->note }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    @if($rows->hasPages())<div class="mt-3">{{ $rows->links() }}</div>@endif
</div></div></div>
@endsection
