<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateLog;
use App\Models\SchoolInformation;
use Illuminate\Http\Request;

/**
 * Public, no-login certificate verification reached by scanning the QR code.
 * Shows whether the certificate is genuine, and its key details.
 */
class PublicCertificateController extends Controller
{
    public function verify(Request $request, string $token)
    {
        $cert = Certificate::with('template', 'student')->where('verify_token', $token)->first();

        $school = null;
        try { $school = SchoolInformation::getActiveSchool(); } catch (\Throwable $e) {}

        // A certificate is only "valid" once it has actually been issued.
        $state = 'invalid';
        if ($cert) {
            if ($cert->status === 'revoked') $state = 'revoked';
            elseif ($cert->status === 'issued') $state = 'valid';
            else $state = 'pending'; // draft/approved but never generated
        }

        if ($cert) {
            CertificateLog::record('verified', [
                'certificate_id' => $cert->id, 'student_id' => $cert->student_id,
                'serial' => $cert->serial, 'note' => 'QR scan / verify page',
            ]);
        }

        return response()
            ->view('certificates.verify', compact('cert', 'state', 'school'))
            ->header('X-Robots-Tag', 'noindex');
    }
}
