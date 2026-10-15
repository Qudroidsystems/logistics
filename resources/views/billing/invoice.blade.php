<!doctype html>
<html><head><meta charset="utf-8"><title>Invoice {{ $invoice->number }}</title>
@php
    $naira = fn ($k) => 'NGN '.number_format(((int) $k) / 100, 2);
    $brand = json_decode($provider->branding ?? 'null', true) ?: [];
    $colour = $brand['primary_color'] ?? '#0F766E';
    $date = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('j M Y');
@endphp
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; margin: 0; }
    .head { background: {{ $colour }}; color: #fff; padding: 18px 24px; }
    .head h1 { margin: 0; font-size: 20px; }
    .wrap { padding: 18px 24px; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 2px 0; vertical-align: top; }
    .lines th, .lines td { padding: 5px 4px; border-bottom: 1px solid #e5e5e5; text-align: left; vertical-align: top; }
    .lines td.r, .lines th.r { text-align: right; }
    .total td { font-weight: bold; font-size: 12px; border-top: 2px solid #222; border-bottom: none; }
    .muted { color: #777; }
    .stamp { display: inline-block; padding: 2px 8px; border: 2px solid; font-weight: bold; }
    h3 { font-size: 11px; margin: 16px 0 6px; }
</style></head>
<body>
<div class="head">
    <h1>{{ $provider->display_name }}</h1>
    <div>Invoice {{ $invoice->number }}</div>
</div>
<div class="wrap">
    <table class="meta"><tr>
        <td style="width:50%">
            <strong>Billed to</strong><br>{{ $account->name }}
            @if($account->billing_address)<br>{{ $account->billing_address }}@endif
            @if($account->billing_email)<br><span class="muted">{{ $account->billing_email }}</span>@endif
        </td>
        <td>
            <table>
                <tr><td class="muted">Issued</td><td>{{ $date($invoice->created_at) }}</td></tr>
                <tr><td class="muted">Period</td><td>{{ $date($invoice->period_start) }} to {{ $date($invoice->period_end) }}</td></tr>
                <tr><td class="muted">Due</td><td>{{ $date($invoice->due_on) }}</td></tr>
                <tr><td class="muted">Status</td><td>
                    @if($invoice->status === 'paid')<span class="stamp" style="color:#15803d">PAID</span>
                    @elseif($overdue)<span class="stamp" style="color:#b91c1c">OVERDUE</span>
                    @else Unpaid @endif
                </td></tr>
            </table>
        </td>
    </tr></table>

    <h3>Deliveries</h3>
    <table class="lines">
        <thead><tr><th>Date</th><th>Order</th><th>Your ref</th><th>From</th><th>To</th><th class="r">Amount</th></tr></thead>
        <tbody>
        @foreach($lines as $l)
            <tr>
                <td>{{ $date($l['date']) }}</td>
                <td>{{ $l['order'] }}@if(! in_array($l['status'], ['delivered', 'confirmed', 'completed'], true))<br><span class="muted">{{ str_replace('_', ' ', $l['status']) }}</span>@endif</td>
                <td>{{ $l['reference'] }}</td>
                <td>{{ $l['from'] }}</td>
                <td>{{ $l['to'] }}</td>
                <td class="r">{{ $naira($l['amount']) }}</td>
            </tr>
        @endforeach
        <tr class="total"><td colspan="5">Total ({{ count($lines) }} {{ count($lines) === 1 ? 'delivery' : 'deliveries' }})</td><td class="r">{{ $naira($invoice->total) }}</td></tr>
        </tbody>
    </table>

    @if($bank && $invoice->status !== 'paid')
        <h3>How to pay</h3>
        <p>Bank transfer to {{ $bank->account_name }}, {{ $bank->bank_name }}, account {{ $bank->account_number }}. Use {{ $invoice->number }} as the reference.</p>
    @endif
    <p class="muted">Deliveries closed as failed or cancelled show what is owed under the agreed terms, after any refund.</p>
</div>
</body></html>
