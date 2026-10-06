<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Log of payment receipts sent to parents (SMS / WhatsApp / email). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_receipt_messages')) {
            Schema::create('payment_receipt_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->string('reference', 80);
                $table->decimal('amount', 15, 2);
                $table->string('method', 60)->nullable();
                $table->string('channel', 20);
                $table->string('recipient');
                $table->string('recipient_name')->nullable();
                $table->text('body');
                $table->string('status', 20)->default('queued')->index(); // sent|failed|skipped
                $table->string('error', 500)->nullable();
                $table->string('provider_message_id')->nullable();
                $table->timestamps();

                $table->unique(['reference', 'channel', 'recipient'], 'uq_prm_ref_channel_recipient');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_receipt_messages');
    }
};
