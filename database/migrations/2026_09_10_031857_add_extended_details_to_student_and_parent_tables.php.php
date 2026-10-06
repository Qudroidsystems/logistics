<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Additive only — no existing columns touched, no data migration needed.
     * blood_group already exists on studentRegistration; it's included here
     * only in the comment for context, not re-added.
     */
    public function up(): void
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->string('genotype', 10)->nullable()->after('blood_group');
            $table->string('emergency_contact_name')->nullable()->after('genotype');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            $table->text('allergies_medical_conditions')->nullable()->after('emergency_contact_phone');
        });

        Schema::table('parentRegistration', function (Blueprint $table) {
            $table->string('guardian_name')->nullable()->after('parent_address');
            $table->string('guardian_relationship', 100)->nullable()->after('guardian_name');
            $table->string('guardian_phone', 20)->nullable()->after('guardian_relationship');
            $table->string('whatsapp_number', 20)->nullable()->after('guardian_phone');
        });
    }

    public function down(): void
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->dropColumn([
                'genotype',
                'emergency_contact_name',
                'emergency_contact_phone',
                'allergies_medical_conditions',
            ]);
        });

        Schema::table('parentRegistration', function (Blueprint $table) {
            $table->dropColumn([
                'guardian_name',
                'guardian_relationship',
                'guardian_phone',
                'whatsapp_number',
            ]);
        });
    }
};