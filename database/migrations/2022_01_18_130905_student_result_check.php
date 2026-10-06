<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StudentResultCheck extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('studentResultCheck', function (Blueprint $table) {
            $table->id();
            $table->string('studentId');
            $table->string('studentclassid');
            $table->string('termid');
            $table->string('sessionid');
            $table->string('classCategoryid');
            $table->string('resultCheckStatus')->nullable();
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
        Schema::dropIfExists('studentResultCheck');
    }
}
