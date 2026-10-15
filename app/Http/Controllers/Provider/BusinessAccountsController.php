<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Billing\BusinessAccountService;
use App\Modules\Billing\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Business accounts (customers who book on credit) and their monthly invoices. Run by the money roles: owner, admin
 * and finance. Dispatch picks an account when booking; that is on the booking form.
 */
class BusinessAccountsController extends WorkspaceController
{
    public function index(Request $request, BusinessAccountService $accounts)
    {
        [$op] = $this->ctx($request, 'money');

        return $this->view('provider.business-accounts', $request, ['accounts' => $accounts->all((int) $op->id), 'pagetitle' => 'Business accounts'], 'money');
    }

    public function show(Request $request, string $account, BusinessAccountService $accounts, InvoiceService $invoices)
    {
        [$op] = $this->ctx($request, 'money');
        try {
            $a = $accounts->find((int) $op->id, $account);
        } catch (RuntimeException) {
            abort(404);
        }
        $unbilled = DB::table('orders as o')->join('shipments as s', 's.order_id', '=', 'o.id')->join('escrow_holds as h', 'h.agreement_id', '=', 'o.agreement_id')
            ->where('o.business_account_id', $a->id)->whereNull('o.invoice_id')->orderByDesc('o.id')->limit(200)
            ->get(['o.order_number', 'o.created_at', 's.public_id', 's.status', 's.tracking_code', DB::raw('GREATEST(h.amount - h.refunded_amount - h.subsidy, 0) AS owed')]);

        return $this->view('provider.business-account', $request, [
            'a' => $a, 'owed' => $accounts->owed((int) $a->id), 'unbilled' => $unbilled, 'invoices' => $invoices->forAccount((int) $a->id),
            'merchantCode' => $a->merchant_id ? DB::table('merchants')->where('id', $a->merchant_id)->value('merchant_code') : null,
            'finished' => InvoiceService::FINISHED, 'pagetitle' => $a->name,
        ], 'money');
    }

    public function store(Request $request, BusinessAccountService $accounts, ?string $account = null)
    {
        [$op] = $this->ctx($request, 'money');
        $d = $request->validate([
            'name' => 'required|string|max:160', 'billing_email' => 'nullable|email:rfc|max:190', 'billing_phone' => 'nullable|string|max:24',
            'billing_address' => 'nullable|string|max:255', 'credit_limit_naira' => 'nullable|numeric|min:0|max:10000000000',
            'terms_days' => 'nullable|integer|min:0|max:120', 'merchant_code' => 'nullable|string|max:24', 'note' => 'nullable|string|max:500',
        ]);
        $d['credit_limit'] = isset($d['credit_limit_naira']) && $d['credit_limit_naira'] !== null ? (int) round(((float) $d['credit_limit_naira']) * 100) : null;
        try {
            $id = $accounts->save((int) $op->id, $d, $account);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('provider.accounts.show', $id)->with('success', $account ? 'Account saved.' : 'Account added. Staff can now book deliveries on it.');
    }

    public function status(Request $request, string $account, BusinessAccountService $accounts)
    {
        [$op] = $this->ctx($request, 'money');
        $status = (string) $request->input('status');
        try {
            $accounts->setStatus((int) $op->id, $account, $status);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', match ($status) {
            'active' => 'The account can take bookings again.',
            'on_hold' => 'On hold. Nothing new can be booked on it until you reopen it.',
            default => 'Closed. What it owes stays on its invoices.',
        });
    }

    /** Bill the finished deliveries now instead of waiting for the 1st of the month. */
    public function issue(Request $request, string $account, BusinessAccountService $accounts, InvoiceService $invoices)
    {
        [$op] = $this->ctx($request, 'money');
        try {
            $i = $invoices->issue((int) $op->id, (int) $accounts->find((int) $op->id, $account)->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with($i ? 'success' : 'error', $i ? "Invoice {$i->number} issued for ".BusinessAccountService::naira((int) $i->total).'.' : 'There are no finished deliveries to bill yet.');
    }

    public function pdf(Request $request, string $invoice, InvoiceService $invoices)
    {
        [$op] = $this->ctx($request, 'money');
        try {
            $f = $invoices->pdf($invoices->find((int) $op->id, $invoice));
        } catch (RuntimeException) {
            abort(404);
        }

        return response($f['content'], 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$f['name'].'"']);
    }

    public function send(Request $request, string $invoice, InvoiceService $invoices)
    {
        [$op] = $this->ctx($request, 'money');
        try {
            $i = $invoices->find((int) $op->id, $invoice);
            $invoices->send($i);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::warning("Invoice {$invoice} email failed: {$e->getMessage()}");

            return back()->with('error', 'The email could not be sent. Check the email settings, or download the PDF and send it yourself.');
        }

        return back()->with('success', "Invoice {$i->number} emailed.");
    }

    public function paid(Request $request, string $invoice, InvoiceService $invoices)
    {
        [$op] = $this->ctx($request, 'money');
        $d = $request->validate(['reference' => 'nullable|string|max:120']);
        try {
            $invoices->markPaid((int) $op->id, $invoice, $request->user()->id, $d['reference'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment recorded.');
    }
}
