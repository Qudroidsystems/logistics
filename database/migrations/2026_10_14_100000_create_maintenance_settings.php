<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-controlled maintenance mode. A single row (id = 1) holds the whole
 * state. Everything here is turned on and off from the settings screen; there
 * is no command-line step to recover, and Super Admins always keep access.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('maintenance_settings')) {
            Schema::create('maintenance_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('is_active')->default(false);
                $table->string('title', 150)->nullable();
                $table->text('message')->nullable();
                $table->string('contact_info', 255)->nullable();     // phone / email shown to locked-out users
                $table->json('allow_role_ids')->nullable();           // roles that keep access while on
                $table->unsignedSmallInteger('retry_after')->nullable(); // minutes, for the Retry-After header
                $table->timestamp('scheduled_at')->nullable();        // optional planned switch-on (still UI-reversible)
                $table->string('scheduled_note', 255)->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->unsignedBigInteger('activated_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });

            DB::table('maintenance_settings')->insert([
                'id' => 1, 'is_active' => false,
                'title' => 'We\'ll be back shortly',
                'message' => 'The school portal is temporarily unavailable while we carry out scheduled maintenance. Please check back soon.',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_settings');
    }
};
