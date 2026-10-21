<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Texts to phones can now also go out on WhatsApp; sms_log records which channel each one used. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_log', function (Blueprint $table) {
            if (! Schema::hasColumn('sms_log', 'channel')) {
                // sms | whatsapp
                $table->string('channel', 10)->default('sms')->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sms_log', function (Blueprint $table) {
            if (Schema::hasColumn('sms_log', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};
