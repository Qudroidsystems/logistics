<?php

namespace App\Support;

/**
 * Tamper-proof verification codes for printed report cards.
 * Code = student-class-session-term-signature (HMAC with the app key), so a
 * code can't be guessed or edited to point at another student's result.
 */
class ResultVerification
{
    public static function code(int $studentId, int $classId, int $sessionId, int $termId): string
    {
        return "{$studentId}-{$classId}-{$sessionId}-{$termId}-" . self::sign($studentId, $classId, $sessionId, $termId);
    }

    public static function url(int $studentId, int $classId, int $sessionId, int $termId): string
    {
        return route('results.verify', self::code($studentId, $classId, $sessionId, $termId));
    }

    /** @return array{0:int,1:int,2:int,3:int}|null  [student, class, session, term] */
    public static function parse(string $code): ?array
    {
        if (!preg_match('/^(\d+)-(\d+)-(\d+)-(\d+)-([a-f0-9]{12})$/', $code, $m)) {
            return null;
        }
        [$s, $c, $se, $t] = [(int) $m[1], (int) $m[2], (int) $m[3], (int) $m[4]];
        return hash_equals(self::sign($s, $c, $se, $t), $m[5]) ? [$s, $c, $se, $t] : null;
    }

    protected static function sign(int $s, int $c, int $se, int $t): string
    {
        return substr(hash_hmac('sha256', "result|{$s}|{$c}|{$se}|{$t}", (string) config('app.key')), 0, 12);
    }
}
