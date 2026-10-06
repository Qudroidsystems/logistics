{{-- resources/views/curriculum/notes/print.blade.php — printable lesson note --}}
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lesson Note — {{ $note->title }}</title>
<style>
  body{font-family:Georgia,'Times New Roman',serif;color:#1f2937;margin:0;background:#f1f5f9}
  .sheet{max-width:820px;margin:16px auto;background:#fff;padding:36px 44px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
  h1{font-size:1.4rem;margin:0 0 2px;color:#1e3a5f}
  .meta{color:#6b7280;font-size:.85rem;margin-bottom:14px}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:6px 24px;font-size:.9rem;margin-bottom:14px}
  .grid div span{color:#6b7280}
  h2{font-size:1rem;color:#1e3a5f;border-bottom:2px solid #c8a24a;padding-bottom:3px;margin:16px 0 8px}
  .body{font-size:.95rem;line-height:1.55}
  ul{margin:4px 0 0 18px}
  .pills span{display:inline-block;background:#eef2ff;color:#3730a3;border-radius:999px;padding:2px 10px;font-size:.8rem;margin:2px 4px 2px 0}
  .toolbar{max-width:820px;margin:12px auto 0;text-align:right}
  .btn{display:inline-block;background:#1e3a5f;color:#fff;border:0;border-radius:8px;padding:8px 16px;font-size:.9rem;cursor:pointer;text-decoration:none}
  @media print{.toolbar{display:none}body{background:#fff}.sheet{box-shadow:none;margin:0;max-width:none}}
</style>
</head><body>
<div class="toolbar"><button class="btn" onclick="window.print()">Print / Save as PDF</button></div>
<div class="sheet">
    <h1>Lesson Note</h1>
    <div class="meta">{{ $label }} @if($note->week_no)· Week {{ $note->week_no }}@endif</div>

    <div class="grid">
        <div><span>Topic / title:</span> {{ $note->title }}</div>
        <div><span>Subject:</span> {{ optional($note->subject)->subject ?? '—' }}</div>
        <div><span>Status:</span> {{ ucfirst($note->status) }}</div>
        <div><span>Delivered:</span> {{ $note->delivered_on ? $note->delivered_on->format('d M Y') : '—' }}</div>
    </div>

    @if($note->topics->count())
        <h2>Topics covered</h2>
        <ul>@foreach($note->topics as $t)<li>{{ $t->title }}@if($t->week_no) (Wk {{ $t->week_no }})@endif</li>@endforeach</ul>
    @endif

    @if($note->objectives)
        <h2>Objectives</h2>
        <div class="body">{!! nl2br(e($note->objectives)) !!}</div>
    @endif

    @if($note->content)
        <h2>Content / procedure</h2>
        <div class="body">{!! $note->content !!}</div>
    @endif

    @if($note->materials)
        <h2>Materials / teaching aids</h2>
        <div class="body">{!! nl2br(e($note->materials)) !!}</div>
    @endif

    @if(!empty($methods))
        <h2>Teaching methods</h2>
        <div class="pills">@foreach($methods as $m)<span>{{ $m }}</span>@endforeach</div>
    @endif

    @if($note->review_comment)
        <h2>HOD comment</h2>
        <div class="body">{{ $note->review_comment }}</div>
    @endif
</div>
</body></html>
