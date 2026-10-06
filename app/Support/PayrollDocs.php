<?php

namespace App\Support;

use App\Models\MessagingSetting;
use App\Models\SchoolInformation;
use Illuminate\Support\Facades\DB;

/**
 * Shared bits for payslips, tax certificates and statements.
 */
class PayrollDocs
{
    public const PUBLISHED = ['approved', 'paid', 'locked'];

    public static function school(): ?SchoolInformation
    {
        return SchoolInformation::getActiveSchool() ?? SchoolInformation::first();
    }

    /** Employer details for statutory documents (TIN, pension code, signatory). */
    public static function employer(): array
    {
        $s = MessagingSetting::firstOrCreate(['channel' => 'payroll_employer'], ['driver' => 'none', 'is_active' => true, 'config' => []]);
        return array_merge([
            'employer_name' => self::school()->school_name ?? config('app.name'), 'tin' => null, 'tax_office' => null,
            'pension_employer_code' => null, 'nhf_employer_code' => null, 'signatory_name' => null, 'signatory_title' => 'Bursar',
            'payslip_password' => 'phone4', // phone4 | staffid | none
            'due_paye_day' => 10, 'due_pension_working_days' => 7, 'due_nhf_day' => 10, 'due_other_day' => 10,
        ], $s->config ?? []);
    }

    public static function saveEmployer(array $data, int $by): void
    {
        $s = MessagingSetting::firstOrCreate(['channel' => 'payroll_employer'], ['driver' => 'none', 'is_active' => true, 'config' => []]);
        $s->config = array_merge($s->config ?? [], $data);
        $s->updated_by = $by;
        $s->save();
    }

    /** School logo as a data URI for DomPDF (no remote fetch needed). */
    public static function logo(): ?string
    {
        $file = self::school()->school_logo ?? null;
        if (!$file) return null;
        foreach ([public_path('storage/' . ltrim($file, '/')), storage_path('app/public/' . ltrim($file, '/')), public_path(ltrim($file, '/'))] as $p) {
            if (is_file($p)) {
                $mime = function_exists('mime_content_type') ? (mime_content_type($p) ?: 'image/png') : 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
            }
        }
        return null;
    }

    public static function qr(string $data): ?string
    {
        try {
            return 'data:image/png;base64,' . base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(220)->errorCorrection('M')->generate($data));
        } catch (\Throwable $e) {
            try {
                return 'data:image/svg+xml;base64,' . base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(220)->generate($data));
            } catch (\Throwable $e2) { return null; }
        }
    }

    public static function verifyUrl(string $code): string
    {
        return rtrim(config('app.url'), '/') . '/verify/payslip/' . $code;
    }

    /** staffbioinfo row for a user (null if the user isn't staff). */
    public static function staffForUser(?int $userId): ?object
    {
        if (!$userId) return null;
        return DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->where('s.userid', $userId)
            ->first(['s.*', 'u.name', 'u.email', 'u.phone_number']);
    }

    public static function staff(int $staffId): ?object
    {
        return DB::table('staffbioinfo as s')->join('users as u', 'u.id', '=', 's.userid')->where('s.id', $staffId)
            ->first(['s.*', 'u.name', 'u.email', 'u.phone_number']);
    }

    public static function money($v): string
    {
        return '₦' . number_format((float) $v, 2);
    }

    /** Password for emailed PDFs, or null. */
    public static function pdfPassword(object $staff): ?string
    {
        $mode = self::employer()['payslip_password'] ?? 'phone4';
        if ($mode === 'none') return null;
        if ($mode === 'staffid' && !empty($staff->employmentid)) return (string) $staff->employmentid;
        $digits = preg_replace('/\D/', '', (string) ($staff->phonenumber ?? $staff->phone_number ?? ''));
        return strlen($digits) >= 4 ? substr($digits, -4) : null;
    }

    /** Render a view to PDF bytes, optionally password-protected. */
    public static function pdf(string $view, array $data, ?string $password = null, string $paper = 'a4'): string
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data)->setPaper($paper);
        if ($password) {
            try {
                $pdf->render();
                $pdf->getDomPDF()->getCanvas()->get_cpdf()->setEncryption($password, bin2hex(random_bytes(8)), ['print']);
                return $pdf->getDomPDF()->output();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('PDF encryption failed', ['error' => $e->getMessage()]);
            }
        }
        return $pdf->output();
    }
}
