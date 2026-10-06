<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_user_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('device_serial');       // e.g. PKD7022588362 — supports multiple devices later
            $table->unsignedInteger('device_pin');  // the PIN/User ID enrolled on the device
            $table->enum('person_type', ['student', 'staff']);
            $table->unsignedBigInteger('person_id'); // studentRegistration.id OR staffbioinfo.id
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['device_serial', 'device_pin']);
            $table->index(['person_type', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_user_mappings');
    }
};
