<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $p['display_name'] }} on {{ config('app.name') }}</title>
<meta name="description" content="{{ $p['headline'] ?? 'Delivery provider on '.config('app.name') }}">
<style>
    :root { --bg:#f6f7f9; --card:#fff; --ink:#1b2430; --muted:#6b7686; --line:#e3e7ee; --brand:#1d6fdc; --star:#e6a100; }
    @media (prefers-color-scheme: dark) { :root { --bg:#12161c; --card:#1b212a; --ink:#e8edf4; --muted:#93a0b2; --line:#2a323d; --brand:#5aa2ff; --star:#f1b92e; } }
    * { box-sizing: border-box; }
    body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background:var(--bg); color:var(--ink); line-height:1.5; }
    .wrap { max-width:720px; margin:0 auto; padding:20px 16px 40px; }
    .card { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:18px; margin-bottom:14px; }
    h1 { font-size:1.5rem; margin:0 0 2px; }
    h2 { font-size:1.05rem; margin:0 0 10px; }
    .muted { color:var(--muted); }
    .tier { display:inline-block; font-size:.78rem; padding:2px 10px; border-radius:999px; border:1px solid var(--line); text-transform:capitalize; }
    .stats { display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; margin-top:14px; }
    .stat { text-align:center; border:1px solid var(--line); border-radius:12px; padding:10px 4px; }
    .stat b { display:block; font-size:1.2rem; }
    .stars { color:var(--star); letter-spacing:1px; }
    .review { border-top:1px solid var(--line); padding:12px 0; }
    .review:first-of-type { border-top:0; padding-top:0; }
    .tag { font-size:.75rem; background:var(--bg); border:1px solid var(--line); border-radius:999px; padding:1px 8px; margin-right:4px; }
    .btn { display:block; text-align:center; background:var(--brand); color:#fff; text-decoration:none; padding:12px; border-radius:12px; font-weight:600; }
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>{{ $p['display_name'] }}</h1>
        @if(!empty($p['headline']))<div class="muted">{{ $p['headline'] }}</div>@endif
        <div style="margin-top:8px"><span class="tier">{{ $p['tier'] ?? 'new' }}</span>
            @foreach($badges as $b)<span class="tier">{{ str_replace('_', ' ', $b) }}</span>@endforeach</div>
        <div class="stats">
            <div class="stat"><b>{{ $p['rating_count'] ? number_format($p['rating_avg'], 1).' ★' : 'New' }}</b><span class="muted">{{ $p['rating_count'] }} {{ $p['rating_count'] == 1 ? 'review' : 'reviews' }}</span></div>
            <div class="stat"><b>{{ number_format($p['jobs_completed'] ?? 0) }}</b><span class="muted">jobs done</span></div>
            <div class="stat"><b>{{ isset($p['completion_rate']) ? rtrim(rtrim(number_format($p['completion_rate'], 1), '0'), '.').'%' : '—' }}</b><span class="muted">completed</span></div>
        </div>
    </div>

    @if(!empty($p['about']))
    <div class="card"><h2>About</h2><div>{!! nl2br(e($p['about'])) !!}</div>
        @if(!empty($p['years_operating']))<div class="muted" style="margin-top:8px">{{ $p['years_operating'] }} {{ $p['years_operating'] == 1 ? 'year' : 'years' }} in business</div>@endif
        @if(count($languages))<div class="muted">Speaks {{ implode(', ', $languages) }}</div>@endif
    </div>
    @endif

    <div class="card">
        <h2>Reviews</h2>
        @forelse($reviews as $r)
            <div class="review">
                <div><span class="stars">{{ str_repeat('★', (int) $r['score']) }}{{ str_repeat('☆', 5 - (int) $r['score']) }}</span> <strong>{{ $r['reviewer'] }}</strong>
                    <span class="muted"> · {{ \Illuminate\Support\Carbon::parse($r['created_at'])->format('M Y') }}</span></div>
                @if(!empty($r['comment']))<div>{{ $r['comment'] }}</div>@endif
                @if(!empty($r['tags']))<div style="margin-top:4px">@foreach($r['tags'] as $t)<span class="tag">{{ str_replace('_', ' ', $t) }}</span>@endforeach</div>@endif
            </div>
        @empty
            <div class="muted">No reviews yet.</div>
        @endforelse
    </div>

    <a class="btn" href="{{ auth()->check() ? route('account.request.new', ['provider' => $operatorId]) : route('login') }}">{{ auth()->check() ? 'Ask '.$p['display_name'].' for a price' : 'Sign in to ask for a price' }}</a>
</div>
</body>
</html>
