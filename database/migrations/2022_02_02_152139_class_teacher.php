<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Classteacher extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('classteacher', function (Blueprint $table) {
            $table->id();
            $table->string('staffid');
            $table->string('schoolclassid');
            $table->string('classcategoryid')->nullable();
            $table->string('termid');
            $table->string('sessionid');
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
        Schema::dropIfExists('classteacher');
    }
}
