<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff_bio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')
                                        ->on('users')->onUpdate('cascade')->onDelete('cascade');
           // $table->foreignId('user_id')->contrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('firstname')->nullable()->default("no info");
            $table->string('lastname')->nullable()->default("no info");
            $table->string('othernames')->nullable()->default("no info");
            $table->string('phone')->nullable()->default("no info");
            $table->string('address')->nullable()->default("no info");
            $table->string('gender')->nullable()->default("no info");
            $table->string('maritalstatus')->nullable()->default("no info");
            $table->string('nationality')->nullable()->default("no info");
            $table->string('dob')->nullable()->default("no info");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_bio');
    }
};
