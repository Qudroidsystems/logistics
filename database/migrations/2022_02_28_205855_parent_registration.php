<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ParentRegistration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('parentRegistration', function (Blueprint $table) {
            $table->id();
            $table->string('studentId')->nullable();
            $table->string('father_title')->nullable();
            $table->string('father')->nullable();
            $table->string('mother_title')->nullable();
            $table->string('mother')->nullable();
            $table->string('father_phone')->nullable();
            $table->string('mother_phone')->nullable();
            $table->string('parent_address')->nullable();
            $table->string('office_address')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('religion')->nullable();
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
        Schema::dropIfExists('parentRegistration');
    }
}
