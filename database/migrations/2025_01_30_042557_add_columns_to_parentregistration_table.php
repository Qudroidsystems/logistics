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
        Schema::table('parentRegistration', function (Blueprint $table) {
            $table->string('mother_occupation')->nullable();
            $table->string('mother_office_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parentRegistration', function (Blueprint $table) {
            $table->dropColumn('mother_occupation'); // Drop the column if the migration is rolled back
            $table->dropColumn('mother_office_address');
        });
    }
};
