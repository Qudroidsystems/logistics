<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->operatorId();
            $table->string('code', 64)->nullable();
            // asset | liability | revenue | expense | equity
            $table->string('type', 16);
            // customer | driver | vendor | operator | merchant | platform | agreement
            $table->string('owner_type', 24);
            $table->unsignedBigInteger('owner_id')->nullable();
            // customer_wallet | driver_wallet | vendor_wallet | operator_wallet | merchant_wallet |
            // order_escrow | gateway_clearing | cod_clearing | platform_commission | platform_revenue |
            // tax_payable | promo_expense | refund_reserve | payout_in_transit | shopper_float
            $table->string('purpose', 32);
            $table->char('currency', 3)->default('NGN');
            // Cached; written ONLY by the LedgerPoster inside the posting transaction.
            $table->bigInteger('balance')->default(0);
            $table->unsignedBigInteger('balance_version')->default(0);
            $table->boolean('allow_negative')->default(false);
            $table->timestampTz('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id', 'purpose', 'currency'], 'ledger_accounts_owner_unique');
            $table->index(['operator_id', 'purpose']);
        });

        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            // topup | order_payment | escrow_hold | escrow_release | commission | driver_earning | cod_collected |
            // cod_remitted | refund | payout | adjustment | incentive | promo | fee | chargeback | shopper_advance
            $table->string('kind', 32);
            $table->string('reference_type', 32)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('idempotency_key', 120)->unique();
            $table->string('description')->nullable();
            $table->foreignId('reverses_id')->nullable()->constrained('ledger_transactions');
            $table->foreignId('posted_by')->nullable()->constrained('users');
            $table->boolean('is_test')->default(false);
            $table->timestampTz('posted_at')->useCurrent();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['operator_id', 'kind', 'posted_at']);
        });

        // Entries are append-only and grow fastest, so they are partitioned by month.
        DB::statement(<<<'SQL'
            CREATE TABLE ledger_entries (
                id BIGINT GENERATED ALWAYS AS IDENTITY,
                transaction_id BIGINT NOT NULL REFERENCES ledger_transactions(id),
                account_id BIGINT NOT NULL REFERENCES ledger_accounts(id),
                direction VARCHAR(6) NOT NULL CHECK (direction IN ('debit','credit')),
                amount BIGINT NOT NULL CHECK (amount > 0),
                balance_after BIGINT,
                created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                PRIMARY KEY (id, created_at)
            ) PARTITION BY RANGE (created_at)
        SQL);
        DB::statement('CREATE INDEX ledger_entries_tx_idx ON ledger_entries (transaction_id)');
        DB::statement('CREATE INDEX ledger_entries_account_idx ON ledger_entries (account_id, created_at)');
        DB::statement('CREATE TABLE ledger_entries_default PARTITION OF ledger_entries DEFAULT');

        // Safety net behind the application check: every transaction must balance at commit.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION ledger_assert_balanced() RETURNS trigger AS $$
            DECLARE diff BIGINT;
            BEGIN
                SELECT COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE -amount END), 0)
                  INTO diff FROM ledger_entries WHERE transaction_id = NEW.transaction_id;
                IF diff <> 0 THEN
                    RAISE EXCEPTION 'Ledger transaction % is unbalanced by %', NEW.transaction_id, diff;
                END IF;
                RETURN NULL;
            END;
            $$ LANGUAGE plpgsql
        SQL);
        DB::statement(<<<'SQL'
            CREATE CONSTRAINT TRIGGER ledger_entries_balanced
            AFTER INSERT ON ledger_entries
            DEFERRABLE INITIALLY DEFERRED
            FOR EACH ROW EXECUTE FUNCTION ledger_assert_balanced()
        SQL);

        // Append-only: block updates and deletes of posted rows.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION ledger_block_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Ledger rows are immutable; post a reversing transaction instead';
            END;
            $$ LANGUAGE plpgsql
        SQL);
        DB::statement('CREATE TRIGGER ledger_entries_immutable BEFORE UPDATE OR DELETE ON ledger_entries FOR EACH ROW EXECUTE FUNCTION ledger_block_mutation()');
        DB::statement('CREATE TRIGGER ledger_transactions_immutable BEFORE UPDATE OR DELETE ON ledger_transactions FOR EACH ROW EXECUTE FUNCTION ledger_block_mutation()');

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->operatorId();
            $table->string('owner_type', 24);
            $table->unsignedBigInteger('owner_id');
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts');
            // active | frozen | closed
            $table->string('status', 16)->default('active');
            $table->unsignedBigInteger('daily_limit')->nullable();
            $table->unsignedSmallInteger('kyc_tier')->default(1);
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->publicId();
            $table->string('owner_type', 24);
            $table->unsignedBigInteger('owner_id');
            $table->string('bank_code', 12);
            $table->string('bank_name', 80)->nullable();
            $table->text('account_number');            // encrypted by the model cast
            $table->string('account_number_last4', 4);
            $table->string('account_name');
            $table->timestampTz('verified_at')->nullable();
            $table->string('verification_ref', 80)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('wallets');
        DB::statement('DROP TABLE IF EXISTS ledger_entries CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS ledger_assert_balanced()');
        DB::statement('DROP FUNCTION IF EXISTS ledger_block_mutation() CASCADE');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('ledger_accounts');
    }
};
