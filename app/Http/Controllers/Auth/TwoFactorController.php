<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Security\Totp;
use App\Modules\Identity\Security\TwoFactorService;
use App\Services\Activity\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Two-step sign-in in the browser: the code screen after the password, and each person's own "Sign-in security"
 * page where they turn it on (scan a QR code, type one code to prove it works, keep the recovery codes) or off.
 */
class TwoFactorController extends Controller
{
    public const PENDING_MINUTES = 10;

    public const MAX_TRIES = 5;

    public function __construct(private TwoFactorService $tf)
    {
    }

    // ---------------------------------------------------------------- the code screen after the password

    public function challenge(Request $request)
    {
        if (! $this->pending($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string|max:32']);
        $pending = $this->pending($request);
        if (! $pending) {
            return redirect()->route('login')->withErrors(['email' => 'That took too long. Please sign in again.']);
        }
        $user = User::find($pending['id']);
        if (! $user || ! $this->tf->enabled($user)) {
            $request->session()->forget('login.two_factor');

            return redirect()->route('login');
        }

        $how = $this->tf->verify($user, (string) $request->input('code'));
        if (! $how) {
            $pending['tries']++;
            ActivityLogger::log($user->id, 'login_failed', 'Wrong two-step sign-in code');
            if ($pending['tries'] >= self::MAX_TRIES) {
                $request->session()->forget('login.two_factor');

                return redirect()->route('login')->withErrors(['email' => 'Too many wrong codes. Please sign in again.']);
            }
            $request->session()->put('login.two_factor', $pending);

            return back()->withErrors(['code' => 'That code is not right. Check the time on your phone and try again.']);
        }

        $request->session()->forget('login.two_factor');
        Auth::guard()->login($user, (bool) $pending['remember']);
        $request->session()->regenerate();
        if ($how === 'recovery') {
            $request->session()->flash('warning', 'You used a recovery code. '.$this->tf->recoveryCodesLeft($user->fresh()).' left. If you lost your phone, set two-step sign-in up again on the Sign-in security page.');
        }

        return app(LoginController::class)->afterSignIn($request, $user);
    }

    // ---------------------------------------------------------------- the person's own page

    public function show(Request $request)
    {
        $user = $request->user();
        $secret = $this->tf->pendingSecret($user);

        return view('security.index', [
            'pagetitle' => 'Sign-in security',
            'enabled' => $this->tf->enabled($user),
            'since' => $user->two_factor_confirmed_at,
            'codesLeft' => $this->tf->recoveryCodesLeft($user),
            'required' => $this->tf->requiredBecause($user),
            'secret' => $secret,
            'qr' => $secret ? $this->qr(Totp::uri($secret, $user->email, (string) config('app.name'))) : null,
            'codes' => session('recovery_codes'),
        ]);
    }

    public function start(Request $request)
    {
        $this->tf->begin($request->user());

        return redirect()->route('security.index');
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string|max:12']);
        $codes = $this->tf->confirm($request->user(), (string) $request->input('code'));
        if (! $codes) {
            return back()->withErrors(['code' => 'That code is not right. Type the 6 digits your app shows now.']);
        }

        return redirect()->route('security.index')->with('recovery_codes', $codes)
            ->with('success', 'Two-step sign-in is on. Keep your recovery codes somewhere safe.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        if (! Hash::check((string) $request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Your password is wrong.']);
        }
        if ($why = $this->tf->requiredBecause($user)) {
            return back()->withErrors(['password' => $why.' It cannot be turned off.']);
        }
        $this->tf->disable($user);

        return redirect()->route('security.index')->with('success', 'Two-step sign-in is off.');
    }

    public function recoveryCodes(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        if (! Hash::check((string) $request->input('password'), $request->user()->password)) {
            return back()->withErrors(['password' => 'Your password is wrong.']);
        }

        return redirect()->route('security.index')->with('recovery_codes', $this->tf->regenerateRecoveryCodes($request->user()))
            ->with('success', 'New recovery codes made. The old ones no longer work.');
    }

    // ----------------------------------------------------------------

    private function pending(Request $request): ?array
    {
        $p = $request->session()->get('login.two_factor');
        if (! is_array($p) || time() - (int) ($p['at'] ?? 0) > self::PENDING_MINUTES * 60) {
            return null;
        }

        return $p;
    }

    private function qr(string $uri): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200, 1), new \BaconQrCode\Renderer\Image\SvgImageBackEnd());

        return (new \BaconQrCode\Writer($renderer))->writeString($uri);
    }
}
