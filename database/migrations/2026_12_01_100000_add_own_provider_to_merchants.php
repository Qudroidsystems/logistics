<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A merchant that also runs its own delivery team on the marketplace (a provider account) can send its
 * own-delivery orders straight to that team: POST /v1/partner/deliveries with "provider": "own".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->foreignId('own_provider_operator_id')->nullable()->after('operator_id')->constrained('operators')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('own_provider_operator_id');
        });
    }
};
