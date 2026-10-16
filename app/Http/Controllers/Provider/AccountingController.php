<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Accounting\AccountingSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/** Linking the provider's QuickBooks or Xero, choosing accounts, and the daily journals sent there. Money roles. */
class AccountingController extends WorkspaceController
{
    public function index(Request $request, AccountingSync $sync)
    {
        [$op] = $this->ctx($request, 'money');
        $c = DB::table('accounting_connections')->where('operator_id', $op->id)->first();
        $books = [];
        $loadError = null;
        if ($c && $c->status !== 'disconnected') {
            try {
                $books = $sync->accounts($c);
            } catch (Throwable $e) {
                $loadError = 'Could not load your accounts: '.$e->getMessage();
            }
        }

        return $this->view('provider.accounting', $request, [
            'pagetitle' => 'Accounting', 'conn' => $c, 'books' => $books, 'loadError' => $loadError,
            'mapping' => $c && $c->accounts ? json_decode($c->accounts, true) : [],
            'configured' => collect(AccountingSync::SERVICES)->map(fn ($n, $k) => $sync->gateway($k)->configured())->all(),
            'history' => $c ? DB::table('accounting_syncs')->where('connection_id', $c->id)->orderByDesc('day')->limit(30)->get() : collect(),
        ], 'money');
    }

    public function connect(Request $request, AccountingSync $sync, string $service)
    {
        [$op] = $this->ctx($request, 'money');
        try {
            return redirect()->away($sync->connectUrl((int) $op->id, (int) $request->user()->id, $service));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function callback(Request $request, AccountingSync $sync, string $service)
    {
        [$op] = $this->ctx($request, 'money');
        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('provider.accounting')->with('error', 'The link was not approved.');
        }
        try {
            $c = $sync->finish((int) $op->id, (int) $request->user()->id, $service, (string) $request->query('code'), (string) $request->query('state'), $request->query());
        } catch (Throwable $e) {
            return redirect()->route('provider.accounting')->with('error', $e instanceof RuntimeException ? $e->getMessage() : 'Could not finish linking. Try again.');
        }

        return redirect()->route('provider.accounting')->with('success', 'Linked to '.($c->tenant_name ?: AccountingSync::SERVICES[$service]).'. Now choose which account each kind of money goes to.');
    }

    public function save(Request $request, AccountingSync $sync)
    {
        [$op] = $this->ctx($request, 'money');
        $c = DB::table('accounting_connections')->where('operator_id', $op->id)->where('status', '!=', 'disconnected')->first();
        if (! $c) {
            return back()->with('error', 'Link QuickBooks or Xero first.');
        }
        $rules = ['sync_from' => 'nullable|date|before_or_equal:today'];
        foreach (array_keys(AccountingSync::ACCOUNTS) as $k) {
            $rules["accounts.{$k}"] = 'required|string|max:64';
        }
        $d = $request->validate($rules, ['accounts.*.required' => 'Choose an account for every line.']);
        $sync->saveSettings($c, $d['accounts'], $d['sync_from'] ?? null);
        if ($c->status === 'error') {
            DB::table('accounting_connections')->where('id', $c->id)->update(['status' => 'active']);
        }

        return back()->with('success', 'Saved. Each day\'s journal is sent overnight, or press Send now.');
    }

    public function syncNow(Request $request, AccountingSync $sync)
    {
        [$op] = $this->ctx($request, 'money');
        $c = DB::table('accounting_connections')->where('operator_id', $op->id)->first();
        if (! $c || $c->status === 'disconnected' || ! AccountingSync::mapped($c)) {
            return back()->with('error', 'Link your books and choose the accounts first.');
        }
        if ($c->status === 'error') {
            DB::table('accounting_connections')->where('id', $c->id)->update(['status' => 'active']);
            $c->status = 'active';
        }
        $n = $sync->run($c);
        $after = DB::table('accounting_connections')->where('id', $c->id)->first();

        return $after->status === 'error'
            ? back()->with('error', 'Stopped: '.$after->error)
            : back()->with('success', $n ? "{$n} day(s) sent." : 'Up to date. Nothing new to send.');
    }

    public function disconnect(Request $request, AccountingSync $sync)
    {
        [$op] = $this->ctx($request, 'money');
        $sync->disconnect((int) $op->id);

        return back()->with('success', 'Unlinked. Nothing more will be sent.');
    }
}
