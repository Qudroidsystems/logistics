<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ResultEntryDeadline extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('resultEntryDeadline', function (Blueprint $table) {
            $table->id();
            $table->string('staffid');
            $table->string('description');
            $table->string('deadlinedate');
            $table->string('termid');
            $table->string('session');
            $table->string('status');
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
        Schema::dropIfExists('resultEntryDeadline');
    }
}
