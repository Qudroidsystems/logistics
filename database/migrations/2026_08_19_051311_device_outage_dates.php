<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_outage_dates', function (Blueprint $table) {
            $table->id();
            $table->date('outage_date');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->timestamps();

            $table->unique('outage_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_outage_dates');
    }
};
