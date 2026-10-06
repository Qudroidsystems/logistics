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
        Schema::create('student_bill_invoice', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no');
            $table->string('student_id');
            $table->string('school_bill_id');
            $table->string('status');
            $table->string('payment_method');
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
        Schema::dropIfExists('student_bill_invoice');
    }
};
