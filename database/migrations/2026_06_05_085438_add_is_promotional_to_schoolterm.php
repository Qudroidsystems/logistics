<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schoolterm', function (Blueprint $table) {
            // Only one term per session should be promotional.
            // Enforced at the application layer (controller sets others to false when setting one to true).
            $table->boolean('is_promotional')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('schoolterm', function (Blueprint $table) {
            $table->dropColumn('is_promotional');
        });
    }
};
