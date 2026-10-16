<?php

namespace App\Http\Controllers\Api\Messaging;

use App\Http\Controllers\Controller;
use App\Modules\Messaging\CallService;
use App\Modules\Messaging\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Rider and customer chat and calls, used by both apps (the same routes under /customer and /driver). */
class ChatController extends Controller
{
    public function __construct(private ChatService $chat, private CallService $calls) {}

    public function show(Request $request, string $shipment)
    {
        $data = $this->chat->thread($this->id($shipment), $request->user()->id, (int) $request->query('after', 0));
        abort_unless($data, 404);

        return response()->json($data);
    }

    public function send(Request $request, string $shipment)
    {
        $data = $request->validate(['body' => 'required|string|max:'.ChatService::MAX_LENGTH]);

        return $this->run(fn () => $this->chat->send($this->id($shipment), $request->user()->id, $data['body']));
    }

    public function call(Request $request, string $shipment)
    {
        $data = $request->validate(['who' => 'nullable|in:customer,driver,pickup,dropoff']);
        $id = $this->id($shipment);

        return $this->run(function () use ($request, $id, $data) {
            // The customer can only call their rider; a rider defaults to the customer.
            $who = $data['who'] ?? ($this->chat->role($this->chat->sides($id), $request->user()->id) === 'customer' ? 'driver' : 'customer');

            return $this->calls->start($id, $request->user()->id, $who);
        });
    }

    /** Africa's Talking calls this when a call through the shared number is answered and when it ends. */
    public function voice(Request $request, string $secret)
    {
        $expected = (string) config('services.calls.callback_secret');
        abort_unless($expected !== '' && hash_equals($expected, $secret), 404);

        return response($this->calls->answer($request->all()), 200, ['Content-Type' => 'application/xml']);
    }

    private function id(string $shipment): int
    {
        $id = DB::table('shipments')->where('public_id', $shipment)->value('id');
        abort_unless($id, 404);

        return (int) $id;
    }

    private function run(callable $fn)
    {
        try {
            return response()->json($fn());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
