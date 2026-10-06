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
        Schema::create('student_bill_payment_record', function (Blueprint $table) {
            $table->id();
            $table->string('student_bill_payment_id');
            $table->string('total_bill');
            $table->string('amount_paid');
            $table->string('amount_owed');
            $table->string('complete_payment');
            $table->string('class_id');
            $table->string('termid_id');
            $table->string('session_id');
            $table->string('generated_by');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_bill_payment_record');
    }
};
