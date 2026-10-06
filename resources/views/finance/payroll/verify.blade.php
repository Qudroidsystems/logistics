<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $kind }} check · {{ $school->school_name ?? config('app.name') }}</title>
<link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css') }}" rel="stylesheet">
<style>body{background:#f1f5f9;min-height:100vh;display:flex;align-items:center;font-family:system-ui,sans-serif}.box{max-width:460px;margin:24px auto;background:#fff;border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.08);padding:28px}.ok{color:#15803d}.bad{color:#b91c1c}</style>
</head><body><div class="container px-3"><div class="box">
    <h5 class="mb-1">{{ $school->school_name ?? config('app.name') }}</h5>
    <p class="text-muted small mb-3">{{ $kind }} check</p>
    @if($ok)
        <h4 class="ok mb-3">✓ Genuine</h4>
        <table class="table table-sm mb-2">@foreach($rows as $k => $v)<tr><th class="text-muted fw-normal">{{ $k }}</th><td class="text-end fw-semibold">{{ $v }}</td></tr>@endforeach</table>
        <p class="small text-muted mb-0">These are the figures held in the school's payroll records. If the document you have shows different figures, it has been altered.</p>
    @else
        <h4 class="bad mb-2">✗ Not found</h4>
        <p class="small text-muted mb-0">We could not match this code to a final payroll record. The document may be provisional, mistyped or not genuine. Contact the school bursary.</p>
    @endif
</div></div></body></html>
