<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Support\SupportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Help requests for the app:
 *   GET  /v1/customer/support                 categories + my requests
 *   POST /v1/customer/support                 {category, body, order?} (order = shipment public id)
 *   GET  /v1/customer/support/{ticket}        the conversation
 *   POST /v1/customer/support/{ticket}/reply  {body}
 */
class SupportApiController extends Controller
{
    public function __construct(private SupportService $support) {}

    public function index(Request $request)
    {
        return response()->json([
            'categories' => collect(SupportService::CATEGORIES)->map(fn ($label, $code) => ['code' => $code, 'label' => $label])->values(),
            'data' => $this->support->mine($request->user()->id),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate(['category' => ['required', Rule::in(array_keys(SupportService::CATEGORIES))], 'body' => 'required|string|max:4000', 'order' => 'nullable|string|size:26']);
        try {
            $id = $this->support->open($request->user()->id, $d['category'], $d['body'], $d['order'] ?? null, 'app');
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->show($request, $id)->setStatusCode(201);
    }

    public function show(Request $request, string $ticket)
    {
        try {
            $t = $this->support->thread($ticket, $request->user()->id);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
        $x = $t['ticket'];

        return response()->json([
            'id' => $x->public_id, 'number' => $x->number, 'subject' => $x->subject, 'status' => $x->status, 'status_label' => SupportService::customerStatus($x->status),
            'order' => $x->order_number, 'shipment' => $x->shipment,
            'messages' => collect($t['messages'])->map(fn ($m) => ['body' => $m['body'], 'mine' => $m['mine'], 'sender' => $m['mine'] ? 'You' : 'Support', 'at' => $m['created_at']])->values(),
        ]);
    }

    public function reply(Request $request, string $ticket)
    {
        $d = $request->validate(['body' => 'required|string|max:4000']);
        try {
            $this->support->customerReply($ticket, $request->user()->id, $d['body']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->show($request, $ticket);
    }
}
