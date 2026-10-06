<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── promotion_rule_templates ─────────────────────────────────────────
        if (!Schema::hasTable('promotion_rule_templates')) {
            Schema::create('promotion_rule_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('grade_scale', ['senior', 'junior'])->default('senior');
                $table->json('rules')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // ── promotion_settings additions ─────────────────────────────────────
        Schema::table('promotion_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('promotion_settings', 'rule_logic')) {
                $table->string('rule_logic', 20)->default('grade_count')->after('repeat_label');
            }
            if (!Schema::hasColumn('promotion_settings', 'promotion_pass_average')) {
                $table->decimal('promotion_pass_average', 5, 2)->nullable()->after('rule_logic');
            }
            if (!Schema::hasColumn('promotion_settings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('promotion_pass_average');
            }
            if (!Schema::hasColumn('promotion_settings', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('is_active');
                $table->foreign('template_id')->references('id')->on('promotion_rule_templates')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotion_settings', function (Blueprint $table) {
            if (Schema::hasColumn('promotion_settings', 'template_id')) {
                $table->dropForeign(['template_id']);
                $table->dropColumn('template_id');
            }
            foreach (['rule_logic', 'promotion_pass_average', 'is_active'] as $col) {
                if (Schema::hasColumn('promotion_settings', $col)) $table->dropColumn($col);
            }
        });
        Schema::dropIfExists('promotion_rule_templates');
    }
};
