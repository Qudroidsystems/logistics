<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Services\Parents\ParentAccountService;
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
     * matched to its account (parents sign in with their phone).
     */
    protected function credentials(Request $request)
    {
        $login = trim((string) $request->input($this->username()));
        if ($login !== '' && !str_contains($login, '@')) {
            if ($user = ParentAccountService::findByPhone($login)) {
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
            throw ValidationException::withMessages([$this->username() => 'This account has been disabled. Please contact the school.']);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'last_login_at')) {
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
        }

        if (!empty($user->must_change_password)) {
            return redirect()->route('parent.password');
        }

        if ($user->hasRole(ParentAccountService::ROLE) && !$user->can('dashboard')) {
            session()->forget('intended');
            return redirect()->intended(route('parent.dashboard'));
        }

        if ($intendedUrl = session('intended')) {
            session()->forget('intended');

            if ($this->isValidIntendedUrl($intendedUrl, $request)) {
                return redirect($intendedUrl);
            }
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