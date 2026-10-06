{{-- resources/views/exam/coverage/index.blade.php — term-end per-topic analysis --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Term-end Topic Analysis" icon="ri-line-chart-fill"
        subtitle="Across all approved papers: what was taught, what was examined, and how students performed per topic." :back="route('dashboard')" back-label="Dashboard" />

    <x-cb.card title="Choose a class" icon="ri-filter-3-line">
        <form method="GET" class="row g-2">
            <div class="col-md-6"><select name="subjectclass_id" class="form-select" onchange="this.form.submit()">
                @forelse($assignments as $a)<option value="{{ $a->subjectclass_id }}" @selected($scId==$a->subjectclass_id)>{{ $a->label }}</option>
                @empty<option value="">No assignments</option>@endforelse
            </select></div>
        </form>
    </x-cb.card>

    @if($data)
        <div class="row g-2 my-1">
            <div class="col-6 col-md-3"><x-cb.stat :value="$data->papers->count()" label="Approved papers" icon="ri-file-list-3-line" /></div>
            <div class="col-6 col-md-3"><x-cb.stat :value="$data->taught.'/'.$data->syllabus" label="Topics taught" icon="ri-book-open-line" accent="amber" /></div>
            <div class="col-6 col-md-3"><x-cb.stat :value="$data->examined.'/'.$data->syllabus" label="Topics examined" icon="ri-focus-3-line" accent="teal" /></div>
            <div class="col-6 col-md-3"><x-cb.stat :value="$data->syllabus ? (int) round($data->examined/$data->syllabus*100).'%' : '0%'" label="Syllabus examined" icon="ri-pie-chart-line" accent="violet" /></div>
        </div>

        <x-cb.card title="Per topic" icon="ri-table-line" :flush="true">
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Topic</th><th class="text-center">Wk</th><th class="text-center">Taught</th><th class="text-center">Examined</th><th class="text-center">Qs</th><th class="text-center">Avg score</th><th>Flag</th></tr></thead>
                <tbody>
                @forelse($data->rows as $r)
                    <tr>
                        <td>{{ $r->title }}</td>
                        <td class="text-center text-muted">{{ $r->week_no ?: '—' }}</td>
                        <td class="text-center">@if($r->taught)<i class="ri-check-line text-success"></i>@else<i class="ri-close-line text-muted"></i>@endif</td>
                        <td class="text-center">@if($r->examined)<i class="ri-check-line text-success"></i>@else<i class="ri-close-line text-muted"></i>@endif</td>
                        <td class="text-center">{{ $r->questions ?: '—' }}</td>
                        <td class="text-center">@if($r->avg_pct!==null)<span class="status-pill {{ $r->avg_pct>=50?'st-paid':'st-danger' }}">{{ $r->avg_pct }}%</span>@else<span class="text-muted">—</span>@endif</td>
                        <td class="small">
                            @if($r->taught && !$r->examined)<span class="status-pill st-pending">Not tested</span>
                            @elseif(!$r->taught && $r->examined)<span class="status-pill st-danger">Tested, not taught</span>
                            @elseif($r->avg_pct!==null && $r->avg_pct<50)<span class="status-pill st-danger">Weak</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No syllabus topics for this class.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-cb.card>
    @else
        <x-cb.card><div class="empty-state"><i class="ri-line-chart-line"></i><p>Pick a class to see its analysis.</p></div></x-cb.card>
    @endif
</div></div></div>
@endsection
