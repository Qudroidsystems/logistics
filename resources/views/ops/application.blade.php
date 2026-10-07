@extends('layouts.master')

@section('content')
@php $op = $a['operator']; $app = $a['application']; $reviewable = $app && in_array($app['status'], ['submitted', 'needs_changes']); @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="$op['display_name']" icon="ri-shield-user-line" :subtitle="str_replace('_', ' ', $op['type']).' · '.($a['owner']['name'] ?? '').' · '.($a['owner']['email'] ?? '')" :back="route('ops.applications')" back-label="Applications">
        <x-slot:pills>
            <span class="cb-meta-pill">Application: {{ str_replace('_', ' ', $app['status'] ?? 'none') }}</span>
            <span class="cb-meta-pill">Account: {{ $op['status'] }}</span>
        </x-slot:pills>
    </x-cb.hero>
    @include('ops._flash')

    @if(count($a['missing_approvals']))
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>Still to approve before this can go live: <strong>{{ implode(', ', array_map(fn ($t) => str_replace('_', ' ', $t), $a['missing_approvals'])) }}</strong>.</div></div>
    @endif
    @if($app && $app['review_note'])<div class="cb-banner info"><i class="ri-chat-1-line"></i><div>Last note: {{ $app['review_note'] }}</div></div>@endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card title="Documents" icon="ri-file-shield-2-line" :count="count($a['documents'])" :flush="true">
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>Document</th><th>Ending</th><th>Expires</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($a['documents'] as $doc)
                        <tr>
                            <td>{{ str_replace('_', ' ', $doc['doc_type']) }}<div class="small text-muted">{{ $doc['subject_type'] }}</div></td>
                            <td>{{ $doc['number_last4'] ?? '—' }}</td>
                            <td>{{ $doc['expires_on'] ?? '—' }}</td>
                            <td><span class="badge bg-{{ ['approved' => 'success', 'rejected' => 'danger'][$doc['status']] ?? 'secondary' }}">{{ $doc['status'] }}</span>@if($doc['rejection_reason'])<div class="small text-muted">{{ $doc['rejection_reason'] }}</div>@endif</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('ops.document', $doc['public_id']) }}" class="btn btn-sm btn-outline-secondary" target="_blank">View file</a>
                                @can('Approve kyc')
                                    @if($doc['status'] !== 'approved')
                                    <form method="POST" action="{{ route('ops.document.approve', $doc['public_id']) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                                    @endif
                                @endcan
                                @can('Reject kyc')
                                    @if($doc['status'] !== 'rejected')
                                    <form method="POST" action="{{ route('ops.document.reject', $doc['public_id']) }}" class="d-inline" onsubmit="var r=prompt('Why is this document being rejected?'); if(!r){return false;} this.querySelector('[name=reason]').value=r;">@csrf<input type="hidden" name="reason"><button class="btn btn-sm btn-outline-danger">Reject</button></form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No documents uploaded.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </x-cb.card>

            @if(count($a['vehicles']))
            <x-cb.card title="Vehicles" icon="ri-motorbike-line" class="mt-3" :flush="true">
                <table class="table mb-0"><thead><tr><th>Type</th><th>Plate</th><th>Make / model</th><th>Status</th></tr></thead><tbody>
                @foreach($a['vehicles'] as $v)<tr><td>{{ $v['vehicle_type'] }}</td><td>{{ $v['plate'] }}</td><td>{{ trim(($v['make'] ?? '').' '.($v['model'] ?? '').' '.($v['year'] ?? '')) ?: '—' }}</td><td>{{ $v['status'] }}</td></tr>@endforeach
                </tbody></table>
            </x-cb.card>
            @endif
        </div>

        <div class="col-lg-4">
            <x-cb.card title="Service areas" icon="ri-map-pin-line">
                @forelse($a['service_areas'] as $z)<span class="badge bg-light text-dark me-1 mb-1">{{ $z }}</span>@empty <span class="text-muted">None set.</span> @endforelse
            </x-cb.card>
            <x-cb.card title="Rate cards" icon="ri-price-tag-3-line" class="mt-3">
                <div class="text-muted">{{ count($a['rate_cards']) }} rate card(s) set.</div>
            </x-cb.card>

            @if($reviewable)
            <x-cb.card title="Decision" icon="ri-hammer-line" class="mt-3">
                <form method="POST" action="{{ route('ops.application.decide', $id) }}">@csrf
                    <label class="form-label small">Note (required to send back or reject)</label>
                    <textarea name="note" rows="3" class="form-control mb-3" maxlength="500">{{ old('note') }}</textarea>
                    <div class="d-grid gap-2">
                        @can('Approve kyc')<button name="action" value="approve" class="btn btn-success" @disabled(count($a['missing_approvals']))>Approve and go live</button>@endcan
                        @can('Reject kyc')
                        <button name="action" value="request_changes" class="btn btn-outline-warning">Send back for changes</button>
                        <button name="action" value="reject" class="btn btn-outline-danger" onclick="return confirm('Reject this application?')">Reject</button>
                        @endcan
                    </div>
                </form>
            </x-cb.card>
            @endif
        </div>
    </div>
</div></div></div>
@endsection
