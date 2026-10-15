<?php

namespace App\Modules\Booking;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A receipt (invoice) for a paid delivery, as a PDF the customer can download. The first download numbers it and
 * keeps a row in `invoices`, so the same order always gets the same number.
 */
class Receipts
{
    /** @return array the figures and details printed on the receipt */
    public function data(int $orderId, int $customerId): array
    {
        $o = DB::table('orders as o')->join('operators as p', 'p.id', '=', 'o.operator_id')->leftJoin('users as u', 'u.id', '=', 'o.customer_id')
            ->where('o.id', $orderId)->where('o.customer_id', $customerId)
            ->first(['o.*', 'p.display_name as provider', 'p.legal_name as provider_legal', 'u.name as customer_name', 'u.email as customer_email']);
        if (! $o) {
            throw new RuntimeException('Order not found.');
        }
        if ($o->payment_status !== 'paid') {
            throw new RuntimeException('A receipt is available once the order is paid.');
        }

        $invoice = $this->invoice($o);
        $s = DB::table('shipments')->where('order_id', $o->id)->first(['id', 'tracking_code', 'status', 'delivered_at', 'distance_m']);
        $stops = $s ? DB::table('shipment_stops')->where('shipment_id', $s->id)->orderBy('seq')->get(['seq', 'type', 'line1', 'contact_name', 'status', 'completed_at']) : collect();
        $a = $o->agreement_id ? DB::table('agreements')->where('id', $o->agreement_id)->first(['number', 'discount_lines', 'goods_budget']) : null;
        $paid = DB::table('payment_intents')->where('agreement_id', $o->agreement_id ?? 0)->where('status', 'succeeded')->orderByDesc('id')->first(['reference', 'gateway', 'channel', 'paid_at']);
        $brand = DB::table('brand_settings')->where('app', 'customer')->first();

        return [
            'number' => $invoice->number, 'issued_at' => $invoice->created_at, 'order' => $o, 'shipment' => $s, 'stops' => $stops,
            'agreement' => $a->number ?? null, 'discount_lines' => $a && $a->discount_lines ? json_decode($a->discount_lines, true) : [],
            'payment' => $paid, 'brand' => $brand, 'lines' => json_decode($invoice->lines, true),
        ];
    }

    public function pdf(int $orderId, int $customerId): array
    {
        $d = $this->data($orderId, $customerId);

        return ['name' => 'receipt-'.$d['number'].'.pdf', 'content' => Pdf::loadView('receipts.delivery', $d)->setPaper('a4')->output()];
    }

    private function invoice(object $o): object
    {
        $existing = DB::table('invoices')->where('order_id', $o->id)->first();
        if ($existing) {
            return $existing;
        }
        $lines = array_values(array_filter([
            ['label' => 'Delivery', 'amount' => (int) $o->delivery_fee],
            (int) $o->subtotal > 0 ? ['label' => 'Goods budget', 'amount' => (int) $o->subtotal] : null,
            (int) $o->service_fee > 0 ? ['label' => 'Service fee', 'amount' => (int) $o->service_fee] : null,
            (int) $o->tip > 0 ? ['label' => 'Tip', 'amount' => (int) $o->tip] : null,
            (int) $o->discount > 0 ? ['label' => 'Discount', 'amount' => -(int) $o->discount] : null,
        ]));

        DB::table('invoices')->insertOrIgnore([
            'public_id' => (string) Str::ulid(), 'operator_id' => $o->operator_id, 'customer_id' => $o->customer_id, 'order_id' => $o->id,
            'number' => 'RC'.now()->format('ym').'-'.strtoupper(Str::random(6)), 'lines' => json_encode($lines),
            'tax' => (int) $o->tax, 'total' => (int) $o->total, 'status' => 'paid', 'paid_at' => $o->placed_at,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('invoices')->where('order_id', $o->id)->first();
    }
}
