<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up()
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classcategory_id')->constrained('classcategories')->onDelete('cascade');
            $table->decimal('total_score', 5, 2);
            $table->string('grade');
            $table->boolean('is_senior')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grades');
    }


};
