<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every provider, merchant and the platform itself is an operator.
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            // platform | company | franchise | independent_driver | market_shopper | merchant | corporate_fleet
            $table->string('type', 32)->index();
            $table->string('legal_name');
            $table->string('display_name');
            $table->string('slug', 80)->unique();
            // pending | active | restricted | suspended | closed
            $table->string('status', 24)->default('pending');
            $table->foreignId('parent_operator_id')->nullable()->constrained('operators');
            $table->unsignedBigInteger('home_city_id')->nullable();
            $table->char('default_currency', 3)->default('NGN');
            $table->unsignedSmallInteger('commission_bp')->default(1000);
            $table->string('payout_schedule', 16)->default('weekly');
            $table->jsonb('branding')->nullable();
            $table->jsonb('support_contact')->nullable();
            $table->unsignedSmallInteger('risk_tier')->default(1);
            $table->timestampTz('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'type']);
        });

        Schema::create('operator_members', function (Blueprint $table) {
            $table->id();
            $table->operatorId();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // owner | admin | dispatcher | finance | support | driver_manager
            $table->string('role', 32);
            $table->string('status', 16)->default('active');
            $table->foreignId('invited_by')->nullable()->constrained('users');
            $table->timestampTz('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['operator_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->ulid('public_id')->nullable()->unique();
            $table->timestampTz('phone_verified_at')->nullable();
            $table->string('locale', 8)->default('en');
            $table->timestampTz('last_active_at')->nullable();
            $table->foreignId('current_operator_id')->nullable()->constrained('operators')->nullOnDelete();
            // active | locked | banned | closed
            $table->string('status', 16)->default('active');
            $table->unsignedSmallInteger('risk_score')->default(0);
        });

        Schema::create('operator_capabilities', function (Blueprint $table) {
            $table->id();
            $table->operatorId();
            // own_fleet | accept_marketplace_jobs | post_marketplace_jobs | cod | corporate_billing |
            // hubs | stores | api_access | list_in_directory | negotiate | instant_book |
            // shopping_errands | partner_api
            $table->string('capability', 48);
            $table->boolean('enabled')->default(true);
            $table->jsonb('limits')->nullable();
            $table->timestamps();

            $table->unique(['operator_id', 'capability']);
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            // null = platform-wide; otherwise an operator's own override
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            $table->string('scope', 16)->default('global'); // global | city | operator
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('key', 96);
            $table->jsonb('value');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['operator_id', 'scope', 'scope_id', 'key'], 'platform_settings_unique');
            $table->index('key');
        });

        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->unsignedBigInteger('merchant_id')->nullable()->index();
            $table->string('name');
            $table->string('environment', 8)->default('sandbox'); // sandbox | live
            $table->string('key_prefix', 16)->unique();
            $table->string('key_hash');
            $table->string('signing_secret_hash')->nullable();
            $table->jsonb('scopes')->nullable();
            $table->jsonb('ip_allowlist')->nullable();
            $table->unsignedInteger('rate_limit_per_minute')->default(120);
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('rotated_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'environment']);
        });

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->string('url', 500);
            $table->string('secret');
            $table->jsonb('events');
            $table->boolean('active')->default(true);
            $table->boolean('is_test')->default(false);
            $table->timestamps();

            $table->index(['operator_id', 'active']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->string('event', 64);
            $table->string('event_id', 40)->index();
            $table->jsonb('payload');
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->timestampTz('next_retry_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['endpoint_id', 'created_at']);
            $table->index('next_retry_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('api_clients');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('operator_capabilities');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_operator_id');
            $table->dropColumn(['public_id', 'phone_verified_at', 'locale', 'last_active_at', 'status', 'risk_score']);
        });
        Schema::dropIfExists('operator_members');
        Schema::dropIfExists('operators');
    }
};
