<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PromotionStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('promotionStatus', function (Blueprint $table) {
            $table->id();
            $table->string('studentId');
            $table->string('schoolclassid');
            $table->string('position')->nullable();
            $table->string('termid');
            $table->string('sessionid');
            $table->string('promotionStatus');
            $table->string('classstatus');
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
    Schema::dropIfExists('promotionStaus');
    }
}
