<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubjectRegistrationStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subjectRegistrationStatus', function (Blueprint $table) {
            $table->id();
            $table->string('broadsheetid');
            $table->string('studentid');
            $table->string('subjectclassid');
            $table->string('staffid');
            $table->string('termid');
            $table->string('sessionid');
            $table->string('Status');
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
        Schema::dropIfExists('subjectReigistrationStatus');
    }
}
