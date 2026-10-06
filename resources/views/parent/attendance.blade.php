{{-- resources/views/parent/attendance.blade.php --}}
@extends('layouts.master')

@section('content')
@php
    $label = ['present' => ['Present', 'st-paid'], 'late' => ['Late', 'st-warning'], 'absent' => ['Absent', 'st-danger'],
              'sick_leave' => ['Sick leave', 'st-info'], 'excused' => ['Excused', 'st-muted']];
    $present = ($counts['present'] ?? 0) + ($counts['late'] ?? 0);
    $pct = $total ? round($present / $total * 100) : null;
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="'Attendance — ' . $child->firstname . ' ' . $child->lastname" icon="ri-calendar-check-line"
               subtitle="Daily attendance marked by the class teacher." :back="route('parent.dashboard')" back-label="My children" />

    @include('parent.partials.nav', ['section' => 'attendance'])

    <form class="cb-card mb-3" method="GET">
        <div class="cb-toolbar gap-2">
            <select name="session_id" class="cb-select" onchange="this.form.submit()" aria-label="Session">
                @foreach($sessions as $ss)<option value="{{ $ss->id }}" @selected($ss->id == $sessionId)>{{ $ss->session }}</option>@endforeach
            </select>
            <select name="term_id" class="cb-select" onchange="this.form.submit()" aria-label="Term">
                @foreach($terms as $t)<option value="{{ $t->id }}" @selected($t->id == $termId)>{{ $t->term }}</option>@endforeach
            </select>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6"><x-cb.stat label="Attendance" :value="$pct === null ? '—' : $pct . '%'" icon="ri-percent-line" :accent="$pct !== null && $pct < 80 ? 'amber' : 'teal'" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Present" :value="$counts['present'] ?? 0" icon="ri-checkbox-circle-line" accent="green" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Late" :value="$counts['late'] ?? 0" icon="ri-timer-line" accent="amber" /></div>
        <div class="col-md-3 col-6"><x-cb.stat label="Absent" :value="$counts['absent'] ?? 0" icon="ri-close-circle-line" accent="rose" /></div>
    </div>

    <x-cb.card title="Daily record" icon="ri-calendar-2-line" :count="$days->count()" :flush="true">
        @if($days->isEmpty())
            <div class="empty-state"><i class="ri-calendar-close-line"></i><h6>No attendance recorded</h6><p>Nothing has been marked for this term yet.</p></div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Date</th><th>Status</th><th>Note</th></tr></thead>
                    <tbody>
                    @foreach($days as $date => $rows)
                        <tr>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}</td>
                            <td>
                                @foreach($rows as $r)
                                    @php [$l, $c] = $label[$r->status] ?? [ucfirst($r->status), 'st-muted']; @endphp
                                    <span class="status-pill {{ $c }}">{{ $r->period ? ucfirst($r->period) . ': ' : '' }}{{ $l }}</span>
                                @endforeach
                            </td>
                            <td class="small text-muted">{{ $rows->pluck('notes')->filter()->implode(' · ') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-cb.card>
</div>
</div>
</div>
@endsection
