<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Platform;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Web sign-up. Everyone starts with a customer account (the same as signing up in the customer app); the
 * "I am here to ..." choice only decides where they land next:
 *   customer  their account home
 *   provider  the provider sign-up form (a delivery company, an independent rider or a market shopper)
 *   team      team invitations, for drivers and staff a company has invited by email
 */
class RegisterController extends Controller
{
    use RegistersUsers;

    public const INTENTS = ['customer', 'provider', 'team'];

    protected $redirectTo = '/account';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm(Request $request)
    {
        $intent = in_array($request->query('as'), self::INTENTS, true) ? $request->query('as') : 'customer';

        return view('auth.register', ['intent' => $intent, 'marketplace' => Platform::marketplace()]);
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'intent' => ['nullable', 'in:'.implode(',', self::INTENTS)],
            'referral_code' => ['nullable', 'string', 'max:16'],
            'offers' => ['nullable', 'boolean'],
        ])->after(function ($v) use ($data) {
            if (! empty($data['phone'])) {
                $phone = AuthController::normalizePhone((string) $data['phone']);
                if (strlen($phone) < 10 || DB::table('users')->where('phone_number', $phone)->exists()) {
                    $v->errors()->add('phone', 'That phone number cannot be used.');
                }
            }
        });
    }

    /** Same account as the app's /v1/auth/register: a user with a customer profile and an optional referral. */
    protected function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $u = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'phone_number' => ! empty($data['phone']) ? AuthController::normalizePhone((string) $data['phone']) : null,
                'password' => Hash::make($data['password']),
            ]);
            DB::table('customer_profiles')->insert([
                'public_id' => (string) Str::ulid(), 'user_id' => $u->id, 'referral_code' => strtoupper(Str::random(8)),
                'marketing_opt_in' => ! empty($data['offers']), 'marketing_changed_at' => isset($data['offers']) ? now() : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            app(\App\Modules\Marketing\ReferralService::class)->attach($u->id, $data['referral_code'] ?? null);

            return $u;
        });
    }

    protected function registered(Request $request, $user)
    {
        $intent = $request->input('intent', 'customer');
        if ($intent === 'provider' && Platform::marketplace()) {
            return redirect()->route('provider.start');
        }
        if ($intent === 'team') {
            return redirect()->route('account.invitations');
        }

        // Signed up from "Book this" on the price form: carry on to the booking.
        return redirect()->intended(route('account.dashboard'));
    }
}
