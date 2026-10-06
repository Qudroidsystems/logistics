<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 30), 1), 100);
        $rows = $this->mine($request)->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->orderByDesc('created_at')->limit($limit)->get(['id', 'type', 'data', 'read_at', 'created_at']);

        return response()->json([
            'unread' => $this->mine($request)->whereNull('read_at')->count(),
            'items' => $rows->map(fn ($n) => ['id' => $n->id, 'event' => $n->type, 'read_at' => $n->read_at, 'created_at' => $n->created_at] + (array) json_decode($n->data, true)),
        ]);
    }

    public function read(Request $request, string $id)
    {
        $n = $this->mine($request)->where('id', $id)->update(['read_at' => now(), 'updated_at' => now()]);
        abort_unless($n, 404);

        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request)
    {
        $this->mine($request)->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function mine(Request $request)
    {
        return DB::table('notifications')->where('notifiable_type', User::class)->where('notifiable_id', $request->user()->id);
    }
}
