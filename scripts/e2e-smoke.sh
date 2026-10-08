#!/usr/bin/env bash
# End-to-end smoke test of the whole delivery flow over the JSON API (what the two apps call):
#   customer posts a request -> provider offers -> customer accepts and pays from wallet -> driver works the job
#   -> customer confirms and rates.
#
# Prepare once:   sail artisan migrate && sail artisan logistics:demo      (demo city, customer with wallet, company, driver)
# Run a queue worker in another terminal:   sail artisan queue:work         (dispatch offers and notifications use it)
# Then:           bash scripts/e2e-smoke.sh            [API_BASE=http://localhost/api/v1] [PASSWORD=Demo1234!]
set -u
API="${API_BASE:-http://localhost/api/v1}"
PW="${PASSWORD:-Demo1234!}"
FAILS=0

step() { printf '\n== %s\n' "$1"; }
ok()   { printf '   ok   %s\n' "$1"; }
bad()  { printf '   FAIL %s\n' "$1"; FAILS=$((FAILS+1)); }

# req METHOD PATH TOKEN [JSON]  -> prints the body; sets CODE to the HTTP status
req() {
  local m="$1" p="$2" t="${3:-}" b="${4:-}"
  local args=(-sS -m 30 -X "$m" -H 'Accept: application/json' -H 'Content-Type: application/json' -w '\n%{http_code}')
  [ -n "$t" ] && args+=(-H "Authorization: Bearer $t")
  [ -n "$b" ] && args+=(-d "$b")
  local out; out=$(curl "${args[@]}" "$API$p") || { CODE=000; echo '{}'; return; }
  CODE=$(printf '%s' "$out" | tail -n1)
  printf '%s' "$out" | sed '$d'
}
# jget 'python expression on d'  (reads JSON from stdin; prints nothing when missing)
jget() { python3 -c "import sys,json
try:
    d=json.load(sys.stdin)
except Exception:
    d=None
try:
    v=($1)
except Exception:
    v=None
print('' if v is None else v)"; }
# Run a request, keep body in BODY and status in CODE (no subshell, so CODE survives).
call() { local tmp; tmp=$(mktemp); req "$@" > "$tmp"; BODY=$(cat "$tmp"); rm -f "$tmp"; }
expect() { case "$CODE" in 2*) ok "$1 ($CODE)";; *) bad "$1 (HTTP $CODE) $BODY";; esac; }

step "Server reachable"
call GET /ping; expect ping

step "Sign in: customer, provider owner, driver"
login() { call POST /auth/login '' "{\"login\":\"$1\",\"password\":\"$PW\",\"device_name\":\"smoke\"}"; printf '%s' "$BODY" | jget "d['token']"; }
CT=$(login demo.customer@example.test); PT=$(login demo.provider@example.test); DT=$(login demo.driver@example.test)
for n in CT PT DT; do [ -n "${!n}" ] && ok "$n token" || bad "$n token missing (did you run logistics:demo?)"; done
{ [ -z "$CT" ] || [ -z "$PT" ] || [ -z "$DT" ]; } && exit 1

step "Register a brand-new customer (checks sign-up)"
EMAIL="smoke$(date +%s)@example.test"
call POST /auth/register '' "{\"name\":\"Smoke Test\",\"email\":\"$EMAIL\",\"password\":\"Smoke1234!\",\"device_name\":\"smoke\"}"; expect register

step "Catalogue"
call GET /provider/catalog "$CT"; expect catalog
SVC=$(printf '%s' "$BODY" | jget "d['service_types'][0]['id']")
CITY=$(printf '%s' "$BODY" | jget "[c for c in d['cities'] if 'emo' in c['name']][0]['id']")
LAT=$(printf '%s' "$BODY" | jget "[c for c in d['cities'] if 'emo' in c['name']][0]['lat']")
LNG=$(printf '%s' "$BODY" | jget "[c for c in d['cities'] if 'emo' in c['name']][0]['lng']")
echo "   service=$SVC city=$CITY centre=$LAT,$LNG"
{ [ -z "$SVC" ] || [ -z "$CITY" ] || [ -z "$LAT" ]; } && { bad "no service type or demo city with coordinates"; exit 1; }

step "Customer posts a request"
D_LAT=$(python3 -c "print(round($LAT+0.02,6))"); D_LNG=$(python3 -c "print(round($LNG+0.015,6))")
call POST /customer/requests "$CT" "{\"type\":\"parcel\",\"service_type_id\":$SVC,\"city_id\":$CITY,
 \"pickup\":{\"line1\":\"12 Market Rd\",\"lat\":$LAT,\"lng\":$LNG,\"contact_name\":\"Bola\",\"contact_phone\":\"08031234567\"},
 \"dropoff\":{\"line1\":\"4 Hill St\",\"lat\":$D_LAT,\"lng\":$D_LNG,\"contact_name\":\"Chi\",\"contact_phone\":\"08099999999\"},
 \"packages\":[{\"description\":\"Box of documents\"}],\"visibility\":\"open\"}"; expect "create request"
RID=$(printf '%s' "$BODY" | jget "d['request_id']")
[ -z "$RID" ] && exit 1

step "Provider sees it and sends an offer"
call GET /provider/requests "$PT"; expect "provider inbox"
printf '%s' "$BODY" | grep -q "$RID" && ok "request is in the inbox" || bad "request not in the provider inbox (does the demo service area cover the pins?)"
call POST "/provider/requests/$RID/offer" "$PT" '{"price":150000,"message":"Can do today"}'; expect "offer"
THREAD=$(printf '%s' "$BODY" | jget "d['thread']")
[ -z "$THREAD" ] && exit 1

step "Customer sees the offer and accepts it"
call GET "/customer/requests/$RID/offers" "$CT"; expect "offers list"
echo "   $(printf '%s' "$BODY" | jget "[(o['provider'],o['latest_price']) for o in d]")"
call GET "/customer/negotiations/$THREAD" "$CT"; expect "thread"
OFFER=$(printf '%s' "$BODY" | jget "[m['id'] for m in d['messages'] if m['kind']=='counter_offer'][-1]")
call POST "/customer/negotiations/$THREAD/accept" "$CT" "{\"offer_id\":$OFFER}"; expect "accept"
AGR=$(printf '%s' "$BODY" | jget "d['agreement']")
[ -z "$AGR" ] && exit 1

step "Customer pays from the wallet"
call POST "/customer/agreements/$AGR/pay" "$CT" '{"method":"wallet"}'; expect "wallet pay"
call GET /customer/orders "$CT"; expect "orders list"
SHIP=$(printf '%s' "$BODY" | jget "d[0]['shipment']")
echo "   shipment=$SHIP status=$(printf '%s' "$BODY" | jget "d[0]['status']")"
[ -z "$SHIP" ] && exit 1

step "Driver goes online and gets the job"
call POST /driver/availability "$DT" '{"availability":"online"}'; expect "online"
OFFER_ID=""
for i in 1 2 3 4 5 6 7 8 9 10; do
  call GET /driver/me "$DT"
  OFFER_ID=$(printf '%s' "$BODY" | jget "d['offers'][0]['id']")
  [ -n "$OFFER_ID" ] && break; sleep 2
done
if [ -n "$OFFER_ID" ]; then
  call POST "/driver/offers/$OFFER_ID/accept" "$DT"; expect "driver accepts offer"
else
  echo "   no automatic offer after 20 s (is the queue worker running?) - assigning by hand instead"
  call GET /provider/drivers "$PT"
  DUID=$(printf '%s' "$BODY" | jget "d[0]['user_id']")
  call POST "/provider/shipments/$SHIP/assign" "$PT" "{\"driver_user_id\":$DUID}"; expect "manual assign"
fi

step "Driver works the job"
call POST "/driver/jobs/$SHIP/start" "$DT"; expect "start trip"
call GET "/driver/jobs/$SHIP" "$DT"; expect "job detail"
JOB="$BODY"
stop() { printf '%s' "$JOB" | jget "[s for s in d['stops'] if s['type']=='$1'][0]['$2']"; }
PS=$(stop pickup id); PLA=$(stop pickup lat); PLN=$(stop pickup lng)
DS=$(stop dropoff id); DLA=$(stop dropoff lat); DLN=$(stop dropoff lng)
call POST /driver/location "$DT" "{\"pings\":[{\"lat\":$PLA,\"lng\":$PLN,\"recorded_at\":\"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}]}"; expect "location ping"
call POST "/driver/stops/$PS/complete" "$DT" "{\"proof_type\":\"photo\",\"lat\":$PLA,\"lng\":$PLN}"; expect "pickup done"

step "Customer sees progress and the delivery code"
call GET "/customer/orders/$SHIP" "$CT"; expect "order detail"
echo "   status=$(printf '%s' "$BODY" | jget "d['status']")"
TOKEN=$(printf '%s' "$BODY" | jget "d['tracking_token']")
if [ -n "$TOKEN" ]; then call GET "/track/$TOKEN"; expect "public tracking"; else bad "no tracking token"; fi
call GET "/customer/shipments/$SHIP/delivery-code" "$CT"; expect "delivery code"
CODE4=$(printf '%s' "$BODY" | jget "d['code']")

step "Driver delivers with the code"
call POST "/driver/stops/$DS/complete" "$DT" "{\"proof_type\":\"otp\",\"otp\":\"$CODE4\",\"lat\":$DLA,\"lng\":$DLN,\"recipient_name\":\"Chi\"}"; expect "dropoff done"

step "Customer confirms and rates"
call GET "/customer/orders/$SHIP" "$CT"; echo "   status=$(printf '%s' "$BODY" | jget "d['status']") can_confirm=$(printf '%s' "$BODY" | jget "d['can_confirm']")"
call POST "/customer/shipments/$SHIP/confirm" "$CT" '{"rating":5,"comment":"Smooth"}'; expect "confirm"
call GET /customer/wallet "$CT"; expect "customer wallet"; echo "   $BODY"
call GET /driver/earnings "$DT"; expect "driver earnings"; echo "   $BODY"

if [ $FAILS -eq 0 ]; then printf '\nALL STEPS PASSED\n'; else printf '\n%s STEP(S) FAILED - the first FAIL above is the one to fix\n' "$FAILS"; fi
exit $FAILS
