{{-- resources/views/curriculum/reps/confirm.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Confirm Topics" icon="ri-shield-check-fill" subtitle="Confirm or dispute topics your teachers marked as taught." />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <x-cb.card title="Awaiting your confirmation" icon="ri-time-line" :count="$items->count()">
        @forelse($items as $it)
            <div class="border rounded p-2 mb-2">
                <div class="fw-semibold">{{ $it->title }}</div>
                <div class="small text-muted mb-2">{{ $it->subject_name }} · {{ $it->arm_name }} · taught {{ \Illuminate\Support\Carbon::parse($it->taught_on)->format('d M Y') }}</div>
                <form method="POST" action="{{ route('curriculum.reps.act', $it->id) }}" class="row g-2 align-items-end">@csrf
                    <div class="col-md-7"><input name="note" class="form-control form-control-sm" placeholder="Optional note"></div>
                    <div class="col-md-5 d-flex gap-1">
                        <button name="action" value="confirm" class="action-btn btn-primary-cb"><i class="ri-check-line"></i>Confirm</button>
                        <button name="action" value="dispute" class="action-btn btn-open" onclick="return confirm('Dispute that this topic was taught?')"><i class="ri-close-line"></i>Dispute</button>
                    </div>
                </form>
            </div>
        @empty
            <div class="empty-state"><i class="ri-checkbox-circle-line"></i><h6>Nothing to confirm</h6><p>You're all caught up.</p></div>
        @endforelse
        <div class="cb-banner info mt-1"><i class="ri-information-line"></i><div class="small">You can confirm a topic within 7 days of it being taught. Confirm one at a time, honestly — your HOD can see the confirmation rate.</div></div>
    </x-cb.card>
</div></div></div>
@endsection
