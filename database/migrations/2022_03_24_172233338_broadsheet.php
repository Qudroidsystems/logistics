<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Broadsheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('broadsheet', function (Blueprint $table) {
            $table->id();
            $table->string('studentId')->nullable();
            $table->string('subjectclassid')->nullable();
            $table->string('staffid')->nullable();
            $table->double('ca1',5, 2)->default('0');
            $table->double('ca2',5, 2)->default('0');
            $table->double('ca3',5, 2)->default('0');
            $table->double('exam',5, 2)->default('0');
            $table->double('total',5, 2)->default('0');
            $table->string('grade')->nullable();
            $table->double('allsubjectstotalscores',5, 2)->default('0');
            $table->string('subjectpositionclass')->nullable();
            $table->double('cmin', 5, 2)->default('0');
            $table->double('cmax', 5, 2)->default('0');
            $table->double('avg', 5, 2)->default('0');
            $table->string('remark')->nullable();
            $table->string('termid')->nullable();
            $table->string('sessionid')->nullable();
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
        Schema::dropIfExists('broadsheet');
    }
}
