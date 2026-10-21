<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay by e-Transfer | {{ config('app.name') }}</title>
    <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/css/icons.min.css') }}" rel="stylesheet">
    @if(in_array($p['state'], ['waiting', 'sent'], true))<meta http-equiv="refresh" content="60">@endif
    <style>
        body { background: #f1f5f9; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .box { max-width: 560px; margin: 32px auto; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(15, 23, 42, .08); padding: 28px; }
        .row-kv { display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid #eef2f7; }
        .row-kv:last-child { border-bottom: 0; }
        .kv-label { color: #64748b; }
        .kv-value { font-weight: 600; text-align: right; word-break: break-all; }
        .copy { border: 0; background: #eff6ff; color: #2563eb; border-radius: 8px; font-size: 12px; padding: 2px 8px; margin-left: 6px; }
        .ref { font-family: ui-monospace, Menlo, monospace; font-size: 1.15rem; letter-spacing: .04em; }
        .amount { font-size: 2rem; font-weight: 800; }
    </style>
</head>
<body>
<div class="box">
    <div class="text-muted small mb-1">{{ config('app.name') }} · {{ $p['for'] }}</div>
    <h1 class="h4 mb-3">Pay by Interac e-Transfer</h1>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @if($p['state'] === 'received')
        <div class="alert alert-success mb-0"><i class="ri-checkbox-circle-line me-1"></i>
            We received your e-Transfer of {{ $p['amount_text'] }}. {{ $p['applied_to'] === 'wallet' ? 'It arrived after the booking closed, so it is in your wallet.' : 'Your delivery is going ahead.' }} Thank you.
        </div>
    @elseif($p['state'] === 'rejected')
        <div class="alert alert-danger mb-0">We could not match this e-Transfer: {{ $p['reason'] }}. Please contact us if you already sent it.</div>
    @elseif($p['state'] === 'expired')
        <div class="alert alert-warning mb-0">This payment is closed because no e-Transfer arrived in time. If you sent it, contact us with the reference <span class="ref">{{ $p['reference'] }}</span> and we will add it to your wallet.</div>
    @else
        <div class="amount mb-1">{{ $p['amount_text'] }}</div>
        <p class="text-muted">Send exactly this amount from your bank's app or website. We hold your booking until {{ $p['deadline']->timezone(config('app.timezone'))->format('D j M, g:i A') }}.</p>

        <div class="mb-3">
            <div class="row-kv"><span class="kv-label">Send to</span><span class="kv-value"><span id="email">{{ $p['to']['email'] }}</span><button class="copy" data-copy="email">Copy</button></span></div>
            <div class="row-kv"><span class="kv-label">Recipient name</span><span class="kv-value">{{ $p['to']['name'] }}</span></div>
            <div class="row-kv"><span class="kv-label">Amount</span><span class="kv-value">{{ $p['amount_text'] }}</span></div>
            <div class="row-kv"><span class="kv-label">Message / memo</span><span class="kv-value"><span class="ref" id="ref">{{ $p['reference'] }}</span><button class="copy" data-copy="ref">Copy</button></span></div>
            @if($p['to']['question'])
                <div class="row-kv"><span class="kv-label">Security question</span><span class="kv-value">{{ $p['to']['question'] }}</span></div>
                <div class="row-kv"><span class="kv-label">Answer</span><span class="kv-value">{{ $p['to']['answer'] }}</span></div>
            @else
                <div class="row-kv"><span class="kv-label">Security question</span><span class="kv-value">Not needed (Autodeposit is on)</span></div>
            @endif
        </div>
        <p class="small text-muted">Put the reference in the message so we can match your payment. We never ask for your bank login.</p>

        @if($p['state'] === 'sent')
            <div class="alert alert-info mb-0"><i class="ri-time-line me-1"></i>Thanks, you told us it is on its way. We will email you as soon as it arrives.</div>
        @else
            <form method="POST" action="{{ route('etransfer.sent', $p['reference']) }}" class="border-top pt-3">@csrf
                <label class="form-label small">Already sent it? Tell us the name it came from (optional) so we can find it faster.</label>
                <div class="d-flex gap-2">
                    <input name="sender_name" class="form-control" maxlength="120" placeholder="Name on your bank account">
                    <button class="btn btn-primary text-nowrap">I've sent it</button>
                </div>
            </form>
        @endif
    @endif
</div>
<script>
document.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
        var t = document.getElementById(b.dataset.copy).textContent.trim();
        if (navigator.clipboard) { navigator.clipboard.writeText(t).then(function () { b.textContent = 'Copied'; setTimeout(function () { b.textContent = 'Copy'; }, 1500); }); }
    });
});
</script>
</body>
</html>
