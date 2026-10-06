<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('channel', 20); // email | sms | whatsapp
            $table->string('recipient', 191)->nullable();
            $table->string('status', 20);  // sent | skipped | failed
            $table->string('reason', 255)->nullable();
            $table->decimal('outstanding', 15, 2)->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->text('provider_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};