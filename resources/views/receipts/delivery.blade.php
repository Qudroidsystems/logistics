<!doctype html>
<html><head><meta charset="utf-8"><title>Receipt {{ $number }}</title>
@php
    $naira = fn ($k) => 'NGN '.number_format(((int) $k) / 100, 2);
    $colour = $brand->primary_color ?? '#0F766E';
@endphp
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 0; }
    .head { background: {{ $colour }}; color: #fff; padding: 18px 24px; }
    .head h1 { margin: 0; font-size: 20px; }
    .wrap { padding: 18px 24px; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 2px 0; vertical-align: top; }
    .lines th, .lines td { padding: 6px 4px; border-bottom: 1px solid #e5e5e5; text-align: left; }
    .lines td.r, .lines th.r { text-align: right; }
    .total td { font-weight: bold; font-size: 13px; border-top: 2px solid #222; border-bottom: none; }
    .muted { color: #777; }
    h3 { font-size: 12px; margin: 16px 0 6px; }
</style></head>
<body>
<div class="head">
    <h1>{{ $brand->name ?? config('app.name') }}</h1>
    <div>Receipt {{ $number }}</div>
</div>
<div class="wrap">
    <table class="meta"><tr>
        <td style="width:50%">
            <strong>Billed to</strong><br>{{ $order->customer_name }}<br><span class="muted">{{ $order->customer_email }}</span>
        </td>
        <td>
            <strong>Order</strong> {{ $order->order_number }}<br>
            @if($agreement)<strong>Agreement</strong> {{ $agreement }}<br>@endif
            @if($shipment)<strong>Tracking</strong> {{ $shipment->tracking_code }}<br>@endif
            <strong>Provider</strong> {{ $order->provider }}<br>
            <strong>Paid</strong> {{ \Carbon\Carbon::parse($order->placed_at)->format('j M Y, H:i') }}
            @if($payment)<br><span class="muted">{{ ucfirst($payment->gateway) }}{{ $payment->channel ? ' · '.$payment->channel : '' }} · {{ $payment->reference }}</span>
            @elseif($order->payment_method === 'wallet')<br><span class="muted">Paid from wallet</span>@endif
        </td>
    </tr></table>

    @if(count($stops))
    <h3>Route</h3>
    <table class="lines">
        <tr><th>#</th><th>Stop</th><th>Address</th><th>Status</th></tr>
        @foreach($stops as $st)
        <tr><td>{{ $st->seq }}</td><td>{{ $st->type === 'pickup' ? 'Pickup' : ($st->type === 'dropoff' ? 'Drop-off' : ucfirst($st->type)) }}</td><td>{{ $st->line1 }}{{ $st->contact_name ? ' ('.$st->contact_name.')' : '' }}</td><td>{{ $st->status }}</td></tr>
        @endforeach
    </table>
    @if($shipment && $shipment->distance_m)<div class="muted" style="margin-top:4px">Route distance about {{ number_format($shipment->distance_m / 1000, 1) }} km</div>@endif
    @endif

    <h3>Charges</h3>
    <table class="lines">
        <tr><th>Item</th><th class="r">Amount</th></tr>
        @foreach($lines as $l)
            @if($l['label'] === 'Discount' && count($discount_lines))
                @foreach($discount_lines as $dl)<tr><td>{{ $dl['label'] }}</td><td class="r">-{{ $naira($dl['amount']) }}</td></tr>@endforeach
            @else
                <tr><td>{{ $l['label'] }}</td><td class="r">{{ $l['amount'] < 0 ? '-'.$naira(-$l['amount']) : $naira($l['amount']) }}</td></tr>
            @endif
        @endforeach
        @if((int) $order->tax > 0)<tr><td>Tax</td><td class="r">{{ $naira($order->tax) }}</td></tr>@endif
        <tr class="total"><td>Total paid</td><td class="r">{{ $naira($order->total) }}</td></tr>
    </table>
    @if((int) $order->subtotal > 0)<p class="muted">Any goods budget the shopper did not spend is returned to your wallet.</p>@endif

    <p class="muted" style="margin-top:24px">Issued {{ \Carbon\Carbon::parse($issued_at)->format('j M Y') }}. Keep this receipt for your records.</p>
</div>
</body></html>
