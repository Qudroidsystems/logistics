<?php

namespace App\Http\Controllers;

use App\Models\BrandSetting;
use App\Modules\Booking\InstantQuoteService;
use App\Modules\Booking\PriceChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The public side: the homepage with the instant price form, the embeddable price widget, booking a price,
 * and looking up a delivery by its tracking code.
 */
class InstantBookingController extends Controller
{
    public function __construct(private InstantQuoteService $instant) {}

    public function home()
    {
        return view('public.home', ['brand' => BrandSetting::forApp('customer'), 'options' => $this->instant->options(), 'embed' => false]);
    }

    /** The price form alone, for an iframe on another website: <iframe src="https://your-domain/embed/quote">. */
    public function embed()
    {
        return response()->view('public.embed', ['brand' => BrandSetting::forApp('customer'), 'options' => $this->instant->options(), 'embed' => true])
            ->header('Content-Security-Policy', 'frame-ancestors *');
    }

    /** Business plans: prices and what each includes. */
    public function plans(\App\Modules\Subscriptions\SubscriptionService $subscriptions)
    {
        return view('public.business', ['brand' => BrandSetting::forApp('customer'), 'plans' => $subscriptions->plans(), 'salesEmail' => config('booking.sales_email'), 'salesPhone' => config('booking.sales_phone')]);
    }

    /** POST /quote (JSON). */
    public function quote(Request $request)
    {
        $d = $this->validateQuote($request);
        if (! \App\Support\BotCheck::allows($request, $request->user()?->id)) {
            return response()->json(['error' => 'challenge', 'message' => 'Please confirm you are not a robot to see more prices.'], 429);
        }
        try {
            return response()->json($this->instant->quote($d, $request->user()?->id));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** The details step. Guests sign up or sign in first and come back here. */
    public function details(Request $request, string $quote)
    {
        // Guests book straight away; signing in from here comes back to this page.
        if (! $request->user()) {
            session(['url.intended' => url()->current(), 'intended' => url()->current()]);
        }
        $q = DB::table('quotes')->where('public_id', $quote)->value('id');
        abort_unless($q, 404);

        return view('public.book', ['q' => $this->instant->present((int) $q), 'brand' => BrandSetting::forApp('customer'), 'options' => $this->instant->options(), 'me' => $request->user(), 'pagetitle' => 'Book']);
    }

    public function book(Request $request, string $quote)
    {
        // A guest: make the account from the details given here and sign them in (GuestAccounts emails a password link).
        if (! $request->user()) {
            $g = $request->validate([
                'guest_name' => 'required|string|max:120', 'guest_email' => 'required|email|max:160', 'guest_phone' => 'required|string|max:24',
                'guest_offers' => 'nullable|boolean', 'terms' => 'accepted',
            ], ['terms.accepted' => 'Please accept the terms and privacy policy to book.']);
            // Each guest booking makes an account and sends an email: at most 5 an hour from one address.
            $key = 'guest-accounts:'.$request->ip();
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
                return back()->withInput()->with('error', 'Too many new accounts from this connection. Sign in, or try again in an hour.');
            }
            try {
                \Illuminate\Support\Facades\RateLimiter::hit($key, 3600);
                $user = app(\App\Modules\Identity\GuestAccounts::class)->create($g['guest_name'], $g['guest_email'], $g['guest_phone'], (bool) ($g['guest_offers'] ?? false));
            } catch (RuntimeException $e) {
                session(['url.intended' => route('book.details', $quote), 'intended' => route('book.details', $quote)]);

                return back()->withInput()->with('error', $e->getMessage())->with('sign_in', true);
            }
            \Illuminate\Support\Facades\Auth::login($user);
            $request->session()->regenerate();
            // A price checked as a guest belongs to nobody yet; it is theirs now.
            DB::table('quotes')->where('public_id', $quote)->whereNull('customer_id')->update(['customer_id' => $user->id]);
            $request->merge(['pickup_name' => $request->input('pickup_name') ?: $g['guest_name'], 'pickup_phone' => $request->input('pickup_phone') ?: $g['guest_phone']]);
        }
        $d = $request->validate([
            'pickup_name' => 'nullable|string|max:120', 'pickup_phone' => 'nullable|string|max:24',
            'dropoff_name' => 'required|string|max:120', 'dropoff_phone' => 'required|string|max:24',
            'description' => 'required|string|max:200', 'pickup_instructions' => 'nullable|string|max:300', 'dropoff_instructions' => 'nullable|string|max:300',
            'pickup_at' => 'nullable|date',
        ]);
        // "Get offers from providers instead": the same trip goes out for providers to price.
        if ($request->input('mode') === 'offers') {
            try {
                $req = $this->instant->askProviders($request->user()->id, $quote, $d);
            } catch (\InvalidArgumentException|RuntimeException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            return redirect()->route('account.request', $req)->with('success', 'Sent to providers. Their offers appear here, with how each compares to the instant price.');
        }
        try {
            $agreement = $this->instant->book($request->user()->id, $quote, $d);
        } catch (PriceChanged $e) {
            return redirect()->route('book.details', $e->quote['quote'])->withInput()->with('error', $e->getMessage());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('account.pay', $agreement);
    }

    /** "Track your order": the tracking code alone shows the status; with the last 4 digits of a phone on the job, the live page. */
    public function track(Request $request)
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->query('code', '')));
        $digits = preg_replace('/\D/', '', (string) $request->query('phone4', ''));
        if ($code === '') {
            return view('public.track', ['brand' => BrandSetting::forApp('customer'), 'result' => null, 'code' => '']);
        }
        $s = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->where('s.tracking_code', $code)
            ->first(['s.id', 's.status', 's.updated_at', 'o.customer_id']);
        // Ten wrong phone digits for one code in an hour lock the live page for that code (the status still shows).
        $lock = 'track-phone4:'.$code;
        if ($s && strlen($digits) === 4 && \Illuminate\Support\Facades\RateLimiter::tooManyAttempts($lock, 10)) {
            $digits = '';
        }
        if ($s && strlen($digits) === 4) {
            \Illuminate\Support\Facades\RateLimiter::hit($lock, 3600);
            $phones = DB::table('shipment_stops')->where('shipment_id', $s->id)->pluck('contact_phone')
                ->push(DB::table('users')->where('id', $s->customer_id)->value('phone_number'))->filter();
            if ($phones->contains(fn ($p) => str_ends_with(preg_replace('/\D/', '', (string) $p), $digits))) {
                $token = DB::table('tracking_links')->where('shipment_id', $s->id)->whereNull('revoked_at')
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->orderByRaw("audience = 'recipient' DESC")->value('token');
                if ($token) {
                    return redirect()->route('track', $token);
                }
            }
        }

        return view('public.track', ['brand' => BrandSetting::forApp('customer'), 'code' => $code, 'phoneTried' => strlen($digits) === 4,
            'result' => $s ? ['status' => ucfirst(str_replace('_', ' ', $s->status)), 'updated' => \Illuminate\Support\Carbon::parse($s->updated_at)->diffForHumans()] : false]);
    }

    private function validateQuote(Request $request): array
    {
        return $request->validate([
            'dropoff_centre' => 'nullable|string|size:26',
            'pickup.lat' => 'required_without:dropoff_centre|nullable|numeric|between:-90,90', 'pickup.lng' => 'required_without:dropoff_centre|nullable|numeric|between:-180,180', 'pickup.line1' => 'nullable|string|max:200',
            'dropoff.lat' => 'required|numeric|between:-90,90', 'dropoff.lng' => 'required|numeric|between:-180,180', 'dropoff.line1' => 'nullable|string|max:200',
            'service' => 'required|string|max:24', 'size' => 'required|string|max:12', 'fragile' => 'nullable|boolean',
            'vehicle' => 'nullable|string|max:24', 'pickup_at' => 'nullable|date',
        ]);
    }
}
