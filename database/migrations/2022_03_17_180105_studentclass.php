<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Studentclass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('studentclass', function (Blueprint $table) {
            $table->id();
            $table->string('studentId')->nullable();
            $table->string('schoolclassid')->nullable();
            $table->string('termid')->nullable();
            $table->string('sessionid')->nullable();
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
        Schema::dropIfExists('studentclass');
    }
}
