@extends('layouts.master')

@section('content')
@php
    use App\Modules\Backoffice\ReportService as R;
    $q = array_filter(['from' => $from->toDateString(), 'to' => $to->toDateString(), 'provider' => $provider->public_id ?? null]);
    $today = \Carbon\CarbonImmutable::now(config('app.timezone'));
    $ranges = [
        'Last 7 days' => [$today->subDays(6), $today], 'Last 30 days' => [$today->subDays(29), $today], 'Last 90 days' => [$today->subDays(89), $today],
        'This month' => [$today->startOfMonth(), $today], 'Last month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
        'Last 12 months' => [$today->subMonths(11)->startOfMonth(), $today],
    ];
    $accent = ['teal', 'violet', 'sky', 'amber', 'teal', 'violet', 'sky', 'amber'];
@endphp
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Reports" icon="ri-bar-chart-box-line" :subtitle="$scope.' · '.$from->format('j M Y').' to '.$to->format('j M Y')">
        @if($canExport)
        <x-slot:actions>
            <a class="cb-hero-btn" href="{{ route($exportRoute, ['report' => $report, 'format' => 'xlsx'] + $q) }}"><i class="ri-file-excel-2-line"></i> Excel</a>
            <a class="cb-hero-btn" href="{{ route($exportRoute, ['report' => $report, 'format' => 'pdf'] + $q) }}"><i class="ri-file-pdf-2-line"></i> PDF</a>
        </x-slot:actions>
        @endif
    </x-cb.hero>

    <ul class="nav nav-pills mb-3 flex-wrap gap-1">
        @foreach($allowed as $k)
            <li class="nav-item"><a class="nav-link {{ $k === $report ? 'active' : '' }}" href="{{ route($routeName, ['report' => $k] + $q) }}">{{ R::REPORTS[$k] }}</a></li>
        @endforeach
    </ul>

    <x-cb.card>
        <form method="GET" action="{{ route($routeName, $report) }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-2"><label class="form-label small">From</label><input type="date" name="from" class="form-control" value="{{ $from->toDateString() }}"></div>
            <div class="col-6 col-md-2"><label class="form-label small">To</label><input type="date" name="to" class="form-control" value="{{ $to->toDateString() }}"></div>
            @if($providers)
                <div class="col-12 col-md-3"><label class="form-label small">Provider</label>
                    <select name="provider" class="form-select"><option value="">All providers</option>@foreach($providers as $p)<option value="{{ $p->public_id }}" @selected(($provider->public_id ?? '') === $p->public_id)>{{ $p->display_name }}</option>@endforeach</select>
                </div>
            @endif
            <div class="col-12 col-md-auto"><button class="btn btn-primary">Show</button></div>
            <div class="col-12 col-md d-flex flex-wrap gap-1 justify-content-md-end">
                @foreach($ranges as $label => [$a, $b])
                    <a class="btn btn-sm btn-light" href="{{ route($routeName, ['report' => $report, 'from' => $a->toDateString(), 'to' => $b->toDateString()] + array_filter(['provider' => $provider->public_id ?? null])) }}">{{ $label }}</a>
                @endforeach
            </div>
        </form>
    </x-cb.card>

    @if(! empty($table['note']))<div class="alert alert-warning small">{{ $table['note'] }}</div>@endif

    <div class="row g-3 mb-3">
        @foreach($table['summary'] as $i => [$label, $value, $format])
            <div class="col-6 col-lg-3"><x-cb.stat :label="$label" :value="R::show($value, $format)" icon="ri-bar-chart-2-line" :accent="$accent[$i % 8]" /></div>
        @endforeach
    </div>

    @if(! empty($table['chart']) && count($table['rows']))
        <x-cb.card :title="R::REPORTS[$report]" icon="ri-line-chart-line"><div id="reportChart" style="min-height:320px"></div></x-cb.card>
    @endif

    <x-cb.card :title="count($table['rows']).' rows'" icon="ri-table-line" :flush="true">
        <div class="table-responsive"><table class="table table-sm align-middle mb-0" id="reportTable">
            <thead><tr>@foreach($table['headings'] as $i => $h)<th class="{{ in_array($table['formats'][$i], ['text', 'date'], true) ? '' : 'text-end' }}">{{ $h }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse($table['rows'] as $row)
                <tr>@foreach($row as $i => $v)<td class="{{ in_array($table['formats'][$i], ['text', 'date'], true) ? '' : 'text-end' }}">{{ R::show($v, $table['formats'][$i]) }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($table['headings']) }}" class="text-center text-muted py-4">Nothing in this period.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-cb.card>
</div></div></div>

@if(! empty($table['chart']) && count($table['rows']))
<script src="{{ asset('theme/layouts/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    var c = @json($table['chart']);
    var fmt = function (v) {
        if (v === null || v === undefined) { return '—'; }
        if (c.format === 'money') { return '{{ Money::symbol() }}' + Number(v).toLocaleString(undefined, {maximumFractionDigits: 0}); }
        if (c.format === 'pct') { return v + '%'; }
        if (c.format === 'minutes') { return v + ' min'; }
        return Number(v).toLocaleString();
    };
    new ApexCharts(document.getElementById('reportChart'), {
        chart: {type: c.type, height: 320, stacked: !!c.stacked, toolbar: {show: false}, fontFamily: 'inherit'},
        series: c.series, xaxis: {categories: c.labels, labels: {rotate: -45, hideOverlappingLabels: true}},
        yaxis: c.horizontal ? {} : {labels: {formatter: fmt}}, tooltip: {y: {formatter: fmt}}, dataLabels: {enabled: false},
        plotOptions: {bar: {horizontal: !!c.horizontal, borderRadius: 3, columnWidth: '60%'}},
        stroke: {width: c.type === 'line' ? 3 : 0, curve: 'smooth'}, colors: ['#0ab39c', '#405189', '#f7b84b', '#f06548'],
        legend: {position: 'top'}, noData: {text: 'Nothing in this period'}
    }).render();
})();
</script>
@endif
@endsection
