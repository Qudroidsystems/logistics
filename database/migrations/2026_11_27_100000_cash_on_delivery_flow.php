<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cash on delivery: the rider collects the goods money from the receiver, hands it in at the office, and the
 * provider pays it to whoever sent the parcel. cod_collections (created early, unused until now) tracks each step.
 * status: with_rider | in_office | paid | written_off
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cod_collections', function (Blueprint $table) {
            $table->unique('shipment_id');
            // Who the money belongs to: the customer who booked, or the business on the partner API.
            $table->string('sender_type', 10)->default('customer');
            $table->unsignedBigInteger('sender_id');
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestampTz('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users');
            $table->string('paid_reference', 80)->nullable();
            $table->string('note')->nullable();

            $table->index(['operator_id', 'status']);
            $table->index(['sender_type', 'sender_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cod_collections', function (Blueprint $table) {
            $table->dropUnique(['shipment_id']);
            $table->dropIndex(['operator_id', 'status']);
            $table->dropIndex(['sender_type', 'sender_id']);
            $table->dropConstrainedForeignId('received_by');
            $table->dropConstrainedForeignId('paid_by');
            $table->dropColumn(['sender_type', 'sender_id', 'paid_at', 'paid_reference', 'note']);
        });
    }
};
