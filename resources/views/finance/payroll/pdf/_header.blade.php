<table class="hdr"><tr>
    <td style="width:60px">@if($logo)<img src="{{ $logo }}" style="height:52px">@endif</td>
    <td>
        <div class="school">{{ $employer['employer_name'] ?? ($school->school_name ?? '') }}</div>
        <div class="muted">{{ $school->school_address ?? '' }}</div>
        @php $phonesStr = is_array($school->school_phones ?? null) ? implode(', ', $school->school_phones) : ($school->school_phones ?? ''); @endphp
        <div class="muted">{{ $phonesStr }} {{ !empty($school->school_email) ? '· ' . $school->school_email : '' }}</div>
        @if(!empty($employer['tin']))<div class="muted">Employer TIN: {{ $employer['tin'] }}</div>@endif
    </td>
    <td class="doc-title">{{ $docTitle }}@if(!empty($docSub))<div class="muted" style="font-weight:normal">{{ $docSub }}</div>@endif</td>
</tr></table>
