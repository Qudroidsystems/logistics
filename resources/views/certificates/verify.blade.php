<?php
    $schoolName = $school->school_name ?? config('app.name', 'School');
    $logo = null;
    try { $logo = $school ? ($school->logo_url ?: null) : null; } catch (\Throwable $e) {}
    $student = $cert && $cert->student ? trim(($cert->student->firstname ?? '') . ' ' . ($cert->student->lastname ?? '')) : null;
    $badge = ['valid' => ['#16a34a', 'Verified — Genuine', 'ri-checkbox-circle-fill'], 'revoked' => ['#dc2626', 'Revoked', 'ri-close-circle-fill'], 'pending' => ['#d97706', 'Not yet issued', 'ri-time-fill'], 'invalid' => ['#64748b', 'Not found', 'ri-question-fill']][$state];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Certificate verification — {{ $schoolName }}</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
:root{--ink:#0f172a;--muted:#64748b;--border:#e5e7eb;--bg:#f1f5f9}
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:var(--ink);background:var(--bg)}
.wrap{max-width:560px;margin:0 auto;padding:24px 16px}
.top{display:flex;align-items:center;gap:12px;padding:8px 0 18px}
.top img{height:44px;border-radius:8px}
.top h1{font-size:1.05rem;margin:0}
.card{background:#fff;border:1px solid var(--border);border-radius:16px;padding:22px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.badge{display:inline-flex;align-items:center;gap:8px;font-weight:700;font-size:1.05rem;padding:10px 16px;border-radius:999px;color:#fff}
.rows{margin-top:18px;border-top:1px solid var(--border)}
.row{display:flex;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid #f1f5f9;font-size:.92rem}
.row .k{color:var(--muted)}
.row .v{font-weight:600;text-align:right}
.note{margin-top:16px;font-size:.82rem;color:var(--muted)}
.foot{text-align:center;color:var(--muted);font-size:.78rem;padding:18px 0}
.rev{margin-top:14px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:10px;padding:10px 12px;font-size:.86rem}
</style>
</head>
<body>
<div class="wrap">
    <div class="top">@if($logo)<img src="{{ $logo }}" alt="">@endif<div><h1>{{ $schoolName }}</h1><div style="color:var(--muted);font-size:.8rem">Certificate verification</div></div></div>

    <div class="card">
        <span class="badge" style="background: {{ $badge[0] }}"><i class="{{ $badge[2] }}"></i> {{ $badge[1] }}</span>

        @if($state === 'invalid')
            <p class="note">No certificate matches this code. If you scanned a printed certificate, check the code was captured correctly, or contact the school.</p>
        @elseif($state === 'pending')
            <p class="note">This certificate exists in the school's records but has not been officially issued. It is not yet valid.</p>
        @else
            <div class="rows">
                @if($student)<div class="row"><span class="k">Awarded to</span><span class="v">{{ $student }}</span></div>@endif
                <div class="row"><span class="k">Certificate</span><span class="v">{{ $cert->title ?: ($cert->template->name ?? 'Certificate') }}</span></div>
                <div class="row"><span class="k">Serial no.</span><span class="v">{{ $cert->serial }}</span></div>
                @if($cert->student && $cert->student->admissionNo)<div class="row"><span class="k">Admission no.</span><span class="v">{{ $cert->student->admissionNo }}</span></div>@endif
                <div class="row"><span class="k">Date issued</span><span class="v">{{ $cert->issued_at ? $cert->issued_at->format('d M Y') : '—' }}</span></div>
                <div class="row"><span class="k">Status</span><span class="v" style="color: {{ $badge[0] }}">{{ $badge[1] }}</span></div>
            </div>
            @if($state === 'revoked')
                <div class="rev"><i class="ri-alert-line"></i> This certificate has been <strong>revoked</strong> by {{ $schoolName }}{{ $cert->revoked_at ? ' on ' . $cert->revoked_at->format('d M Y') : '' }} and should not be accepted as valid.</div>
            @else
                <p class="note">This confirms the certificate above was issued by {{ $schoolName }} and is genuine.</p>
            @endif
        @endif
    </div>

    <div class="foot">Verified via {{ $schoolName }} portal · {{ now()->format('d M Y H:i') }}</div>
</div>
</body>
</html>
