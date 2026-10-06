<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_information', function (Blueprint $table) {
            $table->unsignedInteger('no_of_times_school_opened')->default(0)->after('school_website');
            $table->date('date_school_opened')->nullable()->after('no_of_times_school_opened');
            $table->date('date_next_term_begins')->nullable()->after('date_school_opened');
        });
    }

    public function down(): void
    {
        Schema::table('school_information', function (Blueprint $table) {
            $table->dropColumn(['no_of_times_school_opened', 'date_school_opened', 'date_next_term_begins']);
        });
    }
};