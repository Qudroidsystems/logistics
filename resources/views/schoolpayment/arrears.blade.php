{{-- resources/views/schoolpayment/arrears.blade.php
     Outstanding balances from other terms/sessions for one student
     (SchoolPaymentController@arrearsDetails, data from ArrearsService). --}}
@extends('layouts.master')

@section('content')
@php
    $money     = fn ($v) => '₦' . number_format((float) $v, 2);
    $groups    = $arrears['groups'] ?? [];
    $total     = (float) ($arrears['total_arrears'] ?? 0);
    $billCount = collect($groups)->sum('bill_count');
    $paidSoFar = collect($groups)->sum('amount_paid');
    $avatar    = ($student->avatar && !in_array($student->avatar, ['unnamed.jpg', ''], true))
        ? asset('storage/images/student_avatars/' . $student->avatar) : null;
    $excluding = request('exclude_term') && request('exclude_session');
@endphp
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <x-cb.hero title="Outstanding Arrears" icon="ri-alarm-warning-line"
                       subtitle="Unpaid balances {{ $excluding ? 'from other terms and sessions' : 'across all terms and sessions' }} for this student."
                       :back="url()->previous()" backLabel="Back">
                <x-slot:pills>
                    <span class="cb-meta-pill">
                        @if($avatar)
                            <img src="{{ $avatar }}" alt="" style="width:18px;height:18px;border-radius:50%;object-fit:cover" onerror="this.remove()">
                        @else
                            <i class="ri-user-line"></i>
                        @endif
                        {{ $fullName }}
                    </span>
                    <span class="cb-meta-pill"><i class="ri-hashtag"></i>{{ $student->admissionNo ?? '—' }}</span>
                    @if($student->student_status)<span class="cb-meta-pill"><i class="ri-shield-user-line"></i>{{ $student->student_status }}</span>@endif
                </x-slot:pills>
            </x-cb.hero>

            <div class="row g-3 mb-4">
                <div class="col-md-4 col-6"><x-cb.stat label="Total outstanding" :value="$money($total)" icon="ri-money-dollar-circle-line" accent="rose" /></div>
                <div class="col-md-4 col-6"><x-cb.stat label="Terms owing" :value="count($groups)" icon="ri-calendar-2-line" accent="amber" :hint="$billCount . ' unpaid bill' . ($billCount === 1 ? '' : 's')" /></div>
                <div class="col-md-4 col-12"><x-cb.stat label="Paid towards these bills" :value="$money($paidSoFar)" icon="ri-checkbox-circle-line" accent="teal" /></div>
            </div>

            @if(empty($groups))
                <div class="cb-card">
                    <div class="empty-state">
                        <i class="ri-checkbox-circle-line" style="color:var(--cb-teal)"></i>
                        <h6>No arrears</h6>
                        <p>{{ $fullName }} has no unpaid balance {{ $excluding ? 'from other terms or sessions' : 'on record' }}.</p>
                    </div>
                </div>
            @else
                @foreach($groups as $g)
                    <div class="cb-card">
                        <div class="cb-card-header">
                            <div>
                                <h6 class="mb-0"><i class="ri-calendar-line me-1"></i>{{ $g['term_name'] }} · {{ $g['session_name'] }}</h6>
                                <small class="text-muted">{{ $g['class_name'] }} · {{ $g['bill_count'] }} bill{{ $g['bill_count'] == 1 ? '' : 's' }}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="status-pill st-rejected">Owing {{ $money($g['outstanding']) }}</span>
                                <a class="action-btn btn-primary-cb"
                                   href="{{ route('schoolpayment.termsessionpayments', ['studentId' => $studentId, 'termid' => $g['term_id'], 'sessionid' => $g['session_id']]) }}">
                                    <i class="ri-cash-line"></i>Record payment
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="cb-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Bill</th>
                                        <th class="text-end">Original</th>
                                        <th class="text-end">Scholarship / Discount</th>
                                        <th class="text-end">Payable</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($g['bills'] as $b)
                                        @php
                                            $deduction = $b['scholarship_deduction'] + $b['discount_deduction'];
                                            $payable   = $b['adjusted_amount'] > 0 ? $b['adjusted_amount'] : $b['original_amount'];
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $b['title'] ?? 'Bill' }}</div>
                                                @if(!empty($b['description']))<small class="text-muted">{{ $b['description'] }}</small>@endif
                                            </td>
                                            <td class="text-end">{{ $money($b['original_amount']) }}</td>
                                            <td class="text-end">{{ $deduction > 0 ? '−' . $money($deduction) : '—' }}</td>
                                            <td class="text-end">{{ $money($payable) }}</td>
                                            <td class="text-end">{{ $money($b['amount_paid']) }}</td>
                                            <td class="text-end fw-bold" style="color:#b91c1c">{{ $money($b['outstanding']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-end">Term total</th>
                                        <th class="text-end">{{ $money($g['amount_paid']) }}</th>
                                        <th class="text-end" style="color:#b91c1c">{{ $money($g['outstanding']) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach

                <div class="cb-banner warning">
                    <i class="ri-information-line"></i>
                    <div>Balances come from the student's payment book. Use <strong>Record payment</strong> on a term to open that term's payment page.</div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
