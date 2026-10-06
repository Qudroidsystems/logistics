<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per provider operator: where they are in onboarding and what staff said about it.
        Schema::create('provider_applications', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('operator_id')->unique()->constrained('operators');
            // draft | submitted | needs_changes | approved | rejected
            $table->string('status', 16)->default('draft')->index();
            $table->timestampTz('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->unsignedSmallInteger('submissions')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_applications');
    }
};
