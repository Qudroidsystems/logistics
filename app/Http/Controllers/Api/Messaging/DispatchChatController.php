<?php

namespace App\Http\Controllers\Api\Messaging;

use App\Http\Controllers\Controller;
use App\Modules\Messaging\DispatchChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** The rider's chat with their company's dispatch desk: /v1/driver/jobs/{shipment}/dispatch-chat. */
class DispatchChatController extends Controller
{
    public function __construct(private DispatchChatService $chat) {}

    public function show(Request $request, string $shipment)
    {
        $data = $this->chat->thread($this->id($shipment), $request->user()->id, (int) $request->query('after', 0));
        abort_unless($data, 404);

        return response()->json($data);
    }

    public function send(Request $request, string $shipment)
    {
        $d = $request->validate(['body' => 'required|string|max:'.DispatchChatService::MAX_LENGTH]);
        try {
            return response()->json($this->chat->send($this->id($shipment), $request->user()->id, $d['body']));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function id(string $shipment): int
    {
        $id = DB::table('shipments')->where('public_id', $shipment)->value('id');
        abort_unless($id, 404);

        return (int) $id;
    }
}
