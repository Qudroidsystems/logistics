<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('school_information', function (Blueprint $table) {
            // Change phone from single to multiple (JSON)
            $table->json('school_phones')->nullable()->after('school_phone');
            $table->dropColumn('school_phone'); // Remove old single phone column

            // Add new fields
            $table->string('school_stamp')->nullable()->after('app_logo');
            $table->date('date_school_closed')->nullable()->after('date_school_opened');
        });
    }

    public function down()
    {
        Schema::table('school_information', function (Blueprint $table) {
            $table->string('school_phone')->nullable()->after('school_address');
            $table->dropColumn('school_phones');
            $table->dropColumn('school_stamp');
            $table->dropColumn('date_school_closed');
        });
    }
};
