<?php

namespace App\Modules\Drivers;

use App\Jobs\SendPlainEmail;
use App\Models\User;
use App\Modules\Notifications\NotificationService;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * "Drive with us": the public application, and staff approving it into a driver on the platform's own fleet.
 * On approval the applicant gets (or reuses) an account, becomes an active driver of the fleet operator, their vehicle
 * is added and assigned, and their documents are filed as approved KYC documents.
 */
class DriverApplicationService
{
    public const TERMS_VERSION = '2026-10';

    public const EXPERIENCE = ['lt1' => 'Less than a year', '1to2' => '1 to 2 years', '3to5' => '3 to 5 years', '5plus' => 'More than 5 years'];

    public const AVAILABILITY = ['weekday_day' => 'Weekday daytime', 'weekday_evening' => 'Weekday evenings', 'weekend' => 'Weekends'];

    /** Files an applicant can send, and whether staff need each before approving. */
    public const FILES = ['licence' => "Driver's licence", 'insurance' => 'Insurance (pink slip)', 'registration' => 'Vehicle registration (ownership)', 'abstract' => "Driver's abstract"];

    public const CHECKS = ['licence' => 'Licence is valid and matches the name', 'insurance' => 'Insurance covers the vehicle and is in date', 'registration' => 'Registration matches the plate', 'call' => 'Spoke with the applicant'];

    public function __construct(private NotificationService $notifications) {}

    public static function operatorId(): ?int
    {
        $id = (int) config('booking.driver_operator_id');

        return $id > 0 && DB::table('operators')->where('id', $id)->exists() ? $id : null;
    }

    /** @param array<string, UploadedFile|null> $files */
    public function apply(array $d, array $files, ?string $ip = null, ?int $userId = null): string
    {
        $email = strtolower(trim($d['email']));
        $open = DB::table('driver_applications')->where('email', $email)->where('status', 'new')->value('public_id');
        if ($open) {
            throw new RuntimeException('We already have an application from this email. We will be in touch soon.');
        }
        if ($userId === null && ($u = User::where('email', $email)->first()) && DB::table('driver_profiles')->where('user_id', $u->id)->where('operator_id', self::operatorId() ?? 0)->whereIn('status', ['active', 'applied'])->exists()) {
            throw new RuntimeException('You already drive with us. Sign in to the driver app.');
        }
        $pid = (string) Str::ulid();
        $hash = fn (?string $n) => $n ? hash_hmac('sha256', preg_replace('/[^A-Za-z0-9]/', '', strtoupper($n)), (string) config('app.key')) : null;
        $last4 = fn (?string $n) => $n ? substr(preg_replace('/[^A-Za-z0-9]/', '', strtoupper($n)), -4) : null;
        $stored = [];
        foreach (array_keys(self::FILES) as $kind) {
            $f = $files[$kind] ?? null;
            if ($f instanceof UploadedFile) {
                $stored[$kind] = $f->storeAs("driver-applications/{$pid}", $kind.'.'.strtolower($f->getClientOriginalExtension() ?: 'bin'), 'local');
            }
        }
        DB::table('driver_applications')->insert([
            'public_id' => $pid, 'user_id' => $userId, 'name' => trim($d['name']), 'email' => $email,
            'phone' => Phone::store((string) $d['phone']) ?? trim((string) $d['phone']), 'city' => trim($d['city']),
            'subdivision' => $d['subdivision'] ?? null, 'postal_code' => isset($d['postal_code']) ? strtoupper(trim((string) $d['postal_code'])) : null,
            'experience' => $d['experience'], 'experience_notes' => $d['experience_notes'] ?? null, 'availability' => json_encode(array_values((array) ($d['availability'] ?? []))),
            'vehicle_type' => $d['vehicle_type'] ?? null, 'vehicle_make' => $d['vehicle_make'] ?? null, 'vehicle_model' => $d['vehicle_model'] ?? null,
            'vehicle_year' => $d['vehicle_year'] ?? null, 'plate' => isset($d['plate']) ? strtoupper(preg_replace('/\s+/', '', (string) $d['plate'])) : null,
            'licence_class' => $d['licence_class'] ?? null, 'licence_number_hash' => $hash($d['licence_number'] ?? null), 'licence_number_last4' => $last4($d['licence_number'] ?? null),
            'licence_expires_on' => $d['licence_expires_on'] ?? null, 'licence_file' => $stored['licence'] ?? null,
            'insurance_company' => $d['insurance_company'] ?? null, 'insurance_policy_last4' => $last4($d['insurance_policy'] ?? null),
            'insurance_expires_on' => $d['insurance_expires_on'] ?? null, 'insurance_file' => $stored['insurance'] ?? null,
            'registration_file' => $stored['registration'] ?? null, 'abstract_file' => $stored['abstract'] ?? null,
            'sms_consent' => (bool) ($d['sms_consent'] ?? false), 'background_check_consent' => (bool) ($d['background_check_consent'] ?? false),
            'terms_accepted_at' => now(), 'terms_version' => self::TERMS_VERSION, 'ip' => $ip, 'status' => 'new', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $pid;
    }

    public function file(object $app, string $kind): ?string
    {
        $path = $app->{$kind.'_file'} ?? null;

        return $path && Storage::disk('local')->exists($path) ? $path : null;
    }

    /** Approves an application. $checks = the CHECKS staff ticked. Returns the new driver profile id. */
    public function approve(int $id, int $staffId, array $checks, ?string $note = null): int
    {
        $opId = self::operatorId() ?? throw new RuntimeException('Set DRIVE_OPERATOR_ID (or INSTANT_OPERATOR_ID) to the fleet drivers join, then approve.');
        [$profileId, $userId, $isNew] = DB::transaction(function () use ($id, $staffId, $checks, $note, $opId) {
            $a = DB::table('driver_applications')->where('id', $id)->lockForUpdate()->first();
            if (! $a || $a->status !== 'new') {
                throw new RuntimeException('That application has already been decided.');
            }
            if (! $a->licence_file) {
                throw new RuntimeException("There is no driver's licence on this application. Ask for it before approving.");
            }
            if ($a->licence_expires_on && \Illuminate\Support\Carbon::parse($a->licence_expires_on)->isPast()) {
                throw new RuntimeException('The licence has expired.');
            }
            if ($a->plate && (! $a->insurance_file || ! $a->registration_file)) {
                throw new RuntimeException('A driver with their own vehicle needs insurance and registration on file before approval.');
            }
            if ($a->insurance_expires_on && \Illuminate\Support\Carbon::parse($a->insurance_expires_on)->isPast()) {
                throw new RuntimeException('The insurance has expired.');
            }

            $user = $a->user_id ? User::find($a->user_id) : User::where('email', $a->email)->first();
            $isNew = ! $user;
            if ($isNew) {
                $phoneTaken = User::where('phone_number', $a->phone)->exists();
                $user = User::create(['name' => $a->name, 'email' => $a->email, 'phone_number' => $phoneTaken ? null : $a->phone, 'password' => Hash::make(Str::random(40))]);
            }

            $member = DB::table('operator_members')->where(['operator_id' => $opId, 'user_id' => $user->id])->first();
            if ($member) {
                DB::table('operator_members')->where('id', $member->id)->update(['role' => $member->role === 'driver' ? 'driver' : $member->role, 'status' => 'active', 'updated_at' => now()]);
            } else {
                DB::table('operator_members')->insert(['operator_id' => $opId, 'user_id' => $user->id, 'role' => 'driver', 'status' => 'active', 'invited_by' => $staffId,
                    'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('users')->where('id', $user->id)->whereNull('current_operator_id')->update(['current_operator_id' => $opId]);

            $scope = ['user_id' => $user->id, 'operator_id' => $opId];
            $profile = DB::table('driver_profiles')->where($scope)->first();
            $vals = ['status' => 'active', 'kyc_status' => 'company_verified', 'availability' => 'offline', 'onboarded_at' => DB::raw('COALESCE(onboarded_at, now())'), 'deleted_at' => null, 'updated_at' => now()];
            if ($profile) {
                DB::table('driver_profiles')->where('id', $profile->id)->update($vals);
                $profileId = (int) $profile->id;
            } else {
                $profileId = (int) DB::table('driver_profiles')->insertGetId($scope + ['onboarded_at' => now()] + $vals + ['public_id' => (string) Str::ulid(), 'created_at' => now()]);
            }

            $vehicleId = null;
            $type = $a->vehicle_type ? DB::table('vehicle_types')->where('code', $a->vehicle_type)->first(['id', 'max_weight_g']) : null;
            if ($a->plate && $type) {
                $existing = DB::table('vehicles')->where(['operator_id' => $opId, 'plate' => $a->plate])->first();
                $vehicleId = $existing ? (int) $existing->id : (int) DB::table('vehicles')->insertGetId([
                    'public_id' => (string) Str::ulid(), 'operator_id' => $opId, 'vehicle_type_id' => $type->id, 'plate' => $a->plate,
                    'make' => $a->vehicle_make, 'model' => $a->vehicle_model, 'year' => $a->vehicle_year, 'max_weight_g' => $type->max_weight_g,
                    'ownership' => 'driver_owned', 'status' => 'active', 'insurance_expires_on' => $a->insurance_expires_on, 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('vehicle_assignments')->whereNull('ended_at')->where('driver_profile_id', $profileId)->update(['ended_at' => now()]);
                DB::table('vehicle_assignments')->insert(['vehicle_id' => $vehicleId, 'driver_profile_id' => $profileId, 'started_at' => now()]);
                DB::table('driver_profiles')->where('id', $profileId)->update(['current_vehicle_id' => $vehicleId]);
            }

            // The documents become the driver's (and vehicle's) KYC record, already reviewed.
            $docs = [['drivers_licence', 'driver', $profileId, $a->licence_file, $a->licence_number_hash, $a->licence_number_last4, $a->licence_expires_on],
                ['insurance', $vehicleId ? 'vehicle' : 'driver', $vehicleId ?? $profileId, $a->insurance_file, null, $a->insurance_policy_last4, $a->insurance_expires_on],
                ['vehicle_registration', $vehicleId ? 'vehicle' : 'driver', $vehicleId ?? $profileId, $a->registration_file, null, null, null],
                ['drivers_abstract', 'driver', $profileId, $a->abstract_file, null, null, null]];
            foreach ($docs as [$type, $subjectType, $subjectId, $path, $numHash, $num4, $expires]) {
                if (! $path) {
                    continue;
                }
                DB::table('kyc_documents')->insert([
                    'public_id' => (string) Str::ulid(), 'operator_id' => $opId, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'doc_type' => $type,
                    'country' => (string) config('region.country', 'NG'), 'number_hash' => $numHash, 'number_last4' => $num4, 'file_path' => $path, 'expires_on' => $expires,
                    'status' => 'approved', 'reviewed_by' => $staffId, 'reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            DB::table('driver_applications')->where('id', $a->id)->update([
                'status' => 'approved', 'user_id' => $user->id, 'driver_profile_id' => $profileId, 'checks' => json_encode(array_values(array_intersect(array_keys(self::CHECKS), $checks))),
                'decided_by' => $staffId, 'decided_at' => now(), 'decision_note' => $note ? mb_substr($note, 0, 500) : null, 'updated_at' => now(),
            ]);

            return [$profileId, (int) $user->id, $isNew];
        });

        $this->notifications->notify($userId, 'driver.application_approved', ['fleet' => (string) DB::table('operators')->where('id', $opId)->value('display_name')], '/driver');
        if ($isNew) {
            Password::broker()->sendResetLink(['email' => User::find($userId)->email]);
        }

        return $profileId;
    }

    public function decline(int $id, int $staffId, string $note, bool $tell = true): void
    {
        $a = DB::table('driver_applications')->where('id', $id)->first();
        $n = DB::table('driver_applications')->where('id', $id)->where('status', 'new')
            ->update(['status' => 'declined', 'decided_by' => $staffId, 'decided_at' => now(), 'decision_note' => mb_substr($note, 0, 500), 'updated_at' => now()]);
        if (! $n) {
            throw new RuntimeException('That application has already been decided.');
        }
        if ($tell) {
            SendPlainEmail::dispatch($a->email, 'Your application to drive with '.config('app.name'),
                "Hi {$a->name},\n\nThank you for applying to drive with us. We are not able to go ahead with your application at the moment.\n\n{$note}\n\nYou are welcome to apply again later.");
        }
    }
}
