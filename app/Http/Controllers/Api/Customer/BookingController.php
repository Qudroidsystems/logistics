<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Booking\AgreementDiscounts;
use App\Modules\Booking\Receipts;
use App\Modules\Booking\RequestAttachments;
use App\Modules\Booking\SavedAddresses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Customer booking extras: saved addresses, files on a request, promo codes before paying, and receipts. */
class BookingController extends Controller
{
    // ---------- saved addresses

    public function addresses(Request $request, SavedAddresses $svc)
    {
        return response()->json($svc->list($request->user()->id));
    }

    public function saveAddress(Request $request, SavedAddresses $svc, ?string $address = null)
    {
        $d = $request->validate([
            'label' => 'nullable|string|max:60', 'line1' => 'required|string|max:255', 'line2' => 'nullable|string|max:255', 'landmark' => 'nullable|string|max:255',
            'instructions' => 'nullable|string|max:500', 'city_id' => 'nullable|integer|exists:cities,id',
            'contact_name' => 'nullable|string|max:120', 'contact_phone' => 'nullable|string|max:24',
            'lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180', 'is_default' => 'nullable|boolean',
        ]);
        $d['directions_note'] = $d['instructions'] ?? null;

        return $this->run(fn () => $svc->save($request->user()->id, $d, $address), $address ? 200 : 201);
    }

    public function deleteAddress(Request $request, SavedAddresses $svc, string $address)
    {
        return $this->run(function () use ($svc, $request, $address) {
            $svc->remove($request->user()->id, $address);

            return ['ok' => true];
        });
    }

    // ---------- photos and documents on a request

    public function attachments(Request $request, RequestAttachments $svc, string $serviceRequest)
    {
        $req = DB::table('service_requests')->where('public_id', $serviceRequest)->where('customer_id', $request->user()->id)->first();
        abort_unless($req, 404);

        return response()->json($svc->forRequest((int) $req->id));
    }

    public function upload(Request $request, RequestAttachments $svc, string $serviceRequest)
    {
        $request->validate([
            'file' => 'required|file|max:'.RequestAttachments::MAX_KB.'|mimes:'.implode(',', RequestAttachments::MIMES),
            'caption' => 'nullable|string|max:200',
        ]);

        return $this->run(fn () => $svc->add($request->user()->id, $serviceRequest, $request->file('file'), $request->input('caption')), 201);
    }

    public function deleteAttachment(Request $request, RequestAttachments $svc, string $attachment)
    {
        return $this->run(function () use ($svc, $request, $attachment) {
            $svc->remove($request->user()->id, $attachment);

            return ['ok' => true];
        });
    }

    /** Streams the file to anyone who can see the request (customer, invited provider, the job's driver, staff). */
    public function showAttachment(Request $request, RequestAttachments $svc, string $attachment)
    {
        $a = $svc->viewable($request->user(), $attachment);
        abort_unless($a && Storage::exists($a->path), 404);

        return Storage::response($a->path, $a->original_name, ['Cache-Control' => 'private, max-age=300']);
    }

    // ---------- discounts before paying

    public function paymentSummary(Request $request, AgreementDiscounts $svc, string $agreement)
    {
        return $this->run(fn () => $svc->summary($this->agreementId($request, $agreement), $request->user()->id));
    }

    public function applyPromo(Request $request, AgreementDiscounts $svc, string $agreement)
    {
        $d = $request->validate(['code' => 'required|string|max:32']);

        return $this->run(fn () => $svc->applyPromo($this->agreementId($request, $agreement), $request->user()->id, $d['code']));
    }

    public function removePromo(Request $request, AgreementDiscounts $svc, string $agreement)
    {
        return $this->run(fn () => $svc->removePromo($this->agreementId($request, $agreement), $request->user()->id));
    }

    // ---------- receipts

    public function receipt(Request $request, Receipts $svc, string $shipment)
    {
        $orderId = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')
            ->where('s.public_id', $shipment)->where('o.customer_id', $request->user()->id)->value('o.id');
        abort_unless($orderId, 404);
        try {
            $pdf = $svc->pdf((int) $orderId, $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'rejected', 'message' => $e->getMessage()], 422);
        }

        return response($pdf['content'], 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$pdf['name'].'"']);
    }

    private function agreementId(Request $request, string $publicId): int
    {
        $id = DB::table('agreements')->where('public_id', $publicId)->where('customer_id', $request->user()->id)->value('id');
        abort_unless($id, 404);

        return (int) $id;
    }

    private function run(callable $fn, int $status = 200)
    {
        try {
            return response()->json($fn(), $status);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'rejected', 'message' => $e->getMessage()], 422);
        }
    }
}
