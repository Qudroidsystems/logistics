<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A provider invites someone to join their team. The emailed token is stored only as a hash.
        Schema::create('operator_invitations', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->string('email', 190)->index();
            // admin | dispatcher | finance | support | driver_manager | driver
            $table->string('role', 32);
            $table->string('token_hash', 64)->unique();
            $table->foreignId('invited_by')->constrained('users');
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['operator_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_invitations');
    }
};
