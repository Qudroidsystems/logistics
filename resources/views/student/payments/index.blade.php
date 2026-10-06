{{-- resources/views/student/payments/index.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $naira = fn ($v) => '₦' . number_format((float) $v, 2);
    $statusMeta = [
        'paid'    => ['Paid',        'st-paid',    'ri-checkbox-circle-line', 'var(--cb-green)'],
        'covered' => ['Fully covered','st-covered','ri-shield-star-line',     'var(--cb-sky)'],
        'partial' => ['Part-paid',   'st-partial', 'ri-loader-4-line',        'var(--cb-amber)'],
        'unpaid'  => ['Unpaid',      'st-unpaid',  'ri-time-line',            '#cbd5e1'],
    ];
    $historyMeta = [
        'completed' => ['Bill cleared', 'st-completed'],
        'part'      => ['Part payment', 'st-partial'],
        'reversal'  => ['Reversed',     'st-reversal'],
    ];
    $overallPct = ($totals['adjusted'] ?? 0) > 0 ? min(100, round($totals['paid'] / $totals['adjusted'] * 100)) : 0;
    $fullName   = trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? ''));
    $initials   = strtoupper(substr($student->firstname ?? '', 0, 1) . substr($student->lastname ?? '', 0, 1));
@endphp

<style>
.pay-bills { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; padding: 20px 22px; }
.pay-bill { border: 1px solid var(--cb-border); border-radius: var(--cb-radius); padding: 18px; background: var(--cb-surface); position: relative; overflow: hidden; transition: transform .15s, box-shadow .15s; }
.pay-bill:hover { transform: translateY(-2px); box-shadow: var(--cb-shadow); }
.pay-bill::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--stripe, #cbd5e1); }
.pay-bill-title { font-weight: 700; color: var(--cb-heading); font-size: 14px; }
.pay-bill-desc { font-size: 11.5px; color: var(--cb-muted); margin-top: 2px; }
.pay-amount { font-size: 22px; font-weight: 700; color: var(--cb-heading); margin: 12px 0 2px; font-variant-numeric: tabular-nums; }
.pay-was { font-size: 12px; color: var(--cb-muted); text-decoration: line-through; }
.pay-row { display: flex; justify-content: space-between; gap: 8px; margin: 12px 0 8px; }
.pay-mini-label { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: var(--cb-muted); }
.pay-mini-value { font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; }
.pay-chip { display: inline-flex; align-items: center; gap: 4px; border-radius: 20px; padding: 2px 9px; font-size: 10.5px; font-weight: 600; }
.pay-chip.schol { background: #fef9c3; color: #92400e; border: 1px solid #fde68a; }
.pay-chip.disc  { background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe; }
.pay-due { font-size: 11px; color: var(--cb-muted); margin-top: 8px; display: flex; align-items: center; gap: 4px; }
.pay-due.overdue { color: #dc2626; font-weight: 600; }
.pay-avatar { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,.5); background: rgba(255,255,255,.15); color: #fff; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pay-overall { padding: 16px 22px; border-top: 1px solid var(--cb-border); background: var(--cb-surface-2); }
.pay-overall .progress-track { height: 10px; }
</style>

<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    {{-- Hero --}}
    <x-cb.hero title="My Payments" icon="ri-wallet-3-line"
               subtitle="Your school fee bills, what you've paid, and what's left — the same figures the bursary sees.">
        <x-slot:actions>
            @if(Route::has('student.fees.pay'))
                <a class="cb-hero-btn" href="{{ route('student.fees.pay', ['term_id' => $selectedTermId, 'session_id' => $selectedSessionId]) }}">
                    <i class="ri-secure-payment-line"></i>Pay online
                </a>
            @endif
            @if($bills->isNotEmpty())
                <a class="cb-hero-btn" href="{{ route('student.payments.receipt', ['term_id' => $selectedTermId, 'session_id' => $selectedSessionId]) }}">
                    <i class="ri-download-2-line"></i>Download statement
                </a>
            @endif
        </x-slot:actions>
        <x-slot:pills>
            <span class="cb-meta-pill" style="padding:3px 12px 3px 3px">
                @if($studentPicture)
                    <img class="pay-avatar" style="width:26px;height:26px;border-width:1px" src="{{ asset('storage/student_avatars/' . basename($studentPicture)) }}" alt=""
                         onerror="this.style.display='none'">
                @else
                    <span class="pay-avatar" style="width:26px;height:26px;font-size:10px;border-width:1px">{{ $initials }}</span>
                @endif
                {{ $fullName }}
            </span>
            <span class="cb-meta-pill"><i class="ri-hashtag"></i>{{ $student->admissionNo ?? '—' }}</span>
            @if($class)<span class="cb-meta-pill"><i class="ri-building-line"></i>{{ $class->schoolclass }}</span>@endif
            @if($term && $session)<span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $term->term }} · {{ $session->session }}</span>@endif
        </x-slot:pills>
    </x-cb.hero>

    @if(session('error'))
        <div class="cb-banner danger"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>
    @endif

    {{-- Period picker --}}
    <div class="cb-card">
        <form method="GET" action="{{ route('student.payments') }}" class="cb-toolbar" style="border-bottom:none" id="periodForm">
            <select name="session_id" class="cb-select" aria-label="Session" onchange="this.form.submit()">
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}" @selected($selectedSessionId == $s->id)>{{ $s->session }}{{ $s->status === 'Current' ? ' (current)' : '' }}</option>
                @endforeach
            </select>
            <div class="term-chips" role="group" aria-label="Term">
                @foreach($terms as $t)
                    <a class="term-chip {{ $selectedTermId == $t->id ? 'active' : '' }}"
                       href="{{ route('student.payments', ['session_id' => $selectedSessionId, 'term_id' => $t->id]) }}">{{ $t->term }}</a>
                @endforeach
            </div>
            <input type="hidden" name="term_id" value="{{ $selectedTermId }}">
        </form>
    </div>

    {{-- Arrears from other terms --}}
    @if(!empty($arrears['has_arrears']))
        <div class="cb-banner warning">
            <i class="ri-alarm-warning-line"></i>
            <div class="flex-grow-1">
                <strong>Outstanding from other terms: {{ $naira($arrears['total_arrears']) }}</strong>
                <div class="mt-1 d-flex flex-wrap gap-2">
                    @foreach($arrears['groups'] as $g)
                        <a class="status-pill st-warning text-decoration-none"
                           href="{{ route('student.payments', ['session_id' => $g['session_id'], 'term_id' => $g['term_id']]) }}">
                            {{ $g['term_name'] }} · {{ $g['session_name'] }}: {{ $naira($g['outstanding']) }} <i class="ri-arrow-right-s-line"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @include('instalments.partials.schedule', ['studentId' => $student->id, 'termId' => $selectedTermId, 'sessionId' => $selectedSessionId, 'payable' => $totals['adjusted'] ?? null, 'paid' => $totals['paid'] ?? null])

    @if($bills->isEmpty())
        <div class="cb-card">
            <div class="empty-state">
                <i class="ri-bill-line"></i>
                <h6>No bills for this term</h6>
                <p>{{ $statementError ?? 'No fee bills have been set for the selected term and session.' }}</p>
            </div>
        </div>
    @else

        {{-- Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-lg col-md-4 col-6"><x-cb.stat label="Total payable" :value="$naira($totals['adjusted'])" icon="ri-file-list-3-line" accent="teal" /></div>
            <div class="col-lg col-md-4 col-6"><x-cb.stat label="Paid" :value="$naira($totals['paid'])" icon="ri-checkbox-circle-line" accent="green" /></div>
            <div class="col-lg col-md-4 col-6"><x-cb.stat label="Outstanding" :value="$naira($totals['outstanding'])" icon="ri-time-line" :accent="$totals['outstanding'] > 0 ? 'rose' : 'green'" /></div>
            <div class="col-lg col-md-6 col-6"><x-cb.stat label="Scholarship & discount savings" :value="$naira($totals['savings'])" icon="ri-gift-line" accent="violet" /></div>
            <div class="col-lg col-md-6 col-12"><x-cb.stat label="Bills this term" :value="$bills->count()" icon="ri-stack-line" accent="sky"
                :hint="$bills->where('is_paid', true)->count() . ' cleared'" /></div>
        </div>

        {{-- Benefits --}}
        @if($scholarshipAssignment || $discountAssignments->isNotEmpty())
            <div class="cb-banner violet">
                <i class="ri-gift-line"></i>
                <div>
                    @if($scholarshipAssignment)
                        <strong>Scholarship: {{ $scholarshipAssignment->scholarship->title ?? 'Scholarship' }}</strong>
                    @endif
                    @if($discountAssignments->isNotEmpty())
                        {!! $scholarshipAssignment ? '<br>' : '' !!}<strong>Discount{{ $discountAssignments->count() > 1 ? 's' : '' }}:</strong>
                        {{ $discountAssignments->map(fn ($d) => $d->discount->title ?? 'Special discount')->join(', ') }}
                    @endif
                    <div class="small mt-1">These are already taken off the amounts below — you save {{ $naira($totals['savings']) }} this term.</div>
                </div>
            </div>
        @endif

        {{-- Bills --}}
        <x-cb.card title="Bills" icon="ri-bill-line" :count="$bills->count()" :flush="true">
            <x-slot:tools>
                <small class="text-muted">{{ $overallPct }}% of this term's fees paid</small>
            </x-slot:tools>

            <div class="pay-bills">
                @foreach($bills as $bill)
                    @php [$stLabel, $stClass, $stIcon, $stripe] = $statusMeta[$bill['status']]; @endphp
                    <div class="pay-bill" style="--stripe: {{ $stripe }}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="pay-bill-title">{{ $bill['title'] }}</div>
                                @if($bill['description'])<div class="pay-bill-desc">{{ $bill['description'] }}</div>@endif
                            </div>
                            <span class="status-pill {{ $stClass }}"><i class="{{ $stIcon }}"></i>{{ $stLabel }}</span>
                        </div>

                        @if($bill['total_savings'] > 0)
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @if($bill['scholarship_deduction'] > 0)
                                    <span class="pay-chip schol" title="{{ $bill['scholarship_label'] }}"><i class="ri-award-line"></i>−{{ $naira($bill['scholarship_deduction']) }}</span>
                                @endif
                                @if($bill['discount_deduction'] > 0)
                                    <span class="pay-chip disc" title="{{ implode(', ', (array) $bill['discount_labels']) }}"><i class="ri-price-tag-3-line"></i>−{{ $naira($bill['discount_deduction']) }}</span>
                                @endif
                            </div>
                        @endif

                        <div class="pay-amount">{{ $naira($bill['adjusted_amount']) }}</div>
                        @if($bill['total_savings'] > 0)<div class="pay-was">{{ $naira($bill['original_amount']) }}</div>@endif

                        <div class="pay-row">
                            <div><div class="pay-mini-label">Paid</div><div class="pay-mini-value text-success">{{ $naira($bill['amount_paid']) }}</div></div>
                            <div class="text-end"><div class="pay-mini-label">Balance</div>
                                <div class="pay-mini-value {{ $bill['balance'] > 0 ? 'text-danger' : 'text-success' }}">{{ $naira($bill['balance']) }}</div></div>
                        </div>
                        <div class="progress-track" role="progressbar" aria-valuenow="{{ $bill['progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Paid">
                            <div class="progress-fill" style="width:{{ $bill['progress'] }}%;background:{{ $stripe }}"></div>
                        </div>
                        <div class="progress-meta"><span>{{ $bill['progress'] }}% paid</span></div>

                        @if($bill['due_date'])
                            <div class="pay-due {{ $bill['is_overdue'] ? 'overdue' : '' }}">
                                <i class="ri-calendar-event-line"></i>{{ $bill['is_overdue'] ? 'Overdue since' : 'Due' }} {{ $bill['due_date'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="pay-overall">
                <div class="d-flex justify-content-between small mb-1">
                    <strong style="color:var(--cb-heading)">This term</strong>
                    <span>{{ $naira($totals['paid']) }} of {{ $naira($totals['adjusted']) }} · {{ $overallPct }}%</span>
                </div>
                <div class="progress-track" role="progressbar" aria-valuenow="{{ $overallPct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Term fees paid">
                    <div class="progress-fill" style="width:{{ $overallPct }}%;background:{{ $overallPct >= 100 ? 'var(--cb-green)' : 'var(--cb-teal)' }}"></div>
                </div>
            </div>
        </x-cb.card>
    @endif

    <div class="row g-4">
        {{-- History --}}
        <div class="{{ count($paymentTrend) > 0 ? 'col-xl-8' : 'col-12' }}">
            <x-cb.card title="Payment History" icon="ri-history-line" :count="$paymentHistory->count()" :flush="true">
                @if($paymentHistory->isNotEmpty())
                    <div class="table-responsive">
                        <table class="cb-table stack">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Bill</th>
                                    <th class="num">Amount</th>
                                    <th class="num">Balance after</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paymentHistory as $p)
                                    @php [$hLabel, $hClass] = $historyMeta[$p->status]; @endphp
                                    <tr>
                                        <td data-label="Date">{{ $p->paid_at?->format('d M Y') ?? '—' }}<div class="small text-muted">{{ $p->paid_at?->format('h:i A') }}</div></td>
                                        <td data-label="Bill"><span class="subject-name">{{ $p->bill_title ?? '—' }}</span></td>
                                        <td data-label="Amount" class="num fw-bold {{ $p->amount_paid < 0 ? 'text-danger' : 'text-success' }}">{{ $naira($p->amount_paid) }}</td>
                                        <td data-label="Balance after" class="num">{{ $naira($p->balance_after) }}</td>
                                        <td data-label="Method"><span class="term-badge">{{ ucfirst($p->method) }}</span></td>
                                        <td data-label="Reference" class="small text-muted">{{ $p->reference ?: ($p->invoice_no ?: '—') }}</td>
                                        <td data-label="Status"><span class="status-pill {{ $hClass }}">{{ $hLabel }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state" style="padding:36px 20px">
                        <i class="ri-receipt-line" style="font-size:44px"></i>
                        <h6>No payments recorded this term</h6>
                        <p>Payments appear here as soon as the bursary records them.</p>
                    </div>
                @endif
            </x-cb.card>
        </div>

        {{-- Trend --}}
        @if(count($paymentTrend) > 0)
            <div class="col-xl-4">
                <x-cb.card title="Paid per term" icon="ri-bar-chart-2-line">
                    <div style="height:240px"><canvas id="paymentTrendChart" aria-label="Amount paid per term this session" role="img"></canvas></div>
                    <div class="small text-muted mt-2">{{ $session->session ?? '' }} session</div>
                </x-cb.card>
            </div>
        @endif
    </div>

</div>
</div>
</div>

@if(count($paymentTrend) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const el = document.getElementById('paymentTrendChart');
    if (!el || typeof Chart === 'undefined') return;
    const css = getComputedStyle(document.documentElement);
    new Chart(el, {
        type: 'bar',
        data: {
            labels: @json(array_keys($paymentTrend)),
            datasets: [{
                label: 'Amount paid',
                data: @json(array_values($paymentTrend)),
                backgroundColor: '#0d9488',
                borderRadius: 6,
                maxBarThickness: 48,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: c => '₦' + Number(c.raw).toLocaleString('en-NG', { minimumFractionDigits: 2 }) } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: css.getPropertyValue('--cb-muted') } },
                y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.2)' },
                     ticks: { color: css.getPropertyValue('--cb-muted'), callback: v => '₦' + Number(v).toLocaleString('en-NG') } }
            }
        }
    });
})();
</script>
@endif
@endsection
