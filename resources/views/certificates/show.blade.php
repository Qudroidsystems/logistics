{{-- resources/views/certificates/show.blade.php --}}
@extends('layouts.master')

@section('content')
@php [$sl,$scl]=$certificate->label(); $t=$certificate->template; @endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Certificate {{ $certificate->serial }}" icon="ri-award-fill" subtitle="{{ trim(($certificate->student->firstname ?? '').' '.($certificate->student->lastname ?? '')) }} · {{ $t->name ?? '' }}" :back="route('certificates.index')" back-label="Certificates">
        <x-slot name="actions">
            @can('Generate certificates')<a href="{{ route('certificates.print', $certificate) }}" class="action-btn btn-primary-cb"><i class="ri-printer-line"></i>Print</a>@endcan
            @can('Approve certificates')@if($certificate->status==='draft')<form method="POST" action="{{ route('certificates.approve', $certificate) }}" class="d-inline">@csrf<button class="action-btn btn-go"><i class="ri-check-double-line"></i>Approve</button></form>@endif@endcan
            <a href="{{ route('certificates.verify', ['token'=>$certificate->verify_token]) }}" target="_blank" class="action-btn btn-go"><i class="ri-qr-code-line"></i>Verify page</a>
        </x-slot>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif

    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card title="Details" icon="ri-information-line">
                <div class="row g-3">
                    <div class="col-md-6"><div class="small text-muted">Status</div><span class="status-pill {{ $scl }}">{{ $sl }}</span></div>
                    <div class="col-md-6"><div class="small text-muted">Serial</div><div class="fw-semibold">{{ $certificate->serial }}</div></div>
                    <div class="col-md-6"><div class="small text-muted">Student</div>{{ trim(($certificate->student->firstname ?? '').' '.($certificate->student->lastname ?? '')) }} ({{ $certificate->student->admissionNo ?? '' }})</div>
                    <div class="col-md-6"><div class="small text-muted">Template</div>{{ $t->name ?? '—' }}</div>
                    <div class="col-md-6"><div class="small text-muted">Times generated</div>{{ $certificate->generation_count }}@if($t && $t->generation_limit) of {{ $t->generation_limit }} allowed @else (unlimited) @endif</div>
                    <div class="col-md-6"><div class="small text-muted">Last generated</div>{{ $certificate->last_generated_at ? $certificate->last_generated_at->format('d M Y H:i') : '—' }}</div>
                    <div class="col-md-6"><div class="small text-muted">Issued</div>{{ $certificate->issued_at ? $certificate->issued_at->format('d M Y H:i') : '—' }}</div>
                    <div class="col-md-6"><div class="small text-muted">Approved</div>{{ $certificate->approved_at ? $certificate->approved_at->format('d M Y H:i') : '—' }}</div>
                    @if($certificate->isRevoked())
                    <div class="col-12"><div class="cb-banner warning"><i class="ri-close-circle-line"></i><div><strong>Revoked</strong> {{ $certificate->revoked_at?->format('d M Y') }} — {{ $certificate->revoke_reason ?: 'no reason given' }}</div></div></div>
                    @endif
                </div>
            </x-cb.card>

            <x-cb.card title="Audit trail" icon="ri-history-line" :count="$logs->count()" :flush="true">
                @if($logs->isEmpty())<div class="empty-state"><i class="ri-history-line"></i><h6>No activity</h6></div>
                @else
                <div class="table-responsive"><table class="table align-middle mb-0">
                    <thead><tr><th>When</th><th>Action</th><th>By</th><th>Note</th></tr></thead>
                    <tbody>@foreach($logs as $lg) @php [$al,$acl]=$lg->label(); @endphp
                        <tr><td class="small">{{ optional($lg->created_at)->format('d M Y H:i') }}</td><td><span class="status-pill {{ $acl }}">{{ $al }}</span></td><td class="small">{{ $lg->user_name ?: '—' }}</td><td class="small">{{ $lg->note }}</td></tr>
                    @endforeach</tbody>
                </table></div>
                @endif
            </x-cb.card>
        </div>

        <div class="col-lg-4">
            <x-cb.card title="Verification" icon="ri-qr-code-line">
                <div class="text-center">
                    <canvas id="qrBox" width="180" height="180" class="mb-2"></canvas>
                    <div class="small text-muted mb-2">Scan to verify this certificate.</div>
                    <input class="form-control form-control-sm" readonly value="{{ $verifyUrl }}">
                </div>
            </x-cb.card>

            @if($certificate->rendered_path)
            <x-cb.card title="Saved copy" icon="ri-image-line">
                <a href="{{ route('certificates.download', $certificate) }}" class="action-btn btn-primary-cb w-100 justify-content-center"><i class="ri-download-2-line"></i>Download PNG</a>
            </x-cb.card>
            @endif

            @can('Revoke certificates')
            @if(!$certificate->isRevoked())
            <x-cb.card title="Revoke" icon="ri-close-circle-line">
                <form method="POST" action="{{ route('certificates.revoke', $certificate) }}" onsubmit="return confirm('Revoke this certificate? Its QR will show REVOKED.')">@csrf
                    <input name="reason" class="form-control form-control-sm mb-2" placeholder="Reason (optional)" maxlength="300">
                    <button class="action-btn btn-open w-100 justify-content-center text-danger"><i class="ri-close-circle-line"></i>Revoke certificate</button>
                </form>
            </x-cb.card>
            @endif
            @endcan
        </div>
    </div>
</div></div></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<script>
try { new QRious({ element: document.getElementById('qrBox'), value: @json($verifyUrl), size: 180, level: 'M' }); } catch (e) {}
</script>
@endsection
