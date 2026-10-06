<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            $table->decimal('bf', 8, 2)->nullable()->default(0.00)->after('total'); // Brought Forward
            $table->decimal('cum', 8, 2)->nullable()->default(0.00)->after('bf'); // Cumulative Score
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadsheets', function (Blueprint $table) {
            $table->dropColumn(['bf', 'cum']);
        });
    }
};
