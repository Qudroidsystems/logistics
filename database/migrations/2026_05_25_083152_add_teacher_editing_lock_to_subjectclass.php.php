<?php
// database/migrations/2026_05_25_000003_add_teacher_editing_lock_to_subjectclass.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('subjectclass', function (Blueprint $table) {
            if (!Schema::hasColumn('subjectclass', 'teacher_editing_enabled')) {
                $table->boolean('teacher_editing_enabled')->default(true);
            }
            if (!Schema::hasColumn('subjectclass', 'teacher_editing_disabled_at')) {
                $table->timestamp('teacher_editing_disabled_at')->nullable();
            }
            if (!Schema::hasColumn('subjectclass', 'teacher_editing_disabled_by')) {
                $table->unsignedBigInteger('teacher_editing_disabled_by')->nullable();
            }
        });

        // Add foreign key
        try {
            Schema::table('subjectclass', function (Blueprint $table) {
                if (Schema::hasColumn('subjectclass', 'teacher_editing_disabled_by')) {
                    $table->foreign('teacher_editing_disabled_by', 'subjectclass_teacher_editing_disabled_by_foreign')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                }
            });
        } catch (\Exception $e) {}
    }

    public function down()
    {
        Schema::table('subjectclass', function (Blueprint $table) {
            try {
                $table->dropForeign('subjectclass_teacher_editing_disabled_by_foreign');
            } catch (\Exception $e) {}

            if (Schema::hasColumn('subjectclass', 'teacher_editing_enabled')) {
                $table->dropColumn('teacher_editing_enabled');
            }
            if (Schema::hasColumn('subjectclass', 'teacher_editing_disabled_at')) {
                $table->dropColumn('teacher_editing_disabled_at');
            }
            if (Schema::hasColumn('subjectclass', 'teacher_editing_disabled_by')) {
                $table->dropColumn('teacher_editing_disabled_by');
            }
        });
    }
};
