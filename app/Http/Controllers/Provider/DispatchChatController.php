<?php

namespace App\Http\Controllers\Provider;

use App\Modules\Messaging\DispatchChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** The dispatch desk's side of the rider chat, on the provider's job page (JSON, polled by the page). */
class DispatchChatController extends WorkspaceController
{
    public function show(Request $request, string $shipment, DispatchChatService $chat)
    {
        $data = $chat->thread($this->shipmentId($request, $shipment), $request->user()->id, (int) $request->query('after', 0));
        abort_unless($data, 403);

        return response()->json($data);
    }

    public function send(Request $request, string $shipment, DispatchChatService $chat)
    {
        $d = $request->validate(['body' => 'required|string|max:'.DispatchChatService::MAX_LENGTH]);
        try {
            return response()->json($chat->send($this->shipmentId($request, $shipment), $request->user()->id, $d['body']));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function shipmentId(Request $request, string $shipment): int
    {
        [$op] = $this->ctx($request, 'jobs');
        $id = DB::table('shipments')->where(['public_id' => $shipment, 'operator_id' => $op->id])->value('id');
        abort_unless($id, 404);

        return (int) $id;
    }
}
