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
       Schema::create('mock_subject_vettings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->unsignedBigInteger('subjectclassId');
            $table->unsignedBigInteger('termid');
            $table->unsignedBigInteger('sessionid');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('userid')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('subjectclassId')->references('id')->on('subjectclass')->onDelete('cascade');
            $table->foreign('termid')->references('id')->on('schoolterm')->onDelete('cascade');
            $table->foreign('sessionid')->references('id')->on('schoolsession')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_subject_vettings');
    }
};
