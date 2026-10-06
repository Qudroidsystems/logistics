<?php
// database/migrations/2025_01_15_000003_create_timetable_change_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('timetable_change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('current_slot_id');
            $table->unsignedBigInteger('proposed_slot_id')->nullable();
            $table->enum('change_type', ['swap', 'move', 'substitute', 'cancel']);
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('current_slot_id')->references('id')->on('timetable_slots')->onDelete('cascade');
            $table->foreign('proposed_slot_id')->references('id')->on('timetable_slots')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('timetable_change_requests');
    }
};
