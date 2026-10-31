<?php

namespace App\Http\Controllers;

use App\Modules\Maps\Geocoder;
use Illuminate\Http\Request;
use RuntimeException;

/** Address search for the website and the apps: GET /geo/search?q=&lat=&lng=&session=, GET /geo/place?id=&session=. */
class GeoController extends Controller
{
    public function search(Request $request, Geocoder $geo)
    {
        $d = $request->validate(['q' => 'required|string|max:200', 'lat' => 'nullable|numeric|between:-90,90', 'lng' => 'nullable|numeric|between:-180,180', 'session' => 'nullable|string|max:64']);

        return response()->json(['results' => $geo->search($d['q'], isset($d['lat']) ? (float) $d['lat'] : null, isset($d['lng']) ? (float) $d['lng'] : null, $d['session'] ?? null),
            'type_ahead' => $geo->typeAhead()]);
    }

    public function place(Request $request, Geocoder $geo)
    {
        $d = $request->validate(['id' => 'required|string|max:300', 'session' => 'nullable|string|max:64']);
        try {
            return response()->json($geo->place($d['id'], $d['session'] ?? null));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
