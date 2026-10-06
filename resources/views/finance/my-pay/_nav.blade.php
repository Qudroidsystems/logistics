{{-- My Pay tabs + period picker. Needs $s, $q, $years, $section; optional $year/$from/$to --}}
@php $tabs = ['index' => ['Payslips', 'ri-file-list-3-line'], 'tax' => ['Tax history', 'ri-government-line'], 'pension' => ['Pension', 'ri-shield-user-line'], 'deductions' => ['Deductions', 'ri-indeterminate-circle-line']];
    if (!$s->viewing_other) $tabs += ['loans' => ['Loans', 'ri-hand-coin-line'], 'cooperative' => ['Cooperative', 'ri-safe-2-line'], 'claims' => ['Extra duty', 'ri-time-line']]; @endphp
@if($s->viewing_other)<div class="cb-banner info"><i class="ri-eye-line"></i><div>You are viewing <b>{{ $s->name }}</b>'s pay records as the bursary.</div></div>@endif
<div class="cb-card mb-3">
    <div class="cb-toolbar flex-wrap gap-2">
        <div class="term-chips">
            @foreach($tabs as $k => [$l, $i])<a class="term-chip {{ $section === $k ? 'active' : '' }}" href="{{ route('my-pay.' . $k, $q) }}"><i class="{{ $i }} me-1"></i>{{ $l }}</a>@endforeach
        </div>
        @if(!in_array($section, ['index', 'loans', 'cooperative', 'claims']))
            <form method="GET" class="d-flex flex-wrap gap-2 ms-auto align-items-center">
                @foreach($q as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                <select name="year" class="cb-select" onchange="this.form.from.value='';this.form.to.value='';this.form.submit()" aria-label="Year">
                    @foreach($years as $y)<option value="{{ $y }}" @selected(($year ?? null) == $y)>{{ $y }}</option>@endforeach
                    @if(!($year ?? null))<option selected>Custom</option>@endif
                </select>
                <span class="small text-muted">or</span>
                <input type="month" name="from" class="form-control form-control-sm" style="width:150px" value="{{ !($year ?? null) && isset($from) ? substr($from, 0, 7) : '' }}" aria-label="From">
                <input type="month" name="to" class="form-control form-control-sm" style="width:150px" value="{{ !($year ?? null) && isset($to) ? substr($to, 0, 7) : '' }}" aria-label="To">
                <button class="btn btn-sm btn-light">Show</button>
            </form>
        @endif
    </div>
</div>
