<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SchoolInformation;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use App\Services\Parents\ParentAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

/**
 * Parents reset a forgotten password with a 6-digit code sent to their phone
 * (SMS, falling back to WhatsApp). The code lives 10 minutes, 5 tries max.
 */
class ParentPasswordController extends Controller
{
    protected const TTL_MINUTES = 10;
    protected const MAX_TRIES   = 5;

    public function __construct(protected MessagingService $messaging)
    {
        $this->middleware('guest');
    }

    public function requestForm()
    {
        return view('parent.auth.forgot');
    }

    public function sendCode(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);
        $phone = MessagingService::normalizePhone($request->phone);

        // Same answer whether or not the number exists (no account probing).
        $request->session()->put('pp_reset_phone', $phone ?: $request->phone);
        $done = redirect()->route('parent.reset')->with('success', 'If that number belongs to a parent account, a reset code has been sent to it.');

        $user = $phone ? ParentAccountService::findByPhone($phone) : null;
        if (!$user || !$user->hasRole(ParentAccountService::ROLE) || !empty($user->is_disabled)) {
            return $done;
        }

        // One code per minute per account.
        if (Cache::has($this->key($user) . ':sent')) {
            return $done;
        }

        $code = (string) random_int(100000, 999999);
        Cache::put($this->key($user), ['hash' => Hash::make($code), 'tries' => 0], now()->addMinutes(self::TTL_MINUTES));
        Cache::put($this->key($user) . ':sent', 1, now()->addMinute());

        $school = (SchoolInformation::getActiveSchool() ?? SchoolInformation::first())->school_name ?? config('app.name');
        $text   = "{$school}: your parent portal reset code is {$code}. It expires in " . self::TTL_MINUTES . " minutes. Do not share it.";

        foreach (['sms', 'whatsapp'] as $ch) {
            if (!$this->messaging->enabled($ch)) continue;
            $r = $this->messaging->send($ch, $phone, $text, ['name' => $user->name]);
            if (($r['status'] ?? '') === 'sent') break;
            Log::warning('Parent reset code not sent', ['user' => $user->id, 'channel' => $ch, 'error' => $r['error'] ?? null]);
        }

        return $done;
    }

    public function resetForm(Request $request)
    {
        if (!$request->session()->has('pp_reset_phone')) {
            return redirect()->route('parent.forgot');
        }
        return view('parent.auth.reset', ['phone' => ParentAccountService::localPhone((string) $request->session()->get('pp_reset_phone'))]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'code'     => 'required|digits:6',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $fail = fn () => back()->withErrors(['code' => 'That code is wrong or has expired. Request a new one.']);
        $user = ParentAccountService::findByPhone((string) $request->session()->get('pp_reset_phone'));
        if (!$user || !$user->hasRole(ParentAccountService::ROLE)) return $fail();

        $entry = Cache::get($this->key($user));
        if (!$entry || $entry['tries'] >= self::MAX_TRIES) return $fail();

        if (!Hash::check($request->code, $entry['hash'])) {
            $entry['tries']++;
            Cache::put($this->key($user), $entry, now()->addMinutes(self::TTL_MINUTES));
            return $fail();
        }

        Cache::forget($this->key($user));
        $request->session()->forget('pp_reset_phone');
        $user->forceFill(['password' => Hash::make($request->password), 'must_change_password' => false])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('parent.dashboard')->with('success', 'Your password has been reset.');
    }

    protected function key(User $user): string
    {
        return 'parent-reset:' . $user->id;
    }
}
