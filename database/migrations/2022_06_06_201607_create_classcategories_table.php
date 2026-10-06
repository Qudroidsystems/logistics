<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClasscategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('classcategories', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->double('ca1score',5, 2)->default('0');
            $table->double('ca2score',5, 2)->default('0');
            $table->double('examscore',5, 2)->default('0');
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
        Schema::dropIfExists('classcategories');
    }
}
