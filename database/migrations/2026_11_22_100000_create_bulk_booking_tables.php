<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bulk bookings: a spreadsheet of deliveries checked first, then booked in one go.
 *
 *   bulk_batches       one upload: who sent it, the settings that apply to every row, and where it got to
 *   bulk_batch_rows    one delivery per row: what was read, what is wrong with it, and what happened when it was booked.
 *                      A row's reference can be booked only once per provider, so the same sheet sent twice does not
 *                      double the deliveries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_batches', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('operator_id')->constrained('operators');
            $table->foreignId('created_by')->constrained('users');
            $table->string('filename', 160)->nullable();
            // service_type_id, city_id, payment, customer {name, phone, email}, pickup {line1, lat, lng, contact_phone}
            $table->jsonb('defaults');
            // checked | booking | booked | discarded
            $table->string('status', 12)->default('checked');
            $table->unsignedSmallInteger('row_count')->default(0);
            $table->unsignedSmallInteger('ok_count')->default(0);
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->unsignedSmallInteger('failed_count')->default(0);
            $table->timestampTz('booked_at')->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'created_at']);
        });

        Schema::create('bulk_batch_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('bulk_batches')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('operators');
            $table->unsignedSmallInteger('line');
            $table->string('reference', 80)->nullable();
            $table->jsonb('input');
            $table->jsonb('errors')->nullable();
            // ready | invalid | booked | failed
            $table->string('status', 10);
            $table->string('message', 255)->nullable();
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            $table->foreignId('shipment_id')->nullable()->constrained('shipments');
            $table->timestamps();

            $table->index(['batch_id', 'line']);
        });
        DB::statement("CREATE UNIQUE INDEX bulk_batch_rows_booked_ref ON bulk_batch_rows (operator_id, reference) WHERE status = 'booked' AND reference IS NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_batch_rows');
        Schema::dropIfExists('bulk_batches');
    }
};
