<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * "My account" for customers, providers, merchants and drivers: name, email, photo and password.
 * One page serves the account and driver areas (routes account.me, driver.me) so each keeps its own sidebar.
 * Everything acts on the signed-in user only; there is no id in the URL.
 */
class MeController extends Controller
{
    public function show(Request $request)
    {
        $u = $request->user();
        $area = $this->area($request);

        return view('me.show', [
            'u' => $u, 'area' => $area, 'pagetitle' => 'My account',
            'back' => route($area === 'driver' ? 'driver.home' : 'account.dashboard'),
            'emailVerified' => (bool) $u->email_verified_at,
            'phoneVerified' => (bool) $u->phone_verified_at,
        ]);
    }

    public function profile(Request $request)
    {
        $u = $request->user();
        $data = $request->validate([
            'name'   => 'required|string|max:150',
            'email'  => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($u->id)],
            'avatar' => 'nullable|image|max:2048',
        ]);

        $emailChanged = strcasecmp((string) $u->email, $data['email']) !== 0;
        $u->name = $data['name'];
        if ($emailChanged) {
            $u->email = $data['email'];
            $u->email_verified_at = null;
        }
        if ($request->hasFile('avatar')) {
            $u->avatar = basename($request->file('avatar')->store('avatars', 'public'));
        }
        $u->save();

        return back()->with('success', $emailChanged ? 'Saved. Please confirm your new email address.' : 'Your details are saved.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $u = $request->user();
        $u->forceFill(['password' => Hash::make($request->password)])->save();
        // Sign out the apps and other devices that still hold an old token.
        $u->tokens()->delete();

        return back()->with('success', 'Password changed. Other devices have been signed out.');
    }

    private function area(Request $request): string
    {
        return str_starts_with((string) $request->route()?->getName(), 'driver.') ? 'driver' : 'account';
    }
}
