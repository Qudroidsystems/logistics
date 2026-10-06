{{-- resources/views/certificates/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Certificates" icon="ri-award-fill" subtitle="Generate, approve, print and verify student certificates.">
        <x-slot name="actions">
            @can('Generate certificates')<a href="{{ route('certificates.generate') }}" class="action-btn btn-primary-cb"><i class="ri-add-line"></i>Generate</a>@endcan
            @can('Manage certificate templates')<a href="{{ route('certificates.templates.index') }}" class="action-btn btn-go"><i class="ri-layout-4-line"></i>Templates</a>@endcan
            @can('View certificate audit')<a href="{{ route('certificates.logs') }}" class="action-btn btn-go"><i class="ri-history-line"></i>Audit log</a>@endcan
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3 mb-1">
        <div class="col-6 col-lg-3"><x-cb.stat label="Issued" :value="$stats['issued']" icon="ri-award-fill" accent="teal" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Draft / approved" :value="$stats['draft']" icon="ri-draft-line" accent="info" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Revoked" :value="$stats['revoked']" icon="ri-close-circle-line" accent="rose" /></div>
        <div class="col-6 col-lg-3"><x-cb.stat label="Total prints" :value="$stats['prints']" icon="ri-printer-line" accent="violet" /></div>
    </div>

    <x-cb.card title="Filter" icon="ri-filter-3-line">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small">Search</label><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Student, admission no or serial"></div>
            <div class="col-md-3"><label class="form-label small">Template</label>
                <select name="template" class="form-select"><option value="">All</option>@foreach($templates as $t)<option value="{{ $t->id }}" @selected(request('template')==$t->id)>{{ $t->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small">Status</label>
                <select name="status" class="form-select"><option value="">Any</option>@foreach($statuses as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v[0] }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-search-line"></i></button></div>
        </form>
    </x-cb.card>

    <x-cb.card title="Certificates" icon="ri-award-line" :count="$rows->total()" :flush="true">
        @if($rows->isEmpty())
            <div class="empty-state"><i class="ri-award-line"></i><h6>No certificates</h6><p>Generate a certificate to get started.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Student</th><th>Serial</th><th>Template</th><th>Status</th><th class="text-end">Copies</th><th>Last generated</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @foreach($rows as $c) @php [$l,$cl]=$c->label(); @endphp
                    <tr>
                        <td>{{ trim(($c->student->firstname ?? '') . ' ' . ($c->student->lastname ?? '')) }}<div class="small text-muted">{{ $c->student->admissionNo ?? '' }}</div></td>
                        <td class="small">{{ $c->serial }}</td>
                        <td class="small">{{ $c->template->name ?? '—' }}</td>
                        <td><span class="status-pill {{ $cl }}">{{ $l }}</span></td>
                        <td class="text-end">{{ $c->generation_count }}@if($c->template && $c->template->generation_limit)<span class="text-muted small">/{{ $c->template->generation_limit }}</span>@endif</td>
                        <td class="small">{{ $c->last_generated_at ? $c->last_generated_at->format('d M Y H:i') : '—' }}</td>
                        <td class="text-end">
                            @can('Generate certificates')<a href="{{ route('certificates.print', $c) }}" class="action-btn btn-open" title="Print"><i class="ri-printer-line"></i></a>@endcan
                            <a href="{{ route('certificates.show', $c) }}" class="action-btn btn-open" title="Details"><i class="ri-eye-line"></i></a>
                            @can('Approve certificates')@if($c->status==='draft')<form method="POST" action="{{ route('certificates.approve', $c) }}" class="d-inline">@csrf<button class="action-btn btn-open" title="Approve"><i class="ri-check-double-line"></i></button></form>@endif@endcan
                            <a href="{{ route('certificates.verify', ['token'=>$c->verify_token]) }}" target="_blank" class="action-btn btn-open" title="Verify page"><i class="ri-qr-code-line"></i></a>
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
