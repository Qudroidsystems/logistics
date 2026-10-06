<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds a polished, professional A4-landscape college certificate as a default
 * template. Idempotent: created only if a template with this name is absent, so
 * admin edits are never clobbered on re-seed.
 */
class CertificateDefaultTemplateSeeder extends Seeder
{
    private const NAVY = '#1e3a5f';
    private const GOLD = '#c8a24a';
    private const INK  = '#374151';
    private const MUTE = '#6b7280';

    public function run(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('certificate_templates')) return;

        CertificateTemplate::firstOrCreate(
            ['name' => 'College Certificate (Default)'],
            [
                'description'       => 'A professional A4 landscape certificate — double border, seal, dual signatures and a verification QR.',
                'orientation'       => 'landscape',
                'width'             => 1123,
                'height'            => 794,
                'serial_prefix'     => 'CERT',
                'requires_approval' => true,
                'generation_limit'  => null,
                'is_active'         => true,
                'design'            => json_encode($this->design()),
            ]
        );
    }

    private function design(): array
    {
        $navy = self::NAVY; $gold = self::GOLD; $ink = self::INK; $mute = self::MUTE;

        $tb = function (string $text, float $left, float $top, float $width, float $size, array $o = []) {
            return array_merge([
                'type' => 'textbox', 'version' => '5.3.0', 'text' => $text,
                'left' => $left, 'top' => $top, 'width' => $width, 'fontSize' => $size,
                'fontFamily' => 'Helvetica', 'fill' => '#111111', 'textAlign' => 'center',
                'fontWeight' => 'normal', 'fontStyle' => 'normal', 'lineHeight' => 1.3,
                'scaleX' => 1, 'scaleY' => 1, 'angle' => 0,
            ], $o);
        };
        $rect = function (float $left, float $top, float $w, float $h, array $o = []) {
            return array_merge([
                'type' => 'rect', 'version' => '5.3.0', 'left' => $left, 'top' => $top,
                'width' => $w, 'height' => $h, 'fill' => 'transparent', 'stroke' => null,
                'strokeWidth' => 0, 'rx' => 0, 'ry' => 0, 'scaleX' => 1, 'scaleY' => 1, 'angle' => 0,
            ], $o);
        };
        $circle = function (float $left, float $top, float $r, array $o = []) {
            return array_merge([
                'type' => 'circle', 'version' => '5.3.0', 'left' => $left, 'top' => $top,
                'radius' => $r, 'fill' => 'transparent', 'stroke' => null, 'strokeWidth' => 0,
                'scaleX' => 1, 'scaleY' => 1, 'angle' => 0,
            ], $o);
        };

        $objects = [
            // panel + double border
            $rect(44, 44, 1035, 706, ['fill' => '#fdfbf6']),
            $rect(28, 28, 1067, 738, ['fill' => 'transparent', 'stroke' => $navy, 'strokeWidth' => 6, 'rx' => 6, 'ry' => 6]),
            $rect(46, 46, 1031, 702, ['fill' => 'transparent', 'stroke' => $gold, 'strokeWidth' => 2]),
            // corner accents
            $rect(40, 40, 16, 16, ['fill' => $gold, 'angle' => 45]),
            $rect(1083, 40, 16, 16, ['fill' => $gold, 'angle' => 45]),
            $rect(40, 754, 16, 16, ['fill' => $gold, 'angle' => 45]),
            $rect(1083, 754, 16, 16, ['fill' => $gold, 'angle' => 45]),

            // serial (top-left) + date (top-right)
            $tb('No: CERT/2026/00001', 70, 66, 300, 12, ['textAlign' => 'left', 'fill' => $mute, 'fieldKey' => 'cert.serial', 'fieldLabel' => 'Serial / certificate no.']),
            $tb('Date: 01 Jan 2026', 753, 66, 300, 12, ['textAlign' => 'right', 'fill' => $mute, 'fieldKey' => 'cert.date', 'fieldLabel' => 'Date issued']),

            // logo (centered) — image placeholder
            $rect(516, 70, 91, 91, ['fill' => 'rgba(30,58,95,0.04)', 'stroke' => $gold, 'strokeWidth' => 1, 'strokeDashArray' => [5, 4], 'fieldKey' => 'school.logo', 'fieldLabel' => 'School logo']),

            // school name + address
            $tb('SCHOOL NAME', 161, 172, 800, 32, ['fontFamily' => 'Georgia', 'fontWeight' => 'bold', 'fill' => $navy, 'charSpacing' => 60, 'fieldKey' => 'school.name', 'fieldLabel' => 'School name']),
            $tb('School address line', 261, 214, 600, 13, ['fill' => $mute, 'fieldKey' => 'school.address', 'fieldLabel' => 'Address']),

            // gold divider
            $rect(511, 246, 100, 3, ['fill' => $gold]),

            // title
            $tb('CERTIFICATE', 111, 266, 900, 54, ['fontFamily' => 'Georgia', 'fontWeight' => 'bold', 'fill' => $navy, 'charSpacing' => 260]),
            $tb('of Achievement', 261, 336, 600, 22, ['fontFamily' => 'Georgia', 'fontStyle' => 'italic', 'fill' => $gold, 'fieldKey' => 'cert.title', 'fieldLabel' => 'Certificate title']),

            // recipient
            $tb('This certificate is proudly presented to', 261, 386, 600, 15, ['fill' => $mute]),
            $tb('Student Name', 111, 410, 900, 46, ['fontFamily' => 'Georgia', 'fontWeight' => 'bold', 'fill' => $navy, 'fieldKey' => 'student.name', 'fieldLabel' => 'Full name']),
            $rect(361, 478, 400, 2, ['fill' => $gold]),

            // citation
            $tb('in recognition of the successful completion of the prescribed course of study, and for exemplary conduct, diligence and academic excellence.', 211, 498, 700, 15, ['fill' => $ink, 'lineHeight' => 1.45]),

            // seal (centre, above signatures)
            $circle(509, 560, 52, ['fill' => 'rgba(200,162,74,0.08)', 'stroke' => $gold, 'strokeWidth' => 3]),
            $circle(521, 572, 40, ['fill' => 'transparent', 'stroke' => $navy, 'strokeWidth' => 1]),
            $tb('★', 543, 588, 36, 30, ['fill' => $gold]),
            $tb('OFFICIAL SEAL', 511, 628, 100, 9, ['fill' => $navy, 'charSpacing' => 120]),

            // signatures
            $rect(150, 672, 230, 2, ['fill' => $navy]),
            $tb('Principal', 150, 680, 230, 13, ['fill' => $ink]),
            $rect(743, 672, 230, 2, ['fill' => $navy]),
            $tb('Registrar', 743, 680, 230, 13, ['fill' => $ink]),

            // verification QR (bottom-right) — image placeholder
            $rect(957, 600, 96, 96, ['fill' => 'rgba(30,58,95,0.03)', 'stroke' => $gold, 'strokeWidth' => 1, 'strokeDashArray' => [5, 4], 'fieldKey' => 'cert.qr', 'fieldLabel' => 'Verification QR']),
            $tb('Scan to verify', 942, 700, 126, 10, ['fill' => $mute]),
        ];

        return [
            'version'    => '5.3.0',
            'objects'    => $objects,
            'background' => '#ffffff',
        ];
    }
}
