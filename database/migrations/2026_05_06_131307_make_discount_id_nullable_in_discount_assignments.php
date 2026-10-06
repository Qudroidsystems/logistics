<?php
// database/migrations/2026_05_06_000000_make_discount_id_nullable_in_discount_assignments.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('discount_assignments', function (Blueprint $table) {
            // Make discount_id nullable
            $table->unsignedBigInteger('discount_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('discount_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_id')->nullable(false)->change();
        });
    }
};
