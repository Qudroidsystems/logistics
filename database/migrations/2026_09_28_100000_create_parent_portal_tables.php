<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parent portal: parent accounts are users with the "Parent" role, linked to
 * their children through parent_student.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('parent_student')) {
            Schema::create('parent_student', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->string('relationship', 20)->nullable();      // father | mother | guardian
                $table->string('source', 10)->default('auto');       // auto (from records) | manual
                $table->timestamps();
                $table->unique(['user_id', 'student_id']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false);
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'credentials_sent_at')) {
                $table->timestamp('credentials_sent_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'is_disabled')) {
                $table->boolean('is_disabled')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student');
        Schema::table('users', function (Blueprint $table) {
            foreach (['must_change_password', 'last_login_at', 'credentials_sent_at', 'is_disabled'] as $c) {
                if (Schema::hasColumn('users', $c)) $table->dropColumn($c);
            }
        });
    }
};
