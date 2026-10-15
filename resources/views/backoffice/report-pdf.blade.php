@php use App\Modules\Backoffice\ReportService as R; @endphp
<!doctype html>
<html><head><meta charset="utf-8"><title>{{ $title }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    .muted { color: #666; margin-bottom: 10px; }
    .tiles { margin: 8px 0 12px; }
    .tile { display: inline-block; border: 1px solid #ddd; border-radius: 4px; padding: 5px 8px; margin: 0 6px 6px 0; }
    .tile b { display: block; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f1f3f5; text-align: left; font-weight: bold; }
    th, td { border-bottom: 1px solid #e5e5e5; padding: 4px 5px; }
    .r { text-align: right; }
</style></head>
<body>
    <h1>{{ $title }}</h1>
    <div class="muted">{{ $scope }} · {{ $from->format('j M Y') }} to {{ $to->format('j M Y') }} · printed {{ now()->format('j M Y, H:i') }}</div>
    <div class="tiles">
        @foreach($table['summary'] as [$label, $value, $format])<span class="tile">{{ $label }}<b>{{ R::show($value, $format) }}</b></span>@endforeach
    </div>
    <table>
        <thead><tr>@foreach($table['headings'] as $i => $h)<th class="{{ in_array($table['formats'][$i], ['text', 'date'], true) ? '' : 'r' }}">{{ $h }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($table['rows'] as $row)
            <tr>@foreach($row as $i => $v)<td class="{{ in_array($table['formats'][$i], ['text', 'date'], true) ? '' : 'r' }}">{{ R::show($v, $table['formats'][$i]) }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($table['headings']) }}">Nothing in this period.</td></tr>
        @endforelse
        </tbody>
    </table>
</body></html>
