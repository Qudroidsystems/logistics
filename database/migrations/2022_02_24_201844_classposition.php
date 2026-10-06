<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class classposition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('classposition', function (Blueprint $table) {
            $table->id();
            $table->string('studentId')->nullable();
            $table->string('classid')->nullable();
            $table->string('position')->nullable();
            $table->string('termid')->nullable();
            $table->string('session')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration s.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists('classposition');
    }
}
