<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('default_address_id')->nullable();
            $table->string('referral_code', 16)->nullable()->unique();
            $table->foreignId('referred_by')->nullable()->constrained('users');
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('cancel_rate', 5, 2)->default(0);
            $table->string('status', 16)->default('active');
            $table->boolean('marketing_opt_in')->default(false);
            $table->string('preferred_payment_method', 24)->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            // bicycle | motorbike | tricycle | car | van | truck
            $table->string('code', 24)->unique();
            $table->string('name', 60);
            $table->unsignedInteger('max_weight_g');
            $table->unsignedBigInteger('max_volume_cm3')->nullable();
            $table->unsignedInteger('price_multiplier_bp')->default(10000);
            $table->boolean('requires_licence')->default(true);
            $table->string('icon', 60)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types');
            $table->string('plate', 24);
            $table->string('vin', 32)->nullable();
            $table->string('make', 40)->nullable();
            $table->string('model', 40)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('colour', 24)->nullable();
            $table->unsignedInteger('max_weight_g')->nullable();
            // driver_owned | operator_owned | leased
            $table->string('ownership', 16)->default('operator_owned');
            $table->string('status', 16)->default('pending');
            $table->date('insurance_expires_on')->nullable();
            $table->date('roadworthy_expires_on')->nullable();
            $table->jsonb('photos')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['operator_id', 'plate']);
            $table->index(['operator_id', 'status']);
        });

        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->operatorId();
            // applied | documents_pending | under_review | training | active | suspended | offboarded
            $table->string('status', 24)->default('applied');
            // offline | online | on_job | break
            $table->string('availability', 16)->default('offline');
            $table->foreignId('home_zone_id')->nullable()->constrained('zones');
            $table->foreignId('current_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->geoPoint('last_point');
            $table->bigInteger('last_h3_9')->nullable();
            $table->timestampTz('last_seen_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('acceptance_rate', 5, 2)->nullable();
            $table->decimal('completion_rate', 5, 2)->nullable();
            $table->decimal('on_time_rate', 5, 2)->nullable();
            $table->unsignedSmallInteger('max_active_jobs')->default(1);
            $table->boolean('accepts_cod')->default(false);
            $table->boolean('accepts_fragile')->default(true);
            $table->boolean('accepts_food')->default(false);
            $table->boolean('accepts_documents')->default(true);
            $table->string('kyc_status', 16)->default('pending');
            $table->timestampTz('onboarded_at')->nullable();
            $table->timestampTz('last_trip_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'operator_id']);
            $table->index(['operator_id', 'status', 'availability']);
            $table->index('last_h3_9');
            $table->spatialIndex('last_point');
        });

        Schema::create('vehicle_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_profile_id')->constrained('driver_profiles')->cascadeOnDelete();
            $table->timestampTz('started_at');
            $table->timestampTz('ended_at')->nullable();

            $table->index(['vehicle_id', 'started_at']);
        });

        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            // driver | vehicle | vendor | operator | customer | merchant
            $table->string('subject_type', 24);
            $table->unsignedBigInteger('subject_id');
            // national_id | drivers_licence | vehicle_registration | insurance | roadworthiness |
            // cac_certificate | utility_bill | guarantor_form | police_report
            $table->string('doc_type', 32);
            $table->char('country', 2)->default('NG');
            $table->string('number_hash', 64)->nullable()->index();
            $table->string('number_last4', 4)->nullable();
            $table->string('file_path');
            $table->date('expires_on')->nullable();
            // pending | approved | rejected | expired
            $table->string('status', 16)->default('pending');
            $table->string('provider', 32)->nullable();
            $table->string('provider_ref', 80)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestampTz('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['status', 'expires_on']);
        });

        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 24);
            $table->unsignedBigInteger('subject_id');
            // identity | face_match | liveness | bank_account_name | phone_sim_swap | background
            $table->string('type', 32);
            $table->string('provider', 32);
            $table->string('result', 16);
            $table->decimal('confidence', 5, 2)->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id', 'type']);
        });

        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_profile_id')->constrained('driver_profiles')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 24);
            $table->string('relationship', 40)->nullable();
            $table->string('address')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_fingerprint', 128);
            $table->string('platform', 16)->nullable();
            $table->string('os', 32)->nullable();
            $table->string('app_version', 24)->nullable();
            $table->string('push_token', 400)->nullable();
            $table->boolean('is_rooted')->default(false);
            $table->boolean('mock_location_detected')->default(false);
            $table->boolean('trusted')->default(false);
            $table->timestampTz('first_seen_at')->useCurrent();
            $table->timestampTz('last_seen_at')->nullable();

            $table->unique(['user_id', 'device_fingerprint']);
            $table->index('device_fingerprint');
        });

        Schema::create('risk_events', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 24);
            $table->unsignedBigInteger('subject_id');
            // gps_spoof | duplicate_identity | chargeback | cancel_abuse | promo_abuse | payout_anomaly | device_shared
            $table->string('type', 32);
            $table->string('severity', 12)->default('medium');
            $table->jsonb('evidence')->nullable();
            // open | reviewed | dismissed | actioned
            $table->string('status', 16)->default('open');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['status', 'severity']);
        });

        Schema::create('risk_scores', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 24);
            $table->unsignedBigInteger('subject_id');
            $table->unsignedSmallInteger('score');
            $table->jsonb('factors')->nullable();
            $table->timestampTz('computed_at');

            $table->unique(['subject_type', 'subject_id']);
        });

        Schema::create('restrictions', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 24);
            $table->unsignedBigInteger('subject_id');
            // ban | suspension | payout_hold | cod_block | new_job_block | region_block | directory_hidden
            $table->string('type', 32);
            $table->string('reason');
            $table->timestampTz('starts_at')->useCurrent();
            $table->timestampTz('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->string('appeal_status', 16)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'type']);
            $table->index('ends_at');
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->unsignedBigInteger('shipment_id')->nullable()->index();
            $table->foreignId('reporter_id')->nullable()->constrained('users');
            // accident | theft | damage | harassment | lost_item | wrong_delivery
            $table->string('type', 24);
            $table->string('severity', 12)->default('medium');
            $table->text('description');
            $table->jsonb('evidence')->nullable();
            $table->string('status', 16)->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->text('resolution')->nullable();
            $table->bigInteger('compensation_amount')->default(0);
            $table->timestamps();
        });

        Schema::create('training_modules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('title');
            $table->boolean('required')->default(true);
            $table->unsignedSmallInteger('pass_mark')->default(70);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('driver_training_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_profile_id')->constrained('driver_profiles')->cascadeOnDelete();
            $table->foreignId('training_module_id')->constrained('training_modules');
            $table->string('status', 16)->default('not_started');
            $table->unsignedSmallInteger('score')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['driver_profile_id', 'training_module_id'], 'driver_training_unique');
        });
    }

    public function down(): void
    {
        foreach ([
            'driver_training_progress', 'training_modules', 'incidents', 'restrictions', 'risk_scores',
            'risk_events', 'devices', 'guarantors', 'verifications', 'kyc_documents', 'vehicle_assignments',
            'driver_profiles', 'vehicles', 'vehicle_types', 'customer_profiles',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
