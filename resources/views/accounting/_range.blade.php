{{-- Date filter for reports. Needs $mode ('range'|'asat'), $from/$to or $asAt --}}
<form method="GET" class="d-flex flex-wrap gap-2 align-items-center d-print-none">
    @foreach(request()->except(['from', 'to', 'as_at', 'format', 'page']) as $k => $v)@if(!is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
    @if(($mode ?? 'range') === 'asat')
        <label class="small mb-0">As at</label><input type="date" name="as_at" value="{{ $asAt }}" class="form-control form-control-sm" style="width:160px">
    @else
        <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm" style="width:150px" aria-label="From"><span class="small">to</span>
        <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm" style="width:150px" aria-label="To">
    @endif
    <button class="btn btn-sm btn-light">Show</button>
    @unless($noCsv ?? false)<a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn btn-sm btn-light" title="Excel"><i class="ri-file-excel-2-line"></i></a>@endunless
    <button type="button" class="btn btn-sm btn-light" onclick="window.print()"><i class="ri-printer-line"></i></button>
</form>
