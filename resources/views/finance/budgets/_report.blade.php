{{-- Budget vs actual table. Needs $report, $m --}}
<div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Line</th><th class="text-end">Budget</th><th class="text-end">Spent</th><th class="text-end">Committed</th><th class="text-end">Remaining</th><th style="min-width:140px">Used</th></tr></thead>
    <tbody>
    @forelse(collect($report['rows'])->sortByDesc('used') as $r)
        @php $cls = $r['used'] > 100 ? 'bg-danger' : ($r['used'] > $report['elapsed'] + 10 ? 'bg-warning' : ''); @endphp
        <tr class="{{ $r['used'] > 100 ? 'table-danger' : '' }}">
            <td>{{ $r['name'] }}</td><td class="text-end">{{ $m($r['budget']) }}</td><td class="text-end">{{ $m($r['actual']) }}</td>
            <td class="text-end text-muted">{{ $r['committed'] ? $m($r['committed']) : '—' }}</td>
            <td class="text-end fw-bold {{ $r['remaining'] < 0 ? 'text-danger' : '' }}">{{ $m($r['remaining']) }}</td>
            <td><div class="progress-track"><div class="progress-fill {{ $cls }}" style="width:{{ min(100, $r['used']) }}%"></div></div><div class="small text-muted">{{ $r['used'] }}%</div></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted p-4">No lines yet.</td></tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr><th>Total</th><th class="text-end">{{ $m($report['total_budget']) }}</th><th class="text-end">{{ $m($report['total_actual']) }}</th><th class="text-end">{{ $m($report['total_committed']) }}</th><th class="text-end">{{ $m($report['total_budget'] - $report['total_actual'] - $report['total_committed']) }}</th><th class="small text-muted">{{ $report['elapsed'] }}% of the period gone</th></tr>
        @if($report['unbudgeted'] > 0)<tr><td colspan="6" class="small text-danger">{{ $m($report['unbudgeted']) }} was spent on categories with no budget line.</td></tr>@endif
    </tfoot>
</table></div>
