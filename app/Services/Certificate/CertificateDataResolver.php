<?php

namespace App\Services\Certificate;

use App\Models\SchoolInformation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the {{placeholders}} an admin drops on a certificate into real
 * values for a given student, plus the catalogue of available fields shown in
 * the designer. Everything is returned as a flat key => string map; image
 * fields (photo, logo, QR) return URLs the client loads into the canvas.
 */
class CertificateDataResolver
{
    /** Field groups shown in the designer's "insert field" menu. */
    public function catalog(): array
    {
        return [
            'Student' => [
                ['key' => 'student.name', 'label' => 'Full name'],
                ['key' => 'student.firstname', 'label' => 'First name'],
                ['key' => 'student.lastname', 'label' => 'Last name'],
                ['key' => 'student.othername', 'label' => 'Other name'],
                ['key' => 'student.admission_no', 'label' => 'Admission number'],
                ['key' => 'student.class', 'label' => 'Class'],
                ['key' => 'student.gender', 'label' => 'Gender'],
                ['key' => 'student.dob', 'label' => 'Date of birth'],
                ['key' => 'student.photo', 'label' => 'Student photo (image)', 'image' => true],
            ],
            'School' => [
                ['key' => 'school.name', 'label' => 'School name'],
                ['key' => 'school.address', 'label' => 'Address'],
                ['key' => 'school.phone', 'label' => 'Phone'],
                ['key' => 'school.email', 'label' => 'Email'],
                ['key' => 'school.motto', 'label' => 'Motto'],
                ['key' => 'school.logo', 'label' => 'School logo (image)', 'image' => true],
            ],
            'Certificate' => [
                ['key' => 'cert.title', 'label' => 'Certificate title'],
                ['key' => 'cert.serial', 'label' => 'Serial / certificate no.'],
                ['key' => 'cert.date', 'label' => 'Date issued'],
                ['key' => 'cert.session', 'label' => 'Session'],
                ['key' => 'cert.term', 'label' => 'Term'],
                ['key' => 'cert.qr', 'label' => 'Verification QR code (image)', 'image' => true],
            ],
            'Testimonial' => [
                ['key' => 'testimonial.admission_date', 'label' => 'Date of admission'],
                ['key' => 'testimonial.leaving_date', 'label' => 'Date of leaving'],
                ['key' => 'testimonial.entry_class', 'label' => 'Class admitted into'],
                ['key' => 'testimonial.leaving_class', 'label' => 'Class on leaving'],
                ['key' => 'testimonial.duration', 'label' => 'Duration of stay'],
                ['key' => 'testimonial.conduct', 'label' => 'Conduct'],
                ['key' => 'testimonial.character', 'label' => 'Character remark'],
                ['key' => 'testimonial.positions', 'label' => 'Positions held'],
                ['key' => 'testimonial.clubs', 'label' => 'Clubs / societies'],
                ['key' => 'testimonial.awards', 'label' => 'Awards'],
                ['key' => 'testimonial.academic', 'label' => 'Academic ability'],
                ['key' => 'testimonial.reason', 'label' => 'Reason for leaving'],
                ['key' => 'testimonial.remark', 'label' => "Principal's remark"],
            ],
        ];
    }

    /**
     * @param array $ctx serial, title, session_name, term_name, date, verify_url, qr_url, custom[]
     * @return array{fields: array<string,string>, images: array<string,?string>}
     */
    public function resolve(int $studentId, array $ctx = []): array
    {
        $s = DB::table('studentRegistration as s')
            ->leftJoin('studentclass as sc', function ($j) use ($ctx) {
                $j->on('sc.studentId', '=', 's.id');
                if (!empty($ctx['session_id'])) $j->where('sc.sessionid', $ctx['session_id']);
            })
            ->leftJoin('schoolclass as c', 'c.id', '=', 'sc.schoolclassid')
            ->leftJoin('schoolarm as a', 'a.id', '=', 'c.arm')
            ->leftJoin('studentpicture as sp', 'sp.studentid', '=', 's.id')
            ->where('s.id', $studentId)
            ->select(
                's.firstname', 's.lastname', 's.othername', 's.admissionNo', 's.gender', 's.dateofbirth', 's.admission_date', 's.admissionYear',
                DB::raw("TRIM(CONCAT(COALESCE(c.schoolclass,''),' ',COALESCE(a.arm,''))) as class_name"),
                'sp.picture'
            )->first();

        $school = null;
        try { $school = SchoolInformation::getActiveSchool(); } catch (\Throwable $e) {}

        $name = $s ? trim(($s->firstname ?? '') . ' ' . ($s->lastname ?? '')) : '';
        $dob = $s->dateofbirth ?? '';
        if ($dob && strtoupper($dob) !== 'N/A') {
            try { $dob = Carbon::parse($dob)->format('d M Y'); } catch (\Throwable $e) {}
        }

        $fields = [
            'student.name'         => $name,
            'student.firstname'    => $s->firstname ?? '',
            'student.lastname'     => $s->lastname ?? '',
            'student.othername'    => $s->othername ?? '',
            'student.admission_no' => $s->admissionNo ?? '',
            'student.class'        => $s->class_name ?? '',
            'student.gender'       => $s->gender ?? '',
            'student.dob'          => $dob ?: '',
            'school.name'          => $school->school_name ?? config('app.name', ''),
            'school.address'       => $school->school_address ?? '',
            'school.phone'         => $this->phone($school),
            'school.email'         => $school->school_email ?? '',
            'school.motto'         => $school->motto ?? ($school->school_motto ?? ''),
            'cert.title'           => $ctx['title'] ?? '',
            'cert.serial'          => $ctx['serial'] ?? '',
            'cert.date'            => $ctx['date'] ?? now()->format('d M Y'),
            'cert.session'         => $ctx['session_name'] ?? '',
            'cert.term'            => $ctx['term_name'] ?? '',
            'cert.verify_url'      => $ctx['verify_url'] ?? '',
        ];

        foreach (($ctx['custom'] ?? []) as $k => $v) {
            $fields['custom.' . $k] = (string) $v;
        }

        // Testimonial fields: admin-entered values (ctx['testimonial']) override sensible auto-fills.
        $tin = $ctx['testimonial'] ?? [];
        $admission = $s->admission_date ?? null;
        if ($admission && strtoupper((string) $admission) !== 'N/A') {
            try { $admission = Carbon::parse($admission)->format('d M Y'); } catch (\Throwable $e) {}
        }
        $tAuto = [
            'admission_date' => $admission ?: ($s->admissionYear ?? ''),
            'leaving_date'   => $ctx['date'] ?? now()->format('d M Y'),
            'entry_class'    => '',
            'leaving_class'  => $s->class_name ?? '',
            'duration'       => '',
            'conduct'        => '',
            'character'      => '',
            'positions'      => '',
            'clubs'          => '',
            'awards'         => '',
            'academic'       => '',
            'reason'         => '',
            'remark'         => '',
        ];
        foreach ($tAuto as $k => $auto) {
            $val = $tin[$k] ?? null;
            $fields['testimonial.' . $k] = (string) (($val !== null && $val !== '') ? $val : $auto);
        }

        $images = [
            'student.photo' => $this->photoUrl($s->picture ?? null),
            'school.logo'   => $this->logoUrl($school),
            'cert.qr'       => $ctx['qr_url'] ?? null,
        ];

        return ['fields' => $fields, 'images' => $images];
    }

    protected function phone($school): string
    {
        if (!$school) return '';
        $p = $school->school_phones ?? null;
        if (is_array($p)) return implode(', ', $p);
        return (string) ($p ?? '');
    }

    protected function logoUrl($school): ?string
    {
        if (!$school) return null;
        try { return $school->logo_url ?: null; } catch (\Throwable $e) { return null; }
    }

    protected function photoUrl(?string $picture): ?string
    {
        if (!$picture) return null;
        if (filter_var($picture, FILTER_VALIDATE_URL)) return $picture;
        // Common storage locations for student pictures.
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($picture)) {
                return asset('storage/' . ltrim($picture, '/'));
            }
        } catch (\Throwable $e) {}
        if (str_starts_with($picture, 'storage/') || str_starts_with($picture, 'http')) return asset($picture);
        return asset('storage/' . ltrim($picture, '/'));
    }
}
