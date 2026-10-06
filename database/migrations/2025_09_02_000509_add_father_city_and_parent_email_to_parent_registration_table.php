<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFatherCityAndParentEmailToParentRegistrationTable extends Migration
{
    public function up()
    {
        Schema::table('parentRegistration', function (Blueprint $table) {
            $table->string('father_city')->nullable()->after('father_occupation');
            $table->string('parent_email')->nullable()->after('mother_phone');
        });
    }

    public function down()
    {
        Schema::table('parentRegistration', function (Blueprint $table) {
            $table->dropColumn(['father_city', 'parent_email']);
        });
    }
}