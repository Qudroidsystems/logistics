<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ID Card Verification — {{ $schoolInfo?->school_name }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f1f5f9;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
}

.card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(0,0,0,.12);
    max-width: 440px;
    width: 100%;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #1e3a5f, #2169ad);
    padding: 22px 24px;
    text-align: center;
    color: #fff;
}
.card-header img.logo {
    width: 52px; height: 52px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,.5);
    object-fit: contain; margin-bottom: 8px;
}
.card-header h1 { font-size: 15px; font-weight: 700; }
.card-header p  { font-size: 11px; opacity: .75; margin-top: 2px; }

.status-banner {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 20px; font-weight: 700; font-size: 13px;
}
.status-banner.valid   { background: #f0fdf4; color: #15803d; border-bottom: 1px solid #bbf7d0; }
.status-banner.invalid { background: #fef2f2; color: #dc2626; border-bottom: 1px solid #fecaca; }
.status-icon {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.valid   .status-icon { background: #dcfce7; }
.invalid .status-icon { background: #fee2e2; }

.body { padding: 20px 24px; }

.student-header {
    display: flex; gap: 14px; align-items: center; margin-bottom: 18px;
}
.student-photo {
    width: 70px; height: 70px; border-radius: 50%;
    border: 3px solid #2169ad; overflow: hidden; flex-shrink: 0;
    background: #dbeafe; display: flex; align-items: center;
    justify-content: center; font-size: 22px; font-weight: 800; color: #2169ad;
}
.student-photo img { width: 100%; height: 100%; object-fit: cover; }
.student-name  { font-size: 16px; font-weight: 800; color: #1e2937; }
.student-adm   { font-size: 11px; color: #2169ad; font-weight: 700; margin-top: 3px; }
.student-class { font-size: 11px; color: #64748b; margin-top: 2px; }

.info-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 1px; background: #e2e8f0;
    border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;
    margin-bottom: 16px;
}
.info-cell {
    background: #fff; padding: 9px 12px;
}
.info-cell .label {
    font-size: 9px; font-weight: 700; color: #6366f1;
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px;
}
.info-cell .value {
    font-size: 11px; font-weight: 600; color: #1e2937;
}

.status-pill {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 700;
}
.pill-active   { background: #dcfce7; color: #15803d; }
.pill-inactive { background: #fee2e2; color: #dc2626; }

.footer {
    border-top: 1px solid #f1f5f9; padding: 12px 24px;
    font-size: 10px; color: #94a3b8; text-align: center;
}
.footer strong { color: #64748b; }

.timestamp { font-size: 10px; color: #94a3b8; text-align: center; margin-top: 8px; }
</style>
</head>
<body>

<div class="card">

    {{-- HEADER --}}
    <div class="card-header">
        @if($schoolInfo?->school_logo)
            <img class="logo" src="{{ $schoolInfo->getLogoUrlAttribute() }}" alt="Logo">
        @endif
        <h1>{{ $schoolInfo?->school_name ?? 'School Portal' }}</h1>
        <p>Student ID Verification System</p>
    </div>

    {{-- STATUS BANNER --}}
    <div class="status-banner {{ $valid ? 'valid' : 'invalid' }}">
        <div class="status-icon">{{ $valid ? '✓' : '✗' }}</div>
        <div>{{ $message }}</div>
    </div>

    {{-- STUDENT DETAILS --}}
    @if($valid && $student)
    <div class="body">

        @php
            $firstname = $student->firstname ?? '';
            $lastname  = $student->lastname  ?? '';
            $othername = $student->othername ?? '';
            $fullname  = trim("$firstname $othername $lastname");
            $initials  = strtoupper(substr($firstname,0,1) . substr($lastname,0,1));
            $classArm  = trim(($student->schoolclass ?? '') . ' ' . ($student->arm ?? ''));
            $photoUrl  = $student->picture
                ? asset('storage/images/student_avatars/' . $student->picture)
                : null;
            $dob       = $student->dateofbirth
                ? \Carbon\Carbon::parse($student->dateofbirth)->format('d M Y') : 'N/A';
            $admDate   = $student->admission_date
                ? \Carbon\Carbon::parse($student->admission_date)->format('d M Y') : 'N/A';
            $statusVal = $student->student_status ?? '';
            $isActive  = strtolower($statusVal) === 'active';
        @endphp

        <div class="student-header">
            <div class="student-photo">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}"
                         onerror="this.style.display='none';this.parentElement.textContent='{{ $initials }}';"
                         alt="{{ $fullname }}">
                @else
                    {{ $initials }}
                @endif
            </div>
            <div>
                <div class="student-name">{{ $fullname }}</div>
                <div class="student-adm">{{ $student->admissionNo }}</div>
                <div class="student-class">{{ $classArm ?: 'Class not assigned' }}</div>
                @if($statusVal)
                <div style="margin-top:4px;">
                    <span class="status-pill {{ $isActive ? 'pill-active' : 'pill-inactive' }}">
                        {{ $statusVal }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <div class="info-grid">
            <div class="info-cell">
                <div class="label">Gender</div>
                <div class="value">{{ $student->gender ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Date of Birth</div>
                <div class="value">{{ $dob }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Blood Group</div>
                <div class="value">{{ $student->blood_group ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Nationality</div>
                <div class="value">{{ $student->nationality ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Session</div>
                <div class="value">{{ $student->session ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Admission Date</div>
                <div class="value">{{ $admDate }}</div>
            </div>
            @if($student->student_category)
            <div class="info-cell" style="grid-column:span 2;">
                <div class="label">Category</div>
                <div class="value">{{ $student->student_category }}</div>
            </div>
            @endif
        </div>

        <div class="timestamp">
            Verified at {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>

    @else
    <div class="body" style="text-align:center;padding:30px 24px;">
        <div style="font-size:48px;opacity:.2;">&#128683;</div>
        <p style="color:#6b7280;font-size:13px;margin-top:10px;">
            This ID card could not be verified. Please report to the school office.
        </p>
    </div>
    @endif

    <div class="footer">
        Powered by <strong>{{ $schoolInfo?->school_name ?? 'School Portal' }}</strong>
        &nbsp;&bull;&nbsp; {{ now()->year }}
    </div>

</div>

</body>
</html>
