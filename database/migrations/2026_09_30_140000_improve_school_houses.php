<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * School houses: house points, member roles (captain…), motto/active flag.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schoolhouses')) {
            Schema::table('schoolhouses', function (Blueprint $table) {
                if (!Schema::hasColumn('schoolhouses', 'motto'))       $table->string('motto', 150)->nullable();
                if (!Schema::hasColumn('schoolhouses', 'description')) $table->text('description')->nullable();
                if (!Schema::hasColumn('schoolhouses', 'is_active'))   $table->boolean('is_active')->default(true);
            });
        }

        if (Schema::hasTable('studenthouses')) {
            Schema::table('studenthouses', function (Blueprint $table) {
                if (!Schema::hasColumn('studenthouses', 'role')) $table->string('role', 30)->default('member');
            });
            try {
                Schema::table('studenthouses', fn (Blueprint $t) => $t->index('studentid', 'idx_studenthouses_studentid'));
            } catch (\Throwable $e) { /* index already there */ }
        }

        if (!Schema::hasTable('house_points')) {
            Schema::create('house_points', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('house_id')->index();
                $table->integer('points');                          // negative = deduction
                $table->string('category', 30)->default('other');   // sports | academics | conduct | cultural | other
                $table->string('reason', 255);
                $table->date('event_date');
                $table->unsignedBigInteger('student_id')->nullable(); // optional: who earned it
                $table->unsignedBigInteger('session_id')->index();
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedBigInteger('awarded_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('house_points');
    }
};
