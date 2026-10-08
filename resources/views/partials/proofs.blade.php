@if(!empty($proofs) && count($proofs))
<x-cb.card title="Proof of delivery" icon="ri-camera-line" class="mb-3">
    @foreach($proofs as $p)
        <div class="d-flex justify-content-between align-items-center small mb-2">
            <span>{{ ucfirst($p->type) }}@if($p->recipient_name) · {{ $p->recipient_name }}@endif <span class="text-muted">{{ \Illuminate\Support\Carbon::parse($p->created_at)->format('j M, g:ia') }}</span></span>
            @if($p->file_path)<a href="{{ route('proofs.show', $p->public_id) }}" target="_blank" rel="noopener">View photo</a>@elseif($p->otp_verified)<span class="text-success"><i class="ri-checkbox-circle-line"></i> Code checked</span>@endif
        </div>
    @endforeach
</x-cb.card>
@endif
