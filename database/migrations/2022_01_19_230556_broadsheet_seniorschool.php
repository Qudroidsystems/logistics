<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BroadsheetSeniorschool extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('broadsheetSeniorschool', function (Blueprint $table) {
            $table->id();
            $table->string('studentId');
            $table->string('subjectid');
            $table->string('staffid');
            $table->string('ca1');
            $table->string('ca2');
            $table->string('exam');
            $table->string('total');
            $table->string('grade');
            $table->string('subjectpositionarm');
            $table->string('subjectpositionclass');
            $table->string('positionarm');
            $table->string('positionclass');
            $table->string('remark');
            $table->string('termid');
            $table->string('session');
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
        Schema::dropIfExists('broadsheetSeniorschool');
    }
}
