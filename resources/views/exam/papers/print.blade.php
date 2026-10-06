{{-- resources/views/exam/papers/print.blade.php — stamped printable exam paper --}}
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $paper->title }} — {{ $label }}</title>
<style>
  body{font-family:'Times New Roman',Georgia,serif;color:#111;margin:0;background:#f1f5f9}
  .sheet{max-width:820px;margin:16px auto;background:#fff;padding:36px 44px;box-shadow:0 1px 4px rgba(0,0,0,.08);position:relative}
  .head{text-align:center;border-bottom:2px solid #111;padding-bottom:8px;margin-bottom:12px}
  .head h1{font-size:1.3rem;margin:0}
  .head .sub{font-size:.85rem;color:#444}
  .title{text-align:center;font-weight:bold;margin:10px 0 2px;text-transform:uppercase;font-size:1.05rem}
  .bar{display:flex;justify-content:space-between;font-size:.9rem;margin:6px 0 2px}
  .instr{font-style:italic;font-size:.9rem;margin:6px 0 14px;border-left:3px solid #c8a24a;padding-left:8px}
  .q{margin:0 0 12px;font-size:.95rem;line-height:1.5}
  .q .num{font-weight:bold}
  .marks{float:right;color:#555}
  .opts{margin:4px 0 0 20px}
  .opts span{display:inline-block;width:48%;font-size:.9rem}
  .sec{font-weight:bold;text-decoration:underline;margin:14px 0 6px}
  .stamp{position:absolute;top:28px;right:30px;border:2px solid #15803d;color:#15803d;border-radius:8px;padding:4px 10px;font-size:.8rem;transform:rotate(-8deg);font-weight:bold}
  .foot{margin-top:18px;border-top:1px solid #ccc;padding-top:6px;font-size:.78rem;color:#555;display:flex;justify-content:space-between}
  .toolbar{max-width:820px;margin:12px auto 0;text-align:right}
  .btn{display:inline-block;background:#1e3a5f;color:#fff;border:0;border-radius:8px;padding:8px 16px;cursor:pointer;text-decoration:none}
  @media print{.toolbar{display:none}body{background:#fff}.sheet{box-shadow:none;margin:0;max-width:none}}
</style>
</head><body>
<div class="toolbar"><button class="btn" onclick="window.print()">Print / Save as PDF</button></div>
<div class="sheet">
    @if(in_array($paper->status,['approved','locked']))
        <div class="stamp">VETTED &amp; APPROVED</div>
    @endif
    <div class="head">
        <h1>{{ $school->school_name ?? 'School' }}</h1>
        <div class="sub">{{ $school->school_address ?? '' }}</div>
        @if($school->school_motto ?? false)<div class="sub">"{{ $school->school_motto }}"</div>@endif
    </div>

    <div class="title">{{ $paper->title }}</div>
    <div class="bar">
        <span><strong>Class:</strong> {{ $label }}</span>
        <span><strong>Type:</strong> {{ $paper->typeLabel() }}</span>
    </div>
    <div class="bar">
        <span><strong>Time allowed:</strong> {{ $paper->duration_minutes ? $paper->duration_minutes.' minutes' : '________' }}</span>
        <span><strong>Total marks:</strong> {{ rtrim(rtrim(number_format($paper->total_marks,2),'0'),'.') }}</span>
    </div>
    <div class="bar"><span><strong>Name:</strong> ______________________________</span><span><strong>Date:</strong> __________</span></div>

    @if($paper->instructions)<div class="instr">{{ $paper->instructions }}</div>@endif

    @php $lastSection = null; @endphp
    @foreach($paper->questions as $i => $q)
        @if($q->section && $q->section !== $lastSection)
            <div class="sec">Section {{ $q->section }}</div>
            @php $lastSection = $q->section; @endphp
        @endif
        <div class="q">
            <span class="marks">[{{ rtrim(rtrim(number_format($q->marks,2),'0'),'.') }}]</span>
            <span class="num">{{ $q->number ?: ($i+1) }}.</span> {{ $q->question }}
            @if($q->type==='objective' && $q->options)
                <div class="opts">@foreach($q->options as $L=>$opt)<span>({{ $L }}) {{ $opt }}</span>@endforeach</div>
            @endif
        </div>
    @endforeach

    <div class="foot">
        <span>Prepared by the subject teacher</span>
        <span>{{ $vetter ? 'Vetted by: '.$vetter : 'Awaiting vetting' }}@if($paper->vetted_at) · {{ $paper->vetted_at->format('d M Y') }}@endif</span>
    </div>
</div>
</body></html>
