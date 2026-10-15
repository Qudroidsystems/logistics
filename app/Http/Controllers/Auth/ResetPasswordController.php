<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Security\TwoFactorService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /** A reset link proves the email, not the second step: people with two-step sign-in sign in again with their code. */
    protected function resetPassword($user, $password)
    {
        $this->setUserPassword($user, $password);
        $user->setRememberToken(Str::random(60));
        $user->save();
        event(new PasswordReset($user));

        if (! app(TwoFactorService::class)->enabled($user)) {
            $this->guard()->login($user);
        }
    }
}
