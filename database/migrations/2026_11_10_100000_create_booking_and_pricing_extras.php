<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer booking and pricing extras: a chosen pickup time and rush flag on requests, photos and documents on a
 * request, promo codes and corporate discounts on agreements (the platform pays for the discount, so escrow
 * remembers how much of it came from the platform), and receipts tied to orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->timestampTz('pickup_at')->nullable()->after('needed_by');
            $table->boolean('rush')->default(false)->after('pickup_at');
        });

        Schema::create('request_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            // photo | document
            $table->string('kind', 12);
            $table->string('path');
            $table->string('original_name', 200)->nullable();
            $table->string('mime', 100);
            $table->unsignedInteger('size_bytes');
            $table->string('sha256', 64);
            $table->string('caption', 200)->nullable();
            $table->timestampTz('created_at')->nullable();

            $table->index('request_id');
        });

        Schema::table('agreements', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes');
            // What the customer does not pay: promo + corporate discount, never more than the price.
            $table->bigInteger('discount')->default(0);
            $table->jsonb('discount_lines')->nullable();
        });

        Schema::table('escrow_holds', function (Blueprint $table) {
            // The part of the hold the platform put in for a discount; it goes back to the platform, never to the customer.
            $table->bigInteger('subsidy')->default(0);
            $table->bigInteger('subsidy_returned')->default(0);
        });

        Schema::create('corporate_discounts', function (Blueprint $table) {
            $table->id();
            // null = every provider (platform-wide); otherwise only this provider's jobs
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            // exactly one of these is set
            $table->foreignId('merchant_id')->nullable()->constrained('merchants');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('name', 120);
            $table->unsignedInteger('rate_bp');
            $table->bigInteger('cap')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampTz('valid_to')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'active']);
            $table->index(['user_id', 'active']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $t) => $t->dropColumn('order_id'));
        Schema::dropIfExists('corporate_discounts');
        Schema::table('escrow_holds', fn (Blueprint $t) => $t->dropColumn(['subsidy', 'subsidy_returned']));
        Schema::table('agreements', function (Blueprint $t) {
            $t->dropConstrainedForeignId('promo_code_id');
            $t->dropColumn(['discount', 'discount_lines']);
        });
        Schema::dropIfExists('request_attachments');
        Schema::table('service_requests', fn (Blueprint $t) => $t->dropColumn(['pickup_at', 'rush']));
    }
};
