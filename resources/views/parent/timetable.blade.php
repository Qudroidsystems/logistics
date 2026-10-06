{{-- resources/views/parent/timetable.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero :title="'Timetable — ' . $child->firstname . ' ' . $child->lastname" icon="ri-time-line"
               :subtitle="$child->class_name ? 'Class timetable for ' . $child->class_name . '.' : 'Class timetable.'" :back="route('parent.dashboard')" back-label="My children" />

    @include('parent.partials.nav', ['section' => 'timetable'])

    @if(!$setting || $periods->isEmpty())
        <div class="cb-card"><div class="empty-state"><i class="ri-time-line"></i><h6>No timetable published</h6><p>The school has not published a timetable for this class yet.</p></div></div>
    @else
        <x-cb.card title="Weekly timetable" icon="ri-calendar-schedule-line" :flush="true">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 pp-tt">
                    <thead><tr><th>Day</th>
                        @foreach($periods as $p)
                            <th class="text-center"><div>{{ $p->name }}</div><small class="text-muted fw-normal">{{ substr((string) $p->start_time, 0, 5) }}–{{ substr((string) $p->end_time, 0, 5) }}</small></th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                    @foreach($days as $day)
                        <tr>
                            <th>{{ $day }}</th>
                            @foreach($periods as $p)
                                @php $slot = $grid[$day][$p->id] ?? null; @endphp
                                @if($p->is_break)
                                    <td class="text-center text-muted small pp-break">{{ $p->name }}</td>
                                @elseif($slot && !$slot->is_free && $slot->subject)
                                    <td class="text-center"><div class="fw-semibold">{{ $slot->subject }}</div><small class="text-muted">{{ $slot->teacher }}</small></td>
                                @else
                                    <td class="text-center text-muted small">{{ $slot && $slot->is_free ? 'Free' : '—' }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-cb.card>
    @endif
</div>
</div>
</div>
<style>.pp-tt th,.pp-tt td{min-width:110px}.pp-break{background:rgba(148,163,184,.12)}</style>
@endsection
