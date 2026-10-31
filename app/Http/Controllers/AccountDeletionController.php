<?php

namespace App\Http\Controllers;

use App\Models\BrandSetting;
use App\Modules\Identity\AccountDeletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Deleting an account: the public page that explains how (Google Play asks for a web link), the signed-in web
 * action, and the app endpoints (POST /v1/auth/delete-account, GET …/check).
 */
class AccountDeletionController extends Controller
{
    public function __construct(private AccountDeletionService $deletion) {}

    public function info(Request $request)
    {
        if (! $request->user()) {
            // Come back here after signing in.
            session(['url.intended' => route('delete-account'), 'intended' => route('delete-account')]);
        }
        return view('public.delete-account', ['brand' => BrandSetting::forApp('customer'),
            'blockers' => $request->user() ? $this->deletion->blockers($request->user()) : null]);
    }

    public function destroy(Request $request)
    {
        $d = $request->validate(['password' => 'required|string', 'reason' => 'nullable|string|max:300', 'confirm' => 'accepted'],
            ['confirm.accepted' => 'Tick the box to confirm you want your account deleted.']);
        try {
            $this->deletion->delete($request->user(), $d['password'], 'web', $d['reason'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('delete-account')->with('deleted', true);
    }

    public function apiCheck(Request $request)
    {
        return response()->json(['blockers' => $this->deletion->blockers($request->user())]);
    }

    public function apiDestroy(Request $request)
    {
        $d = $request->validate(['password' => 'required|string', 'reason' => 'nullable|string|max:300']);
        try {
            $this->deletion->delete($request->user(), $d['password'], 'app', $d['reason'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['deleted' => true]);
    }
}
