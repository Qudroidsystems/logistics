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
        Schema::create('school_bill_class_term_session', function (Blueprint $table) {
            $table->id();
            $table->string('bill_id');
            $table->string('class_id');
            $table->string('termid_id');
            $table->string('session_id');
            $table->string('createdBy');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_bill_class_term_session');
    }
};
