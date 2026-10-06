{{-- resources/views/broadsheet/ranking/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Broadsheet Ranking" icon="ri-trophy-fill"
        subtitle="Configure the UNOFFICIAL best-student ranking per section. This drives only the best-student panel and the optional Rank column — never the official position columns." />

    @if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>@endif

    <div class="cb-banner info"><i class="ri-information-line"></i><div>Official positions (Arm/Class × Total/Cum) on report cards and promotions are unchanged by anything here.</div></div>

    <div class="row g-3">
        @foreach($sections as $section => $s)
        <div class="col-lg-6">
            <x-cb.card :title="ucfirst($section).' section'" icon="ri-graduation-cap-line">
                <form method="POST" action="{{ route('broadsheet.ranking.save', $section) }}">@csrf
                    <label class="form-label small fw-semibold">Rank by (primary measure)</label>
                    <select name="primary_measure" class="form-select form-select-sm mb-3">
                        @foreach($measures as $k => $lbl)<option value="{{ $k }}" @selected($s->primary_measure===$k)>{{ $lbl }}</option>@endforeach
                    </select>

                    <label class="form-label small fw-semibold">Tie-breakers (in order, up to 3)</label>
                    @for($i=0;$i<3;$i++)
                        <select name="tiebreakers[]" class="form-select form-select-sm mb-1">
                            <option value="">— none —</option>
                            @foreach($measures as $k => $lbl)<option value="{{ $k }}" @selected(($s->tiebreakers[$i] ?? null)===$k)>{{ $lbl }}</option>@endforeach
                        </select>
                    @endfor

                    <hr class="my-3">
                    <label class="form-label small fw-semibold">Eligibility</label>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><input type="number" min="1" max="40" name="min_subjects" value="{{ $s->min_subjects }}" class="form-control form-control-sm" placeholder="Min subjects"></div>
                        <div class="col-6"><input type="number" step="0.01" min="0" max="100" name="min_average" value="{{ $s->min_average }}" class="form-control form-control-sm" placeholder="Min average"></div>
                    </div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="require_all_compulsory" value="1" id="rac_{{ $section }}" @checked($s->require_all_compulsory)><label class="form-check-label small" for="rac_{{ $section }}">All compulsory subjects must be scored</label></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="exclude_failed" value="1" id="ef_{{ $section }}" @checked($s->exclude_failed)><label class="form-check-label small" for="ef_{{ $section }}">Exclude anyone with a failed subject (&lt;40)</label></div>

                    <hr class="my-3">
                    <div class="row g-2">
                        <div class="col-7"><label class="form-label small fw-semibold">Scope</label>
                            <select name="scope" class="form-select form-select-sm">
                                @foreach($scopes as $k => $lbl)<option value="{{ $k }}" @selected($s->scope===$k)>{{ $lbl }}</option>@endforeach
                            </select></div>
                        <div class="col-5"><label class="form-label small fw-semibold">Show top</label>
                            <select name="top_n" class="form-select form-select-sm">
                                @foreach([1,3,5] as $n)<option value="{{ $n }}" @selected((int)$s->top_n===$n)>Top {{ $n }}</option>@endforeach
                            </select></div>
                    </div>

                    <div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="show_rank_column" value="1" id="src_{{ $section }}" @checked($s->show_rank_column)><label class="form-check-label small" for="src_{{ $section }}">Add an extra "Rank" column to the sheet</label></div>

                    <label class="form-label small fw-semibold mt-3">Core subjects (for "Core-subjects average")</label>
                    <select name="core_subject_ids[]" class="form-select form-select-sm" multiple size="5">
                        @foreach($subjects as $subj)<option value="{{ $subj->id }}" @selected(in_array($subj->id, (array)($s->core_subject_ids ?? [])))>{{ $subj->subject }}</option>@endforeach
                    </select>
                    <div class="small text-muted">Hold Ctrl/Cmd to select several.</div>

                    <button class="action-btn btn-primary-cb w-100 justify-content-center mt-3"><i class="ri-save-line"></i>Save {{ $section }} settings</button>
                </form>
            </x-cb.card>
        </div>
        @endforeach
    </div>
</div></div></div>
@endsection
