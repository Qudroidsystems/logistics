<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_key_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->text('note')->nullable();
            // pending | approved | declined | issued
            $table->string('status', 12)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestampTz('decided_at')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->foreignId('api_client_id')->nullable()->constrained('api_clients')->nullOnDelete();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
        // At most one open request per merchant: waiting for a decision, or approved and not yet used.
        DB::statement("CREATE UNIQUE INDEX live_key_requests_one_open_idx ON live_key_requests (merchant_id) WHERE status IN ('pending','approved')");
    }

    public function down(): void
    {
        Schema::dropIfExists('live_key_requests');
    }
};
