<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Partner API · {{ config('app.name') }}</title>
@include('developers._style')
</head>
<body><div class="wrap">
<p class="muted" style="margin:0">{{ config('app.name') }} for developers</p>
<h1>Partner API</h1>
<p>Book and track deliveries from your own shop or marketplace. You send us the pickup, drop-off and parcel details; we price it with the best available delivery provider, take payment, assign a driver and tell you what happens through signed webhooks.</p>
<nav><a href="#auth">Authentication</a><a href="#me">Who am I</a><a href="#quote">Quote</a><a href="#create">Create a delivery</a><a href="#cancel">Cancel</a><a href="#failed">Failed deliveries</a><a href="#webhooks">Webhooks</a><a href="#errors">Errors</a></nav>

<h2 id="auth">Authentication</h2>
<p>Every request carries your API key as a bearer token. Keys are issued to verified merchants by {{ config('app.name') }} staff and look like <code>lgk_live_ab12cd34.&lt;secret&gt;</code>. The secret part is shown once, so store it safely; if it leaks, ask us to revoke it and issue another.</p>
<pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>
<p>Test keys and live keys are separate. A test key creates test deliveries that never move real money and send webhooks only to your test endpoints. Each key has scopes (<code>quotes</code>, <code>deliveries</code>, <code>webhooks</code>) and a per-minute request limit; going over it returns <code>429</code> with a <code>Retry-After</code> header.</p>
<p>Base URL: <code>{{ $base }}</code>. Amounts are whole kobo (₦1 = 100). All bodies are JSON.</p>

<h2 id="me">Who am I</h2>
<p><span class="tag">GET</span><code>/me</code></p>
<p>Returns your merchant name, your merchant ID (the code customers see), the key's environment and the marketplace badge text you may show.</p>
<pre><code>curl {{ $base }}/me -H "Authorization: Bearer YOUR_API_KEY"</code></pre>

<h2 id="quote">Quote a delivery</h2>
<p><span class="tag">POST</span><code>/quotes</code> &nbsp; scope <code>quotes</code></p>
<p>Prices a delivery with the best eligible provider without committing to anything. Use it to show the delivery fee at checkout.</p>
<table>
<tr><th>Field</th><th></th><th>Notes</th></tr>
<tr><td><code>service_type_id</code>, <code>city_id</code></td><td>required</td><td>Ask us for the IDs for your city and service (for example same-day parcel).</td></tr>
<tr><td><code>pickup</code>, <code>dropoff</code></td><td>required</td><td>Objects with <code>line1</code>, <code>lat</code>, <code>lng</code>; optional <code>contact_name</code>, <code>contact_phone</code>. The drop-off phone gets the delivery code and tracking link by text.</td></tr>
<tr><td><code>weight_g</code>, <code>fragile</code>, <code>declared_value</code></td><td>optional</td><td>Weight in grams, a boolean, and goods value in kobo.</td></tr>
<tr><td><code>vehicle_type_id</code>, <code>packages</code></td><td>optional</td><td>Up to 20 package objects.</td></tr>
</table>
<pre><code>curl -X POST {{ $base }}/quotes \
  -H "Authorization: Bearer YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{"service_type_id":1,"city_id":1,
       "pickup":{"line1":"12 Market Rd","lat":7.80,"lng":6.74},
       "dropoff":{"line1":"4 Hill St","lat":7.83,"lng":6.76,"contact_phone":"08031234567"},
       "weight_g":1500}'</code></pre>
<pre><code>{
  "quote_id": 812,
  "total": 185000,
  "currency": "NGN",
  "distance_m": 4210,
  "breakdown": [ ... ],
  "failed_delivery_terms": { "customer_fee_bp": 5000, "return_to_sender": true, "return_fee_bp": 2000, "driver_paid": true, "wait_minutes": 10 }
}</code></pre>
<p class="muted">A quote is only informational. The price you are charged is calculated again when you create the delivery.</p>

<h2 id="create">Create a delivery</h2>
<p><span class="tag">POST</span><code>/deliveries</code> &nbsp; scope <code>deliveries</code></p>
<p>Takes the same body as a quote plus <code>external_order_id</code> (your own order number, up to 80 characters). That ID is the idempotency key: sending the same one again returns the delivery already created instead of making a second one, so it is safe to retry after a timeout.</p>
<p>How you pay depends on your settlement mode. With a <b>prepaid wallet</b> the delivery is paid from your wallet immediately and dispatch starts. With <b>pay at checkout</b> the response contains a <code>payment_url</code>; send the payer there and dispatch starts when payment is confirmed.</p>
<pre><code>{
  "agreement": "AG261006K3D9QX",
  "status": "locked",
  "price": 185000,
  "currency": "NGN",
  "payment_url": "https://checkout.paystack.com/...",
  "order": "ORD-000123",
  "tracking_url": "https://.../track/Ab3...",
  "failed_delivery_terms": { ... }
}</code></pre>
<p><code>tracking_url</code> is a live map page you can link your customer to. It needs no login.</p>

<h2 id="cancel">Cancel a delivery</h2>
<p><span class="tag">POST</span><code>/deliveries/{external_order_id}/cancel</code> &nbsp; scope <code>deliveries</code></p>
<p>Optional body: <code>{"reason":"..."}</code>. Before a driver is assigned you get a full refund. After a driver is assigned and before pickup, a cancellation fee applies. Once the parcel is picked up it can no longer be cancelled. The response says which stage applied: <code>stage</code>, <code>fee</code>, <code>refunded</code>.</p>

<h2 id="failed">When a delivery fails</h2>
<p>If the receiver cannot be reached, refuses the parcel or the address is wrong, the driver waits at the drop-off, then reports the failure. What it costs and whether the parcel comes back is fixed per delivery when it is created, and returned in <code>failed_delivery_terms</code> on quotes and deliveries so you can show it to your customer:</p>
<table>
<tr><th>Field</th><th>Meaning</th></tr>
<tr><td><code>wait_minutes</code></td><td>How long the driver must wait at the drop-off before reporting a failure.</td></tr>
<tr><td><code>customer_fee_bp</code></td><td>Share of the delivery price still charged, in basis points (5000 = 50%).</td></tr>
<tr><td><code>return_to_sender</code></td><td>Whether the driver brings the parcel back to your pickup address.</td></tr>
<tr><td><code>return_fee_bp</code></td><td>Extra share of the price charged for the return trip (only when the parcel is returned). The two shares together never exceed 100%.</td></tr>
<tr><td><code>driver_paid</code></td><td>Whether the driver is still paid for the failed trip (affects the provider, not you).</td></tr>
</table>
<p>The rest of what you paid, including unspent amounts, is refunded. You hear about it through the <code>delivery.failed</code> webhook, and <code>delivery.returned</code> when the parcel is back at the pickup address.</p>

<h2 id="webhooks">Webhooks</h2>
<p>Add an endpoint URL (HTTPS) on your merchant page, or give it to {{ config('app.name') }} staff, and you get a signed <code>POST</code> for every event below. Test keys send to your test endpoints only.</p>
<table>
<tr><th>Event</th><th>When</th></tr>
<tr><td><code>delivery.created</code></td><td>The delivery is booked.</td></tr>
<tr><td><code>delivery.assigned</code></td><td>A driver has accepted the job.</td></tr>
<tr><td><code>delivery.driver_arrived</code></td><td>The driver reached the pickup or drop-off.</td></tr>
<tr><td><code>delivery.picked_up</code></td><td>The parcel is on the road.</td></tr>
<tr><td><code>delivery.delivered</code></td><td>Handed over with the delivery code or a photo.</td></tr>
<tr><td><code>delivery.confirmed</code></td><td>The delivery was confirmed, or the confirmation window passed.</td></tr>
<tr><td><code>delivery.failed</code></td><td>The receiver could not be reached (see above). Includes <code>reason</code>.</td></tr>
<tr><td><code>delivery.returned</code></td><td>The parcel is back at the pickup address.</td></tr>
<tr><td><code>delivery.disputed</code>, <code>delivery.dispute_resolved</code></td><td>A problem was reported, then decided (<code>decision</code>).</td></tr>
<tr><td><code>delivery.cancelled</code></td><td>The delivery was cancelled.</td></tr>
</table>
<pre><code>{
  "id": "evt_x8k2...",
  "type": "delivery.delivered",
  "created": "2026-10-06T14:02:11+00:00",
  "data": {
    "order_number": "ORD-000123", "external_order_id": "your-order-77",
    "merchant_code": "MC-000123", "tracking_code": "TRK...", "status": "delivered"
  }
}</code></pre>
<p>Payloads never contain phone numbers, contact details or delivery codes. From your merchant page you can also send yourself a <code>delivery.test</code> event with the same signature, to check your receiver before going live.</p>

<h3>Check the signature</h3>
<p>Each request has an <code>X-Logistics-Signature</code> header of the form <code>t=&lt;unix time&gt;,v1=&lt;hex&gt;</code>, where <code>v1</code> is the HMAC-SHA256 of <code>"&lt;t&gt;.&lt;raw body&gt;"</code> using your endpoint's secret (shown once when the endpoint is created, starts with <code>whsec_</code>). Reject anything whose timestamp is more than 5 minutes old, and compare signatures in constant time.</p>
@verbatim
<pre><code>// PHP
$header = $_SERVER['HTTP_X_LOGISTICS_SIGNATURE'] ?? '';
preg_match('/t=(\d+),v1=([a-f0-9]+)/', $header, $m);
$body = file_get_contents('php://input');
$ok = $m && abs(time() - (int) $m[1]) < 300
   && hash_equals(hash_hmac('sha256', $m[1].'.'.$body, $secret), $m[2]);

// Node.js
const [, t, v1] = /t=(\d+),v1=([a-f0-9]+)/.exec(req.headers['x-logistics-signature']) || [];
const expected = crypto.createHmac('sha256', secret).update(t + '.' + rawBody).digest('hex');
const ok = t && Math.abs(Date.now()/1000 - t) < 300 &&
  crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(v1));</code></pre>
@endverbatim
<p>Answer with any <code>2xx</code> status. Anything else is retried after 1, 5, 30 minutes, 2 hours and 12 hours. Events can arrive more than once and out of order, so use the event <code>id</code> to ignore repeats and the <code>status</code> in the payload as the current state.</p>

<h2 id="errors">Errors</h2>
<table>
<tr><th>Status</th><th>Body</th><th>Meaning</th></tr>
<tr><td>401</td><td><code>{"error":"invalid_api_key"}</code></td><td>Missing, wrong or revoked key.</td></tr>
<tr><td>403</td><td><code>{"error":"insufficient_scope"}</code></td><td>The key lacks the scope this call needs.</td></tr>
<tr><td>422</td><td><code>{"message":..., "errors":{...}}</code></td><td>Invalid fields, or <code>unavailable</code> / <code>cannot_create</code> / <code>cannot_cancel</code> with a message (for example no provider can serve this delivery right now, or your merchant account is not verified).</td></tr>
<tr><td>429</td><td><code>{"error":"rate_limited"}</code></td><td>Slow down; see <code>Retry-After</code>.</td></tr>
</table>
<p class="muted">Questions or a key request: contact {{ config('app.name') }} staff.</p>
<p class="muted">Building a customer or driver app instead? See the <a href="{{ route('developers.app') }}">App API</a>.</p>
</div></body></html>
