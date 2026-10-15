<?php

namespace App\Modules\Billing;

use App\Mail\NoticeMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Monthly invoices for business accounts.
 *
 *   - An invoice bills every delivery on the account that is finished (delivered, closed as failed, cancelled or
 *     returned) and booked by the end of the period, and is not on an earlier invoice. Jobs still on the road wait
 *     for the next one. The amount per job is what the business owes after any refund under the agreement.
 *   - Numbers run per provider and month: INV-2611-0001. Due on the issue date plus the account's terms.
 *   - `invoices:monthly` (the 1st of each month) bills last month for every account; staff can also bill an account
 *     straight away from its page.
 *   - The provider records the payment when the money arrives.
 */
class InvoiceService
{
    public const FINISHED = ['delivered', 'confirmed', 'completed', 'cancelled', 'failed_attempt', 'returned'];

    /** Bills the account's finished, unbilled deliveries up to $until. Null when there is nothing to bill. */
    public function issue(int $operatorId, int $accountId, ?CarbonImmutable $until = null, ?CarbonImmutable $from = null): ?object
    {
        $until ??= CarbonImmutable::now();

        return DB::transaction(function () use ($operatorId, $accountId, $until, $from) {
            $a = DB::table('business_accounts')->where('id', $accountId)->where('operator_id', $operatorId)->lockForUpdate()->first();
            if (! $a) {
                throw new RuntimeException('That business account was not found.');
            }
            $rows = DB::table('orders as o')->join('shipments as s', 's.order_id', '=', 'o.id')->join('escrow_holds as h', 'h.agreement_id', '=', 'o.agreement_id')
                ->leftJoin('agreements as ag', 'ag.id', '=', 'o.agreement_id')
                ->where('o.business_account_id', $accountId)->whereNull('o.invoice_id')->whereIn('s.status', self::FINISHED)
                ->where('o.created_at', '<=', $until->endOfDay())->orderBy('o.id')
                ->get(['o.id', 'o.order_number', 'o.created_at', 'o.external_order_id', 's.id as shipment_id', 's.status', 's.tracking_code',
                    DB::raw('GREATEST(h.amount - h.refunded_amount - h.subsidy, 0) AS owed'), DB::raw("ag.terms->>'external_order_id' AS ext")]);
            if ($rows->isEmpty()) {
                return null;
            }

            $stops = DB::table('shipment_stops')->whereIn('shipment_id', $rows->pluck('shipment_id'))->whereIn('type', ['pickup', 'dropoff'])
                ->orderBy('seq')->get(['shipment_id', 'type', 'line1'])->groupBy('shipment_id');
            $lines = [];
            foreach ($rows as $r) {
                if ((int) $r->owed <= 0) {
                    continue;   // fully refunded (cancelled early): nothing to bill, but it is settled by this invoice
                }
                $st = $stops[$r->shipment_id] ?? collect();
                $lines[] = [
                    'order' => $r->order_number, 'date' => CarbonImmutable::parse($r->created_at)->toDateString(), 'reference' => $r->external_order_id ?: $r->ext,
                    'tracking' => $r->tracking_code, 'from' => $st->firstWhere('type', 'pickup')->line1 ?? null,
                    'to' => $st->where('type', 'dropoff')->last()->line1 ?? null, 'status' => $r->status, 'amount' => (int) $r->owed,
                ];
            }
            $total = array_sum(array_column($lines, 'amount'));
            $issued = CarbonImmutable::now();
            $prefix = 'INV-'.$issued->format('ym').'-';
            $seq = 1 + (int) DB::table('invoices')->where('operator_id', $operatorId)->where('number', 'like', $prefix.'%')->count();

            $id = DB::table('invoices')->insertGetId([
                'public_id' => (string) Str::ulid(), 'operator_id' => $operatorId, 'business_account_id' => $accountId,
                'number' => $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                'period_start' => ($from ?? CarbonImmutable::parse($rows->min('created_at')))->toDateString(), 'period_end' => $until->toDateString(),
                'lines' => json_encode($lines), 'tax' => 0, 'total' => $total,
                // Nothing to pay (every job was refunded): the invoice is a statement and is already settled.
                'status' => $total > 0 ? 'issued' : 'paid', 'paid_at' => $total > 0 ? null : now(),
                'due_on' => $issued->addDays((int) $a->terms_days)->toDateString(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('orders')->whereIn('id', $rows->pluck('id'))->update(['invoice_id' => $id, 'updated_at' => now()]);

            return DB::table('invoices')->find($id);
        });
    }

    /**
     * Bills last month for every account of every provider. Safe to run again: billed deliveries are not billed twice.
     *
     * @return int invoices issued
     */
    public function monthly(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $from = $now->subMonthNoOverflow()->startOfMonth();
        $until = $from->endOfMonth();
        $n = 0;
        $accounts = DB::table('business_accounts')->where('status', '!=', 'closed')
            ->whereExists(fn ($q) => $q->from('orders')->whereColumn('orders.business_account_id', 'business_accounts.id')->whereNull('orders.invoice_id'))
            ->get(['id', 'operator_id']);
        foreach ($accounts as $a) {
            if ($this->issue((int) $a->operator_id, (int) $a->id, $until, $from)) {
                $n++;
            }
        }

        return $n;
    }

    /** @return list<object> the account's invoices, newest first, with 'overdue' worked out */
    public function forAccount(int $accountId): array
    {
        $today = CarbonImmutable::now()->toDateString();

        return DB::table('invoices')->where('business_account_id', $accountId)->orderByDesc('id')->get()
            ->map(fn ($i) => tap($i, fn ($i) => $i->overdue = $i->status === 'issued' && $i->due_on < $today))->all();
    }

    public function find(int $operatorId, string $publicId): object
    {
        $i = DB::table('invoices')->where('operator_id', $operatorId)->where('public_id', $publicId)->whereNotNull('business_account_id')->first();
        if (! $i) {
            throw new RuntimeException('That invoice was not found.');
        }

        return $i;
    }

    /**
     * The money arrived. (Deliveries on account are already 'paid' as far as dispatch is concerned, because the
     * provider funded their escrow; the invoice is what tracks the business's debt.)
     */
    public function markPaid(int $operatorId, string $publicId, int $staffUserId, ?string $reference = null): void
    {
        DB::transaction(function () use ($operatorId, $publicId, $staffUserId, $reference) {
            $i = DB::table('invoices')->where('operator_id', $operatorId)->where('public_id', $publicId)->whereNotNull('business_account_id')->lockForUpdate()->first();
            if (! $i) {
                throw new RuntimeException('That invoice was not found.');
            }
            if ($i->status === 'paid') {
                throw new RuntimeException("Invoice {$i->number} is already paid.");
            }
            DB::table('invoices')->where('id', $i->id)->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => $staffUserId,
                'paid_reference' => $reference ? mb_substr($reference, 0, 120) : null, 'updated_at' => now()]);
        });
    }

    /** @return array{name:string, content:string} */
    public function pdf(object $invoice): array
    {
        $a = DB::table('business_accounts')->find($invoice->business_account_id);
        $p = DB::table('operators')->find($invoice->operator_id);
        // Where to pay: the provider's default payout account.
        $bank = DB::table('bank_accounts')->where('owner_type', 'operator')->where('owner_id', $invoice->operator_id)->whereNull('deleted_at')
            ->orderByDesc('is_default')->orderBy('id')->first(['bank_name', 'account_name', 'account_number']);
        if ($bank) {
            try {
                $bank->account_number = \Illuminate\Support\Facades\Crypt::decryptString($bank->account_number);
            } catch (\Throwable) {
                $bank = null;
            }
        }

        return ['name' => "{$invoice->number}.pdf", 'content' => Pdf::loadView('billing.invoice', [
            'invoice' => $invoice, 'account' => $a, 'provider' => $p, 'bank' => $bank, 'lines' => json_decode($invoice->lines, true) ?: [],
            'overdue' => $invoice->status === 'issued' && $invoice->due_on < CarbonImmutable::now()->toDateString(),
        ])->setPaper('a4')->output()];
    }

    /** Emails the invoice as a PDF to the account's billing email. */
    public function send(object $invoice): void
    {
        $a = DB::table('business_accounts')->find($invoice->business_account_id);
        if (! $a || ! $a->billing_email) {
            throw new RuntimeException('Add a billing email to the account first.');
        }
        $provider = (string) DB::table('operators')->where('id', $invoice->operator_id)->value('display_name');
        $pdf = $this->pdf($invoice);
        $path = "invoices/{$invoice->public_id}.pdf";
        Storage::disk('local')->put($path, $pdf['content']);
        $due = CarbonImmutable::parse($invoice->due_on)->format('j M Y');
        $text = "Please find attached invoice {$invoice->number} from {$provider} for ".BusinessAccountService::naira((int) $invoice->total).", due on {$due}.";
        try {
            Mail::to($a->billing_email)->send(new NoticeMail("Invoice {$invoice->number} from {$provider}", $text, $a->name, $provider, null,
                ['path' => Storage::disk('local')->path($path), 'filename' => $pdf['name']]));
        } finally {
            Storage::disk('local')->delete($path);
        }
        DB::table('invoices')->where('id', $invoice->id)->update(['sent_at' => now(), 'updated_at' => now()]);
    }
}
