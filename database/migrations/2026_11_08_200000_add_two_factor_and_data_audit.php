<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two-step sign-in columns, and the data-change audit trail.
 *
 * A trigger on each audited table writes one audit_logs row per inserted, changed or deleted row: only the columns
 * that changed, with secrets masked, attributed to the person in the audit.* connection settings (App\Modules\
 * Identity\Security\Audit). Busy columns that move on their own (GPS, ETA, ratings, last seen) are left out so the
 * log shows decisions, not telemetry.
 */
return new class extends Migration
{
    /** table => columns to ignore on top of the common ones (updated_at, last seen and similar). */
    public const TABLES = [
        'users' => ['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'two_factor_last_step', 'current_operator_id'],
        'roles' => [], 'permissions' => [], 'model_has_roles' => [], 'model_has_permissions' => [], 'role_has_permissions' => [],
        'operators' => ['risk_score'], 'operator_members' => [], 'operator_capabilities' => [], 'operator_invitations' => [],
        'platform_settings' => [], 'feature_flags' => [], 'maintenance_settings' => [], 'payment_gateways' => [], 'brand_settings' => [],
        'backup_settings' => ['last_run_at'], 'messaging_settings' => [],
        'service_types' => [], 'vehicle_types' => [], 'package_categories' => [], 'cities' => [], 'zones' => [], 'zone_pairs' => [],
        'pricing_plans' => [], 'pricing_rules' => [], 'surge_rules' => [], 'promo_codes' => ['used_count'], 'commission_rules' => [], 'tax_rates' => [],
        'provider_profiles' => [], 'provider_rate_cards' => [], 'provider_cost_profiles' => [], 'provider_applications' => [],
        'merchants' => [], 'api_clients' => [], 'webhook_endpoints' => [],
        'driver_profiles' => ['last_point', 'availability', 'rating_avg', 'rating_count', 'acceptance_rate', 'completion_rate', 'on_time_rate', 'last_trip_at'],
        'vehicles' => [], 'vehicle_assignments' => [], 'kyc_documents' => [], 'restrictions' => [], 'bank_accounts' => [],
        'orders' => [], 'shipments' => ['eta_at', 'eta_confidence', 'current_stop_seq', 'distance_m', 'duration_s'], 'assignments' => [],
        'agreements' => [], 'cancellations' => [], 'refunds' => [], 'payout_requests' => [], 'disputes' => [], 'claims' => [], 'delivery_exceptions' => [],
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->text('two_factor_secret')->nullable();
            $t->text('two_factor_recovery_codes')->nullable();
            $t->timestampTz('two_factor_confirmed_at')->nullable();
            $t->unsignedBigInteger('two_factor_last_step')->nullable();
        });

        Schema::table('activity_logs', function (Blueprint $t) {
            $t->string('request_id', 40)->nullable()->index();
        });

        // Removing a person or a provider must not be blocked by their history; the entry stays, unattributed.
        Schema::table('audit_logs', function (Blueprint $t) {
            $t->dropForeign(['user_id']);
            $t->dropForeign(['operator_id']);
            $t->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $t->foreign('operator_id')->references('id')->on('operators')->nullOnDelete();
            $t->index(['user_id', 'created_at']);
            $t->index('created_at');
            $t->index('request_id');
        });

        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION audit_mask(j jsonb) RETURNS jsonb LANGUAGE sql IMMUTABLE AS $f$
    SELECT coalesce(jsonb_object_agg(key, CASE
        WHEN key ~* '(^password$|^pin$|^otp$|secret|token|api_key|private|credential|recovery_codes|^config$|account_number|^bvn$|^nin$|_hash$)' AND value <> 'null'::jsonb
        THEN '"[hidden]"'::jsonb ELSE value END), '{}'::jsonb)
    FROM jsonb_each(j)
$f$;

CREATE OR REPLACE FUNCTION audit_row_change() RETURNS trigger LANGUAGE plpgsql AS $f$
DECLARE
    common text[] := ARRAY['updated_at', 'created_at', 'last_seen_at', 'last_seen_url', 'last_login_at', 'last_login_ip', 'last_active_at', 'remember_token'];
    skip text[] := common || string_to_array(coalesce(TG_ARGV[0], ''), ',');
    old_j jsonb; new_j jsonb; row_j jsonb; before_j jsonb := '{}'::jsonb; after_j jsonb := '{}'::jsonb;
    k text; act text; uid bigint; op bigint; sid bigint;
BEGIN
    IF coalesce(current_setting('audit.off', true), '') = '1' THEN RETURN NULL; END IF;
    IF TG_OP <> 'INSERT' THEN old_j := to_jsonb(OLD); END IF;
    IF TG_OP <> 'DELETE' THEN new_j := to_jsonb(NEW); END IF;
    row_j := coalesce(new_j, old_j);

    IF TG_OP = 'UPDATE' THEN
        FOR k IN SELECT jsonb_object_keys(new_j) LOOP
            CONTINUE WHEN k = ANY(skip);
            IF new_j->k IS DISTINCT FROM old_j->k THEN
                before_j := before_j || jsonb_build_object(k, old_j->k);
                after_j := after_j || jsonb_build_object(k, new_j->k);
            END IF;
        END LOOP;
        IF after_j = '{}'::jsonb THEN RETURN NULL; END IF;
        act := CASE
            WHEN after_j ? 'deleted_at' AND after_j->>'deleted_at' IS NOT NULL THEN 'deleted'
            WHEN after_j ? 'deleted_at' THEN 'restored'
            ELSE 'updated' END;
        before_j := audit_mask(before_j);
        after_j := audit_mask(after_j);
    ELSIF TG_OP = 'INSERT' THEN
        act := 'created'; before_j := NULL; after_j := audit_mask(new_j - skip);
    ELSE
        act := 'deleted'; after_j := NULL; before_j := audit_mask(old_j - skip);
    END IF;

    sid := CASE
        WHEN row_j->>'id' ~ '^[0-9]+$' THEN (row_j->>'id')::bigint
        WHEN row_j->>'model_id' ~ '^[0-9]+$' THEN (row_j->>'model_id')::bigint
        WHEN row_j->>'role_id' ~ '^[0-9]+$' THEN (row_j->>'role_id')::bigint
        WHEN row_j->>'user_id' ~ '^[0-9]+$' THEN (row_j->>'user_id')::bigint
        END;
    uid := nullif(current_setting('audit.user_id', true), '')::bigint;
    -- Someone deleting their own account: the entry cannot point at a person who no longer exists.
    IF TG_TABLE_NAME = 'users' AND TG_OP = 'DELETE' AND uid = sid THEN uid := NULL; END IF;
    op := CASE
        WHEN TG_TABLE_NAME = 'operators' AND TG_OP <> 'DELETE' THEN sid
        WHEN row_j->>'operator_id' ~ '^[0-9]+$' THEN (row_j->>'operator_id')::bigint
        ELSE nullif(current_setting('audit.operator_id', true), '')::bigint END;

    BEGIN
        INSERT INTO audit_logs (user_id, operator_id, action, subject_type, subject_id, before, after, ip, user_agent, request_id, created_at)
        VALUES (uid, op, act, TG_TABLE_NAME, sid, before_j, after_j,
                nullif(current_setting('audit.ip', true), ''), nullif(current_setting('audit.user_agent', true), ''),
                nullif(current_setting('audit.request_id', true), ''), now());
    EXCEPTION WHEN others THEN
        -- The trail must never stop the real change.
        RAISE WARNING 'audit_row_change on %: %', TG_TABLE_NAME, SQLERRM;
    END;
    RETURN NULL;
END
$f$;
SQL);

        foreach (self::TABLES as $table => $ignore) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            DB::unprepared("DROP TRIGGER IF EXISTS audit_row_change ON \"$table\"");
            DB::unprepared("CREATE TRIGGER audit_row_change AFTER INSERT OR UPDATE OR DELETE ON \"$table\" FOR EACH ROW EXECUTE FUNCTION audit_row_change('".implode(',', $ignore)."')");
        }

        // Permissions for the new pages; Super Admin holds every permission.
        if (Schema::hasTable('permissions')) {
            foreach (['Manage security settings', 'Reset two-factor'] as $name) {
                DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'title' => 'System', 'created_at' => now(), 'updated_at' => now()]);
            }
            $role = DB::table('roles')->where('name', 'Super Admin')->where('guard_name', 'web')->value('id');
            if ($role) {
                foreach (DB::table('permissions')->whereIn('name', ['Manage security settings', 'Reset two-factor', 'View audit log'])->pluck('id') as $pid) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $pid, 'role_id' => $role]);
                }
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            if (Schema::hasTable($table)) {
                DB::unprepared("DROP TRIGGER IF EXISTS audit_row_change ON \"$table\"");
            }
        }
        DB::unprepared('DROP FUNCTION IF EXISTS audit_row_change()');
        DB::unprepared('DROP FUNCTION IF EXISTS audit_mask(jsonb)');

        Schema::table('audit_logs', function (Blueprint $t) {
            $t->dropIndex(['user_id', 'created_at']);
            $t->dropIndex(['created_at']);
            $t->dropIndex(['request_id']);
            $t->dropForeign(['user_id']);
            $t->dropForeign(['operator_id']);
            $t->foreign('user_id')->references('id')->on('users');
            $t->foreign('operator_id')->references('id')->on('operators');
        });
        Schema::table('activity_logs', fn (Blueprint $t) => $t->dropColumn('request_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'two_factor_last_step']));
    }
};
