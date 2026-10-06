@php $m = fn ($v) => '₦' . number_format((float) $v, 2); @endphp
<!doctype html><html><head><meta charset="utf-8">@include('finance.payroll.pdf._style')</head><body>
@include('finance.payroll.pdf._header', ['docTitle' => strtoupper($title), 'docSub' => $label])
<table class="grid">
    <tr><th style="width:18%">Name</th><td style="width:32%">{{ $staff->name }}</td><th style="width:18%">Staff ID</th><td>{{ $staff->employmentid ?? '—' }}</td></tr>
    @if($kind === 'pension')
        <tr><th>Pension company</th><td>{{ $data['pfa'] ?? ($profile->pfa_name ?? '—') }}</td><th>RSA PIN</th><td>{{ $data['rsa'] ?? ($profile->rsa_pin ?? '—') }}</td></tr>
    @else
        <tr><th>TIN</th><td>{{ $data['tin'] ?? ($profile->tin ?? '—') }}</td><th>Tax state</th><td>{{ isset($data['states']) && count($data['states']) ? implode(', ', $data['states']->all()) : ($profile->tax_state ?? '—') }}</td></tr>
    @endif
</table>

@if($kind === 'tax')
    <table class="grid">
        <tr><th>Month</th><th class="r">Gross pay</th><th class="r">Taxable pay</th><th class="r">Pension</th><th class="r">NHF</th><th class="r">NHIA</th><th class="r">PAYE</th></tr>
        @foreach($data['rows'] as $r)<tr><td>{{ $r->month }}</td><td class="r">{{ $m($r->gross) }}</td><td class="r">{{ $m($r->taxable) }}</td><td class="r">{{ $m($r->pension) }}</td><td class="r">{{ $m($r->nhf) }}</td><td class="r">{{ $m($r->nhia) }}</td><td class="r">{{ $m($r->paye) }}</td></tr>@endforeach
        <tr class="tot"><td>Total ({{ $data['totals']['months'] }} months)</td><td class="r">{{ $m($data['totals']['gross']) }}</td><td class="r">{{ $m($data['totals']['taxable']) }}</td><td class="r">{{ $m($data['totals']['pension']) }}</td><td class="r">{{ $m($data['totals']['nhf']) }}</td><td class="r">{{ $m($data['totals']['nhia']) }}</td><td class="r">{{ $m($data['totals']['paye']) }}</td></tr>
    </table>
@elseif($kind === 'pension')
    <table class="grid">
        <tr><th>Month</th><th class="r">Pensionable pay</th><th class="r">Your part</th><th class="r">School's part</th><th class="r">Total</th><th class="r">Running total</th><th>Paid to PFA</th></tr>
        @foreach($data['rows'] as $r)<tr><td>{{ $r->month }}</td><td class="r">{{ $m($r->base) }}</td><td class="r">{{ $m($r->employee) }}</td><td class="r">{{ $m($r->employer) }}</td><td class="r">{{ $m($r->total) }}</td><td class="r">{{ $m($r->running) }}</td><td>{{ $r->remitted === null ? '—' : ($r->remitted ? 'Yes ' . $r->remitted_on : 'Not yet') }}</td></tr>@endforeach
        <tr class="tot"><td>Total</td><td></td><td class="r">{{ $m($data['totals']['employee']) }}</td><td class="r">{{ $m($data['totals']['employer']) }}</td><td class="r">{{ $m($data['totals']['total']) }}</td><td></td><td></td></tr>
    </table>
@else
    <table class="grid">
        <tr><th>Month</th><th class="r">Gross pay</th><th class="r">PAYE</th><th class="r">Pension</th><th class="r">Other deductions</th><th class="r">Net pay</th></tr>
        @foreach($data['rows'] as $r)
            @php $other = (float) $r->total_deductions - (float) $r->paye_tax - (float) $r->employee_pension; @endphp
            <tr><td>{{ \Carbon\Carbon::parse($r->start_date)->format('M Y') }}</td><td class="r">{{ $m($r->total_earnings) }}</td><td class="r">{{ $m($r->paye_tax) }}</td><td class="r">{{ $m($r->employee_pension) }}</td><td class="r">{{ $m($other) }}</td><td class="r">{{ $m($r->net_pay) }}</td></tr>
        @endforeach
        <tr class="tot"><td>Total ({{ $data['rows']->count() }} months)</td><td class="r">{{ $m($data['rows']->sum('total_earnings')) }}</td><td class="r">{{ $m($data['rows']->sum('paye_tax')) }}</td><td class="r">{{ $m($data['rows']->sum('employee_pension')) }}</td><td class="r">{{ $m($data['rows']->sum('total_deductions') - $data['rows']->sum('paye_tax') - $data['rows']->sum('employee_pension')) }}</td><td class="r">{{ $m($data['rows']->sum('net_pay')) }}</td></tr>
    </table>
    @if($data['rows']->count())<p>Average monthly net pay: <b>{{ $m($data['rows']->avg('net_pay')) }}</b> · average gross: <b>{{ $m($data['rows']->avg('total_earnings')) }}</b></p>@endif
@endif

<p class="muted" style="margin-top:14px">Figures are taken from approved payroll records. Issued {{ $generated->format('d M Y') }}{{ !empty($employer['signatory_name']) ? ' by ' . $employer['signatory_name'] . ', ' . ($employer['signatory_title'] ?? '') : '' }}.</p>
<div class="foot">{{ $staff->name }} · {{ $title }} · {{ $label }}</div>
</body></html>
