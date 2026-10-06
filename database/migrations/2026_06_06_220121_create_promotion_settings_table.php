<?php
// database/migrations/2026_06_06_000001_create_promotion_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('promotion_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schoolclass_id')->constrained('schoolclass')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('schoolsession')->onDelete('cascade');
            $table->foreignId('term_id')->nullable()->constrained('schoolterm')->onDelete('cascade');

            // Rule types
            $table->enum('rule_type', ['compulsory_only', 'average_only', 'both'])->default('both');

            // Compulsory subject rules
            $table->integer('min_compulsory_pass')->nullable()->comment('Minimum number of compulsory subjects to pass');
            $table->enum('compulsory_fail_action', ['repeat', 'see_principal', 'trial'])->default('repeat');

            // Average score rules
            $table->decimal('promotion_pass_average', 5, 2)->nullable()->comment('Minimum average to pass');
            $table->decimal('trial_pass_average', 5, 2)->nullable()->comment('Average range for trial promotion');
            $table->decimal('see_principal_average', 5, 2)->nullable()->comment('Average range for see principal');

            // Combined rules
            $table->enum('combined_logic', ['and', 'or'])->default('and');

            // Promotion outcomes
            $table->string('promoted_label')->default('Promoted');
            $table->string('trial_label')->default('Promoted on Trial');
            $table->string('see_principal_label')->default('Advised to See Principal');
            $table->string('repeat_label')->default('Advice to Repeat');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['schoolclass_id', 'session_id', 'term_id'], 'unique_promotion_settings');
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotion_settings');
    }
}
