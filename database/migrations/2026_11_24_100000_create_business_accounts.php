<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Business accounts: companies a provider delivers for on credit and bills once a month.
 *
 *   business_accounts        one per business customer of a provider: credit limit, payment terms, billing contact,
 *                            and optionally the partner-API merchant whose deliveries go on the account
 *   orders.business_account_id (already there)   a delivery booked on account
 *   orders.invoice_id        the invoice that billed it; null while it is still to be billed
 *   invoices (already there) gains when it was sent and how it was paid. Monthly invoices have business_account_id set;
 *                            receipts for paid orders (order_id set) are unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_accounts', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->string('name', 160);
            $table->foreignId('merchant_id')->nullable()->constrained('merchants');
            $table->string('billing_email', 190)->nullable();
            $table->string('billing_phone', 24)->nullable();
            $table->string('billing_address')->nullable();
            // kobo; null means no limit
            $table->bigInteger('credit_limit')->nullable();
            $table->unsignedSmallInteger('terms_days')->default(30);
            // active | on_hold | closed
            $table->string('status', 8)->default('active');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'status']);
        });
        DB::statement('CREATE UNIQUE INDEX business_accounts_one_per_merchant ON business_accounts (operator_id, merchant_id) WHERE merchant_id IS NOT NULL');

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->index()->constrained('invoices')->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->timestampTz('sent_at')->nullable();
            $table->string('paid_reference', 120)->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users');
            $table->index(['business_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['business_account_id', 'status']);
            $table->dropConstrainedForeignId('paid_by');
            $table->dropColumn(['sent_at', 'paid_reference']);
        });
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('invoice_id'));
        Schema::dropIfExists('business_accounts');
    }
};
