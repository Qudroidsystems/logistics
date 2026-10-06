{{-- resources/views/instalments/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">
    <x-cb.hero title="Instalment Plans" icon="ri-calendar-todo-line" subtitle="Let families pay a term's fees in dated parts. Reminders and result access follow the plan.">
        @can('Manage instalment-plans')
            <x-slot:actions><a href="{{ route('instalment-plans.create') }}" class="cb-hero-btn"><i class="ri-add-line"></i>New plan</a></x-slot:actions>
        @endcan
    </x-cb.hero>

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif

    <x-cb.card title="Plans" icon="ri-list-check-2" :count="$plans->count()" :flush="true">
        <form class="cb-toolbar" method="GET">
            <select name="session_id" class="cb-select" onchange="this.form.submit()" aria-label="Session">
                @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($s->id == $sessionId)>{{ $s->session }}{{ $s->status === 'Current' ? ' (current)' : '' }}</option>@endforeach
            </select>
        </form>
        @if($plans->isEmpty())
            <div class="empty-state"><i class="ri-calendar-todo-line"></i><h6>No plans for this session</h6><p>Create a plan, e.g. 50% at resumption, 30% at midterm, 20% before exams.</p></div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Plan</th><th>Term</th><th>Applies to</th><th>Schedule</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($plans as $p)
                        <tr>
                            <td><a href="{{ route('instalment-plans.show', $p) }}" class="fw-semibold">{{ $p->name }}</a>
                                @if($p->description)<div class="small text-muted">{{ $p->description }}</div>@endif</td>
                            <td>{{ $terms[$p->term_id] ?? '—' }}</td>
                            <td class="small">
                                @if($p->applies_to === 'selected') {{ $p->assignments_count }} selected student(s)
                                @elseif($p->applies_to === 'classes') {{ collect($p->class_ids)->map(fn ($id) => $classes[$id] ?? null)->filter()->implode(', ') }}
                                @else Everyone @endif
                            </td>
                            <td class="small">
                                @foreach($p->steps() as $s)
                                    <div>{{ rtrim(rtrim(number_format($s['percent'], 2), '0'), '.') }}% by {{ \Carbon\Carbon::parse($s['due_date'])->format('d M') }}</div>
                                @endforeach
                            </td>
                            <td><span class="status-pill {{ $p->is_active ? 'st-paid' : 'st-muted' }}">{{ $p->is_active ? 'Active' : 'Off' }}</span></td>
                            <td class="text-end"><a href="{{ route('instalment-plans.show', $p) }}" class="action-btn btn-open"><i class="ri-eye-line"></i>Open</a></td>
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
