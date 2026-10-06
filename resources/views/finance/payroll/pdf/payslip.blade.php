@php $m = fn ($v) => '₦' . number_format((float) $v, 2); $gross = $earnings->sum('amount'); $ded = $deductions->sum('amount'); @endphp
<!doctype html><html><head><meta charset="utf-8">@include('finance.payroll.pdf._style')</head><body>
@include('finance.payroll.pdf._header', ['docTitle' => 'PAYSLIP', 'docSub' => $run->period_name])
@unless($final)<div style="margin-bottom:8px"><span class="stamp">PROVISIONAL — NOT YET FINAL</span></div>@endunless

<table class="grid">
    <tr><th style="width:18%">Name</th><td style="width:32%">{{ $staff->name ?? '' }}</td><th style="width:18%">Staff ID</th><td>{{ $staff->employmentid ?? '—' }}</td></tr>
    <tr><th>Pay period</th><td>{{ \Carbon\Carbon::parse($run->start_date)->format('d M') }} – {{ \Carbon\Carbon::parse($run->end_date)->format('d M Y') }}{{ ($run->proration ?? 1) < 1 ? ' (part month ' . round($run->proration * 100) . '%)' : '' }}</td><th>Pay date</th><td>{{ $run->payment_date ? \Carbon\Carbon::parse($run->payment_date)->format('d M Y') : '—' }}</td></tr>
    <tr><th>Bank</th><td>{{ $bank['name'] ?? '—' }} {{ $bank['account'] ?? '' }}</td><th>TIN / Tax state</th><td>{{ $run->tin ?: '—' }} / {{ $run->tax_state ?: '—' }}</td></tr>
    <tr><th>Pension</th><td>{{ $run->pfa_name ?: '—' }} {{ $run->rsa_pin ? '· ' . $run->rsa_pin : '' }}</td><th>Department</th><td>{{ $staff->department ?? '—' }}</td></tr>
</table>

<table style="width:100%"><tr>
    <td style="width:50%;vertical-align:top;padding-right:6px">
        <table class="grid"><tr><th>Earnings</th><th class="r">Amount</th></tr>
            @foreach($earnings as $l)<tr><td>{{ $l->label }}</td><td class="r">{{ $m($l->amount) }}</td></tr>@endforeach
            <tr class="tot"><td>Gross pay</td><td class="r">{{ $m($gross) }}</td></tr>
        </table>
    </td>
    <td style="width:50%;vertical-align:top;padding-left:6px">
        <table class="grid"><tr><th>Deductions</th><th class="r">Amount</th></tr>
            @forelse($deductions as $l)<tr><td>{{ $l->label }}</td><td class="r">{{ $m($l->amount) }}</td></tr>@empty<tr><td colspan="2" class="muted">None</td></tr>@endforelse
            <tr class="tot"><td>Total deductions</td><td class="r">{{ $m($ded) }}</td></tr>
        </table>
    </td>
</tr></table>

<table class="grid"><tr><td class="b" style="width:70%">NET PAY</td><td class="r net">{{ $m($run->net_pay) }}</td></tr></table>

<table style="width:100%"><tr>
    <td style="width:50%;vertical-align:top;padding-right:6px">
        @if($employerLines->isNotEmpty())
            <table class="grid"><tr><th colspan="2">Paid by the school for you (not deducted)</th></tr>
                @foreach($employerLines as $l)<tr><td>{{ $l->label }}</td><td class="r">{{ $m($l->amount) }}</td></tr>@endforeach
            </table>
        @endif
        <table class="grid"><tr><th colspan="2">Year to date ({{ \Carbon\Carbon::parse($run->start_date)->year }})</th></tr>
            <tr><td>Gross pay</td><td class="r">{{ $m($ytd['gross']) }}</td></tr>
            <tr><td>PAYE</td><td class="r">{{ $m($ytd['paye']) }}</td></tr>
            <tr><td>Pension (your part)</td><td class="r">{{ $m($ytd['pension']) }}</td></tr>
            <tr><td>Net pay</td><td class="r">{{ $m($ytd['net']) }}</td></tr>
        </table>
    </td>
    <td style="width:50%;vertical-align:top;padding-left:6px">
        @if($tax)
            <table class="grid"><tr><th colspan="2">How PAYE was worked out ({{ $tax['rule'] ?? '' }})</th></tr>
                <tr><td>Taxable pay for the year</td><td class="r">{{ $m($tax['annual_gross'] ?? 0) }}</td></tr>
                @foreach(($tax['reliefs'] ?? []) as $k => $v)<tr><td class="muted">less {{ ['pension' => 'pension', 'nhf' => 'NHF', 'nhia' => 'health insurance', 'rent' => 'rent relief', 'other' => 'other reliefs', 'cra' => 'CRA'][$k] ?? $k }}</td><td class="r muted">−{{ $m($v) }}</td></tr>@endforeach
                <tr><td>Chargeable income</td><td class="r">{{ $m($tax['chargeable'] ?? 0) }}</td></tr>
                <tr><td>Tax for the year</td><td class="r">{{ $m($tax['annual_tax'] ?? 0) }}</td></tr>
                <tr class="tot"><td>PAYE this month</td><td class="r">{{ $m($run->paye_tax) }}</td></tr>
            </table>
        @endif
    </td>
</tr></table>

<table style="width:100%;margin-top:6px"><tr>
    <td class="muted" style="font-size:9px;vertical-align:bottom">
        @if($verifyUrl)Check this payslip: {{ $verifyUrl }}<br>@endif
        This payslip was produced by the school portal{{ !empty($employer['signatory_name']) ? ' for ' . $employer['signatory_name'] . ', ' . ($employer['signatory_title'] ?? '') : '' }}.
    </td>
    <td style="width:90px;text-align:right">@if($qr)<img src="{{ $qr }}" style="width:82px;height:82px">@endif</td>
</tr></table>
<div class="foot">Confidential — {{ $staff->name ?? '' }} · {{ $run->period_name }} · generated {{ now()->format('d M Y H:i') }}</div>
</body></html>
