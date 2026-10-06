<?php
// database/migrations/2024_01_01_000004_create_payment_batches_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payment batches - groups multiple bill payments in one transaction
        Schema::create('payment_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_no')->unique();
            $table->unsignedBigInteger('student_id');
            $table->date('payment_date');
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'pos', 'online', 'cheque']);
            $table->string('reference_no')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
            $table->text('notes')->nullable();
            $table->json('receipt_data')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('studentRegistration');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index('batch_no');
            $table->index('payment_date');
        });

        // Payment batch items - individual bills in a batch
        Schema::create('payment_batch_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_batch_id');
            $table->unsignedBigInteger('school_bill_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('termid_id');
            $table->unsignedBigInteger('session_id');
            $table->decimal('original_amount', 15, 2);
            $table->decimal('scholarship_deduction', 15, 2)->default(0);
            $table->decimal('discount_deduction', 15, 2)->default(0);
            $table->decimal('adjusted_amount', 15, 2);
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->unsignedBigInteger('student_bill_payment_id')->nullable();
            $table->timestamps();

            $table->foreign('payment_batch_id')->references('id')->on('payment_batches')->onDelete('cascade');
            $table->foreign('school_bill_id')->references('id')->on('school_bill');
            $table->foreign('class_id')->references('id')->on('schoolclass');
            $table->foreign('termid_id')->references('id')->on('schoolterm');
            $table->foreign('session_id')->references('id')->on('schoolsession');
            $table->index(['payment_batch_id', 'school_bill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_batch_items');
        Schema::dropIfExists('payment_batches');
    }
};
