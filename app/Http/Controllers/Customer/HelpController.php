<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Support\SupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/** My account > Help: ask for help (about an order or in general) and follow the replies. */
class HelpController extends Controller
{
    public function __construct(private SupportService $support) {}

    public function index(Request $request)
    {
        $uid = $request->user()->id;

        return view('account.help', [
            'tickets' => $this->support->mine($uid), 'categories' => SupportService::CATEGORIES, 'order' => $request->query('order'),
            'orders' => DB::table('orders as o')->join('shipments as s', 's.order_id', '=', 'o.id')->where('o.customer_id', $uid)->orderByDesc('o.id')->limit(30)
                ->get(['s.public_id', 'o.order_number', 's.status', 'o.created_at']),
            'pagetitle' => 'Help',
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate(['category' => ['required', Rule::in(array_keys(SupportService::CATEGORIES))], 'body' => 'required|string|max:4000', 'order' => 'nullable|string|size:26']);
        try {
            $id = $this->support->open($request->user()->id, $d['category'], $d['body'], $d['order'] ?? null, 'web');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('account.help.show', $id)->with('success', 'Sent. We usually answer within a few hours; you will get a notification when we do.');
    }

    public function show(Request $request, string $ticket)
    {
        try {
            $t = $this->support->thread($ticket, $request->user()->id);
        } catch (RuntimeException) {
            abort(404);
        }

        return view('account.help-thread', $t + ['pagetitle' => 'Help request']);
    }

    public function reply(Request $request, string $ticket)
    {
        $d = $request->validate(['body' => 'required|string|max:4000']);
        try {
            $this->support->customerReply($ticket, $request->user()->id, $d['body']);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sent.');
    }
}
