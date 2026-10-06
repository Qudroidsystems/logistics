<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distinguish certificate templates from testimonial (leaving/character) templates.
 * Both share the same designer, QR verification, approval, audit and lock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_templates', 'kind')) {
                $table->string('kind', 20)->default('certificate')->after('name')->index(); // certificate | testimonial
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_templates', 'kind')) $table->dropColumn('kind');
        });
    }
};
