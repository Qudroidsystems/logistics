{{-- resources/views/leave/approvals.blade.php --}}
@extends('layouts.master')

@section('content')
@php $S = \App\Services\Leave\LeaveService::STATUS; $fmt = fn ($d) => rtrim(rtrim(number_format((float) $d, 1), '0'), '.'); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Leave Approvals" icon="ri-inbox-line" subtitle="Requests waiting for your recommendation or approval." :back="route('leave.index')" back-label="My leave">
        <x-slot:actions>@canany(['View leave records', 'Manage leave types'])<a href="{{ route('leave.records') }}" class="cb-hero-btn"><i class="ri-table-line"></i>Leave records</a>@endcanany</x-slot:actions>
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Waiting for you" icon="ri-time-line" :count="$pending->count()" :flush="true">
                @forelse($pending as $p)
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div><strong>{{ $p->name }}</strong> <span class="text-muted small">{{ $p->department }}</span>
                                <div><span class="status-pill st-info" style="background:{{ preg_match('/^#[0-9a-fA-F]{3,8}$/', $p->color) ? $p->color : '#0f766e' }}22">{{ $p->type }}</span>
                                    {{ \Carbon\Carbon::parse($p->start_date)->format('D d M') }} – {{ \Carbon\Carbon::parse($p->end_date)->format('D d M Y') }} · <b>{{ $fmt($p->days) }} day(s)</b>{{ $p->paid ? '' : ' · unpaid' }}</div></div>
                            <span class="status-pill {{ $S[$p->status][1] }}">{{ $S[$p->status][0] }}</span>
                        </div>
                        <div class="small mt-2">{{ $p->reason }}</div>
                        <div class="small text-muted mt-1">
                            @if($p->limited)Balance after this: <b class="{{ $p->remaining < 0 ? 'text-danger' : '' }}">{{ $fmt($p->remaining) }}</b> day(s) · @endif
                            @if($p->relief_name)Cover: {{ $p->relief_name }} · @endif
                            @if($p->contact_phone)Phone: {{ $p->contact_phone }} · @endif
                            @if($p->hod_name)Recommended by {{ $p->hod_name }}{{ $p->hod_note ? ' — “' . $p->hod_note . '”' : '' }} · @endif
                            @if($p->attachment)<a href="{{ route('leave.attachment', $p->id) }}" target="_blank"><i class="ri-attachment-line"></i> document</a>@endif
                        </div>
                        @if(!empty($p->handover_note))<div class="small mt-1"><b>Handover:</b> {{ $p->handover_note }}</div>@endif
                        @if(!empty($p->classes))
                            <details class="small mt-1"><summary class="text-muted">Classes needing cover ({{ collect($p->classes)->flatten()->count() }} lesson slots a week)</summary>
                                @foreach($p->classes as $day => $list)<div><b>{{ $day }}:</b> {{ implode(' · ', $list) }}</div>@endforeach
                            </details>
                        @endif
                        @if($p->clash)<div class="small text-warning mt-1"><i class="ri-error-warning-line"></i> Also on leave then in this department: {{ $p->clash }}</div>@endif
                        @if($p->status === 'pending_hod' && auth()->user()->can('Approve leave') && \Carbon\Carbon::parse($p->created_at)->lte(now()->subDays(3)))<div class="small text-muted mt-1">Waiting for the HOD for over 3 days — you can decide directly.</div>@endif
                        <form method="POST" action="{{ route('leave.act', $p->id) }}" class="d-flex gap-2 mt-2 flex-wrap">@csrf
                            <input name="note" class="form-control form-control-sm" style="max-width:340px" placeholder="Note (needed when declining)">
                            @php $final = $p->status === 'pending_principal' || auth()->user()->can('Approve leave'); @endphp
                            <button name="decision" value="yes" class="action-btn btn-go"><i class="ri-check-line"></i>{{ $final ? 'Approve' : 'Recommend' }}</button>
                            <button name="decision" value="no" class="action-btn btn-open"><i class="ri-close-line"></i>{{ $final ? 'Decline' : 'Don\'t recommend' }}</button>
                        </form>
                    </div>
                @empty
                    <div class="empty-state"><i class="ri-inbox-line"></i><h6>Nothing waiting</h6></div>
                @endforelse
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            <x-cb.card title="On leave today" icon="ri-user-unfollow-line" :count="$onLeave->count()" :flush="true">
                @forelse($onLeave as $o)<div class="list-group-item px-3 py-2 small border-bottom"><strong>{{ $o->name }}</strong> · {{ $o->type }}<div class="text-muted">until {{ \Carbon\Carbon::parse($o->end_date)->format('d M') }}{{ $o->relief_name ? ' · cover: ' . $o->relief_name : '' }}</div></div>
                @empty<div class="p-3 small text-muted">Nobody.</div>@endforelse
            </x-cb.card>
            <x-cb.card title="Recently decided by you" icon="ri-history-line" :flush="true">
                @forelse($recent as $r)<div class="px-3 py-2 small border-bottom">{{ $r->name }} · {{ $r->type }} <span class="status-pill {{ $S[$r->status][1] ?? 'st-muted' }}">{{ $S[$r->status][0] ?? $r->status }}</span></div>
                @empty<div class="p-3 small text-muted">None yet.</div>@endforelse
            </x-cb.card>
        </div>
    </div>
</div>
</div>
</div>
@endsection
