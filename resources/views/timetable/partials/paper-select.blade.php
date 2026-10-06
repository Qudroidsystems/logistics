{{--
    Shared paper-size picker.
    Expects: $selectId (string), $selected (string, optional), $labelClass (string, optional)
--}}
@php
    $selectId   = $selectId   ?? 'paperSize';
    $selected   = $selected   ?? 'a3';
    $labelClass = $labelClass ?? 'form-label fw-semibold';
    $labels     = \App\Http\Controllers\TimetableController::PAPER_LABELS;
    $allSizes   = \App\Http\Controllers\TimetableController::PAPER_SIZES;

    // Common sizes float to the top of the list; the long tail goes into
    // an optgroup so the picker stays scannable.
    $primary = ['a4','a3','a2','a1','a0','b4','b3','b2','b1','letter','legal','ledger','tabloid'];
    $primarySizes = array_values(array_intersect($primary, $allSizes));
    $extraSizes   = array_values(array_diff($allSizes, $primarySizes));
@endphp

<label class="{{ $labelClass }}" for="{{ $selectId }}">Paper Size</label>
<select class="form-select" id="{{ $selectId }}" name="paper">
    @foreach($primarySizes as $size)
        @php $meta = $labels[$size] ?? [strtoupper($size), '', '']; @endphp
        <option value="{{ $size }}" {{ $selected === $size ? 'selected' : '' }}>
            {{ $meta[0] }}@if(!empty($meta[1])) — {{ $meta[1] }}@endif@if(!empty($meta[2])) ({{ $meta[2] }})@endif
        </option>
    @endforeach

    @if(!empty($extraSizes))
        <optgroup label="More sizes">
            @foreach($extraSizes as $size)
                @php $meta = $labels[$size] ?? [strtoupper($size), '', '']; @endphp
                <option value="{{ $size }}" {{ $selected === $size ? 'selected' : '' }}>
                    {{ $meta[0] }}@if(!empty($meta[1])) — {{ $meta[1] }}@endif
                </option>
            @endforeach
        </optgroup>
    @endif
</select>
<small class="text-muted d-block mt-1">
    A1/A0 suit a whole-school master grid printed as a wall chart.
    Sizes above A3 may be slower to render.
</small>