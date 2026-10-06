<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StudentRegistration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('studentRegistration', function (Blueprint $table) {
            $table->id();
            $table->string('admissionNo');
            $table->string('tittle');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('othername');
            $table->string('gender');
            $table->string('home_address');
            $table->string('home_address2');
            $table->string('dateofbirth');
            $table->string('age');
            $table->string('placeofbirth');
            $table->string('religion');
            $table->string('nationlity');
            $table->string('state');
            $table->string('local');
            $table->string('last_school');
            $table->string('last_class');
            $table->string('registeredBy');
            $table->string('batchid')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists('studentRegistration');
    }
}
