<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student ID Cards — {{ $schoolInfo?->school_name }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    background: #ffffff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.page-wrap {
    width: 210mm;
    padding: 8mm 5mm;
    background: #fff;
}

/* Side-by-side pair using table */
.pair-table {
    width: 100%;
    border-collapse: collapse;
    page-break-inside: avoid;
    break-inside: avoid;
    margin-bottom: 0;
}
.pair-label-row td {
    font-size: 6pt;
    font-weight: bold;
    color: #94a3b8;
    letter-spacing: 1pt;
    text-transform: uppercase;
    text-align: center;
    padding-bottom: 1.5mm;
}
.pair-card-row td {
    vertical-align: top;
    padding: 0 2.5mm;
}

/* Cut guide */
.cut-table { width: 100%; border-collapse: collapse; }
.cut-table td { padding: 2mm 0; text-align: center; }
.cut-hr { border: none; border-top: 0.5pt dashed #cbd5e1; margin: 0 8mm; }
.cut-text { font-size: 5.5pt; color: #cbd5e1; letter-spacing: 1pt; margin-top: 1mm; font-family: 'DejaVu Sans', Arial, sans-serif; }

/* ═══════════════════════
   CARD SHELL
   85mm × 135mm portrait
   ═══════════════════════ */
.card {
    width: 85mm;
    height: 135mm;
    border-radius: 2.5mm;
    overflow: hidden;
    position: relative;
    background: #fff;
    border: 0.3mm solid #d1d5db;
    font-family: 'DejaVu Sans', Arial, sans-serif;
}

/* Accent strips */
.accent { height: 2mm; background: #1e3a5f; font-size: 0; line-height: 0; }
.accent-bot { height: 2mm; background: #2169ad; font-size: 0; line-height: 0; position: absolute; bottom: 0; left: 0; right: 0; }

/* Watermark */
.watermark {
    position: absolute;
    top: 28mm; left: 50%;
    width: 45mm; height: 45mm;
    margin-left: -22.5mm;
    opacity: 0.06;
    z-index: 0;
}
.watermark img { width: 45mm; height: 45mm; object-fit: contain; }

/* ── FRONT HEADER ── */
.f-header {
    background: #1e3a5f;
    text-align: center;
    padding: 2.5mm 2mm 11mm;
    position: relative;
    overflow: hidden;
}
.f-header-deco {
    position: absolute; top: -4mm; right: -4mm;
    width: 18mm; height: 18mm; border-radius: 50%;
    background: rgba(255,255,255,0.07);
}
.f-logo {
    width: 15mm; height: 15mm;
    border-radius: 50%;
    border: 0.5mm solid rgba(255,255,255,0.5);
    overflow: hidden;
    margin: 0 auto 1.5mm;
    background: rgba(255,255,255,0.13);
    display: block;
}
.f-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
.f-logo-placeholder {
    width: 15mm; height: 15mm; border-radius: 50%;
    background: rgba(255,255,255,0.15);
    border: 0.5mm solid rgba(255,255,255,0.4);
    margin: 0 auto 1.5mm;
    line-height: 15mm; text-align: center;
    font-size: 12pt; color: #fff;
}
.f-school { color: #fff; font-size: 7pt; font-weight: bold; line-height: 1.3; }
.f-motto  { color: rgba(255,255,255,0.72); font-size: 4.8pt; font-style: italic; margin-top: 0.8mm; }
.f-badge  {
    display: inline-block;
    background: rgba(255,255,255,0.18);
    border: 0.3mm solid rgba(255,255,255,0.38);
    color: #fff; font-size: 4.2pt; font-weight: bold;
    letter-spacing: 1.5pt; padding: 0.8mm 3mm;
    border-radius: 4mm; margin-top: 1.5mm;
}

/* Photo */
.f-photo-wrap {
    text-align: center;
    margin-top: -9.5mm;
    margin-bottom: 1mm;
    position: relative; z-index: 2;
}
.f-photo {
    width: 19mm; height: 19mm; border-radius: 50%;
    border: 0.8mm solid #fff;
    box-shadow: 0 0 0 0.7mm #2169ad;
    overflow: hidden; display: inline-block;
    background: #dbeafe; vertical-align: top;
}
.f-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
.f-initials {
    width: 19mm; height: 19mm; border-radius: 50%;
    background: #dbeafe; border: 0.8mm solid #fff;
    box-shadow: 0 0 0 0.7mm #2169ad;
    display: inline-block; text-align: center;
    vertical-align: top; line-height: 17.4mm;
    font-size: 11pt; font-weight: bold; color: #2169ad;
}

/* Name / Adm */
.f-name {
    text-align: center; font-size: 7.5pt; font-weight: bold;
    color: #1e2937; padding: 0 2mm; line-height: 1.3;
    position: relative; z-index: 2;
}
.f-adm { text-align: center; margin: 1mm 0; position: relative; z-index: 2; }
.f-adm-lbl {
    background: #1e3a5f; color: #fff;
    font-size: 4.3pt; font-weight: bold; padding: 0.6mm 1.8mm;
    border-radius: 0.8mm 0 0 0.8mm;
}
.f-adm-val {
    background: #eff6ff; color: #2169ad;
    font-size: 4.8pt; font-weight: bold; padding: 0.6mm 1.8mm;
    border: 0.3mm solid #bfdbfe; border-left: none;
    border-radius: 0 0.8mm 0.8mm 0;
}

/* ── Info table — proper HTML table ── */
.info-tbl {
    width: calc(100% - 5mm);
    margin: 1mm 2.5mm 0;
    border-collapse: collapse;
    border: 0.3mm solid #e2e8f0;
    border-radius: 1.5mm;
    overflow: hidden;
    position: relative; z-index: 2;
    font-size: 0; /* remove whitespace gaps */
}
.info-tbl tr { border-bottom: 0.3mm solid #e2e8f0; }
.info-tbl tr:last-child { border-bottom: none; }

/* Label cell */
.info-tbl td.lbl {
    background: #eef2ff;
    color: #4338ca;
    font-size: 4pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.2pt;
    padding: 1mm 1.2mm;
    width: 18%;
    vertical-align: middle;
    white-space: nowrap;
    border-right: 0.3mm solid #e2e8f0;
}
/* Value cell */
.info-tbl td.val {
    color: #1e2937;
    font-size: 5pt;
    font-weight: bold;
    padding: 1mm 1.5mm;
    width: 32%;
    vertical-align: middle;
}
/* Divider between left and right pair */
.info-tbl td.div {
    border-left: 0.3mm solid #e2e8f0;
    padding: 0; width: 0;
}

/* QR */
.f-qr { text-align: center; padding: 1.5mm 0 0; position: relative; z-index: 2; }
.f-qr img { width: 13mm; height: 13mm; }
.f-qr-lbl { font-size: 3.8pt; color: #94a3b8; letter-spacing: 0.6pt; margin-top: 0.5mm; }

/* ── BACK CARD ── */
.b-header {
    background: #1e3a5f;
    text-align: center;
    padding: 2.5mm 2mm;
    position: relative; overflow: hidden;
}
.b-header-sub { color: rgba(255,255,255,0.75); font-size: 4.2pt; font-weight: bold; letter-spacing: 2pt; }
.b-header-name { color: #fff; font-size: 6.5pt; font-weight: bold; margin-top: 1mm; line-height: 1.3; }
.b-header-addr { color: rgba(255,255,255,0.6); font-size: 4pt; margin-top: 0.8mm; }

.b-mag { height: 4.5mm; background: #1a1a2e; font-size: 0; }

.b-chip {
    margin: 2mm 2.5mm 0;
    background: #eef2ff;
    border-left: 0.9mm solid #2169ad;
    border-radius: 1.2mm;
    padding: 1.2mm 2mm;
    position: relative; z-index: 2;
}
.b-chip-name { font-size: 6pt; font-weight: bold; color: #1e3a5f; }
.b-chip-sub  { font-size: 4.3pt; font-weight: bold; color: #4338ca; margin-top: 0.5mm; }

.b-terms { padding: 1.5mm 2.5mm 0; position: relative; z-index: 2; }
.b-terms-head { font-size: 5pt; font-weight: bold; color: #1e3a5f; text-transform: uppercase; letter-spacing: 0.4pt; margin-bottom: 0.8mm; }
.b-terms ul { padding-left: 3.5mm; font-size: 4.2pt; color: #4b5563; line-height: 1.7; }

.b-contact {
    margin: 1.5mm 2.5mm 0;
    background: #f0f4ff;
    border-left: 0.9mm solid #2169ad;
    border-radius: 1.2mm;
    padding: 1mm 2mm;
    position: relative; z-index: 2;
}
.b-contact-head { font-size: 3.8pt; font-weight: bold; color: #2169ad; text-transform: uppercase; letter-spacing: 0.6pt; margin-bottom: 0.8mm; }
.b-contact-line { font-size: 4.2pt; color: #374151; margin-bottom: 0.5mm; }

.b-sig-table { width: calc(100% - 5mm); margin: 2mm 2.5mm 0; border-collapse: collapse; position: relative; z-index: 2; }
.b-sig-table td { text-align: center; width: 50%; padding: 0 1.5mm; }
.b-sig-line { height: 5.5mm; border-bottom: 0.5mm solid #1e3a5f; }
.b-sig-lbl  { font-size: 3.8pt; color: #6b7280; margin-top: 0.8mm; }

.b-issued { text-align: center; margin-top: 1.5mm; position: relative; z-index: 2; }
.b-issued-lbl    { font-size: 3.8pt; color: #9ca3af; letter-spacing: 0.5pt; text-transform: uppercase; }
.b-issued-school { font-size: 5.5pt; font-weight: bold; color: #1e3a5f; }
.b-issued-role   { font-size: 3.8pt; color: #6b7280; }

.b-barcode { text-align: center; padding: 1.5mm 0 0; position: relative; z-index: 2; }
.b-barcode img { height: 10mm; max-width: 70mm; }
.b-barcode-adm { font-size: 5pt; font-weight: bold; color: #1e3a5f; letter-spacing: 1.5pt; margin-top: 0.5mm; }
.b-barcode-sub { font-size: 3.8pt; color: #94a3b8; margin-top: 0.3mm; }
.b-barcode-fallback { font-family: monospace; font-size: 12pt; color: #1e3a5f; letter-spacing: -1px; }

</style>
</head>
<body>
@php
    $schoolName = $schoolInfo?->school_name ?? 'School Name';
    $logoUrl    = ($schoolInfo && $schoolInfo->school_logo) ? $schoolInfo->getLogoUrlAttribute() : null;
    $expiry     = now()->addYear()->format('F Y');
    $barcodeGen = class_exists(\Picqer\Barcode\BarcodeGeneratorPNG::class)
                    ? new \Picqer\Barcode\BarcodeGeneratorPNG() : null;
@endphp

<div class="page-wrap">
@foreach($students as $index => $student)
@php
    $firstname  = $student->firstname  ?? '';
    $lastname   = $student->lastname   ?? '';
    $othername  = $student->othername  ?? '';
    $fullname   = trim("$firstname $othername $lastname");
    $initials   = strtoupper(substr($firstname,0,1).substr($lastname,0,1));
    $classArm   = trim(($student->schoolclass ?? '') . ' ' . ($student->arm ?? ''));
    $admNo      = $student->admissionNo ?? '';
    $dob        = !empty($student->dateofbirth)
                    ? \Carbon\Carbon::parse($student->dateofbirth)->format('d M Y') : '';
    $admDate    = !empty($student->admission_date)
                    ? \Carbon\Carbon::parse($student->admission_date)->format('d M Y') : '';
    $photoUrl   = $student->picture ? asset('storage/images/student_avatars/'.$student->picture) : null;
    $phone      = $schoolInfo?->school_phone   ?? '';
    $email      = $schoolInfo?->school_email   ?? '';
    $website    = $schoolInfo?->school_website ?? '';
    $address    = $schoolInfo?->school_address ?? '';

    // All fields — filter empty — chunk into pairs for 2-col table rows
    $fields = array_values(array_filter([
        ['Class',      $classArm],
        ['Gender',     $student->gender           ?? ''],
        ['D.O.B',      $dob],
        ['Blood Grp',  $student->blood_group       ?? ''],
        ['Nationality',$student->nationality       ?? ''],
        ['State',      $student->state             ?? ''],
        ['L.G.A',      $student->local             ?? ''],
        ['Session',    $student->session           ?? ''],
        ['Adm. Date',  $admDate],
        ['Category',   $student->student_category  ?? ''],
        ['Status',     $student->student_status    ?? ''],
    ], fn($r) => !empty(trim($r[1]))));

    $pairs = array_chunk($fields, 2);

    $payload    = base64_encode(json_encode(['id'=>$student->id,'adm'=>$admNo,'ts'=>now()->timestamp]));
    $qrB64      = base64_encode(
        \SimpleSoftwareIO\QrCode\Facades\QrCode::size(110)->format('png')
            ->generate(route('student-id-cards.verify', ['token' => $payload]))
    );
    $barcodeB64 = $barcodeGen
        ? base64_encode($barcodeGen->getBarcode($admNo, $barcodeGen::TYPE_CODE_128, 2, 40))
        : null;
@endphp

<table class="pair-table">
  <tr class="pair-label-row">
    <td style="width:50%;">&#9654; FRONT</td>
    <td style="width:50%;">&#9664; BACK</td>
  </tr>
  <tr class="pair-card-row">

    {{-- ═══ FRONT ═══ --}}
    <td>
    <div class="card">
      <div class="accent"></div>

      {{-- Watermark --}}
      @if($logoUrl)
      <div class="watermark"><img src="{{ $logoUrl }}" alt=""></div>
      @endif

      {{-- Header --}}
      <div class="f-header">
        <div class="f-header-deco"></div>
        @if($logoUrl)
          <div class="f-logo"><img src="{{ $logoUrl }}" alt="logo"></div>
        @else
          <div class="f-logo-placeholder">&#127979;</div>
        @endif
        <div class="f-school">{{ $schoolName }}</div>
        @if(!empty($schoolInfo?->school_motto))
        <div class="f-motto">{{ $schoolInfo->school_motto }}</div>
        @endif
        <div class="f-badge">STUDENT ID CARD</div>
      </div>

      {{-- Photo --}}
      <div class="f-photo-wrap">
        @if($photoUrl)
          <div class="f-photo"><img src="{{ $photoUrl }}" alt="{{ $fullname }}"></div>
        @else
          <div class="f-initials">{{ $initials }}</div>
        @endif
      </div>

      {{-- Name --}}
      <div class="f-name">{{ $fullname }}</div>
      <div class="f-adm">
        <span class="f-adm-lbl">ADM NO</span><span class="f-adm-val">{{ $admNo }}</span>
      </div>

      {{-- Info table — proper HTML table, 2 fields per row --}}
      <table class="info-tbl">
        @foreach($pairs as $pi => $pair)
        <tr>
          {{-- Left label --}}
          <td class="lbl">{{ $pair[0][0] }}</td>
          {{-- Left value --}}
          <td class="val">{{ $pair[0][1] }}</td>
          @if(isset($pair[1]))
          {{-- Divider + Right label + Right value --}}
          <td class="lbl" style="border-left:0.3mm solid #e2e8f0;">{{ $pair[1][0] }}</td>
          <td class="val">{{ $pair[1][1] }}</td>
          @else
          <td class="val" colspan="2"></td>
          @endif
        </tr>
        @endforeach
      </table>

      {{-- QR --}}
      <div class="f-qr">
        <img src="data:image/png;base64,{{ $qrB64 }}" alt="QR">
        <div class="f-qr-lbl">SCAN TO VERIFY</div>
      </div>

      <div class="accent-bot"></div>
    </div>
    </td>

    {{-- ═══ BACK ═══ --}}
    <td>
    <div class="card">
      <div class="accent"></div>

      {{-- Watermark --}}
      @if($logoUrl)
      <div class="watermark"><img src="{{ $logoUrl }}" alt=""></div>
      @endif

      {{-- Header --}}
      <div class="b-header">
        <div class="b-header-sub">STUDENT IDENTITY CARD</div>
        <div class="b-header-name">{{ $schoolName }}</div>
        @if($address)
        <div class="b-header-addr">{{ $address }}</div>
        @endif
      </div>

      {{-- Mag strip --}}
      <div class="b-mag"></div>

      {{-- Student chip --}}
      <div class="b-chip">
        <div class="b-chip-name">{{ $fullname }}</div>
        <div class="b-chip-sub">{{ $classArm ?: 'N/A' }} &nbsp;|&nbsp; {{ $admNo }}</div>
      </div>

      {{-- Terms --}}
      <div class="b-terms">
        <div class="b-terms-head">Terms &amp; Conditions</div>
        <ul>
          <li>Property of {{ $schoolName }}. Must be carried on premises at all times.</li>
          <li>Report loss immediately. Replacement fee applies.</li>
          <li>Not transferable. Misuse attracts disciplinary action.</li>
          <li>Return upon completion or withdrawal of enrolment.</li>
        </ul>
      </div>

      {{-- Contact --}}
      @if($phone || $email || $website)
      <div class="b-contact">
        <div class="b-contact-head">Contact</div>
        @if($phone)<div class="b-contact-line">Tel: {{ $phone }}</div>@endif
        @if($email)<div class="b-contact-line">Email: {{ $email }}</div>@endif
        @if($website)<div class="b-contact-line">Web: {{ $website }}</div>@endif
      </div>
      @endif

      {{-- Signatures --}}
      <table class="b-sig-table">
        <tr>
          <td>
            <div class="b-sig-line"></div>
            <div class="b-sig-lbl">Cardholder's Signature</div>
          </td>
          <td>
            <div class="b-sig-line"></div>
            <div class="b-sig-lbl">Authorised Signature</div>
          </td>
        </tr>
      </table>

      {{-- Issued by --}}
      <div class="b-issued">
        <div class="b-issued-lbl">Issued By</div>
        <div class="b-issued-school">{{ $schoolName }}</div>
        <div class="b-issued-role">Admin Officer</div>
      </div>

      {{-- Barcode --}}
      <div class="b-barcode">
        @if($barcodeB64)
          <img src="data:image/png;base64,{{ $barcodeB64 }}" alt="barcode">
        @else
          <div class="b-barcode-fallback">||| {{ $admNo }} |||</div>
        @endif
        <div class="b-barcode-adm">{{ $admNo }}</div>
        <div class="b-barcode-sub">Valid Until: {{ $expiry }} &bull; {{ now()->year }}</div>
      </div>

      <div class="accent-bot"></div>
    </div>
    </td>

  </tr>
</table>

@if(!$loop->last)
<table class="cut-table">
  <tr><td>
    <hr class="cut-hr">
    <div class="cut-text">&#9986; &nbsp; CUT HERE &nbsp; &#9986;</div>
  </td></tr>
</table>
@endif

@endforeach
</div>
</body>
</html>
