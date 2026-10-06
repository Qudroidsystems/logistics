<?php

namespace App\Http\Controllers;

use App\Models\ResultSendItem;
use Illuminate\Support\Facades\Storage;

/**
 * Public, no-login download of one report card via the secure link sent to
 * parents (/r/{token}). Links expire and every download is counted.
 */
class ResultLinkController extends Controller
{
    public function show(string $token)
    {
        $item = ResultSendItem::with(['student', 'send'])->where('token', $token)->first();

        if (!$item || !$item->pdf_path || !Storage::disk('local')->exists($item->pdf_path)) {
            return response()->view('result-sends.link-expired', ['reason' => 'missing'], 404);
        }
        if ($item->link_expires_at && $item->link_expires_at->isPast()) {
            return response()->view('result-sends.link-expired', ['reason' => 'expired'], 410);
        }
        if ($item->send && $item->send->status === 'cancelled' && $item->status !== 'done') {
            return response()->view('result-sends.link-expired', ['reason' => 'missing'], 404);
        }

        $item->increment('downloads');
        $item->forceFill(['last_downloaded_at' => now()])->save();

        $name = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim(($item->student->firstname ?? '') . '_' . ($item->student->lastname ?? ''))) ?: 'student';
        return response()->file(Storage::disk('local')->path($item->pdf_path), [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Report_Card_' . $name . '.pdf"',
            'Cache-Control'       => 'private, no-store',
            'X-Robots-Tag'        => 'noindex, nofollow',
        ]);
    }
}
