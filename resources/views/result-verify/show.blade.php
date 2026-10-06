{{-- resources/views/result-verify/show.blade.php — public result verification (QR code) --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Result verification — {{ $school->school_name ?? config('app.name') }}</title>
    <style>
        :root { --navy: #0f2342; --teal: #0d9488; --muted: #64748b; --border: #e2e8f0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f1f5f9; color: #1e293b; padding: 16px; }
        .wrap { max-width: 720px; margin: 0 auto; }
        .head { background: var(--navy); color: #fff; border-radius: 14px 14px 0 0; padding: 18px 20px; display: flex; gap: 14px; align-items: center; }
        .head img { height: 52px; width: 52px; object-fit: contain; background: #fff; border-radius: 8px; padding: 3px; }
        .head h1 { font-size: 18px; margin: 0; } .head p { margin: 2px 0 0; font-size: 12px; opacity: .8; }
        .card { background: #fff; border-radius: 0 0 14px 14px; padding: 20px; box-shadow: 0 10px 30px rgba(15,35,66,.08); }
        .badge { display: inline-flex; gap: 8px; align-items: center; padding: 8px 14px; border-radius: 999px; font-weight: 700; font-size: 14px; }
        .ok { background: #dcfce7; color: #166534; } .bad { background: #fee2e2; color: #991b1b; } .hold { background: #fef3c7; color: #92400e; }
        .meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin: 18px 0; }
        .meta div span { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); }
        .meta div strong { font-size: 15px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 9px 10px; border-bottom: 1px solid var(--border); text-align: left; }
        th { background: #f8fafc; font-size: 12px; text-transform: uppercase; color: var(--muted); }
        td.n, th.n { text-align: right; }
        .foot { font-size: 12px; color: var(--muted); margin-top: 16px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        @if(!empty($school->school_logo))<img src="{{ asset('storage/' . $school->school_logo) }}" alt="" onerror="this.remove()">@endif
        <div><h1>{{ $school->school_name ?? config('app.name') }}</h1><p>Report card verification</p></div>
    </div>
    <div class="card">
        @if(!$valid)
            <span class="badge bad">✕ Not a valid report card code</span>
            <p class="foot">This verification link is not recognised. The report card may have been altered, or the QR code is damaged. Please contact the school to confirm the result.</p>
        @else
            <span class="badge {{ $held ? 'hold' : 'ok' }}">{{ $held ? '● Genuine — result details withheld' : '✓ Genuine result' }}</span>
            <div class="meta">
                <div><span>Student</span><strong>{{ trim(strtoupper($student->lastname) . ' ' . $student->firstname . ' ' . ($student->othername ?? '')) }}</strong></div>
                <div><span>Admission no</span><strong>{{ $student->admissionNo }}</strong></div>
                <div><span>Class</span><strong>{{ $class }}</strong></div>
                <div><span>Term</span><strong>{{ $term }} · {{ $session }}</strong></div>
            </div>

            @if($held)
                <p class="foot">This report card was issued by the school, but its details are currently on hold. Please contact the school office for confirmation of the scores.</p>
            @elseif($scores->isEmpty())
                <p class="foot">No scores are recorded for this term.</p>
            @else
                <table>
                    <thead><tr><th>Subject</th><th class="n">Total</th><th class="n">Grade</th></tr></thead>
                    <tbody>
                    @foreach($scores as $s)
                        <tr><td>{{ $s->subject }}</td><td class="n">{{ rtrim(rtrim(number_format((float) $s->total, 2), '0'), '.') }}</td><td class="n">{{ $s->grade ?: '—' }}</td></tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th>{{ $scores->count() }} subjects</th><th class="n">{{ rtrim(rtrim(number_format($total, 2), '0'), '.') }}</th><th class="n">Avg {{ $average }}</th></tr>
                    </tfoot>
                </table>
                <p class="foot">Compare these scores with the printed report card. If anything differs, the paper copy has been altered — please contact the school.
                    @if($updated) Scores as recorded on {{ \Illuminate\Support\Carbon::parse($updated)->format('j M Y') }}.@endif</p>
            @endif
            <p class="foot">Code: {{ $code }}</p>
        @endif
    </div>
</div>
</body>
</html>
