<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Drive with us": people apply on the website to drive for the platform's own fleet (the operator in
 * config('booking.driver_operator_id')). Staff check the licence, insurance and registration and approve: the applicant
 * then gets a driver account (driver_profile_id) and their documents become kyc_documents.
 * Licence and policy numbers are kept only as a hash and the last 4 characters; files are on the private disk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_applications', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('name', 120);
            $table->string('email', 160);
            $table->string('phone', 24);
            $table->string('city', 80);
            $table->string('subdivision', 8)->nullable();
            $table->string('postal_code', 12)->nullable();
            // Driving
            $table->string('experience', 8);
            $table->string('experience_notes', 500)->nullable();
            $table->jsonb('availability')->nullable();
            // Vehicle
            $table->string('vehicle_type', 24)->nullable();
            $table->string('vehicle_make', 40)->nullable();
            $table->string('vehicle_model', 40)->nullable();
            $table->unsignedSmallInteger('vehicle_year')->nullable();
            $table->string('plate', 24)->nullable();
            // Licence
            $table->string('licence_class', 12)->nullable();
            $table->string('licence_number_hash', 64)->nullable();
            $table->string('licence_number_last4', 4)->nullable();
            $table->date('licence_expires_on')->nullable();
            $table->string('licence_file')->nullable();
            // Insurance and registration
            $table->string('insurance_company', 80)->nullable();
            $table->string('insurance_policy_last4', 4)->nullable();
            $table->date('insurance_expires_on')->nullable();
            $table->string('insurance_file')->nullable();
            $table->string('registration_file')->nullable();
            $table->string('abstract_file')->nullable();
            // Consents
            $table->boolean('sms_consent')->default(false);
            $table->boolean('background_check_consent')->default(false);
            $table->timestampTz('terms_accepted_at');
            $table->string('terms_version', 16);
            $table->string('ip', 45)->nullable();
            // new | approved | declined
            $table->string('status', 10)->default('new');
            $table->jsonb('checks')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestampTz('decided_at')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->foreignId('driver_profile_id')->nullable()->constrained('driver_profiles');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_applications');
    }
};
