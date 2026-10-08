<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Streams a proof-of-delivery photo from the private disk. Allowed for: staff who can view deliveries,
 * the customer who placed the order, and active team members of the company that carried it.
 */
class ProofController extends Controller
{
    public function show(Request $request, string $proof)
    {
        $row = DB::table('proofs as p')
            ->join('shipments as s', 's.id', '=', 'p.shipment_id')
            ->join('orders as o', 'o.id', '=', 's.order_id')
            ->where('p.public_id', $proof)
            ->first(['p.file_path', 'o.customer_id', 'o.operator_id as order_operator', 's.operator_id as ship_operator']);

        abort_unless($row && $row->file_path, 404);

        $u = $request->user();
        $allowed = $u->can('View delivery') || $u->can('View dispute')
            || (int) $row->customer_id === (int) $u->id
            || DB::table('operator_members')->where('user_id', $u->id)->where('status', 'active')
                ->whereIn('operator_id', array_filter([$row->order_operator, $row->ship_operator]))->exists();
        abort_unless($allowed, 403);

        $disk = Storage::disk();
        abort_unless($disk->exists($row->file_path), 404);

        return $disk->response($row->file_path, null, ['Cache-Control' => 'private, max-age=300']);
    }
}
