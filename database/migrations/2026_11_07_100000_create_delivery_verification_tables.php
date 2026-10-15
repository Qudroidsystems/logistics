<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Proof of delivery agreed between the job giver and the provider.
 *
 *   verification_policies    the provider's standard proofs, per service type or for everything
 *   service_requests.verification   extra proofs the job giver asks for when posting the job
 *   shipments.verification_policy   what was agreed, frozen when the order is created (it is also in the signed terms)
 *   delivery_codes           one-time receiver codes: hashed, expiring, attempt-limited, re-issuable
 *   verification_exceptions  a driver cannot meet a required proof: reason, then a provider decision
 *   custody_events           a hash chain over scans, proofs and exceptions, so edits after the fact show up
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('operators');
            // null = the provider's default for every service type
            $table->foreignId('service_type_id')->nullable()->constrained('service_types');
            $table->jsonb('pickup');
            $table->jsonb('dropoff');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
        DB::statement('CREATE UNIQUE INDEX verification_policies_scope_unique ON verification_policies (operator_id, service_type_id) NULLS NOT DISTINCT');

        Schema::table('service_requests', function (Blueprint $table) {
            $table->jsonb('verification')->nullable();
        });
        Schema::table('shipments', function (Blueprint $table) {
            $table->jsonb('verification_policy')->nullable();
        });

        Schema::create('delivery_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('stop_id')->constrained('shipment_stops');
            $table->string('code_hash', 64);
            // Encrypted with the app key so the job giver can open it again in their app; never logged or returned to drivers.
            $table->text('code_secret');
            $table->timestampTz('expires_at');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('locked_until')->nullable();
            $table->timestampTz('used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['stop_id', 'revoked_at']);
            $table->index(['shipment_id', 'created_at']);
        });

        Schema::create('verification_exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('stop_id')->constrained('shipment_stops');
            $table->string('method', 16);
            $table->text('reason');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->unsignedBigInteger('driver_profile_id')->nullable();
            // pending | approved | declined
            $table->string('status', 10)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestampTz('decided_at')->nullable();
            $table->string('decision_note', 255)->nullable();
            $table->timestamps();

            $table->index(['stop_id', 'status']);
            $table->index(['shipment_id', 'status']);
        });

        Schema::table('proofs', function (Blueprint $table) {
            $table->string('signature_path')->nullable();
            $table->string('file_sha256', 64)->nullable();
            $table->string('signature_sha256', 64)->nullable();
            $table->string('id_type', 20)->nullable();
            $table->string('id_last4', 4)->nullable();
            $table->bigInteger('cod_collected')->nullable();
            // {"required":[...], "satisfied":[...], "waived":[...]}
            $table->jsonb('checks')->nullable();
        });

        Schema::create('custody_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->unsignedInteger('seq');
            // scan | proof | exception
            $table->string('kind', 16);
            $table->string('ref_type', 24)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->jsonb('payload');
            $table->string('prev_hash', 64);
            $table->string('hash', 64);
            $table->timestampTz('created_at');

            $table->unique(['shipment_id', 'seq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custody_events');
        Schema::table('proofs', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'file_sha256', 'signature_sha256', 'id_type', 'id_last4', 'cod_collected', 'checks']);
        });
        Schema::dropIfExists('verification_exceptions');
        Schema::dropIfExists('delivery_codes');
        Schema::table('shipments', fn (Blueprint $table) => $table->dropColumn('verification_policy'));
        Schema::table('service_requests', fn (Blueprint $table) => $table->dropColumn('verification'));
        Schema::dropIfExists('verification_policies');
    }
};
