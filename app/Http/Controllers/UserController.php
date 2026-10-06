<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Admin-side user management (back-office staff and any platform account) plus
 * "my profile / account settings". Customer, driver, shopper, vendor and store
 * onboarding will live in their own modules and reuse this identity table.
 */
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View user', ['only' => ['index', 'show']]);
        $this->middleware('permission:Create user', ['only' => ['store']]);
        $this->middleware('permission:Update user', ['only' => ['update', 'toggleDisabled', 'resetPassword']]);
        $this->middleware('permission:Delete user', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $q = User::query()->with('roles');

        if ($term = trim((string) $request->get('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone_number', 'like', "%{$term}%")
                ->orWhere('username', 'like', "%{$term}%"));
        }
        if ($role = $request->get('role')) {
            $q->whereHas('roles', fn ($r) => $r->where('name', $role));
        }
        if ($request->get('status') === 'disabled') {
            $q->where('is_disabled', true);
        } elseif ($request->get('status') === 'active') {
            $q->where('is_disabled', false);
        }

        return view('users.index', [
            'pagetitle' => 'User Management',
            'users'     => $q->orderBy('name')->paginate(25)->withQueryString(),
            'roles'     => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'email'        => 'required|email|max:190|unique:users,email',
            'phone_number' => 'nullable|string|max:20|unique:users,phone_number',
            'username'     => 'nullable|string|max:60|unique:users,username',
            'role'         => 'nullable|exists:roles,name',
        ]);

        $temp = Str::password(10, symbols: false);
        $user = User::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'username'     => $data['username'] ?? null,
            'password'     => Hash::make($temp),
        ]);
        $user->forceFill(['must_change_password' => true])->save();

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        return redirect()->route('users.index')
            ->with('success', "User created. Temporary password: {$temp} (they must change it at first sign-in).");
    }

    public function show($id)
    {
        $user = User::with('roles', 'permissions')->findOrFail($id);

        return view('users.show', [
            'pagetitle' => $user->name,
            'user'      => $user,
            'roles'     => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'email'        => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone_number')->ignore($user->id)],
            'username'     => ['nullable', 'string', 'max:60', Rule::unique('users', 'username')->ignore($user->id)],
        ]);
        $user->update($data);

        return back()->with('success', 'User updated.');
    }

    public function toggleDisabled($id)
    {
        $user = User::findOrFail($id);
        abort_if($user->id === auth()->id(), 422, 'You cannot disable your own account.');
        $user->forceFill(['is_disabled' => !$user->is_disabled])->save();

        return back()->with('success', $user->is_disabled ? 'Account disabled.' : 'Account enabled.');
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $temp = Str::password(10, symbols: false);
        $user->forceFill(['password' => Hash::make($temp), 'must_change_password' => true])->save();

        return back()->with('success', "Password reset. Temporary password: {$temp}");
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        abort_if($user->id === auth()->id(), 422, 'You cannot delete your own account.');
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }

    /** Used by the legacy role pages: roles held by users. */
    public function roles()
    {
        return response()->json(Role::orderBy('name')->pluck('name', 'id'));
    }

    // ---------------------------------------------------------------- my account

    public function settings($id)
    {
        abort_unless((int) $id === auth()->id() || auth()->user()->can('Update user'), 403);

        return view('users.settings', ['pagetitle' => 'Account Settings', 'user' => User::findOrFail($id)]);
    }

    public function updateProfile(Request $request, $id)
    {
        abort_unless((int) $id === auth()->id() || auth()->user()->can('Update user'), 403);
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone_number')->ignore($user->id)],
            'avatar'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = basename($request->file('avatar')->store('avatars', 'public'));
        } else {
            unset($data['avatar']);
        }
        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request, $id)
    {
        abort_unless((int) $id === auth()->id(), 403);

        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $request->user()->forceFill(['password' => Hash::make($request->password)])->save();

        return back()->with('success', 'Password changed.');
    }
}
