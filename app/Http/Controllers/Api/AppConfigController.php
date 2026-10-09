<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use Illuminate\Http\Request;

/** Public: what a mobile app shows before anyone signs in. */
class AppConfigController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(BrandSetting::forApp((string) $request->query('app', 'customer')));
    }
}
