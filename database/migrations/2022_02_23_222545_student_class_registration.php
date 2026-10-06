<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StudentClassRegistration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
         Schema::create('studentClassRegistration', function (Blueprint $table) {
            $table->id();
            $table->string('studentId');
            $table->string('studentclassid');
            $table->string('termid');
            $table->string('sessionid');
            $table->string('class_categoryid');
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
        Schema::dropIfExists('studentClassRegistration');
    }
}
