<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAppLogoToSchoolInformationTable extends Migration
{
    public function up()
    {
        Schema::table('school_information', function (Blueprint $table) {
            $table->string('app_logo')->nullable()->after('school_logo');
        });
    }

    public function down()
    {
        Schema::table('school_information', function (Blueprint $table) {
            $table->dropColumn('app_logo');
        });
    }
}
