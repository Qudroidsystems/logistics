<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_settings', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20)->unique();     // sms | whatsapp | email
            $table->string('driver', 30)->default('log');
            $table->boolean('is_active')->default(false);
            $table->json('config')->nullable();          // secrets encrypted
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messaging_settings');
    }
};
