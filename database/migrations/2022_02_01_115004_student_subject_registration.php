<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StudentSubjectRegistration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
         Schema::create('studentSubjectRegistration', function (Blueprint $table) {
            $table->id();
            $table->string('studentId');
            $table->string('studentclassid');
            $table->string('class_categoryid');
            $table->string('termid');
            $table->string('status');
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
        Schema::dropIfExists('studentSubjectRegistration');
    }
}
