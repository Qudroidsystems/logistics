<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use Illuminate\Http\Request;

/** Splash name, tagline, logo and colours for the customer and driver apps. */
class BrandingController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin');
    }

    public function index()
    {
        $rows = [];
        foreach (BrandSetting::APPS as $key => $label) {
            $rows[$key] = BrandSetting::query()->firstOrCreate(['app' => $key], ['name' => config('app.name')]);
        }
        return view('admin.branding.index', ['pagetitle' => 'App Branding', 'rows' => $rows, 'apps' => BrandSetting::APPS]);
    }

    public function save(Request $request, string $app)
    {
        abort_unless(array_key_exists($app, BrandSetting::APPS), 404);
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'tagline' => 'nullable|string|max:120',
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        $row = BrandSetting::query()->firstOrCreate(['app' => $app], ['name' => $data['name']]);
        $row->fill([
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
        ]);
        if ($request->hasFile('logo')) {
            $row->logo_path = $request->file('logo')->store('branding', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $row->logo_path = null;
        }
        $row->save();

        return back()->with('success', BrandSetting::APPS[$app] . ' branding saved.');
    }
}
