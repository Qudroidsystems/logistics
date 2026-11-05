<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row per store per month once its statement has been sent, so the monthly run never sends twice. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dropoff_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dropoff_centre_id')->constrained('dropoff_centres')->cascadeOnDelete();
            $table->char('month', 7);
            $table->unsignedInteger('parcels')->default(0);
            $table->bigInteger('earned')->default(0);
            $table->bigInteger('paid')->default(0);
            $table->bigInteger('owed')->default(0);
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();

            $table->unique(['dropoff_centre_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dropoff_statements');
    }
};
