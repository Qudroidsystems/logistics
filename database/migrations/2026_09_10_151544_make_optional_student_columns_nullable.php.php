<?php

// database/migrations/xxxx_make_optional_student_columns_nullable.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
            $table->string('othername')->nullable()->change();
            $table->string('gender')->nullable()->change();
            $table->string('future_ambition')->nullable()->change();
            $table->string('home_address2')->nullable()->change();
            $table->string('dateofbirth')->nullable()->change();
            $table->string('age')->nullable()->change();
            $table->string('placeofbirth')->nullable()->change();
            $table->string('religion')->nullable()->change();
            $table->string('nationality')->nullable()->change();
            $table->string('state')->nullable()->change();
            $table->string('local')->nullable()->change();
            $table->string('last_school')->nullable()->change();
            $table->string('last_class')->nullable()->change();
            $table->string('registeredBy')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No-op — reverting to NOT NULL will fail if nulls exist.
    }
};