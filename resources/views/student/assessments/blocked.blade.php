{{-- resources/views/student/assessments/blocked.blade.php
     Shown instead of the report when ResultAccessService says the student
     cannot see results for the selected term (fees owed or manual block). --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            @php
                $settings   = $access['settings'] ?? null;
                $isOwing    = ($access['reason'] ?? '') === 'owing';
                $showAmount = $isOwing && ($settings->show_amount_owed ?? false);
                $money      = fn ($v) => '₦' . number_format((float) $v, 2);
                $displayTerm    = $term->term ?? '';
                $displaySession = $session->session ?? '';
            @endphp

            <x-cb.hero title="My Assessment Report" icon="ri-file-chart-line"
                       subtitle="Your subject scores, assessment breakdowns, positions and attendance.">
                <x-slot:pills>
                    <span class="cb-meta-pill">
                        <i class="ri-user-line"></i>{{ trim(($student->lastname ?? '') . ', ' . ($student->firstname ?? '') . ' ' . ($student->othername ?? ''), ', ') }}
                    </span>
                    <span class="cb-meta-pill"><i class="ri-hashtag"></i>{{ $student->admissionNo ?? '—' }}</span>
                    @if($displayTerm && $displaySession)<span class="cb-meta-pill"><i class="ri-calendar-line"></i>{{ $displayTerm }} · {{ $displaySession }}</span>@endif
                </x-slot:pills>
            </x-cb.hero>

            {{-- PERIOD PICKER: the student can still switch to a term that is not on hold --}}
            <div class="cb-card">
                <form method="GET" action="{{ route('assessments') }}" class="cb-toolbar" style="border-bottom:none">
                    <select name="session_id" class="cb-select" aria-label="Session" onchange="this.form.submit()">
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" @selected(($selectedSessionId ?? null) == $s->id)>{{ $s->session }}{{ ($s->status ?? '') === 'Current' ? ' (current)' : '' }}</option>
                        @endforeach
                    </select>
                    <div class="term-chips" role="group" aria-label="Term">
                        <a class="term-chip {{ empty($userSelectedTermId) ? 'active' : '' }}"
                           href="{{ route('assessments', ['session_id' => $selectedSessionId]) }}">Latest</a>
                        @foreach($terms->sortBy('id') as $t)
                            <a class="term-chip {{ ($userSelectedTermId ?? null) == $t->id ? 'active' : '' }}"
                               href="{{ route('assessments', ['session_id' => $selectedSessionId, 'term_id' => $t->id]) }}">{{ $t->term }}</a>
                        @endforeach
                    </div>
                </form>
            </div>

            <div class="cb-card">
                <div class="empty-state" style="padding:2.5rem 1.25rem">
                    <i class="{{ $isOwing ? 'ri-lock-2-line' : 'ri-shield-keyhole-line' }}" style="color:var(--cb-amber, #d97706)"></i>
                    <h6>{{ $isOwing ? 'Results on hold' : 'Results unavailable' }}</h6>
                    <p style="max-width:560px;margin:0 auto">{{ $access['message'] ?? 'Your results are not available at the moment.' }}</p>

                    @if($showAmount)
                        <div class="row g-3 justify-content-center mt-3" style="max-width:720px;margin:0 auto">
                            <div class="col-sm-4 col-6"><x-cb.stat label="This term" :value="$money($access['term_owed'] ?? 0)" icon="ri-bill-line" accent="amber" /></div>
                            @if(($access['arrears'] ?? 0) > 0)
                                <div class="col-sm-4 col-6"><x-cb.stat label="Previous arrears" :value="$money($access['arrears'])" icon="ri-history-line" accent="rose" /></div>
                            @endif
                            <div class="col-sm-4 col-12"><x-cb.stat label="Total outstanding" :value="$money($access['owed'] ?? 0)" icon="ri-money-dollar-circle-line" accent="violet" /></div>
                        </div>
                    @endif

                    <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                        @if($isOwing && Route::has('student.payments'))
                            <a href="{{ route('student.payments', ['term_id' => $term->id ?? null, 'session_id' => $session->id ?? null]) }}" class="action-btn btn-primary-cb">
                                <i class="ri-wallet-3-line"></i>View my fee statement
                            </a>
                        @endif
                        <a href="{{ route('dashboard') }}" class="action-btn btn-open"><i class="ri-home-4-line"></i>Back to dashboard</a>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Once the balance is cleared or the school grants access, this page will show your report automatically.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
