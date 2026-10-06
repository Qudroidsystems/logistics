<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->nullable()->index();
            $table->unsignedBigInteger('school_bill_id')->nullable()->index();
            $table->unsignedBigInteger('student_bill_payment_id')->nullable()->index();
            $table->unsignedBigInteger('student_bill_payment_record_id')->nullable()->index();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('action'); // recorded, bulk_recorded, updated, deleted, invoice_confirmed, reversed
            $table->string('entity_type')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'term_id', 'session_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_audit_logs');
    }
};