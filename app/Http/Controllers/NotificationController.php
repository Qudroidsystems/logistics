<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** The bell: list, open (marks read) and mark all as read. */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('notifications'), 404);
        $user = $request->user();
        $q = $user->notifications();
        if ($request->get('filter') === 'unread') $q = $user->unreadNotifications();

        return view('notifications.index', [
            'pagetitle'     => 'Notifications',
            'notifications' => $q->paginate(25)->withQueryString(),
            'unread'        => $user->unreadNotifications()->count(),
        ]);
    }

    /** Latest items for the bell dropdown. */
    public function feed(Request $request)
    {
        if (!Schema::hasTable('notifications')) return response()->json(['unread' => 0, 'items' => []]);
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items'  => $user->notifications()->limit(8)->get()->map(fn ($n) => [
                'id'    => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'body'  => mb_strimwidth($n->data['body'] ?? '', 0, 140, '…'),
                'icon'  => $n->data['icon'] ?? 'ri-notification-3-line',
                'read'  => (bool) $n->read_at,
                'time'  => $n->created_at->diffForHumans(),
                'url'   => route('notifications.open', $n->id),
            ]),
        ]);
    }

    public function open(Request $request, string $id)
    {
        $n = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();
        $url = $n->data['url'] ?? null;
        return $url ? redirect()->to($url) : redirect()->route('notifications.index');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', 'All notifications marked as read.');
    }
}
