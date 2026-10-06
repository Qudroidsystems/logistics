<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider_key')->unique();
            $table->text('secret_key')->nullable();      // legacy columns; credentials live in config
            $table->text('public_key')->nullable();
            $table->string('mode', 10)->default('sandbox'); // sandbox | live
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->decimal('fee_fixed', 15, 2)->default(0);
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
