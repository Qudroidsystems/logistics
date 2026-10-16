<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Cod\CodService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Cash on delivery: what each rider is carrying, counting it in at the office, and paying senders. Run by the money roles.
 */
class CashController extends WorkspaceController
{
    public function index(Request $request, CodService $cod)
    {
        [$op] = $this->ctx($request, 'money');

        return $this->view('provider.cash', $request, $cod->forProvider((int) $op->id) + ['pagetitle' => 'Cash on delivery'], 'money');
    }

    public function handIn(Request $request, CodService $cod)
    {
        [$op] = $this->ctx($request, 'money');
        $d = $request->validate(['ids' => 'required|array|min:1|max:500', 'ids.*' => 'integer']);
        try {
            $total = $cod->handIn((int) $op->id, array_map('intval', $d['ids']), (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', CodService::naira($total).' counted in at the office.');
    }

    public function payOut(Request $request, CodService $cod)
    {
        [$op] = $this->ctx($request, 'money');
        $d = $request->validate(['ids' => 'required|array|min:1|max:500', 'ids.*' => 'integer', 'reference' => 'required|string|max:80']);
        try {
            $total = $cod->payOut((int) $op->id, array_map('intval', $d['ids']), $d['reference'], (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', CodService::naira($total).' recorded as paid. The sender has been told.');
    }
}
