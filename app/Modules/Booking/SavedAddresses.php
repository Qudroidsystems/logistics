<?php

namespace App\Modules\Booking;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** A customer's favourite places (home, office, mum's house), reused as a pickup or drop-off in one tap. */
class SavedAddresses
{
    public const MAX_PER_USER = 50;

    private const COLUMNS = ['public_id', 'label', 'line1', 'line2', 'landmark', 'directions_note', 'city_id', 'contact_name', 'contact_phone', 'is_default', 'last_used_at'];

    public function list(int $userId): array
    {
        return DB::table('addresses')->where(['owner_type' => 'user', 'owner_id' => $userId])->whereNull('deleted_at')
            ->orderByDesc('is_default')->orderByRaw('last_used_at DESC NULLS LAST')->orderBy('label')
            ->selectRaw(implode(', ', self::COLUMNS).', ST_Y(point::geometry) AS lat, ST_X(point::geometry) AS lng')
            ->get()->map(fn ($r) => $this->shape($r))->all();
    }

    /** @param array{label?:?string, line1:string, line2?:?string, landmark?:?string, directions_note?:?string, city_id?:?int, contact_name?:?string, contact_phone?:?string, lat:float, lng:float, is_default?:bool} $d */
    public function save(int $userId, array $d, ?string $publicId = null): array
    {
        return DB::transaction(function () use ($userId, $d, $publicId) {
            $mine = DB::table('addresses')->where(['owner_type' => 'user', 'owner_id' => $userId])->whereNull('deleted_at');
            if (! $publicId && (clone $mine)->count() >= self::MAX_PER_USER) {
                throw new RuntimeException('You can save up to '.self::MAX_PER_USER.' addresses. Remove one first.');
            }
            if (! empty($d['is_default'])) {
                (clone $mine)->update(['is_default' => false]);
            }
            $row = [
                'label' => $d['label'] ?? null, 'line1' => $d['line1'], 'line2' => $d['line2'] ?? null, 'landmark' => $d['landmark'] ?? null,
                'directions_note' => $d['directions_note'] ?? null, 'city_id' => $d['city_id'] ?? null,
                'contact_name' => $d['contact_name'] ?? null, 'contact_phone' => $d['contact_phone'] ?? null, 'is_default' => (bool) ($d['is_default'] ?? false),
                'point' => DB::raw(sprintf('ST_SetSRID(ST_MakePoint(%F, %F), 4326)::geography', (float) $d['lng'], (float) $d['lat'])),
                'updated_at' => now(),
            ];
            if ($publicId) {
                $n = (clone $mine)->where('public_id', $publicId)->update($row);
                if (! $n) {
                    throw new RuntimeException('Address not found.');
                }
            } else {
                $publicId = (string) Str::ulid();
                DB::table('addresses')->insert($row + ['public_id' => $publicId, 'owner_type' => 'user', 'owner_id' => $userId, 'created_at' => now()]);
            }

            return $this->find($userId, $publicId);
        });
    }

    public function remove(int $userId, string $publicId): void
    {
        $n = DB::table('addresses')->where(['owner_type' => 'user', 'owner_id' => $userId, 'public_id' => $publicId])->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'is_default' => false, 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('Address not found.');
        }
    }

    /** Marks addresses as just used so the most recent ones list first. Matches on the pin, within about 30 m. */
    public function touch(int $userId, array $points): void
    {
        foreach ($points as $p) {
            if (! isset($p['lat'], $p['lng'])) {
                continue;
            }
            DB::table('addresses')->where(['owner_type' => 'user', 'owner_id' => $userId])->whereNull('deleted_at')
                ->whereRaw('ST_DWithin(point, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, 30)', [(float) $p['lng'], (float) $p['lat']])
                ->update(['last_used_at' => now()]);
        }
    }

    public function find(int $userId, string $publicId): ?array
    {
        $r = DB::table('addresses')->where(['owner_type' => 'user', 'owner_id' => $userId, 'public_id' => $publicId])->whereNull('deleted_at')
            ->selectRaw(implode(', ', self::COLUMNS).', ST_Y(point::geometry) AS lat, ST_X(point::geometry) AS lng')->first();

        return $r ? $this->shape($r) : null;
    }

    private function shape(object $r): array
    {
        return [
            'id' => $r->public_id, 'label' => $r->label, 'line1' => $r->line1, 'line2' => $r->line2, 'landmark' => $r->landmark,
            'instructions' => $r->directions_note, 'city_id' => $r->city_id ? (int) $r->city_id : null,
            'contact_name' => $r->contact_name, 'contact_phone' => $r->contact_phone, 'is_default' => (bool) $r->is_default,
            'lat' => (float) $r->lat, 'lng' => (float) $r->lng, 'last_used_at' => $r->last_used_at,
        ];
    }
}
