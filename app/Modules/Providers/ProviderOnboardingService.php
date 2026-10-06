<?php

namespace App\Modules\Providers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Registration and review of providers: logistics companies, independent riders/vehicle owners and market shoppers.
 * Every provider is an `operators` row; nothing is listed or can take work until staff approve the application.
 */
class ProviderOnboardingService
{
    public const TYPES = ['company', 'independent_driver', 'market_shopper'];

    /** Operator-level documents needed before an application can be submitted and approved. Vehicle documents are checked separately. */
    public const REQUIRED_DOCS = [
        'company' => ['cac_certificate', 'national_id'],
        'independent_driver' => ['national_id', 'drivers_licence'],
        'market_shopper' => ['national_id', 'utility_bill'],
    ];

    public const VEHICLE_DOCS = ['vehicle_registration', 'insurance', 'roadworthiness'];

    private const CAPABILITIES = [
        'company' => ['own_fleet', 'accept_marketplace_jobs', 'negotiate', 'list_in_directory'],
        'independent_driver' => ['accept_marketplace_jobs', 'negotiate', 'list_in_directory'],
        'market_shopper' => ['accept_marketplace_jobs', 'negotiate', 'list_in_directory', 'shopping_errands'],
    ];

    // ---------------------------------------------------------------- registration

    /** @return int the new operator id */
    public function register(int $userId, string $type, string $legalName, string $displayName, ?int $cityId): int
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Unknown provider type.');
        }
        $has = DB::table('operator_members as m')->join('operators as o', 'o.id', '=', 'm.operator_id')
            ->where('m.user_id', $userId)->where('m.role', 'owner')->whereIn('o.type', self::TYPES)->whereNull('o.deleted_at')->exists();
        if ($has) {
            throw new RuntimeException('You already have a provider account.');
        }

        return DB::transaction(function () use ($userId, $type, $legalName, $displayName, $cityId) {
            $slug = $this->uniqueSlug($displayName);
            $opId = DB::table('operators')->insertGetId([
                'public_id' => (string) Str::ulid(), 'type' => $type, 'legal_name' => $legalName, 'display_name' => $displayName,
                'slug' => $slug, 'status' => 'pending', 'home_city_id' => $cityId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('operator_members')->insert([
                'operator_id' => $opId, 'user_id' => $userId, 'role' => 'owner', 'status' => 'active', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('users')->where('id', $userId)->update(['current_operator_id' => $opId]);
            DB::table('provider_profiles')->insert([
                'public_id' => (string) Str::ulid(), 'operator_id' => $opId, 'public_slug' => $slug, 'listed' => false,
                'service_types' => json_encode([]), 'vehicle_types' => json_encode([]), 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (self::CAPABILITIES[$type] as $cap) {
                DB::table('operator_capabilities')->insert(['operator_id' => $opId, 'capability' => $cap, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('provider_applications')->insert(['public_id' => (string) Str::ulid(), 'operator_id' => $opId, 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
            if ($type !== 'company') {
                DB::table('driver_profiles')->insert([
                    'public_id' => (string) Str::ulid(), 'user_id' => $userId, 'operator_id' => $opId, 'status' => 'applied', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return $opId;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'provider';
        $slug = $base;
        while (DB::table('operators')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(5));
        }

        return $slug;
    }

    // ---------------------------------------------------------------- profile

    public function updateProfile(int $opId, array $d): void
    {
        $this->assertEditable($opId);
        $row = array_intersect_key($d, array_flip(['headline', 'about', 'years_operating', 'min_job_value']));
        foreach (['languages', 'coverage'] as $k) {
            if (array_key_exists($k, $d)) {
                $row[$k] = json_encode(array_values((array) $d[$k]));
            }
        }
        if ($row) {
            $row['updated_at'] = now();
            DB::table('provider_profiles')->where('operator_id', $opId)->update($row);
        }
        if (! empty($d['display_name'])) {
            DB::table('operators')->where('id', $opId)->update(['display_name' => $d['display_name'], 'updated_at' => now()]);
        }
    }

    /** Availability (accepting | busy | away) can change at any time, including after approval. */
    public function setAvailability(int $opId, string $status): void
    {
        DB::table('provider_profiles')->where('operator_id', $opId)->update(['availability_status' => $status, 'updated_at' => now()]);
    }

    /** Edits are locked only while staff are reviewing, so the reviewer sees what the provider submitted. */
    private function assertEditable(int $opId): void
    {
        if (DB::table('provider_applications')->where('operator_id', $opId)->value('status') === 'submitted') {
            throw new RuntimeException('Your application is under review and cannot be edited right now.');
        }
    }

    // ---------------------------------------------------------------- documents

    /** @return array{0:string,1:int} [subject_type, subject_id] for an operator-level document */
    public function subject(int $opId): array
    {
        if (DB::table('operators')->where('id', $opId)->value('type') === 'company') {
            return ['operator', $opId];
        }

        return ['driver', (int) DB::table('driver_profiles')->where('operator_id', $opId)->value('id')];
    }

    public function addDocument(int $opId, string $docType, ?string $number, UploadedFile $file, ?string $expiresOn, ?string $vehiclePublicId = null): string
    {
        $this->assertEditable($opId);
        if ($vehiclePublicId) {
            $vehicle = DB::table('vehicles')->where('public_id', $vehiclePublicId)->where('operator_id', $opId)->first();
            if (! $vehicle || ! in_array($docType, self::VEHICLE_DOCS, true)) {
                throw new RuntimeException('That document does not belong to this vehicle.');
            }
            [$subjectType, $subjectId] = ['vehicle', (int) $vehicle->id];
        } else {
            if (! in_array($docType, ['cac_certificate', 'national_id', 'drivers_licence', 'utility_bill', 'guarantor_form'], true)) {
                throw new RuntimeException('That document type is not accepted here.');
            }
            [$subjectType, $subjectId] = $this->subject($opId);
        }

        $path = $file->storeAs("kyc/{$opId}", Str::ulid().'.'.$file->getClientOriginalExtension(), 'local');
        $norm = $number ? preg_replace('/[^A-Za-z0-9]/', '', strtoupper($number)) : null;
        $scope = ['operator_id' => $opId, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'doc_type' => $docType];

        return DB::transaction(function () use ($scope, $path, $norm, $expiresOn, $docType) {
            // A new upload replaces any earlier one that was not approved.
            $old = DB::table('kyc_documents')->where($scope)->whereIn('status', ['pending', 'rejected']);
            $oldPaths = (clone $old)->pluck('file_path');
            $old->delete();
            foreach ($oldPaths as $p) {
                Storage::disk('local')->delete($p);
            }
            $pid = (string) Str::ulid();
            DB::table('kyc_documents')->insert($scope + [
                'public_id' => $pid,
                'number_hash' => $norm ? hash_hmac('sha256', $norm, (string) config('app.key')) : null, 'number_last4' => $norm ? substr($norm, -4) : null,
                'file_path' => $path, 'expires_on' => $expiresOn, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $pid;
        });
    }

    public function documents(int $opId)
    {
        return DB::table('kyc_documents')->where('operator_id', $opId)->orderBy('id')
            ->get(['public_id', 'subject_type', 'subject_id', 'doc_type', 'number_last4', 'expires_on', 'status', 'rejection_reason', 'created_at']);
    }

    // ---------------------------------------------------------------- vehicles

    public function addVehicle(int $opId, array $d): string
    {
        $this->assertEditable($opId);
        $type = DB::table('vehicle_types')->where('id', $d['vehicle_type_id'])->where('active', true)->first();
        if (! $type) {
            throw new RuntimeException('Unknown vehicle type.');
        }
        $plate = strtoupper(preg_replace('/\s+/', '', $d['plate']));
        if (DB::table('vehicles')->where(['operator_id' => $opId, 'plate' => $plate])->whereNull('deleted_at')->exists()) {
            throw new RuntimeException('That plate number is already registered on your account.');
        }
        $ownership = DB::table('operators')->where('id', $opId)->value('type') === 'company' ? 'operator_owned' : 'driver_owned';
        $pid = (string) Str::ulid();
        DB::table('vehicles')->insert([
            'public_id' => $pid, 'operator_id' => $opId, 'vehicle_type_id' => $type->id, 'plate' => $plate, 'make' => $d['make'] ?? null, 'model' => $d['model'] ?? null,
            'year' => $d['year'] ?? null, 'colour' => $d['colour'] ?? null, 'ownership' => $ownership, 'status' => 'pending',
            'insurance_expires_on' => $d['insurance_expires_on'] ?? null, 'roadworthy_expires_on' => $d['roadworthy_expires_on'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->syncProfile($opId);

        return $pid;
    }

    // ---------------------------------------------------------------- rate cards and service areas

    public function saveRateCard(int $opId, array $d, ?int $cardId = null): int
    {
        $this->assertEditable($opId);
        if (! DB::table('service_types')->where('id', $d['service_type_id'])->where('active', true)->exists()) {
            throw new RuntimeException('Unknown service type.');
        }
        if (! empty($d['vehicle_type_id']) && ! DB::table('vehicle_types')->where('id', $d['vehicle_type_id'])->where('active', true)->exists()) {
            throw new RuntimeException('Unknown vehicle type.');
        }
        $row = [
            'service_type_id' => $d['service_type_id'], 'vehicle_type_id' => $d['vehicle_type_id'] ?? null, 'city_id' => $d['city_id'] ?? null,
            'base' => $d['base'], 'per_km' => $d['per_km'], 'per_kg' => $d['per_kg'] ?? 0, 'min_fee' => $d['min_fee'] ?? 0,
            'negotiable' => $d['negotiable'] ?? true, 'instant_book' => $d['instant_book'] ?? false, 'active' => $d['active'] ?? true, 'updated_at' => now(),
        ];
        if ($cardId) {
            if (! DB::table('provider_rate_cards')->where(['id' => $cardId, 'operator_id' => $opId])->update($row)) {
                throw new RuntimeException('Rate card not found.');
            }
            $id = $cardId;
        } else {
            $id = DB::table('provider_rate_cards')->insertGetId($row + ['operator_id' => $opId, 'created_at' => now()]);
        }
        $this->syncProfile($opId);

        return $id;
    }

    public function deleteRateCard(int $opId, int $cardId): void
    {
        $this->assertEditable($opId);
        DB::table('provider_rate_cards')->where(['id' => $cardId, 'operator_id' => $opId])->delete();
        $this->syncProfile($opId);
    }

    /** @param int[] $zoneIds */
    public function setServiceAreas(int $opId, array $zoneIds): void
    {
        $this->assertEditable($opId);
        $zoneIds = array_values(array_unique(array_map('intval', $zoneIds)));
        $valid = DB::table('zones')->whereIn('id', $zoneIds)->where('active', true)->where('type', 'service')
            ->where(fn ($q) => $q->whereNull('operator_id')->orWhere('operator_id', $opId))->pluck('id')->all();
        if (count($valid) !== count($zoneIds)) {
            throw new RuntimeException('One or more zones are not available.');
        }
        DB::transaction(function () use ($opId, $valid) {
            DB::table('operator_service_areas')->where('operator_id', $opId)->delete();
            foreach ($valid as $z) {
                DB::table('operator_service_areas')->insert(['operator_id' => $opId, 'zone_id' => $z, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    /** Directory search matches on these, so they are derived from what the provider actually prices and drives. */
    private function syncProfile(int $opId): void
    {
        $cards = DB::table('provider_rate_cards')->where(['operator_id' => $opId, 'active' => true]);
        $services = (clone $cards)->pluck('service_type_id')->unique()->values()->map(fn ($v) => (int) $v)->all();
        $vehicleTypes = (clone $cards)->whereNotNull('vehicle_type_id')->pluck('vehicle_type_id')
            ->merge(DB::table('vehicles')->where('operator_id', $opId)->whereNull('deleted_at')->pluck('vehicle_type_id'))
            ->unique()->values()->map(fn ($v) => (int) $v)->all();
        DB::table('provider_profiles')->where('operator_id', $opId)->update([
            'service_types' => json_encode($services), 'vehicle_types' => json_encode($vehicleTypes),
            'starting_price_hint' => (clone $cards)->min('min_fee'),
            'fleet_size' => DB::table('vehicles')->where('operator_id', $opId)->whereNull('deleted_at')->count(), 'updated_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------- submit

    /** @return string[] what is still missing; empty means ready to submit */
    public function missing(int $opId): array
    {
        $type = DB::table('operators')->where('id', $opId)->value('type');
        [$st, $sid] = $this->subject($opId);
        $have = DB::table('kyc_documents')->where(['operator_id' => $opId, 'subject_type' => $st, 'subject_id' => $sid])->whereIn('status', ['pending', 'approved'])->pluck('doc_type')->all();
        $out = [];
        foreach (self::REQUIRED_DOCS[$type] ?? [] as $doc) {
            if (! in_array($doc, $have, true)) {
                $out[] = "upload your {$doc}";
            }
        }
        if (! DB::table('provider_rate_cards')->where(['operator_id' => $opId, 'active' => true])->exists()) {
            $out[] = 'add at least one rate card';
        }
        if ($type === 'independent_driver' && ! $this->hasVehicleWithRegistration($opId, ['pending', 'approved'])) {
            $out[] = 'add a vehicle with its registration document';
        }
        if (! DB::table('operator_service_areas')->where(['operator_id' => $opId, 'active' => true])->exists()) {
            $out[] = 'choose at least one service area';
        }

        return $out;
    }

    private function hasVehicleWithRegistration(int $opId, array $statuses): bool
    {
        return DB::table('kyc_documents as d')->join('vehicles as v', 'v.id', '=', 'd.subject_id')
            ->where('d.subject_type', 'vehicle')->where('d.doc_type', 'vehicle_registration')->where('v.operator_id', $opId)->whereNull('v.deleted_at')
            ->whereIn('d.status', $statuses)->exists();
    }

    public function submit(int $opId): void
    {
        $status = DB::table('provider_applications')->where('operator_id', $opId)->value('status');
        if (! in_array($status, ['draft', 'needs_changes'], true)) {
            throw new RuntimeException($status === 'approved' ? 'Your account is already approved.' : 'Your application is already with our team.');
        }
        if ($missing = $this->missing($opId)) {
            throw new RuntimeException('Not ready to submit: '.implode('; ', $missing).'.');
        }
        DB::table('provider_applications')->where('operator_id', $opId)->update([
            'status' => 'submitted', 'submitted_at' => now(), 'submissions' => DB::raw('submissions + 1'), 'updated_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------- staff review

    public function approveDocument(string $docPublicId, int $staffId): void
    {
        $n = DB::table('kyc_documents')->where('public_id', $docPublicId)->where('status', 'pending')
            ->update(['status' => 'approved', 'reviewed_by' => $staffId, 'reviewed_at' => now(), 'rejection_reason' => null, 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('Document not found or already reviewed.');
        }
    }

    public function rejectDocument(string $docPublicId, int $staffId, string $reason): void
    {
        $n = DB::table('kyc_documents')->where('public_id', $docPublicId)->where('status', 'pending')
            ->update(['status' => 'rejected', 'reviewed_by' => $staffId, 'reviewed_at' => now(), 'rejection_reason' => $reason, 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('Document not found or already reviewed.');
        }
    }

    public function approve(int $opId, int $staffId, ?string $note = null): void
    {
        DB::transaction(function () use ($opId, $staffId, $note) {
            $app = DB::table('provider_applications')->where('operator_id', $opId)->lockForUpdate()->first();
            if (! $app || $app->status !== 'submitted') {
                throw new RuntimeException('This application is not waiting for review.');
            }
            $type = DB::table('operators')->where('id', $opId)->value('type');
            [$st, $sid] = $this->subject($opId);
            $approved = DB::table('kyc_documents')->where(['operator_id' => $opId, 'subject_type' => $st, 'subject_id' => $sid, 'status' => 'approved'])->pluck('doc_type')->all();
            if ($lacking = array_diff(self::REQUIRED_DOCS[$type] ?? [], $approved)) {
                throw new RuntimeException('Approve these documents first: '.implode(', ', $lacking).'.');
            }
            if ($type === 'independent_driver' && ! $this->hasVehicleWithRegistration($opId, ['approved'])) {
                throw new RuntimeException('Approve the vehicle registration document first.');
            }

            DB::table('operators')->where('id', $opId)->update(['status' => 'active', 'approved_at' => now(), 'approved_by' => $staffId, 'updated_at' => now()]);
            DB::table('provider_applications')->where('operator_id', $opId)->update(['status' => 'approved', 'reviewed_by' => $staffId, 'reviewed_at' => now(), 'review_note' => $note, 'updated_at' => now()]);
            // Only vehicles whose registration was approved become usable.
            DB::table('vehicles')->where('operator_id', $opId)->where('status', 'pending')->whereExists(
                fn ($q) => $q->select(DB::raw(1))->from('kyc_documents as d')->whereColumn('d.subject_id', 'vehicles.id')
                    ->where('d.subject_type', 'vehicle')->where('d.doc_type', 'vehicle_registration')->where('d.status', 'approved')
            )->update(['status' => 'active', 'updated_at' => now()]);
            DB::table('driver_profiles')->where('operator_id', $opId)->update(['status' => 'active', 'kyc_status' => 'approved', 'onboarded_at' => now(), 'updated_at' => now()]);

            $listed = DB::table('operator_capabilities')->where(['operator_id' => $opId, 'capability' => 'list_in_directory', 'enabled' => true])->exists();
            DB::table('provider_profiles')->where('operator_id', $opId)->update([
                'listed' => $listed, 'tier' => 'verified', 'verified_badges' => json_encode(['kyc_complete']), 'updated_at' => now(),
            ]);
        });
    }

    public function requestChanges(int $opId, int $staffId, string $note): void
    {
        $n = DB::table('provider_applications')->where('operator_id', $opId)->where('status', 'submitted')
            ->update(['status' => 'needs_changes', 'reviewed_by' => $staffId, 'reviewed_at' => now(), 'review_note' => $note, 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('This application is not waiting for review.');
        }
    }

    public function reject(int $opId, int $staffId, string $note): void
    {
        DB::transaction(function () use ($opId, $staffId, $note) {
            $n = DB::table('provider_applications')->where('operator_id', $opId)->whereIn('status', ['submitted', 'needs_changes'])
                ->update(['status' => 'rejected', 'reviewed_by' => $staffId, 'reviewed_at' => now(), 'review_note' => $note, 'updated_at' => now()]);
            if (! $n) {
                throw new RuntimeException('This application cannot be rejected.');
            }
            DB::table('operators')->where('id', $opId)->update(['status' => 'closed', 'updated_at' => now()]);
        });
    }

    /** Takes the provider out of the directory and stops new work; jobs already running and money held are untouched. */
    public function suspend(int $opId, int $staffId, string $reason): void
    {
        DB::transaction(function () use ($opId, $staffId, $reason) {
            if (! DB::table('operators')->where('id', $opId)->where('status', 'active')->update(['status' => 'suspended', 'updated_at' => now()])) {
                throw new RuntimeException('Only active providers can be suspended.');
            }
            DB::table('provider_profiles')->where('operator_id', $opId)->update(['listed' => false, 'availability_status' => 'away', 'updated_at' => now()]);
            DB::table('driver_profiles')->where('operator_id', $opId)->update(['status' => 'suspended', 'availability' => 'offline', 'updated_at' => now()]);
            DB::table('provider_applications')->where('operator_id', $opId)->update(['review_note' => $reason, 'reviewed_by' => $staffId, 'reviewed_at' => now(), 'updated_at' => now()]);
        });
    }

    public function reinstate(int $opId): void
    {
        DB::transaction(function () use ($opId) {
            if (! DB::table('operators')->where('id', $opId)->where('status', 'suspended')->update(['status' => 'active', 'updated_at' => now()])) {
                throw new RuntimeException('Only suspended providers can be reinstated.');
            }
            $listed = DB::table('operator_capabilities')->where(['operator_id' => $opId, 'capability' => 'list_in_directory', 'enabled' => true])->exists();
            DB::table('provider_profiles')->where('operator_id', $opId)->update(['listed' => $listed, 'updated_at' => now()]);
            DB::table('driver_profiles')->where('operator_id', $opId)->update(['status' => 'active', 'updated_at' => now()]);
        });
    }
}
