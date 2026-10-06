@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $t = $data['totals']; @endphp
<!doctype html><html><head><meta charset="utf-8">@include('finance.payroll.pdf._style')</head><body>
@include('finance.payroll.pdf._header', ['docTitle' => 'ANNUAL TAX DEDUCTION CERTIFICATE', 'docSub' => 'Year ended 31 December ' . $year])

<p>This is to certify that the employee named below was paid by <b>{{ $employer['employer_name'] ?? ($school->school_name ?? '') }}</b> during {{ $year }},
and that Pay-As-You-Earn (PAYE) income tax was deducted from their pay as shown.</p>

<table class="grid">
    <tr><th style="width:22%">Employee</th><td style="width:28%">{{ $staff->name }}</td><th style="width:22%">Staff ID</th><td>{{ $staff->employmentid ?? '—' }}</td></tr>
    <tr><th>Employee TIN</th><td>{{ $data['tin'] ?? ($profile->tin ?? '—') }}</td><th>State tax authority</th><td>{{ count($data['states']) ? implode(', ', $data['states']->all()) : ($profile->tax_state ?? '—') }}</td></tr>
    <tr><th>Employer TIN</th><td>{{ $employer['tin'] ?? '—' }}</td><th>Employer tax office</th><td>{{ $employer['tax_office'] ?? '—' }}</td></tr>
    <tr><th>Months paid</th><td>{{ $t['months'] }}</td><th>Pension (RSA PIN)</th><td>{{ $pension['rsa'] ?? ($profile->rsa_pin ?? '—') }}</td></tr>
</table>

<table class="grid">
    <tr><th>Summary for {{ $year }}</th><th class="r">Amount</th></tr>
    <tr><td>Total gross pay</td><td class="r">{{ $m($t['gross']) }}</td></tr>
    <tr><td>Total taxable pay</td><td class="r">{{ $m($t['taxable']) }}</td></tr>
    <tr><td>Pension contributions (employee)</td><td class="r">{{ $m($t['pension']) }}</td></tr>
    <tr><td>National Housing Fund</td><td class="r">{{ $m($t['nhf']) }}</td></tr>
    <tr><td>Health insurance (NHIA)</td><td class="r">{{ $m($t['nhia']) }}</td></tr>
    <tr><td>Employer pension contribution</td><td class="r">{{ $m($pension['totals']['employer']) }}</td></tr>
    <tr class="tot"><td>Total PAYE deducted</td><td class="r">{{ $m($t['paye']) }}</td></tr>
</table>

<table class="grid">
    <tr><th>Month</th><th class="r">Gross pay</th><th class="r">Pension</th><th class="r">NHF</th><th class="r">PAYE</th></tr>
    @foreach($data['rows'] as $r)<tr><td>{{ $r->month }}</td><td class="r">{{ $m($r->gross) }}</td><td class="r">{{ $m($r->pension) }}</td><td class="r">{{ $m($r->nhf) }}</td><td class="r">{{ $m($r->paye) }}</td></tr>@endforeach
</table>

<table style="width:100%;margin-top:18px"><tr>
    <td style="vertical-align:bottom">
        ______________________________<br>
        {{ $employer['signatory_name'] ?? 'Authorised signatory' }}<br><span class="muted">{{ $employer['signatory_title'] ?? '' }} · {{ $generated->format('d M Y') }}</span>
        <p class="muted" style="font-size:9px;margin-top:10px">Certificate code {{ $code }}. Check it at {{ $url }}<br>
        This certificate shows tax deducted by the employer. Whether it has been paid over to the tax authority is shown on the employer's remittance receipts.</p>
    </td>
    <td style="width:100px;text-align:right">@if($qr)<img src="{{ $qr }}" style="width:92px;height:92px">@endif</td>
</tr></table>
<div class="foot">{{ $staff->name }} · Tax certificate {{ $year }} · {{ $code }}</div>
</body></html>
