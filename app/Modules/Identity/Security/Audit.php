<?php

namespace App\Modules\Identity\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Data-change audit trail (the audit_logs table).
 *
 * Row changes on the audited tables are written by a database trigger (see the add_two_factor_and_data_audit
 * migration), so every write is caught whether it comes from Eloquent, the query builder, a command or a job. The
 * trigger reads who made the change from connection settings that this class sets at the start of each request or
 * job: audit.user_id, audit.operator_id, audit.ip, audit.user_agent and audit.request_id.
 *
 * record() adds an entry for things that are not a plain row change, such as turning two-step sign-in on.
 */
class Audit
{
    private static array $context = ['user_id' => null, 'operator_id' => null, 'ip' => null, 'user_agent' => null, 'request_id' => null];

    private static ?string $requestId = null;

    /** One id per request, also stored on the activity log entry so the two can be matched. */
    public static function requestId(): string
    {
        return self::$requestId ??= (string) Str::ulid();
    }

    public static function forRequest(Request $request, ?int $userId, ?int $operatorId): void
    {
        self::$requestId = null;
        self::set([
            'user_id' => $userId, 'operator_id' => $operatorId, 'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''), 'request_id' => self::requestId(),
        ]);
    }

    /** Jobs and commands: no person, no request. */
    public static function forSystem(): void
    {
        self::$requestId = null;
        self::set(['user_id' => null, 'operator_id' => null, 'ip' => null, 'user_agent' => null, 'request_id' => self::requestId()]);
    }

    /** Someone signed in partway through the request. */
    public static function actingAs(?int $userId, ?int $operatorId = null): void
    {
        self::set(['user_id' => $userId, 'operator_id' => $operatorId] + self::$context);
    }

    public static function context(): array
    {
        return self::$context;
    }

    /** Push the context to the database connection (also after a reconnect). */
    public static function apply(): void
    {
        try {
            $c = self::$context;
            DB::selectOne('select set_config(\'audit.user_id\', ?, false), set_config(\'audit.operator_id\', ?, false), set_config(\'audit.ip\', ?, false), set_config(\'audit.user_agent\', ?, false), set_config(\'audit.request_id\', ?, false)', [
                (string) ($c['user_id'] ?? ''), (string) ($c['operator_id'] ?? ''), (string) ($c['ip'] ?? ''), (string) ($c['user_agent'] ?? ''), (string) ($c['request_id'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit context not set', ['error' => $e->getMessage()]);
        }
    }

    public static function record(string $action, ?string $subjectType = null, ?int $subjectId = null, ?array $before = null, ?array $after = null, ?int $operatorId = null): void
    {
        try {
            $c = self::$context;
            DB::table('audit_logs')->insert([
                'user_id' => $c['user_id'], 'operator_id' => $operatorId ?? $c['operator_id'], 'action' => Str::limit($action, 64, ''),
                'subject_type' => $subjectType, 'subject_id' => $subjectId,
                'before' => $before === null ? null : json_encode($before), 'after' => $after === null ? null : json_encode($after),
                'ip' => $c['ip'], 'user_agent' => $c['user_agent'], 'request_id' => $c['request_id'], 'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit record failed', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }

    private static function set(array $context): void
    {
        self::$context = array_intersect_key($context, self::$context) + self::$context;
        // Before the first query the connection does not exist yet; the ConnectionEstablished listener applies it then.
        if (array_key_exists(DB::getDefaultConnection(), DB::getConnections())) {
            self::apply();
        }
    }
}
