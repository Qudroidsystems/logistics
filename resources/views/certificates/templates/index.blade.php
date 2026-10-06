{{-- resources/views/certificates/templates/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Certificate Templates" icon="ri-award-line" subtitle="Design certificate layouts on a canvas. Generate and verify certificates from the Certificates page.">
        <x-slot name="actions">
            <a href="{{ route('certificates.templates.create', request('kind') ? ['kind'=>request('kind')] : []) }}" class="action-btn btn-primary-cb"><i class="ri-add-line"></i>New template</a>
            @can('Generate certificates')<a href="{{ route('certificates.index') }}" class="action-btn btn-go"><i class="ri-award-fill"></i>Certificates</a>@endcan
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <x-cb.card title="Templates" icon="ri-layout-4-line" :count="$templates->total()" :flush="true">
        @if($templates->isEmpty())
            <div class="empty-state"><i class="ri-award-line"></i><h6>No templates yet</h6><p>Create your first certificate template to start.</p></div>
        @else
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>Type</th><th>Orientation</th><th>Approval</th><th>Limit / student</th><th>Certificates</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @foreach($templates as $t)
                    <tr>
                        <td class="fw-semibold">{{ $t->name }}<div class="small text-muted">{{ \Illuminate\Support\Str::limit($t->description, 60) }}</div></td>
                        <td>@if(($t->kind ?? 'certificate')==='testimonial')<span class="status-pill st-info">Testimonial</span>@else<span class="status-pill st-muted">Certificate</span>@endif</td>
                        <td class="small text-capitalize">{{ $t->orientation }} <span class="text-muted">{{ $t->width }}×{{ $t->height }}</span></td>
                        <td>@if($t->requires_approval)<span class="status-pill st-info">Required</span>@else<span class="text-muted small">Not required</span>@endif</td>
                        <td class="small">{{ $t->generation_limit ?? 'Unlimited' }}</td>
                        <td class="small">{{ $t->certificates_count }}</td>
                        <td>@if($t->is_active)<span class="status-pill st-paid">Active</span>@else<span class="status-pill st-muted">Inactive</span>@endif</td>
                        <td class="text-end">
                            <a href="{{ route('certificates.templates.edit', $t) }}" class="action-btn btn-open" title="Design"><i class="ri-pencil-line"></i></a>
                            <form method="POST" action="{{ route('certificates.templates.duplicate', $t) }}" class="d-inline">@csrf<button class="action-btn btn-open" title="Duplicate"><i class="ri-file-copy-line"></i></button></form>
                            <form method="POST" action="{{ route('certificates.templates.destroy', $t) }}" class="d-inline" onsubmit="return confirm('Delete this template?')">@csrf @method('DELETE')<button class="action-btn btn-open" title="Delete"><i class="ri-delete-bin-line"></i></button></form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </x-cb.card>
    @if($templates->hasPages())<div class="mt-3">{{ $templates->links() }}</div>@endif
</div></div></div>
@endsection
