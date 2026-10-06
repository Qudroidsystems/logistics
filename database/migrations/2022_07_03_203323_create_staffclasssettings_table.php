<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffclasssettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staffclasssettings', function (Blueprint $table) {
            $table->id();
            $table->string('noschoolopened');
            $table->string('termends');
            $table->string('nexttermbegins');
            $table->string('staffid');
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
        Schema::dropIfExists('staffclasssettings');
    }
}
