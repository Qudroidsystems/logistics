<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentpersonalityprofilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('studentpersonalityprofiles', function (Blueprint $table) {
            $table->id();
            $table->string('studentid')->nullable();
            $table->string('staffid')->nullable();
            $table->string('schoolclassid')->nullable();
            $table->string('punctuality')->nullable();
            $table->string('neatness')->nullable();
            $table->string('leadership')->nullable();
            $table->string('attitude')->nullable();
            $table->string('reading')->nullable();
            $table->string('honesty')->nullable();
            $table->string('cooperation')->nullable();
            $table->string('selfcontrol')->nullable();
            $table->string('politeness')->nullable();
            $table->string('physicalhealth')->nullable();
            $table->string('stability')->nullable();
            $table->string('gamesandsports')->nullable();
            $table->string('principalscomment')->nullable();
            $table->string('classteachercomment')->nullable();
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
        Schema::dropIfExists('studentpersonalityprofiles');
    }
}
