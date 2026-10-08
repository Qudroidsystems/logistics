<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard';

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