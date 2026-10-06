<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('order_number', 24);
            // The fulfilling operator (the provider); null only while a marketplace request is unassigned.
            $table->foreignId('operator_id')->nullable()->constrained('operators');
            // customer_app | web | admin | api | corporate | store | whatsapp
            $table->string('channel', 16)->default('customer_app');
            $table->foreignId('customer_id')->constrained('users');
            $table->unsignedBigInteger('business_account_id')->nullable()->index();
            $table->foreignId('merchant_id')->nullable()->constrained('merchants');
            $table->string('external_order_id', 80)->nullable();
            $table->foreignId('agreement_id')->nullable()->constrained('agreements');
            $table->foreignId('quote_id')->nullable()->constrained('quotes');
            $table->foreignId('service_type_id')->constrained('service_types');
            // draft | placed | confirmed | in_fulfilment | completed | cancelled | failed | refunded
            $table->string('status', 20)->default('draft');
            // unpaid | authorised | paid | partially_refunded | refunded | cod_pending
            $table->string('payment_status', 20)->default('unpaid');
            // wallet | card | bank_transfer | ussd | cash | cod | invoice
            $table->string('payment_method', 16)->nullable();
            $table->char('currency', 3)->default('NGN');
            $table->money('subtotal');
            $table->money('delivery_fee');
            $table->money('service_fee');
            $table->money('tip');
            $table->money('tax');
            $table->money('discount');
            $table->money('total');
            $table->timestampTz('scheduled_for')->nullable();
            $table->timestampTz('placed_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->string('cancelled_by', 16)->nullable();
            $table->string('cancel_reason_code', 40)->nullable();
            $table->money('cancel_fee');
            $table->string('idempotency_key', 120)->nullable();
            $table->boolean('is_test')->default(false);
            $table->timestamps();

            $table->unique(['operator_id', 'order_number']);
            $table->unique(['merchant_id', 'external_order_id']);
            $table->unique(['customer_id', 'idempotency_key']);
            $table->index(['operator_id', 'status', 'created_at']);
            $table->index(['customer_id', 'created_at']);
        });
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_check CHECK (total = subtotal + delivery_fee + service_fee + tip + tax - discount)');

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreign('accepted_order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('order_id')->constrained('orders');
            $table->operatorId();
            // forward | return | reattempt | transfer | linehaul
            $table->string('kind', 12)->default('forward');
            $table->foreignId('parent_shipment_id')->nullable()->constrained('shipments');
            $table->foreignId('service_type_id')->constrained('service_types');
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types');
            // created | awaiting_dispatch | offered | assigned | heading_to_pickup | at_pickup | picked_up |
            // in_transit | at_dropoff | delivered | failed_attempt | returning | returned | cancelled | lost | damaged
            $table->string('status', 24)->default('created');
            $table->string('tracking_code', 16)->unique();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->unsignedInteger('distance_m')->nullable();
            $table->unsignedInteger('duration_s')->nullable();
            $table->timestampTz('ready_at')->nullable();
            $table->timestampTz('pickup_window_start')->nullable();
            $table->timestampTz('pickup_window_end')->nullable();
            $table->timestampTz('deliver_by')->nullable();
            $table->timestampTz('eta_at')->nullable();
            $table->unsignedSmallInteger('eta_confidence')->nullable();
            $table->unsignedSmallInteger('current_stop_seq')->default(0);
            $table->boolean('requires_cod')->default(false);
            $table->money('cod_amount');
            $table->money('insured_value');
            $table->timestampTz('sla_breached_at')->nullable();
            $table->unsignedBigInteger('route_plan_id')->nullable();
            $table->boolean('needs_manual_dispatch')->default(false);
            $table->boolean('is_test')->default(false);
            $table->timestampTz('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'status']);
            $table->index('order_id');
        });
        // The dispatch worker polls this: only open work is indexed.
        DB::statement("CREATE INDEX shipments_awaiting_dispatch_idx ON shipments (operator_id, created_at) WHERE status = 'awaiting_dispatch'");

        Schema::create('shipment_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->unsignedSmallInteger('seq');
            // pickup | dropoff | hub_dropoff | hub_pickup | waypoint | return
            $table->string('type', 16);
            $table->foreignId('address_id')->nullable()->constrained('addresses');
            // Address data is copied, never joined, so editing an address never rewrites history.
            $table->string('line1');
            $table->string('landmark')->nullable();
            $table->geoPoint('point', false);
            $table->bigInteger('h3_9')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 24)->nullable();
            $table->text('instructions')->nullable();
            $table->timestampTz('window_start')->nullable();
            $table->timestampTz('window_end')->nullable();
            $table->unsignedInteger('service_seconds')->nullable();
            $table->timestampTz('arrived_at')->nullable();
            $table->timestampTz('departed_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            // pending | en_route | arrived | completed | failed | skipped
            $table->string('status', 12)->default('pending');
            $table->string('otp_hash')->nullable();
            $table->boolean('requires_signature')->default(false);
            $table->boolean('requires_photo')->default(true);
            $table->unsignedBigInteger('place_id')->nullable();
            $table->unsignedBigInteger('hub_id')->nullable();
            $table->timestamps();

            $table->unique(['shipment_id', 'seq']);
            $table->spatialIndex('point');
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('pickup_stop_id')->nullable()->constrained('shipment_stops');
            $table->foreignId('dropoff_stop_id')->nullable()->constrained('shipment_stops');
            $table->foreignId('category_id')->nullable()->constrained('package_categories');
            $table->string('description')->nullable();
            $table->unsignedInteger('weight_g')->nullable();
            $table->unsignedInteger('length_mm')->nullable();
            $table->unsignedInteger('width_mm')->nullable();
            $table->unsignedInteger('height_mm')->nullable();
            $table->money('declared_value');
            $table->boolean('fragile')->default(false);
            $table->boolean('perishable')->default(false);
            $table->string('temperature_class', 16)->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('barcode', 40)->nullable()->unique();
            $table->string('qr_payload', 200)->nullable();
            $table->string('label_path')->nullable();
            $table->jsonb('photo_paths')->nullable();
            // created | picked_up | at_hub | in_transit | delivered | damaged | lost | returned
            $table->string('status', 16)->default('created');
            $table->timestamps();
        });

        // Append-only history, partitioned monthly.
        DB::statement(<<<'SQL'
            CREATE TABLE shipment_events (
                id BIGINT GENERATED ALWAYS AS IDENTITY,
                shipment_id BIGINT NOT NULL REFERENCES shipments(id),
                seq INTEGER NOT NULL,
                type VARCHAR(32) NOT NULL,
                from_status VARCHAR(24),
                to_status VARCHAR(24),
                actor_type VARCHAR(16),
                actor_id BIGINT,
                stop_id BIGINT,
                point geography(Point, 4326),
                meta JSONB,
                source VARCHAR(16) NOT NULL DEFAULT 'system',
                created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                PRIMARY KEY (id, created_at)
            ) PARTITION BY RANGE (created_at)
        SQL);
        DB::statement('CREATE INDEX shipment_events_shipment_idx ON shipment_events (shipment_id, created_at)');
        DB::statement('CREATE TABLE shipment_events_default PARTITION OF shipment_events DEFAULT');

        Schema::create('order_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // delivery | waiting | cod_fee | tip | insurance | tax | discount | cancellation | adjustment
            $table->string('type', 16);
            $table->bigInteger('amount');
            $table->string('payer', 12)->default('customer');
            $table->string('payee', 12)->default('provider');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });

        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users');
            $table->boolean('customer_visible')->default(false);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->string('reason_code', 40);
            $table->string('stage', 24);
            $table->money('fee');
            $table->money('refund_amount');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->string('reason');
            $table->foreignId('authorised_by')->nullable()->constrained('users');
            $table->money('restocking_fee');
            $table->string('status', 16)->default('requested');
            $table->timestamps();
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->foreignId('shipment_id')->constrained('shipments');
            $table->foreignId('package_id')->nullable()->constrained('packages');
            // damage | loss | delay | wrong_item
            $table->string('type', 16);
            $table->bigInteger('amount_claimed');
            $table->jsonb('evidence')->nullable();
            $table->string('status', 16)->default('open');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->unsignedBigInteger('payout_ledger_tx_id')->nullable();
            $table->timestamps();
        });

        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 24);
            $table->foreignId('address_id')->nullable()->constrained('addresses');
            $table->text('delivery_preferences')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['recipients', 'claims', 'returns', 'cancellations', 'order_notes', 'order_charges'] as $t) {
            Schema::dropIfExists($t);
        }
        DB::statement('DROP TABLE IF EXISTS shipment_events CASCADE');
        foreach (['packages', 'shipment_stops', 'shipments'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('quotes', fn (Blueprint $t) => $t->dropForeign(['accepted_order_id']));
        Schema::dropIfExists('orders');
    }
};
