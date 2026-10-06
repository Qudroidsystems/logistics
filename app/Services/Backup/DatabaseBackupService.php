<?php

namespace App\Services\Backup;

use App\Models\BackupSetting;
use App\Models\DatabaseBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Creates a gzipped SQL dump of the database. Uses mysqldump when the server
 * allows shell execution (fast, complete); otherwise falls back to a
 * memory-safe PHP/PDO dumper that streams straight into the gzip file.
 * Records each backup, prunes old ones, and can email the file.
 */
class DatabaseBackupService
{
    protected string $disk = 'local';
    protected string $dir  = 'backups';

    /** @return array{ok:bool,backup:?DatabaseBackup,message:string} */
    public function run(string $type = 'manual', ?int $userId = null, bool $email = false): array
    {
        $cfg = config('database.connections.' . config('database.default'));
        if (($cfg['driver'] ?? null) !== 'mysql') {
            return ['ok' => false, 'backup' => null, 'message' => 'Automatic backup currently supports MySQL/MariaDB only.'];
        }

        Storage::disk($this->disk)->makeDirectory($this->dir);
        $db = $cfg['database'] ?? 'database';
        $file = 'db-' . preg_replace('/[^A-Za-z0-9_-]/', '', $db) . '-' . now()->format('Ymd-His') . '.sql.gz';
        $rel  = $this->dir . '/' . $file;
        $abs  = Storage::disk($this->disk)->path($rel);

        $method = 'php';
        try {
            if ($this->canExec() && $this->mysqldumpAvailable()) {
                $ok = $this->dumpWithMysqldump($cfg, $abs);
                if ($ok) { $method = 'mysqldump'; }
                else { $this->dumpWithPhp($cfg, $abs); $method = 'php'; }
            } else {
                $this->dumpWithPhp($cfg, $abs);
            }
        } catch (\Throwable $e) {
            Log::error('DB backup failed: ' . $e->getMessage());
            @unlink($abs);
            DatabaseBackup::create([
                'filename' => $file, 'path' => $rel, 'disk' => $this->disk, 'size' => 0,
                'type' => $type, 'status' => 'failed', 'method' => $method,
                'note' => mb_substr($e->getMessage(), 0, 500), 'created_by' => $userId, 'created_at' => now(),
            ]);
            return ['ok' => false, 'backup' => null, 'message' => 'Backup failed: ' . $e->getMessage()];
        }

        $size = is_file($abs) ? (int) filesize($abs) : 0;
        if ($size <= 0) {
            @unlink($abs);
            return ['ok' => false, 'backup' => null, 'message' => 'Backup produced an empty file.'];
        }

        $backup = DatabaseBackup::create([
            'filename' => $file, 'path' => $rel, 'disk' => $this->disk, 'size' => $size,
            'type' => $type, 'status' => 'success', 'method' => $method, 'created_by' => $userId, 'created_at' => now(),
        ]);

        $settings = BackupSetting::current();
        $this->prune((int) $settings->keep_last);

        if ($email && $settings->email) {
            $sent = $this->email($backup, $settings->email, (bool) $settings->email_attach);
            if ($sent) { $backup->emailed_to = $settings->email; $backup->save(); }
        }

        return ['ok' => true, 'backup' => $backup, 'message' => 'Backup created (' . $backup->humanSize() . ', ' . $method . ').'];
    }

    protected function canExec(): bool
    {
        if (!function_exists('exec')) return false;
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return !in_array('exec', $disabled, true);
    }

    protected function mysqldumpAvailable(): bool
    {
        try { @exec('mysqldump --version 2>/dev/null', $out, $code); return $code === 0; }
        catch (\Throwable $e) { return false; }
    }

    protected function dumpWithMysqldump(array $cfg, string $abs): bool
    {
        $host = $cfg['host'] ?? '127.0.0.1';
        $port = $cfg['port'] ?? 3306;
        $user = $cfg['username'] ?? 'root';
        $db   = $cfg['database'];

        // Password via env so it never appears in the process list.
        putenv('MYSQL_PWD=' . ($cfg['password'] ?? ''));
        $cmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --single-transaction --skip-lock-tables --no-tablespaces --routines --default-character-set=utf8mb4 %s 2>/dev/null | gzip -9 > %s',
            escapeshellarg($host), escapeshellarg((string) $port), escapeshellarg($user),
            escapeshellarg($db), escapeshellarg($abs)
        );
        @exec($cmd, $out, $code);
        putenv('MYSQL_PWD');
        return $code === 0 && is_file($abs) && filesize($abs) > 0;
    }

    protected function dumpWithPhp(array $cfg, string $abs): void
    {
        $pdo = new \PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['host'] ?? '127.0.0.1', $cfg['port'] ?? 3306, $cfg['database']),
            $cfg['username'] ?? 'root', $cfg['password'] ?? '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]
        );

        $gz = gzopen($abs, 'wb9');
        if (!$gz) throw new \RuntimeException('Cannot open backup file for writing.');

        $w = fn ($s) => gzwrite($gz, $s);
        $w("-- CSS Kabba database backup\n-- Generated: " . now()->toDateTimeString() . "\n");
        $w("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $tables = [];
        foreach ($pdo->query('SHOW FULL TABLES', \PDO::FETCH_NUM) as $row) {
            // skip views for the data dump; still recreate their structure below
            $tables[] = ['name' => $row[0], 'type' => $row[1] ?? 'BASE TABLE'];
        }

        foreach ($tables as $t) {
            $name = $t['name'];
            $q = $pdo->quote($name);
            $w("\n-- ----------------------------\n-- " . $name . "\n-- ----------------------------\n");

            if ($t['type'] === 'VIEW') {
                $w("DROP VIEW IF EXISTS `$name`;\n");
                $create = $pdo->query('SHOW CREATE VIEW `' . str_replace('`', '', $name) . '`')->fetch(\PDO::FETCH_NUM);
                if (isset($create[1])) $w($create[1] . ";\n");
                continue;
            }

            $w("DROP TABLE IF EXISTS `$name`;\n");
            $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '', $name) . '`')->fetch(\PDO::FETCH_NUM);
            if (isset($create[1])) $w($create[1] . ";\n\n");

            // stream rows (unbuffered)
            $stmt = $pdo->query('SELECT * FROM `' . str_replace('`', '', $name) . '`');
            $count = 0; $buffer = '';
            while ($rowData = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $vals = array_map(function ($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    if (is_int($v) || is_float($v)) return (string) $v;
                    return $pdo->quote((string) $v);
                }, array_values($rowData));
                $buffer .= '(' . implode(',', $vals) . "),\n";
                $count++;
                if ($count % 500 === 0) {
                    $w("INSERT INTO `$name` VALUES\n" . rtrim($buffer, ",\n") . ";\n");
                    $buffer = '';
                }
            }
            if ($buffer !== '') {
                $w("INSERT INTO `$name` VALUES\n" . rtrim($buffer, ",\n") . ";\n");
            }
            $stmt->closeCursor();
        }

        $w("\nSET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);
    }

    public function prune(int $keepLast): int
    {
        if ($keepLast <= 0) return 0;
        $old = DatabaseBackup::where('status', 'success')->orderByDesc('created_at')->skip($keepLast)->take(1000)->get();
        $removed = 0;
        foreach ($old as $b) {
            try { Storage::disk($b->disk)->delete($b->path); } catch (\Throwable $e) {}
            $b->delete(); $removed++;
        }
        return $removed;
    }

    public function email(DatabaseBackup $backup, string $to, bool $attach): bool
    {
        try {
            $abs = Storage::disk($backup->disk)->path($backup->path);
            $sizeMb = $backup->size / 1048576;
            $canAttach = $attach && $sizeMb <= 15 && is_file($abs);
            $body = "A database backup was created on " . now()->toDayDateTimeString() . ".\n\n"
                . "File: {$backup->filename}\nSize: {$backup->humanSize()}\n"
                . ($canAttach ? "The backup is attached to this email." : "The file is too large to attach; download it from the portal (Admin → Database Backups).");
            Mail::raw($body, function ($m) use ($to, $backup, $canAttach, $abs) {
                $m->to($to)->subject('Database backup — ' . $backup->filename);
                if ($canAttach) $m->attach($abs, ['as' => $backup->filename, 'mime' => 'application/gzip']);
            });
            return true;
        } catch (\Throwable $e) {
            Log::warning('Backup email failed: ' . $e->getMessage());
            return false;
        }
    }
}
