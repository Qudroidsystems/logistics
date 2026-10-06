{{-- Instalment plan card. Pass studentId, termId, sessionId, and (optional) payable + paid in naira. --}}
@php
    $instSched = \App\Services\Billing\InstalmentPlanService::available()
        ? app(\App\Services\Billing\InstalmentPlanService::class)->schedule((int) $studentId, (int) $termId, (int) $sessionId, isset($payable) ? (float) $payable : null, isset($paid) ? (float) $paid : null)
        : null;
    $instMoney = fn ($v) => '₦' . number_format((float) $v, 2);
    $instPill  = ['paid' => ['Paid', 'st-paid'], 'partial' => ['Part paid', 'st-pending'], 'overdue' => ['Overdue', 'st-danger'], 'upcoming' => ['Upcoming', 'st-muted']];
@endphp
@if($instSched && $instSched['payable'] > 0)
    <div class="cb-card mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <h6 class="mb-0"><i class="ri-calendar-todo-line me-1"></i>Instalment plan: {{ $instSched['plan']->name }}</h6>
                <div class="small text-muted">The term's fees can be paid in {{ count($instSched['steps']) }} parts.</div>
            </div>
            @if($instSched['balance'] <= 0)
                <span class="status-pill st-paid">Fully paid</span>
            @elseif($instSched['on_track'])
                <span class="status-pill st-paid">On track</span>
            @else
                <span class="status-pill st-danger">{{ $instMoney($instSched['overdue']) }} overdue</span>
            @endif
        </div>
        @if(!$instSched['on_track'])
            <div class="cb-banner warning mb-2"><i class="ri-alarm-warning-line"></i><div>Pay <strong>{{ $instMoney($instSched['due_now']) }}</strong> now to get back on track.</div></div>
        @elseif($instSched['next'])
            <div class="cb-banner info mb-2"><i class="ri-information-line"></i>
                <div>Next: <strong>{{ $instMoney($instSched['next']['amount']) }}</strong> for {{ $instSched['next']['label'] }}{{ $instSched['next']['due_date'] ? ' by ' . $instSched['next']['due_date'] : '' }}.</div></div>
        @endif
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Instalment</th><th>Due</th><th class="text-end">Amount</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($instSched['steps'] as $st)
                    @php [$l, $c] = $instPill[$st['status']]; @endphp
                    <tr>
                        <td>{{ $st['label'] }} <small class="text-muted">({{ rtrim(rtrim(number_format($st['percent'], 2), '0'), '.') }}%)</small></td>
                        <td class="text-nowrap">{{ $st['due_date'] ?? '—' }}</td>
                        <td class="text-end">{{ $instMoney($st['amount']) }}</td>
                        <td class="text-end text-success">{{ $instMoney($st['paid']) }}</td>
                        <td class="text-end fw-bold">{{ $instMoney($st['balance']) }}</td>
                        <td><span class="status-pill {{ $c }}">{{ $l }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
