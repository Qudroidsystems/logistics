{{-- resources/views/studentmockreports/partials/student_rows.blade.php --}}

@forelse ($allstudents as $index => $student)

@php
    $avatarColors = ['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#30cfd0','#a18cd1','#fda085'];
    $color1 = $avatarColors[$index % count($avatarColors)];
    $color2 = $avatarColors[($index + 2) % count($avatarColors)];
    $initials = strtoupper(
        substr($student->firstname ?? '', 0, 1) .
        substr($student->lastname  ?? '', 0, 1)
    );
    if (empty(trim($initials))) $initials = 'ST';
    $genderClass = strtolower($student->gender ?? '') === 'male' ? 'male' : 'female';
    $genderIcon  = strtolower($student->gender ?? '') === 'male' ? 'bi-gender-male' : 'bi-gender-female';
    $avatarUrl   = $student->picture
        ? asset('storage/images/student_avatars/' . $student->picture)
        : null;
    $fullName = trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? ''));
@endphp

<tr style="animation: rowIn .3s ease {{ $index * 0.03 }}s both;">

    {{-- Checkbox --}}
    <td>
        <input type="checkbox" name="chk_child" value="{{ $student->stid }}"
               style="accent-color:var(--r-accent);width:15px;height:15px;cursor:pointer;">
    </td>

    {{-- Admission No --}}
    <td>
        <span style="font-family:'JetBrains Mono',monospace;font-size:12px;
                     font-weight:600;color:var(--r-primary);
                     background:var(--r-bg);border:1px solid var(--r-border);
                     padding:3px 8px;border-radius:6px;">
            {{ $student->admissionno ?? 'N/A' }}
        </span>
    </td>

    {{-- Photo --}}
    <td>
        <div class="r-avatar-wrap"
             data-image-zoom
             data-image-src="{{ $avatarUrl ?? asset('storage/student_avatars/unnamed.jpg') }}"
             data-image-name="{{ $fullName }}"
             title="Click to enlarge">
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}"
                     alt="{{ $fullName }}"
                     class="r-avatar"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                <div class="r-avatar-placeholder"
                     style="display:none;background:linear-gradient(135deg,{{ $color1 }},{{ $color2 }})">
                    {{ $initials }}
                </div>
            @else
                <div class="r-avatar-placeholder"
                     style="background:linear-gradient(135deg,{{ $color1 }},{{ $color2 }})">
                    {{ $initials }}
                </div>
            @endif
            <div class="r-avatar-zoom-btn"><i class="bi bi-zoom-in"></i></div>
        </div>
    </td>

    {{-- Last Name --}}
    <td>
        <div style="font-weight:700;color:var(--r-primary);">
            {{ $student->lastname ?? '—' }}
        </div>
    </td>

    {{-- First Name --}}
    <td>
        <div style="color:#374151;">{{ $student->firstname ?? '—' }}</div>
    </td>

    {{-- Other Name --}}
    <td>
        <div style="color:var(--r-muted);font-size:12px;">
            {{ $student->othername ?? '—' }}
        </div>
    </td>

    {{-- Gender --}}
    <td>
        <span class="r-gender-badge {{ $genderClass }}">
            <i class="bi {{ $genderIcon }}"></i>
            {{ ucfirst(strtolower($student->gender ?? '—')) }}
        </span>
    </td>

    {{-- Class --}}
    <td>
        <span class="r-class-badge">
            <i class="ri-building-line"></i>
            {{ $student->schoolclass ?? '—' }}
        </span>
    </td>

    {{-- Arm --}}
    <td>
        <span style="background:#f5f3ff;color:#4f46e5;border:1px solid #ddd6fe;
                     padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;">
            {{ $student->schoolarm ?? '—' }}
        </span>
    </td>

    {{-- Session --}}
    <td>
        <span style="color:var(--r-muted);font-size:12px;">
            {{ $student->session ?? '—' }}
        </span>
    </td>

    {{-- Actions --}}
    <td>
        <div class="d-flex gap-1 flex-wrap">
            {{-- View: JS checks term before navigating --}}
            <button type="button"
                    class="r-action-btn view"
                    title="View Mock Result"
                    data-stid="{{ $student->stid }}"
                    data-classid="{{ $student->schoolclassID }}"
                    data-sessionid="{{ $student->sessionid }}"
                    onclick="handleViewResult(this)">
                <i class="ph-eye"></i> View
            </button>

            {{-- PDF: JS checks term before opening --}}
            <button type="button"
                    class="r-action-btn print"
                    title="Export PDF"
                    data-stid="{{ $student->stid }}"
                    data-classid="{{ $student->schoolclassID }}"
                    data-sessionid="{{ $student->sessionid }}"
                    onclick="handlePdfResult(this)">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </button>
        </div>
    </td>
</tr>

@empty
<tr>
    <td colspan="11">
        <div class="r-empty">
            <i class="ri-user-search-line r-empty-icon"></i>
            <h6>No Students Found</h6>
            <p>Select a class and session above to load student records.</p>
        </div>
    </td>
</tr>
@endforelse
