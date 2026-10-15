@if(!empty($proofs) && count($proofs))
<x-cb.card title="Proof of delivery" icon="ri-camera-line" class="mb-3">
    @foreach($proofs as $p)
        @php $checks = !empty($p->checks) ? json_decode($p->checks, true) : null; @endphp
        <div class="small mb-2 border-bottom pb-2">
            <div class="d-flex justify-content-between align-items-center">
                <span>{{ ucfirst(str_replace('_', ' ', $p->type)) }}@if($p->recipient_name) · {{ $p->recipient_name }}@endif <span class="text-muted">{{ \Illuminate\Support\Carbon::parse($p->created_at)->format('j M, g:ia') }}</span></span>
                <span>
                    @if($p->file_path)<a href="{{ route('proofs.show', $p->public_id) }}" target="_blank" rel="noopener">Photo</a>@endif
                    @if(!empty($p->signature_path)) · <a href="{{ route('proofs.show', $p->public_id) }}?f=signature" target="_blank" rel="noopener">Signature</a>@endif
                    @if($p->otp_verified) · <span class="text-success"><i class="ri-checkbox-circle-line"></i> Code checked</span>@endif
                </span>
            </div>
            @if($checks)
                <div class="text-muted">
                    @foreach($checks['satisfied'] ?? [] as $m)<span class="badge bg-success-subtle text-success me-1">{{ \App\Modules\Verification\VerificationPolicy::LABELS[$m] ?? $m }}</span>@endforeach
                    @foreach($checks['waived'] ?? [] as $m)<span class="badge bg-warning-subtle text-warning me-1">Waived: {{ \App\Modules\Verification\VerificationPolicy::LABELS[$m] ?? $m }}</span>@endforeach
                    @if(!empty($p->id_type)) <span>ID {{ str_replace('_', ' ', $p->id_type) }} …{{ $p->id_last4 }}</span>@endif
                </div>
            @endif
        </div>
    @endforeach
</x-cb.card>
@endif
