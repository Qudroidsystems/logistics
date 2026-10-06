<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolhousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schoolhouses', function (Blueprint $table) {
            $table->id();
            $table->string('house')->nullable();
            $table->string('housemasterid')->nullable();
            $table->string('studentid')->nullable();
            $table->string('housecolour')->nullable();
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
        Schema::dropIfExists('schoolhouses');
    }
}
