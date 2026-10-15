<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Models\User;
use App\Modules\Identity\Security\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers {
        sendLoginResponse as private finishPasswordLogin;
    }

    protected $redirectTo = '/dashboard';

    /** The password was right but the account uses two-step sign-in: the code is asked next. */
    private bool $twoFactorPending = false;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * The login box takes an email or a phone number. A phone number is
     * matched to its account (customers, drivers and shoppers sign in with their phone).
     */
    protected function credentials(Request $request)
    {
        $login = trim((string) $request->input($this->username()));
        if ($login !== '' && !str_contains($login, '@')) {
            if ($user = User::where('phone_number', $login)->first()) {
                $login = $user->email;
            }
        }
        return ['email' => $login, 'password' => $request->input('password')];
    }

    /**
     * With two-step sign-in on, a right password does not sign in yet: the person is remembered for ten minutes
     * and asked for the code from their authenticator app (TwoFactorController::challenge).
     */
    protected function attemptLogin(Request $request)
    {
        $credentials = $this->credentials($request);
        $provider = $this->guard()->getProvider();
        $user = $provider->retrieveByCredentials($credentials);
        if ($user && app(TwoFactorService::class)->enabled($user) && $provider->validateCredentials($user, $credentials)) {
            $request->session()->put('login.two_factor', ['id' => $user->id, 'remember' => $request->boolean('remember'), 'at' => time(), 'tries' => 0]);
            $this->twoFactorPending = true;

            return true;
        }

        return $this->guard()->attempt($credentials, $request->boolean('remember'));
    }

    protected function sendLoginResponse(Request $request)
    {
        if (! $this->twoFactorPending) {
            return $this->finishPasswordLogin($request);
        }
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        return redirect()->route('two-factor.challenge');
    }

    /** Where to go once fully signed in (also used after the two-step code). */
    public function afterSignIn(Request $request, User $user)
    {
        return $this->authenticated($request, $user) ?: redirect()->intended($this->redirectPath());
    }

    protected function authenticated(Request $request, $user)
    {
        if (!empty($user->is_disabled)) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages([$this->username() => 'This account has been disabled. Please contact support.']);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'last_login_at')) {
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
        }

        if (!empty($user->must_change_password)) {
            return redirect()->route('password.change');
        }

        if ($intendedUrl = session('intended')) {
            session()->forget('intended');

            if ($this->isValidIntendedUrl($intendedUrl, $request)) {
                return redirect($intendedUrl);
            }
        }

        // A driver who only drives (no management role anywhere) goes straight to the driver pages.
        if (! $user->can('dashboard')) {
            $roles = \Illuminate\Support\Facades\DB::table('operator_members')->where('user_id', $user->id)->where('status', 'active')->pluck('role');
            if ($roles->isNotEmpty() && $roles->every(fn ($r) => $r === 'driver')) {
                return redirect()->route('driver.home');
            }
        }

        // A merchant's team goes to the merchant page.
        if (! $user->can('dashboard') && \Illuminate\Support\Facades\DB::table('operator_members as om')->join('merchants as m', 'm.operator_id', '=', 'om.operator_id')
            ->where('om.user_id', $user->id)->where('om.status', 'active')->exists()) {
            return redirect()->route('merchant.home');
        }

        // A provider who is not staff has no admin dashboard; send them to their own workspace.
        if (! $user->can('dashboard') && \Illuminate\Support\Facades\DB::table('operator_members')->where('user_id', $user->id)->where('status', 'active')->exists()) {
            return redirect()->route('provider.dashboard');
        }

        // Everyone else who is not staff is a customer.
        if (! $user->can('dashboard')) {
            return redirect()->route('account.dashboard');
        }

        return redirect()->intended($this->redirectPath());
    }

    protected function isValidIntendedUrl($url, $request)
    {
        $parsedUrl = parse_url($url);

        if (!isset($parsedUrl['host'])) {
            return true;
        }

        return $parsedUrl['host'] === $request->getHost();
    }

    public function username()
    {
        return 'email';
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}