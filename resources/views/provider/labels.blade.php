<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Labels · {{ $s->tracking_code }}</title>
<style>
    body { font-family: system-ui, sans-serif; margin: 16px; color: #111; background: #fff; }
    .sheet { display: flex; flex-wrap: wrap; gap: 12px; }
    .label { border: 1px dashed #999; width: 90mm; padding: 10px; box-sizing: border-box; page-break-inside: avoid; display: flex; gap: 10px; }
    .label svg { width: 34mm; height: 34mm; flex: none; }
    .code { font: 700 18px/1.2 ui-monospace, monospace; letter-spacing: 1px; }
    .muted { color: #555; font-size: 12px; }
    .no-print { margin-bottom: 12px; }
    @media print { .no-print { display: none; } .label { border-color: #000; } }
</style>
</head>
<body>
<div class="no-print"><button onclick="window.print()">Print</button> {{ count($packages) }} label{{ count($packages) === 1 ? '' : 's' }} for job {{ $s->tracking_code }}</div>
<div class="sheet">
@foreach($packages as $p)
    <div class="label">
        {!! $p['qr_svg'] !!}
        <div>
            <div class="muted">{{ $op->display_name }}</div>
            <div class="code">{{ $p['barcode'] }}</div>
            <div>Parcel {{ $p['seq'] }} of {{ count($packages) }}@if($p['fragile']) · FRAGILE @endif</div>
            <div class="muted">Job {{ $s->tracking_code }}</div>
            @if($dropoff)<div class="muted">To: {{ $dropoff->contact_name ? \Illuminate\Support\Str::before($dropoff->contact_name, ' ').', ' : '' }}{{ \Illuminate\Support\Str::limit($dropoff->line1, 60) }}</div>@endif
        </div>
    </div>
@endforeach
</div>
</body>
</html>
