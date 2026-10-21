<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BrandSetting;
use App\Support\Platform;
use Illuminate\Http\Request;

/** Public: what a mobile app shows before anyone signs in. */
class AppConfigController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(BrandSetting::forApp((string) $request->query('app', 'customer')) + [
            'platform' => Platform::forApp(),
            // Currency, phone format and where maps open, from config/region.php.
            'region' => \App\Support\Money::forClient() + [
                'country' => config('region.country'), 'calling_code' => config('region.calling_code'), 'phone_example' => config('region.phone_example'),
                'map' => config('region.map'), 'timezone' => config('app.timezone'),
            ],
        ]);
    }
}
