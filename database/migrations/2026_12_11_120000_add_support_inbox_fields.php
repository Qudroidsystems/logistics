<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The support inbox: a subject line, when the last message came, and who has unread replies. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('subject', 160)->nullable();
            $table->timestampTz('last_message_at')->nullable();
            $table->boolean('customer_unread')->default(false);
            $table->boolean('staff_unread')->default(true);
            $table->timestampTz('resolved_at')->nullable();
            $table->index(['requester_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['requester_id', 'last_message_at']);
            $table->dropColumn(['subject', 'last_message_at', 'customer_unread', 'staff_unread', 'resolved_at']);
        });
    }
};
