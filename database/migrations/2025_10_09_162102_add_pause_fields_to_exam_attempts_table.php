<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('start_time');
            $table->timestamp('resumed_at')->nullable()->after('paused_at');
            $table->integer('pause_duration')->default(0)->after('resumed_at');
            
            // Index for faster queries on active/paused attempts
            $table->index(['exam_id', 'paused_at', 'resumed_at']);
        });
    }

    public function down()
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex(['exam_id', 'paused_at', 'resumed_at']);
            $table->dropColumn(['paused_at', 'resumed_at', 'pause_duration']);
        });
    }
};