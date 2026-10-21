<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Modules\Directory\DirectoryService;
use Illuminate\Http\Request;

/** GET /v1/customer/directory?q=@musa&kind=rider&lat=&lng= : find providers, riders and shoppers by @handle or name. */
class DirectoryController extends Controller
{
    public function search(Request $request, DirectoryService $dir)
    {
        $d = $request->validate([
            'q' => 'nullable|string|max:60', 'kind' => 'nullable|in:'.implode(',', array_keys(DirectoryService::KINDS)),
            'lat' => 'nullable|numeric|between:-90,90', 'lng' => 'nullable|numeric|between:-180,180', 'limit' => 'nullable|integer|min:1|max:30',
        ]);
        $q = (string) ($d['q'] ?? '');

        return response()->json([
            'query' => $q,
            'kinds' => DirectoryService::KINDS,
            'results' => $dir->search($q, $d['kind'] ?? null, isset($d['lat']) ? (float) $d['lat'] : null, isset($d['lng']) ? (float) $d['lng'] : null, (int) ($d['limit'] ?? 20)),
        ]);
    }
}
