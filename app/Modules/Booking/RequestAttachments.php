<?php

namespace App\Modules\Booking;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Photos and documents a customer adds to a request (the item, a waybill, an invoice for the goods), so providers
 * can price it properly and the driver knows what to collect. Files live on the private disk and are streamed only
 * to the people who can see the request.
 */
class RequestAttachments
{
    public const MAX_FILES = 6;
    public const MAX_KB = 10_240;
    public const MIMES = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

    public function add(int $userId, string $requestPublicId, UploadedFile $file, ?string $caption = null): array
    {
        $req = DB::table('service_requests')->where('public_id', $requestPublicId)->where('customer_id', $userId)->first();
        if (! $req) {
            throw new RuntimeException('Request not found.');
        }
        if (! in_array($req->status, ['open', 'negotiating', 'agreed'], true)) {
            throw new RuntimeException('Files can only be added while the request is open.');
        }
        if (DB::table('request_attachments')->where('request_id', $req->id)->count() >= self::MAX_FILES) {
            throw new RuntimeException('A request can have up to '.self::MAX_FILES.' files.');
        }

        $mime = (string) $file->getMimeType();
        $path = $file->store('request-files/'.date('Y/m'));
        $id = (string) Str::ulid();
        DB::table('request_attachments')->insert([
            'public_id' => $id, 'request_id' => $req->id, 'uploaded_by' => $userId,
            'kind' => str_starts_with($mime, 'image/') ? 'photo' : 'document', 'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 190, ''), 'mime' => $mime, 'size_bytes' => (int) $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()), 'caption' => $caption ? Str::limit(trim($caption), 190, '') : null, 'created_at' => now(),
        ]);

        return $this->shape(DB::table('request_attachments')->where('public_id', $id)->first());
    }

    public function remove(int $userId, string $attachmentPublicId): void
    {
        $row = DB::table('request_attachments as a')->join('service_requests as r', 'r.id', '=', 'a.request_id')
            ->where('a.public_id', $attachmentPublicId)->where('r.customer_id', $userId)->first(['a.id', 'a.path', 'r.status']);
        if (! $row) {
            throw new RuntimeException('File not found.');
        }
        if (! in_array($row->status, ['open', 'negotiating'], true)) {
            throw new RuntimeException('Files cannot be removed once the job is agreed.');
        }
        DB::table('request_attachments')->where('id', $row->id)->delete();
        Storage::delete($row->path);
    }

    public function forRequest(int $requestId): array
    {
        return DB::table('request_attachments')->where('request_id', $requestId)->orderBy('id')->get()->map(fn ($r) => $this->shape($r))->all();
    }

    /** Files for the job a shipment came from, for the provider's job page and the driver. */
    public function forShipment(int $shipmentId): array
    {
        $requestId = DB::table('shipments as s')->join('orders as o', 'o.id', '=', 's.order_id')->join('agreements as a', 'a.id', '=', 'o.agreement_id')
            ->where('s.id', $shipmentId)->value('a.request_id');

        return $requestId ? $this->forRequest((int) $requestId) : [];
    }

    /** The file row when this user may see it: the customer, staff, the invited provider's team, or the job's driver. */
    public function viewable(object $user, string $publicId): ?object
    {
        $a = DB::table('request_attachments as a')->join('service_requests as r', 'r.id', '=', 'a.request_id')
            ->where('a.public_id', $publicId)->first(['a.*', 'r.customer_id']);
        if (! $a) {
            return null;
        }
        if ((int) $a->customer_id === (int) $user->id || $user->can('View delivery') || $user->can('View dispute')) {
            return $a;
        }
        $member = DB::table('request_invitations as i')->join('operator_members as m', 'm.operator_id', '=', 'i.operator_id')
            ->where('i.request_id', $a->request_id)->where('m.user_id', $user->id)->where('m.status', 'active')->exists();
        if ($member) {
            return $a;
        }
        $driver = DB::table('agreements as ag')->join('orders as o', 'o.agreement_id', '=', 'ag.id')->join('shipments as s', 's.order_id', '=', 'o.id')
            ->join('assignments as asg', 'asg.shipment_id', '=', 's.id')->join('driver_profiles as d', 'd.id', '=', 'asg.driver_profile_id')
            ->where('ag.request_id', $a->request_id)->where('d.user_id', $user->id)->exists();

        return $driver ? $a : null;
    }

    private function shape(object $r): array
    {
        return [
            'id' => $r->public_id, 'kind' => $r->kind, 'name' => $r->original_name, 'mime' => $r->mime, 'size_bytes' => (int) $r->size_bytes,
            'caption' => $r->caption, 'url' => url('/api/v1/attachments/'.$r->public_id), 'created_at' => $r->created_at,
        ];
    }
}
