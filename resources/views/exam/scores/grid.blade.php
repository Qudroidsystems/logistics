{{-- resources/views/exam/scores/grid.blade.php — per-question marking grid --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero :title="'Enter Scores — '.$paper->title" icon="ri-table-fill"
        :subtitle="$label.' · '.$students->count().' students · '.$paper->questions->count().' questions'"
        :back="route('exam.papers.show', $paper)" back-label="Paper" />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    @if($students->isEmpty())
        <x-cb.card><div class="empty-state"><i class="ri-user-line"></i><h6>No students</h6><p>No one is registered for this subject-class yet.</p></div></x-cb.card>
    @elseif($paper->questions->isEmpty())
        <x-cb.card><div class="empty-state"><i class="ri-list-ordered"></i><h6>No questions</h6></div></x-cb.card>
    @else
    <form method="POST" action="{{ route('exam.scores.save', $paper) }}">@csrf
        <x-cb.card title="Marking grid" icon="ri-table-line" :flush="true">
            <div class="table-responsive" style="max-height:70vh">
                <table class="table table-sm table-bordered align-middle mb-0" id="gridTable" style="font-size:.85rem">
                    <thead class="table-light" style="position:sticky;top:0;z-index:2">
                        <tr>
                            <th style="position:sticky;left:0;background:#f8f9fa;z-index:3;min-width:180px">Student</th>
                            @foreach($paper->questions as $i => $q)
                                <th class="text-center" title="{{ $q->question }}" style="min-width:64px">
                                    {{ $q->number ?: ($i+1) }}<div class="small text-muted">/{{ rtrim(rtrim(number_format($q->marks,2),'0'),'.') }}</div>
                                </th>
                            @endforeach
                            <th class="text-center" style="min-width:70px">Total<div class="small text-muted">/{{ rtrim(rtrim(number_format($paper->total_marks,2),'0'),'.') }}</div></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $stu)
                            <tr data-sid="{{ $stu->id }}">
                                <td style="position:sticky;left:0;background:#fff;z-index:1">
                                    <span class="fw-semibold">{{ $stu->name }}</span>
                                    <div class="small text-muted">{{ $stu->admissionNo }}</div>
                                </td>
                                @foreach($paper->questions as $q)
                                    @php $val = $scores[$stu->id][$q->id] ?? null; @endphp
                                    <td class="p-1">
                                        <input type="number" step="0.5" min="0" max="{{ $q->marks }}"
                                            name="score[{{ $stu->id }}][{{ $q->id }}]"
                                            value="{{ $val !== null ? rtrim(rtrim(number_format($val,2),'0'),'.') : '' }}"
                                            class="form-control form-control-sm text-center gcell"
                                            style="min-width:56px" oninput="rowTotal(this)">
                                    </td>
                                @endforeach
                                <td class="text-center fw-bold rtot">0</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-cb.card>

        <div class="d-flex justify-content-end mt-3">
            <button class="action-btn btn-primary-cb"><i class="ri-save-line"></i>Save scores</button>
        </div>
    </form>

    <script>
    function rowTotal(el){
        var tr = el.closest('tr'); var sum = 0;
        tr.querySelectorAll('.gcell').forEach(function(c){ sum += parseFloat(c.value)||0; });
        tr.querySelector('.rtot').textContent = Math.round(sum*100)/100;
    }
    document.querySelectorAll('#gridTable tbody tr').forEach(function(tr){
        var c = tr.querySelector('.gcell'); if(c) rowTotal(c);
    });
    </script>
    @endif
</div></div></div>
@endsection
