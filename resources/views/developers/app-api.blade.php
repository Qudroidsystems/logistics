<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>App API · {{ config('app.name') }}</title>
@include('developers._style')
</head>
<body><div class="wrap">
<p class="muted" style="margin:0">{{ config('app.name') }} for developers</p>
<h1>App API</h1>
<p>The JSON API behind the customer and driver mobile apps (Flutter or anything else). It is the same API the website uses, so every rule you see in the web pages applies here too. Merchants integrating their own shop should read the <a href="{{ route('developers.partner') }}">Partner API</a> instead.</p>
<nav><a href="#basics">Basics</a><a href="#auth">Sign in</a><a href="#catalog">Catalogue</a><a href="#customer">Customer</a><a href="#tracking">Tracking</a><a href="#driver">Driver</a><a href="#notifications">Notifications</a><a href="#status">Statuses</a></nav>

<h2 id="basics">Basics</h2>
<p>Base URL: <code>{{ $base }}</code>. Send and expect JSON, and add <code>Accept: application/json</code> so errors come back as JSON. After signing in, send the token on every call:</p>
<pre><code>Authorization: Bearer YOUR_TOKEN</code></pre>
<ul>
<li><b>Money</b> is always a whole number of kobo (₦1 = 100).</li>
<li><b>IDs:</b> things people can see (shipments, agreements, negotiation threads, service requests) use a 26-character public ID. Catalogue rows (service types, cities, vehicle types) and driver offers use plain integers.</li>
<li><b>Errors:</b> bad input is <code>422</code> with <code>{"message": "...", "errors": {"field": ["..."]}}</code>. Business rule failures are <code>422</code> with <code>{"error": "code", "message": "words you can show"}</code>. <code>401</code> means sign in again, <code>403</code> not allowed, <code>404</code> not yours or not found, <code>429</code> slow down (see <code>Retry-After</code>).</li>
<li><b>Rate limits</b> are per user, roughly 60 to 120 calls a minute depending on the area, and tighter on sign-in and anything that sends a code.</li>
<li><b>Your server's clock rules:</b> send timestamps in ISO 8601 (<code>2026-10-06T14:02:11+01:00</code>).</li>
</ul>

<h2 id="auth">Sign in</h2>
<p>One account can be a customer, a driver and a provider team member at once. The app decides which screens to show from <code>/auth/me</code>.</p>
<table>
<tr><th>Call</th><th>Body</th><th>Notes</th></tr>
<tr><td><span class="tag">POST</span><code>/auth/register</code></td><td><code>name, email, password</code> (8+ chars); optional <code>phone, device_name</code></td><td>Creates a customer account and returns a token.</td></tr>
<tr><td><span class="tag">POST</span><code>/auth/login</code></td><td><code>login</code> (email or phone), <code>password</code>; optional <code>device_name</code></td><td>5 wrong tries then a short lockout. Same error for a wrong password and an unknown user.</td></tr>
<tr><td><span class="tag">GET</span><code>/auth/me</code></td><td></td><td>The user, whether they are a customer, and their <code>operators</code> (memberships with <code>type</code>, <code>role</code>, <code>status</code>). A person with a <code>driver</code> membership gets the driver screens.</td></tr>
<tr><td><span class="tag">POST</span><code>/auth/logout</code>, <code>/auth/logout-all</code></td><td></td><td>This device, or every device.</td></tr>
<tr><td><span class="tag">POST</span><code>/auth/password</code></td><td><code>current_password, password</code></td><td>Other devices are signed out.</td></tr>
<tr><td><span class="tag">POST</span><code>/auth/forgot-password</code>, <code>/auth/reset-password</code></td><td><code>email</code>; then <code>email, code</code> (6 digits), <code>password</code></td><td>The code comes by email.</td></tr>
<tr><td><span class="tag">POST</span><code>/auth/email/send-code</code>, <code>/auth/email/verify</code></td><td><code>code</code></td><td>6-digit code, 30 minutes.</td></tr>
<tr><td><span class="tag">POST</span><code>/auth/phone</code>, <code>/auth/phone/send-code</code>, <code>/auth/phone/verify</code></td><td><code>phone</code>; then <code>code</code></td><td>Saves the number, texts a 6-digit code (10 minutes, 3 sends an hour), then confirms it.</td></tr>
</table>
<pre><code>POST /auth/login
{"login": "08031234567", "password": "secret-pass", "device_name": "Pixel 8"}

200
{"token": "12|x8k...", "token_type": "Bearer",
 "user": {"id": 5, "name": "Ada", "email": "...", "phone": "08031234567", "email_verified": false},
 "customer": true,
 "operators": [{"operator_id": "01J...", "type": "rider", "name": "Ada Haulage", "status": "active", "role": "driver", "current": true}],
 "staff": false}</code></pre>

<h2 id="catalog">Catalogue</h2>
<p><span class="tag">GET</span><code>/provider/catalog</code> works for any signed-in user and returns the lists you need to fill in a request: <code>service_types</code> (<code>id, code, name</code>), <code>vehicle_types</code> (<code>id, code, name, max_weight_g</code>) and <code>cities</code> (<code>id, name</code>). Cache it for the session.</p>

<h2 id="customer">Customer</h2>
<h3>Find a provider and post a request</h3>
<table>
<tr><th>Call</th><th>Notes</th></tr>
<tr><td><span class="tag">GET</span><code>/customer/providers?service_type_id=1&amp;limit=20</code></td><td>Listed providers for a service, best first, with rating, jobs done, on-time rate and a starting price hint. <code>public_slug</code> opens the public profile at <code>GET /providers/{slug}</code>.</td></tr>
<tr><td><span class="tag">POST</span><code>/customer/requests</code></td><td>Creates a service request (below). Providers who can serve it are invited and send offers.</td></tr>
<tr><td><span class="tag">GET</span><code>/customer/requests</code></td><td>Your requests.</td></tr>
<tr><td><span class="tag">GET</span><code>/customer/requests/{request}/offers</code></td><td>Offers received, each with a negotiation thread.</td></tr>
</table>
<pre><code>POST /customer/requests
{"type": "parcel", "service_type_id": 1, "city_id": 1,
 "pickup":  {"line1": "12 Market Rd", "lat": 7.80, "lng": 6.74, "contact_name": "Bola", "contact_phone": "08031234567"},
 "dropoff": {"line1": "4 Hill St",    "lat": 7.83, "lng": 6.76, "contact_name": "Chi",  "contact_phone": "08099999999"},
 "budget_max": 300000, "visibility": "open"}</code></pre>
<p><code>type</code> is one of <code>parcel, freight, errand, shopping, moving, bulk</code>. <code>visibility</code> is <code>direct</code> (pick providers with <code>operator_ids</code>), <code>invited</code> or <code>open</code>. For <code>shopping</code> also send <code>errand.list</code> (an array of items) and <code>errand.budget_cap</code>. The drop-off phone gets the delivery code and a tracking link by text, so ask for it.</p>

<h3>Negotiate and agree</h3>
<table>
<tr><th>Call</th><th>Body</th></tr>
<tr><td><span class="tag">GET</span><code>/customer/negotiations/{thread}</code></td><td>The thread: offers, counters and messages.</td></tr>
<tr><td><span class="tag">POST</span><code>/customer/negotiations/{thread}/counter</code></td><td><code>price</code> (kobo, required); optional <code>tip, goods_budget, vehicle_type_id, note, message</code></td></tr>
<tr><td><span class="tag">POST</span><code>/customer/negotiations/{thread}/messages</code></td><td><code>text</code></td></tr>
<tr><td><span class="tag">POST</span><code>/customer/negotiations/{thread}/accept</code></td><td><code>offer_id</code>. Locks an agreement and returns it.</td></tr>
<tr><td><span class="tag">POST</span><code>/customer/negotiations/{thread}/reject</code></td><td></td></tr>
</table>
<p>The agreement carries the price, the cancellation fee and the <b>failed-delivery terms</b> (<code>failed_delivery_policy</code>): how long the driver waits, how much you still pay if the receiver cannot be reached, and whether the parcel comes back. Show these to the customer before they pay; they are fixed when the agreement is created.</p>

<h3>Pay</h3>
<p><span class="tag">POST</span><code>/customer/agreements/{agreement}/pay</code> with <code>{"method": "wallet"}</code> or <code>{"method": "card"}</code>. A wallet payment answers <code>{"status": "paid", "order_id": ...}</code> straight away. A card payment answers with an <code>authorization_url</code>: open it in an in-app browser, and the order is created when the payment is confirmed (poll the order or wait for the notification).</p>

<h3>Wallet</h3>
<table>
<tr><th>Call</th><th>Body / result</th></tr>
<tr><td><span class="tag">GET</span><code>/customer/wallet</code></td><td><code>{"balance": 250000, "currency": "NGN", "pending_withdrawals": 0}</code></td></tr>
<tr><td><span class="tag">POST</span><code>/customer/wallet/top-up</code></td><td><code>amount</code>. Returns <code>reference</code> and a Paystack <code>authorization_url</code>. The balance changes only after Paystack confirms.</td></tr>
<tr><td><span class="tag">GET / POST</span><code>/customer/bank-accounts</code></td><td>Add with <code>bank_code, account_number</code> (10 digits); optional <code>bank_name</code>.</td></tr>
<tr><td><span class="tag">POST</span><code>/customer/wallet/withdraw</code></td><td><code>amount, bank_account_id</code></td></tr>
</table>

<h3>During and after a delivery</h3>
<table>
<tr><th>Call</th><th>Notes</th></tr>
<tr><td><span class="tag">GET</span><code>/customer/shipments/{shipment}/delivery-code</code></td><td>The 4-digit code the receiver gives the driver. Show it large; it is also texted to the receiver.</td></tr>
<tr><td><span class="tag">GET</span><code>/customer/shipments/{shipment}/cancel-preview</code></td><td><code>{"cancellable": true, "stage": "after_assignment", "fee": 18500}</code> so you can show the fee first.</td></tr>
<tr><td><span class="tag">POST</span><code>/customer/shipments/{shipment}/cancel</code></td><td><code>reason</code> (short code). Returns <code>stage, fee, refunded</code>. Not possible once the parcel is picked up.</td></tr>
<tr><td><span class="tag">POST</span><code>/customer/shipments/{shipment}/confirm</code></td><td>Optional <code>rating</code> (1 to 5), <code>tags</code>, <code>comment</code>. Releases payment to the provider.</td></tr>
<tr><td><span class="tag">POST</span><code>/customer/shipments/{shipment}/object</code></td><td>Opens a dispute: <code>type</code> (<code>price, quality, damage, lost, late, driver_conduct, fraud</code>), <code>reason</code> (10+ characters), optional <code>evidence</code>.</td></tr>
<tr><td><span class="tag">POST</span><code>/customer/shipments/{shipment}/rating</code></td><td><code>rating</code> (1 to 5), optional <code>tags, comment</code>.</td></tr>
</table>
<p>If the receiver cannot be reached the driver reports a failed delivery, the agreement's failed-delivery terms are applied, and you get a notification and the shipment status changes to <code>returning</code>, <code>returned</code> or <code>failed_attempt</code>.</p>

<h2 id="tracking">Tracking (no sign-in)</h2>
<p><span class="tag">GET</span><code>/track/{token}</code> returns the live state for a tracking link: status, the driver's last position and an estimated arrival. The token is in the link texted to the receiver and shown to the customer. Poll every 5 to 10 seconds while the screen is open; open the same link at <code>{{ url('/track') }}/{token}</code> in a web view if you prefer our map page.</p>
