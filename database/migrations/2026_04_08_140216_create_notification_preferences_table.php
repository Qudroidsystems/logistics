<?php
// database/migrations/2025_01_15_000008_add_sms_fields_to_notifications.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('timetable_notifications', function (Blueprint $table) {
            $table->string('phone_number', 20)->nullable()->after('email');
            $table->enum('channel', ['email', 'sms', 'both'])->default('email')->after('status');
            $table->timestamp('sms_sent_at')->nullable()->after('sent_at');
        });

        // Notification preferences for teachers
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('email_daily_summary')->default(true);
            $table->boolean('email_weekly_preview')->default(true);
            $table->boolean('email_change_alert')->default(true);
            $table->boolean('sms_daily_summary')->default(false);
            $table->boolean('sms_change_alert')->default(true);
            $table->boolean('push_notifications')->default(true);
            $table->time('notification_time')->default('07:00:00');
            $table->timestamps();

            $table->unique('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_preferences');
        Schema::table('timetable_notifications', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'channel', 'sms_sent_at']);
        });
    }
};
