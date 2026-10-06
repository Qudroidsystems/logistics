<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMissingColumnsTostudentRegistrationTable extends Migration
{
    public function up()
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->string('admissionYear')->nullable()->after('admissionNo');
            $table->string('admission_date')->nullable()->after('admissionYear');
            $table->string('blood_group')->nullable()->after('age');
            $table->string('mother_tongue')->nullable()->after('blood_group');
            $table->string('sport_house')->nullable()->after('religion');
            $table->string('email')->nullable()->after('phone_number');
            $table->string('nin_number')->nullable()->after('email');
            $table->string('city')->nullable()->after('nin_number');
            $table->text('reason_for_leaving')->nullable()->after('last_class');
        });
    }

    public function down()
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->dropColumn([
                'blood_group',
                'mother_tongue',
                'sport_house',
                'email',
                'nin_number',
                'city',
                'reason_for_leaving'
            ]);
        });
    }
}