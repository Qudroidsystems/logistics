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
        Schema::create('student_bill_payment_book', function (Blueprint $table) {
            $table->id();
            $table->string('student_id');
            $table->string('school_bill_id')->nullable();
            $table->string('amount_paid')->nullable();
            $table->string('amount_owed')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('class_id');
            $table->string('term_id');
            $table->string('session_id');
            $table->string('generated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_bill_payment_book');
    }
};
