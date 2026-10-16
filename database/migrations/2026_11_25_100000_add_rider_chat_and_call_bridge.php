<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Chat between the customer and the rider on a job, and calls connected through the company's number so neither sees the other's. */
return new class extends Migration
{
    public function up(): void
    {
        // One chat of each kind per shipment.
        DB::statement('CREATE UNIQUE INDEX conversations_shipment_type_idx ON conversations (shipment_id, type) WHERE shipment_id IS NOT NULL');

        Schema::table('call_logs', function (Blueprint $table) {
            $table->foreignId('caller_id')->nullable()->constrained('users');
            // customer | driver | pickup | dropoff (who was called), or inbound for a call to the shared number
            $table->string('purpose', 10)->nullable();
            $table->string('provider_ref', 80)->nullable()->index();
            $table->timestampTz('ended_at')->nullable();
        });

        Schema::table('masked_numbers', function (Blueprint $table) {
            $table->index(['proxy_number', 'expires_at']);
            $table->index('shipment_id');
        });
    }

    public function down(): void
    {
        Schema::table('masked_numbers', function (Blueprint $table) {
            $table->dropIndex(['proxy_number', 'expires_at']);
            $table->dropIndex(['shipment_id']);
        });
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caller_id');
            $table->dropColumn(['purpose', 'provider_ref', 'ended_at']);
        });
        DB::statement('DROP INDEX IF EXISTS conversations_shipment_type_idx');
    }
};
