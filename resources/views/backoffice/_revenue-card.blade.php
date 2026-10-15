{{-- Daily revenue at a glance: today against yesterday and a 14-day chart. Needs $operatorId (null = everything) and $reportsUrl. --}}
@php
    $rev = app(\App\Modules\Backoffice\ReportService::class)->revenueSnapshot($operatorId);
    $earnLabel = $operatorId ? 'Your earnings' : 'Platform fee';
    $t = $rev['today']; $y = $rev['yesterday'];
    $earnT = $operatorId ? (int) ($t->net ?? 0) : (int) ($t->fee ?? 0);
    $change = ($y && (int) $y->sales > 0) ? round(((int) $t->sales - (int) $y->sales) * 100 / (int) $y->sales) : null;
    $show = fn ($k) => \App\Modules\Backoffice\ReportService::show($k / 100, 'money');
@endphp
<x-cb.card title="Revenue" icon="ri-line-chart-line" class="mb-3">
    <x-slot:tools><a href="{{ $reportsUrl }}" class="btn btn-sm btn-light">All reports</a></x-slot:tools>
    <div class="row g-3 align-items-center">
        <div class="col-md-4">
            <div class="small text-muted">Sales today</div>
            <div class="fs-3 fw-semibold">{{ $show((int) ($t->sales ?? 0)) }}</div>
            <div class="small {{ $change === null ? 'text-muted' : ($change >= 0 ? 'text-success' : 'text-danger') }}">
                @if($change === null) No sales yesterday to compare @else {{ $change >= 0 ? '▲' : '▼' }} {{ abs($change) }}% on yesterday ({{ $show((int) $y->sales) }}) @endif
            </div>
            <div class="mt-2 small">{{ $earnLabel }} today: <strong>{{ $show($earnT) }}</strong></div>
            <div class="small">Bookings today: <strong>{{ (int) ($t->bookings ?? 0) }}</strong> · delivered <strong>{{ (int) ($t->delivered ?? 0) }}</strong></div>
        </div>
        <div class="col-md-8"><div id="revChart" style="min-height:200px"></div></div>
    </div>
</x-cb.card>
<script src="{{ asset('theme/layouts/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
(function () {
    var r = @json(['labels' => $rev['labels'], 'sales' => $rev['sales'], 'earn' => $operatorId ? $rev['net'] : null]);
    var series = [{name: 'Sales', data: r.sales}];
    if (r.earn) { series.push({name: 'Your earnings', data: r.earn}); }
    var fmt = function (v) { return '₦' + Number(v || 0).toLocaleString(undefined, {maximumFractionDigits: 0}); };
    new ApexCharts(document.getElementById('revChart'), {chart: {type: 'area', height: 200, toolbar: {show: false}, sparkline: {enabled: false}, fontFamily: 'inherit'},
        series: series, xaxis: {categories: r.labels, labels: {hideOverlappingLabels: true}}, yaxis: {labels: {formatter: fmt}}, tooltip: {y: {formatter: fmt}},
        dataLabels: {enabled: false}, stroke: {width: 2, curve: 'smooth'}, colors: ['#0ab39c', '#405189'], legend: {show: series.length > 1, position: 'top'}}).render();
})();
</script>
