<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** House patron, assistant house master and a few more details. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('schoolhouses')) return;
        Schema::table('schoolhouses', function (Blueprint $table) {
            if (!Schema::hasColumn('schoolhouses', 'patron_id'))            $table->unsignedBigInteger('patron_id')->nullable();
            if (!Schema::hasColumn('schoolhouses', 'assistant_master_id'))  $table->unsignedBigInteger('assistant_master_id')->nullable();
            if (!Schema::hasColumn('schoolhouses', 'mascot'))               $table->string('mascot', 80)->nullable();
            if (!Schema::hasColumn('schoolhouses', 'founded_year'))         $table->unsignedSmallInteger('founded_year')->nullable();
            if (!Schema::hasColumn('schoolhouses', 'meeting_place'))        $table->string('meeting_place', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('schoolhouses', function (Blueprint $table) {
            foreach (['patron_id', 'assistant_master_id', 'mascot', 'founded_year', 'meeting_place'] as $c) {
                if (Schema::hasColumn('schoolhouses', $c)) $table->dropColumn($c);
            }
        });
    }
};
