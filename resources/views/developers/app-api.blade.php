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
<p><span class="tag">GET</span><code>/provider/catalog</code> works for any signed-in user and returns the lists you need to fill in a request: <code>service_types</code> (<code>id, code, name</code>), <code>vehicle_types</code> (<code>id, code, name, max_weight_g</code>) and <code>cities</code> (<code>id, name, lat, lng</code>). Cache it for the session.</p>

<h2 id="customer">Customer</h2>
<h3>Find a provider and post a request</h3>
<table>
<tr><th>Call</th><th>Notes</th></tr>
<tr><td><span class="tag">GET</span><code>/customer/providers?service_type_id=1&amp;limit=20</code></td><td>Listed providers for a service, best first, with rating, jobs done, on-time rate and a starting price hint. <code>public_slug</code> opens the public profile at <code>GET /providers/{slug}</code>.</td></tr>
<tr><td><span class="tag">POST</span><code>/customer/requests</code></td><td>Creates a service request (below). Providers who can serve it are invited and send offers.</td></tr>
<tr><td><span class="tag">GET</span><code>/customer/requests</code></td><td>Your requests.</td></tr>
<tr><td><span class="tag">GET</span><code>/customer/requests/{request}/offers</code></td><td>Offers received, one per provider: <code>thread, status, provider, rating_avg, tier, jobs_completed, latest_price</code> (kobo), and once agreed <code>agreement</code> and <code>agreement_status</code>.</td></tr>
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
<tr><td><span class="tag">POST</span><code>/customer/negotiations/{thread}/accept</code></td><td><code>offer_id</code>: the <code>id</code> of the latest message with <code>kind</code> <code>counter_offer</code> in the thread. Locks an agreement and returns <code>agreement, number, status, price, platform_fee, goods_budget, tip</code>.</td></tr>
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
<tr><td><span class="tag">GET</span><code>/customer/orders?status=all|active|done</code></td><td>The customer's orders, newest first: <code>shipment, status, tracking_code, order_number, total, provider, created_at, active</code>.</td></tr>
<tr><td><span class="tag">GET</span><code>/customer/orders/{shipment}</code></td><td>One order: stops, timeline, <code>tracking_token</code> (use it with <code>GET /track/{token}</code> for the live driver position), <code>can_cancel, can_confirm, can_rate, shows_code</code> and the failed-delivery <code>terms</code>.</td></tr>
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

<h2 id="driver">Driver</h2>
<p>For users whose <code>/auth/me</code> shows a <code>driver</code> membership. All calls are under <code>/driver</code>.</p>
<table>
<tr><th>Call</th><th>Body / notes</th></tr>
<tr><td><span class="tag">GET</span><code>/driver/me</code></td><td>One call for the home screen: <code>{"driver": {"availability", "company", "rating_avg", ...}, "offers": [...], "jobs": [...]}</code>. <code>availability</code> is <code>online</code>, <code>offline</code>, <code>break</code> or <code>on_job</code>. Returns <code>403</code> when the user has no active driver profile.</td></tr>
<tr><td><span class="tag">POST</span><code>/driver/availability</code></td><td><code>availability</code>: <code>online</code>, <code>offline</code> or <code>break</code>. Only online drivers get offers. Refused with <code>422</code> while the driver is on a job.</td></tr>
<tr><td><span class="tag">GET</span><code>/driver/offers</code></td><td>Open job offers with pickup, drop-off, distance, pay and seconds left. Also pushed as a notification and a text.</td></tr>
<tr><td><span class="tag">POST</span><code>/driver/offers/{id}/accept</code>, <code>/decline</code></td><td>Accept fails with <code>422</code> if it expired or someone else took it.</td></tr>
<tr><td><span class="tag">GET</span><code>/driver/jobs</code></td><td><code>{"live": [...], "done": [...]}</code>: jobs in progress and recently finished ones.</td></tr>
<tr><td><span class="tag">GET</span><code>/driver/jobs/{shipment}</code></td><td>One job with its ordered stops, contacts, status and the failed-delivery terms.</td></tr>
<tr><td><span class="tag">POST</span><code>/driver/jobs/{shipment}/start</code></td><td>Heading to pickup.</td></tr>
<tr><td><span class="tag">POST</span><code>/driver/jobs/{shipment}/release</code></td><td>Hand the job back before pickup.</td></tr>
<tr><td><span class="tag">POST</span><code>/driver/jobs/{shipment}/issue</code></td><td>Report a problem: <code>type</code>, optional <code>note</code>.</td></tr>
<tr><td><span class="tag">POST</span><code>/driver/jobs/{shipment}/fail</code></td><td><code>reason</code> (<code>receiver_unreachable, receiver_refused, wrong_address</code>), optional <code>note</code>. Only at the drop-off, and only after the agreed wait time. Adds a return stop when the terms say the parcel goes back.</td></tr>
<tr><td><span class="tag">POST</span><code>/driver/stops/{id}/complete</code></td><td>See below.</td></tr>
<tr><td><span class="tag">GET</span><code>/driver/earnings</code></td><td><code>today, week, month</code> (kobo, from finished jobs) and the driver's <code>wallet</code> balance. Pay is posted to the wallet when the customer confirms. Cash out with <code>GET /banks</code>, <code>GET|POST /customer/bank-accounts</code> and <code>POST /customer/wallet/withdraw</code>.</td></tr>
<tr><td><span class="tag">POST</span><code>/driver/location</code></td><td>Batch of GPS pings (below).</td></tr>
</table>

<h3>Location pings</h3>
<p>Send while online or on a job: every 5 to 10 seconds on a job, every 30 or so when idle. Queue pings offline and send up to 100 at once.</p>
<pre><code>POST /driver/location
{"pings": [
  {"lat": 7.8012, "lng": 6.7411, "at": "2026-10-06T14:02:11+01:00",
   "accuracy": 8, "speed": 6.4, "heading": 120, "battery": 71, "mocked": false}
]}</code></pre>
<p>Arrival at a stop is detected on the server from these pings. Pings flagged <code>mocked</code> are ignored and recorded.</p>

<h3>Completing a stop</h3>
<pre><code>POST /driver/stops/{id}/complete
{"proof_type": "otp", "otp": "4821", "lat": 7.83, "lng": 6.76, "recipient_name": "Chi"}</code></pre>
<p><code>proof_type</code> is <code>otp, photo, signature</code> or <code>qr_scan</code>. The driver must be within 300 m of the stop. A drop-off needs the receiver's 4-digit delivery code, or a photo. Pick-ups can use a photo or signature.</p>
<p>To send a photo (or a signature image), post the call as <code>multipart/form-data</code> and put the image in the <code>photo</code> field (JPEG or PNG, up to 6 MB). Use <code>proof_type</code> <code>photo</code> or <code>signature</code>. The server stores the file; any path sent by the app is ignored. Compress to about 1600 px on the long side before uploading.</p>

<h2 id="notifications">Notifications</h2>
<table>
<tr><th>Call</th><th>Notes</th></tr>
<tr><td><span class="tag">GET</span><code>/notifications?limit=30&amp;unread=1</code></td><td>Returns <code>{"unread": 3, "items": [...]}</code>, newest first (limit up to 100). Each item has <code>id, category, title, body, data, read_at, created_at</code>; <code>data</code> carries the IDs you need to deep-link.</td></tr>
<tr><td><span class="tag">POST</span><code>/notifications/{id}/read</code>, <code>/notifications/read-all</code></td><td></td></tr>
<tr><td><span class="tag">GET / PUT</span><code>/notifications/preferences</code></td><td>Which channels (in-app, email, SMS) are on for each category. Delivery and payment alerts that the receiver or driver needs cannot be turned off.</td></tr>
</table>
<h3>Push notifications</h3>
<p>Every in-app notification is also sent as a push (Firebase Cloud Messaging) to the user's registered devices. Use the <code>firebase_messaging</code> plugin, then register the token after sign-in and whenever it changes:</p>
<table>
<tr><th>Call</th><th>Body</th></tr>
<tr><td><span class="tag">POST</span><code>/devices</code></td><td><code>device_id</code> (a stable ID the app generates and keeps), <code>push_token</code>; optional <code>platform</code> (<code>android, ios, web</code>), <code>os, app_version</code></td></tr>
<tr><td><span class="tag">DELETE</span><code>/devices/{device_id}</code></td><td>Call on sign-out so the phone stops receiving this account's pushes.</td></tr>
</table>
<p>The push carries <code>title</code>, <code>body</code> and a <code>data</code> map with <code>event</code> (for example <code>delivery.failed</code>) and <code>url</code>. A token can belong to one account at a time. Keep polling <code>/notifications</code> when the app opens, so nothing is missed if a push is dropped.</p>

<h2 id="status">Shipment statuses</h2>
<table>
<tr><th>Status</th><th>Meaning</th></tr>
<tr><td><code>created, awaiting_dispatch, offered</code></td><td>Paid and waiting for a driver.</td></tr>
<tr><td><code>no_supply, unassigned</code></td><td>Nobody accepted yet; staff or the provider are looking.</td></tr>
<tr><td><code>assigned, heading_to_pickup, at_pickup</code></td><td>Driver found and on the way.</td></tr>
<tr><td><code>picked_up, in_transit, at_dropoff</code></td><td>Parcel is moving.</td></tr>
<tr><td><code>delivered, confirmed, completed</code></td><td>Handed over, customer confirmed, money released.</td></tr>
<tr><td><code>failed_attempt, returning, returned</code></td><td>Receiver not reached; parcel on its way back or back with the sender.</td></tr>
<tr><td><code>cancelled</code></td><td>Cancelled by the customer or staff.</td></tr>
</table>
<p class="muted">Related: <a href="{{ route('developers.partner') }}">Partner API for merchants</a>.</p>
</div></body></html>
