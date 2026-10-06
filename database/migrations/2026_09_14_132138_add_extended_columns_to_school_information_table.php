<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_information', function (Blueprint $table) {
            if (!Schema::hasColumn('school_information', 'school_phones')) {
                $table->json('school_phones')->nullable()->after('school_phone');
            }
            if (!Schema::hasColumn('school_information', 'school_stamp')) {
                $table->string('school_stamp')->nullable()->after('app_logo');
            }
            if (!Schema::hasColumn('school_information', 'date_school_closed')) {
                $table->date('date_school_closed')->nullable()->after('date_school_opened');
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_information', function (Blueprint $table) {
            $table->dropColumn(['school_phones', 'school_stamp', 'date_school_closed']);
        });
    }
};
