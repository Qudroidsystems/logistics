<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAdmissionDateTypeInstudentRegistrationTable extends Migration
{
    public function up()
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            // Change admission_date to date type
            $table->date('admission_date')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->string('admission_date', 255)->nullable()->change();
        });
    }
}