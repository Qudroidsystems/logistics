<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds a professional A4 portrait leaving-testimonial (letter style) with an
 * inline-token body, school header, signature line and verification QR.
 * Idempotent (firstOrCreate by name).
 */
class CertificateTestimonialTemplateSeeder extends Seeder
{
    private const NAVY = '#1e3a5f';
    private const GOLD = '#c8a24a';
    private const INK  = '#1f2937';
    private const MUTE = '#6b7280';

    public function run(): void
    {
        if (!Schema::hasTable('certificate_templates')) return;

        CertificateTemplate::firstOrCreate(
            ['name' => 'Leaving Testimonial (Default)'],
            [
                'kind'              => 'testimonial',
                'description'       => 'A formal A4 portrait leaving/character testimonial letter with QR verification.',
                'orientation'       => 'portrait',
                'width'             => 794,
                'height'            => 1123,
                'serial_prefix'     => 'TST',
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
                'fontFamily' => 'Georgia', 'fill' => '#111111', 'textAlign' => 'center',
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

        $body =
            "This is to certify that {{student.name}} (Admission No: {{student.admission_no}}) was a bona fide student of "
            . "{{school.name}} from {{testimonial.admission_date}} to {{testimonial.leaving_date}}, having been admitted into "
            . "{{testimonial.entry_class}} and leaving from {{testimonial.leaving_class}}.\n\n"
            . "Throughout the period of study, the student's general conduct was {{testimonial.conduct}} and academic ability "
            . "{{testimonial.academic}}. During this time the student held the position(s) of {{testimonial.positions}} and took "
            . "active part in {{testimonial.clubs}}. Notable achievements include {{testimonial.awards}}.\n\n"
            . "{{student.name}} is {{testimonial.character}}, and is leaving on account of {{testimonial.reason}}.\n\n"
            . "{{testimonial.remark}}\n\n"
            . "We hereby recommend the bearer and wish him/her every success in future endeavours.";

        $objects = [
            // frame
            $rect(40, 40, 714, 1043, ['fill' => 'transparent', 'stroke' => $navy, 'strokeWidth' => 2]),
            $rect(48, 48, 698, 1027, ['fill' => 'transparent', 'stroke' => $gold, 'strokeWidth' => 1]),

            // header
            $rect(352, 66, 90, 90, ['fill' => 'rgba(30,58,95,0.04)', 'stroke' => $gold, 'strokeWidth' => 1, 'strokeDashArray' => [5, 4], 'fieldKey' => 'school.logo', 'fieldLabel' => 'School logo']),
            $tb('SCHOOL NAME', 97, 164, 600, 26, ['fontWeight' => 'bold', 'fill' => $navy, 'charSpacing' => 40, 'fieldKey' => 'school.name', 'fieldLabel' => 'School name']),
            $tb('School address', 147, 200, 500, 12, ['fontFamily' => 'Helvetica', 'fill' => $mute, 'fieldKey' => 'school.address', 'fieldLabel' => 'Address']),
            $tb('Phone / Email', 147, 218, 500, 12, ['fontFamily' => 'Helvetica', 'fill' => $mute, 'fieldKey' => 'school.phone', 'fieldLabel' => 'Phone']),
            $rect(70, 246, 654, 2, ['fill' => $navy]),

            // title
            $tb('TESTIMONIAL', 97, 266, 600, 30, ['fontWeight' => 'bold', 'fill' => $navy, 'charSpacing' => 220]),

            // ref + date
            $tb('Ref: {{cert.serial}}', 70, 320, 320, 12, ['fontFamily' => 'Helvetica', 'textAlign' => 'left', 'fill' => $mute]),
            $tb('Date: {{cert.date}}', 404, 320, 320, 12, ['fontFamily' => 'Helvetica', 'textAlign' => 'right', 'fill' => $mute]),

            // body (inline tokens)
            $tb($body, 87, 356, 620, 15, ['textAlign' => 'left', 'fill' => $ink, 'lineHeight' => 1.7]),

            // signature (left) + QR (right)
            $rect(90, 980, 260, 2, ['fill' => $navy]),
            $tb('Principal / Head of School', 90, 988, 260, 12, ['fontFamily' => 'Helvetica', 'textAlign' => 'left', 'fill' => $ink]),
            $rect(624, 950, 90, 90, ['fill' => 'rgba(30,58,95,0.03)', 'stroke' => $gold, 'strokeWidth' => 1, 'strokeDashArray' => [5, 4], 'fieldKey' => 'cert.qr', 'fieldLabel' => 'Verification QR']),
            $tb('Scan to verify', 609, 1044, 120, 9, ['fontFamily' => 'Helvetica', 'fill' => $mute]),
        ];

        return ['version' => '5.3.0', 'objects' => $objects, 'background' => '#ffffff'];
    }
}
