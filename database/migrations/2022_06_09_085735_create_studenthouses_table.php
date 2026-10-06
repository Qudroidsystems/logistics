<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudenthousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('studenthouses', function (Blueprint $table) {
            $table->id();
            $table->string('studentid')->nullable();
            $table->string('schoolhouse')->nullable();
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
        Schema::dropIfExists('studenthouses');
    }
}
