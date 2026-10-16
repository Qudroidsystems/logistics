<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A provider's link to its QuickBooks or Xero company, and what has been sent there. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->unique()->constrained('operators');
            // quickbooks | xero
            $table->string('service', 12);
            // QuickBooks realm id or Xero tenant id, and the company's name there.
            $table->string('tenant_id', 64);
            $table->string('tenant_name', 190)->nullable();
            // Encrypted.
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestampTz('expires_at');
            // Which account in their books each kind of money goes to.
            $table->jsonb('accounts')->nullable();
            // active | error | disconnected
            $table->string('status', 12)->default('active');
            $table->string('error', 500)->nullable();
            // The first day to send; days before the connection are not sent unless chosen.
            $table->date('sync_from');
            $table->date('synced_through')->nullable();
            $table->foreignId('connected_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('accounting_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('accounting_connections')->cascadeOnDelete();
            $table->date('day');
            $table->string('external_id', 64)->nullable();
            // sent | empty | failed
            $table->string('status', 8);
            $table->bigInteger('total')->default(0);
            $table->string('error', 500)->nullable();
            $table->timestamps();
            $table->unique(['connection_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_syncs');
        Schema::dropIfExists('accounting_connections');
    }
};
