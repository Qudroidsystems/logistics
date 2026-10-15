{{-- The busiest drivers over the last 7 days. Needs $operatorId (null = everyone) and $reportsUrl. --}}
@php
    $tz = config('app.timezone');
    $week = app(\App\Modules\Backoffice\ReportService::class)->drivers($operatorId, \Carbon\CarbonImmutable::now($tz)->subDays(6)->startOfDay(), \Carbon\CarbonImmutable::now($tz)->endOfDay());
    $h = array_flip($week['headings']);
    $top = array_slice(array_filter($week['rows'], fn ($r) => $r[$h['Delivered']] + $r[$h['Failed']] > 0), 0, 5);
    $show = fn ($v, $f) => \App\Modules\Backoffice\ReportService::show($v, $f);
@endphp
<x-cb.card title="Drivers this week" icon="ri-steering-2-line" :flush="true" class="mb-3">
    <x-slot:tools><a href="{{ $reportsUrl }}" class="btn btn-sm btn-light">Driver performance</a></x-slot:tools>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th>Driver</th><th class="text-end">Delivered</th><th class="text-end">Failed</th><th class="text-end">Pickup to drop-off</th><th class="text-end">Rating</th></tr></thead>
        <tbody>
        @forelse($top as $r)
            <tr><td>{{ $r[0] }}</td><td class="text-end">{{ $r[$h['Delivered']] }}</td><td class="text-end">{{ $r[$h['Failed']] }}</td>
                <td class="text-end">{{ $show($r[$h['Pickup to drop-off']], 'minutes') }}</td><td class="text-end">{{ $show($r[$h['Rating']], 'rating') }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No deliveries in the last 7 days.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</x-cb.card>
