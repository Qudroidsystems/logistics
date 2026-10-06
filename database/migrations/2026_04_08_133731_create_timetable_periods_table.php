<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('setting_id');
            $table->unsignedSmallInteger('order');
            $table->string('name', 60);
            $table->enum('type', ['lesson', 'short_break', 'long_break', 'assembly', 'free'])->default('lesson');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->boolean('is_break')->default(false);
            $table->timestamps();

            $table->index(['setting_id', 'order']);
            $table->foreign('setting_id')->references('id')->on('timetable_settings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_periods');
    }
};
