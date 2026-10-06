<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolsettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schoolsettings', function (Blueprint $table) {
            $table->id();
            $table->date('schoolstarts')->nullable();
            $table->date('schoolends')->nullable();
            $table->string('schoolopened')->nullable();
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
        Schema::dropIfExists('schoolsettings');
    }
}
