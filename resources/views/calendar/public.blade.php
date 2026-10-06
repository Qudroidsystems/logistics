<?php
    $prev = $anchor->copy()->subMonth()->format('Y-m');
    $next = $anchor->copy()->addMonth()->format('Y-m');
    $today = \Carbon\Carbon::today();
    $info = null;
    try { $info = class_exists(\App\Models\SchoolInformation::class) ? \App\Models\SchoolInformation::first() : null; } catch (\Throwable $e) {}
    $schoolName = $info->school_name ?? config('app.name', 'School');
    $logo = null;
    try { $logo = $info && method_exists($info, 'getLogoUrlAttribute') ? $info->logo_url : ($info->school_logo ?? null); } catch (\Throwable $e) {}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $schoolName }} — School Calendar</title>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
:root{--brand:#0f766e;--ink:#0f172a;--muted:#64748b;--border:#e5e7eb;--soft:#f8fafc;--bg:#f1f5f9}
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:var(--ink);background:var(--bg)}
.wrap{max-width:1040px;margin:0 auto;padding:16px}
.top{display:flex;align-items:center;gap:12px;padding:18px 0}
.top img{height:44px;width:auto;border-radius:8px}
.top h1{font-size:1.15rem;margin:0}
.top .sub{color:var(--muted);font-size:.82rem}
.card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:14px;margin-bottom:16px;box-shadow:0 1px 2px rgba(0,0,0,.03)}
.bar{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;flex-wrap:wrap}
.bar h2{font-size:1rem;margin:0}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:8px;border:1px solid var(--border);background:#fff;color:var(--ink);text-decoration:none;font-size:.85rem;cursor:pointer}
.btn.brand{background:var(--brand);color:#fff;border-color:var(--brand)}
.grid{display:flex;gap:16px;flex-wrap:wrap}
.col-main{flex:1 1 560px}.col-side{flex:1 1 260px}
.cal{border:1px solid var(--border);border-radius:12px;overflow:hidden}
.cal-head{display:grid;grid-template-columns:repeat(7,1fr);background:var(--soft);font-size:.7rem;font-weight:600;color:var(--muted);text-transform:uppercase}
.cal-head>div{padding:8px 6px;text-align:center;border-right:1px solid #eef2f7}.cal-head>div:last-child{border-right:0}
.cal-row{display:grid;grid-template-columns:repeat(7,1fr);border-top:1px solid #eef2f7}
.cell{min-height:92px;padding:5px;border-right:1px solid #eef2f7;background:#fff}.cell:last-child{border-right:0}
.cell.muted{background:#fafbfc}.cell.muted .d{color:#cbd5e1}
.cell.today{background:#f0fdfa;box-shadow:inset 0 0 0 2px #99f6e4}
.d{font-size:.76rem;font-weight:600;color:#334155;text-align:right;margin-bottom:3px}
.ev{display:flex;align-items:center;font-size:.68rem;padding:2px 4px;margin-bottom:2px;border-radius:4px;background:#f8fafc;border-left:3px solid #94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:3px;flex:0 0 auto}
.list{list-style:none;margin:0;padding:0}
.list li{display:flex;gap:8px;padding:8px 0;border-top:1px solid #f1f5f9}
.list li:first-child{border-top:0}
.chip{font-size:.68rem;color:var(--muted);white-space:nowrap}
.legend{display:flex;gap:12px;flex-wrap:wrap;font-size:.75rem;color:var(--muted);margin-top:10px}
.feed{display:flex;gap:6px}
.feed input{flex:1;padding:7px 10px;border:1px solid var(--border);border-radius:8px;font-size:.8rem}
.foot{text-align:center;color:var(--muted);font-size:.75rem;padding:18px 0}
@media (max-width:520px){.cell{min-height:62px}.ev{font-size:.6rem}}
</style>
</head>
<body>
<div class="wrap">
    <div class="top">
        @if($logo)<img src="{{ $logo }}" alt="">@endif
        <div><h1>{{ $schoolName }}</h1><div class="sub">School Calendar &amp; Activities</div></div>
    </div>

    <div class="grid">
        <div class="col-main">
            <div class="card">
                <div class="bar">
                    <h2>{{ $anchor->format('F Y') }}</h2>
                    <div>
                        <a class="btn" href="{{ route('calendar.public', ['month'=>$prev]) }}"><i class="ri-arrow-left-s-line"></i></a>
                        <a class="btn" href="{{ route('calendar.public', ['month'=>now()->format('Y-m')]) }}">Today</a>
                        <a class="btn" href="{{ route('calendar.public', ['month'=>$next]) }}"><i class="ri-arrow-right-s-line"></i></a>
                    </div>
                </div>
                <div class="cal">
                    <div class="cal-head">@foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)<div>{{ $d }}</div>@endforeach</div>
                    @foreach($weeks as $week)
                        <div class="cal-row">
                            @foreach($week as $cell)
                                <div class="cell {{ $cell->inMonth?'':'muted' }} {{ $cell->date->isSameDay($today)?'today':'' }}">
                                    <div class="d">{{ $cell->date->day }}</div>
                                    @foreach($cell->events->take(4) as $e)
                                        <div class="ev" style="border-left-color: {{ $e->color }}" title="{{ $e->title }}"><span class="dot" style="background: {{ $e->color }}"></span>{{ \Illuminate\Support\Str::limit($e->title, 14) }}</div>
                                    @endforeach
                                    @if($cell->events->count()>4)<div class="chip">+{{ $cell->events->count()-4 }}</div>@endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <div class="legend">
                    @foreach($categories as $c)<span><span class="dot" style="background: {{ $c->color }}"></span>{{ $c->name }}</span>@endforeach
                    <span><span class="dot" style="background:#dc2626"></span>Holiday</span>
                </div>
            </div>
        </div>

        <div class="col-side">
            <div class="card">
                <div class="bar"><h2>Upcoming</h2></div>
                @if($upcoming->isEmpty())
                    <div class="chip">No upcoming public events.</div>
                @else
                    <ul class="list">
                        @foreach($upcoming as $e)
                            <li>
                                <span class="dot" style="background: {{ $e->color }};margin-top:5px"></span>
                                <div style="flex:1">
                                    <div style="font-weight:600;font-size:.86rem">{{ $e->title }}</div>
                                    <div class="chip">{{ $e->start->isSameDay($e->end) ? $e->start->format('D, d M') : $e->start->format('d M').' – '.$e->end->format('d M') }}@if($e->location) · {{ $e->location }}@endif</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="card">
                <div class="bar"><h2>Subscribe</h2></div>
                <div class="chip" style="margin-bottom:8px">Add to Google or Apple Calendar to stay in sync.</div>
                <div class="feed">
                    <input id="feed" readonly value="{{ $feedUrl }}">
                    <button class="btn brand" onclick="var i=document.getElementById('feed');i.select();try{document.execCommand('copy')}catch(e){}">Copy</button>
                </div>
            </div>
        </div>
    </div>

    <div class="foot">Powered by {{ $schoolName }} portal</div>
</div>
</body>
</html>
