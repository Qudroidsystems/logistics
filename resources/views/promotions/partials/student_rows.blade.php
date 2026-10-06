{{-- resources/views/promotions/partials/student_rows.blade.php --}}

@forelse ($allstudents as $student)
    @php
        $rec             = $student->promotion_recommendation ?? null;
        $recStatus       = $rec['status'] ?? 'awaiting';
        $recLabel        = $rec['status_label'] ?? 'Awaiting';
        $appliedRule     = $rec['applied_rule'] ?? null;
        $appliedRuleName = $appliedRule['name'] ?? null;
        $savedStatus     = strtolower($student->promotion_status ?? '');

        $avatarUrl = $student->picture
            ? asset('storage/student_avatars/' . $student->picture)
            : asset('storage/student_avatars/unnamed.jpg');
    @endphp
    <tr data-student-id="{{ $student->stid }}">

        {{-- Col 1: Checkbox --}}
        <td style="width:40px;">
            <input type="checkbox" class="row-checkbox" value="{{ $student->stid }}">
        </td>

        {{-- Col 2: Admission No --}}
        <td class="fw-medium">{{ $student->admissionno }}</td>

        {{-- Col 3: Student Name --}}
        <td>
            <div class="d-flex align-items-center gap-2">
                @if ($student->picture)
                    <img src="{{ $avatarUrl }}"
                         alt="Student Picture"
                         width="36" height="36"
                         class="student-row-avatar rounded-circle flex-shrink-0"
                         style="object-fit:cover;"
                         onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                @else
                    <span class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center flex-shrink-0 student-row-avatar"
                          style="width:36px;height:36px;font-size:13px;color:#fff;">
                        {{ strtoupper(substr($student->firstname, 0, 1)) }}
                    </span>
                @endif
                <span>{{ $student->lastname }}, {{ $student->firstname }} {{ $student->othername ?? '' }}</span>
            </div>
        </td>

        {{-- Col 4: Class --}}
        <td>{{ $student->schoolclass }}</td>

        {{-- Col 5: Arm --}}
        <td>{{ $student->schoolarm ?? '-' }}</td>

        {{-- Col 6: Session --}}
        <td>{{ $student->session }}</td>

        {{-- Col 7: Overall Avg --}}
        <td>
            @if(isset($student->overall_average) && $student->overall_average !== null)
                @php
                    $avg      = $student->overall_average;
                    $avgClass = $avg >= 50 ? 'text-success' : ($avg >= 40 ? 'text-warning' : 'text-danger');
                @endphp
                <span class="fw-semibold {{ $avgClass }}">{{ number_format($avg, 1) }}%</span>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>

        {{-- Col 7b: Position — competition-ranked on the same average basis --}}
        <td>
            @if(!empty($student->position))
                <span class="fw-semibold">{{ $student->position }}</span>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>

        {{-- Col 8: System Recommendation --}}
        <td data-rec-status="{{ $recStatus }}">
            @if($recStatus === 'awaiting')
                {{--
                    Two distinct sub-states under 'awaiting':
                    1. No promotion settings configured at all for this class → "Not Configured"
                    2. Settings exist but term is non-promotional or no data yet → "Awaiting"
                    Both share status=awaiting; we distinguish via the label set by the evaluator.
                --}}
                @if(isset($rec['settings_id']) === false && isset($rec['rule_logic']) === false)
                    <span class="promotion-badge-pending" title="No promotion rules have been configured for this class">
                        <i class="ri-settings-4-line"></i> Not Configured
                    </span>
                @else
                    <span class="promotion-badge-pending">
                        <i class="ri-time-line"></i> Awaiting
                    </span>
                @endif

            @elseif($recStatus === 'promoted')
                <span class="promotion-badge-promoted">
                    <i class="ri-arrow-up-circle-line"></i> {{ $recLabel }}
                </span>

            @elseif($recStatus === 'trial')
                <span class="promotion-badge-trial">
                    <i class="ri-time-line"></i> {{ $recLabel }}
                </span>

            @elseif($recStatus === 'see_principal')
                <span class="promotion-badge-see_principal">
                    <i class="ri-eye-line"></i> {{ $recLabel }}
                </span>

            @elseif(in_array($recStatus, ['repeated', 'repeat']))
                <span class="promotion-badge-repeated">
                    <i class="ri-repeat-line"></i> {{ $recLabel }}
                </span>

            @else
                <span class="promotion-badge-pending">
                    <i class="ri-question-line"></i> {{ $recLabel }}
                </span>
            @endif

            {{-- Applied Rule Badge — only shown when a rule actually fired --}}
            @if($appliedRuleName)
                <div class="mt-1">
                    <span class="rule-badge"
                          style="background:#eef2ff;color:#3730a3;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600;display:inline-block;white-space:nowrap;">
                        <i class="ri-price-tag-3-line"></i> {{ $appliedRuleName }}
                    </span>
                </div>
            @endif
        </td>

        {{-- Col 9: Promotion Status (saved admin decision) --}}
        <td data-saved-status="{{ $savedStatus }}">
            @if($savedStatus === 'promoted')
                <span class="promotion-badge-promoted">
                    <i class="ri-checkbox-circle-line"></i> Promoted
                </span>
            @elseif($savedStatus === 'trial')
                <span class="promotion-badge-trial">
                    <i class="ri-time-line"></i> On Trial
                </span>
            @elseif($savedStatus === 'see_principal')
                <span class="promotion-badge-see_principal">
                    <i class="ri-eye-line"></i> See Principal
                </span>
            @elseif(in_array($savedStatus, ['repeat', 'repeated']))
                <span class="promotion-badge-repeated">
                    <i class="ri-repeat-line"></i> Repeat
                </span>
            @elseif($savedStatus === 'advanced')
                <span class="promotion-badge-advanced"><i class="ri-skip-forward-line"></i> Advanced</span>
            @else
                <span class="promotion-badge-pending">
                    <i class="ri-minus-circle-line"></i> Pending
                </span>
            @endif
        </td>

        {{-- Col 10: Actions --}}
        <td style="width:120px;">
            <div class="d-flex gap-1">
                <button type="button"
                        class="btn btn-icon btn-subtle-primary"
                        title="Manage Promotion"
                        onclick="openPromotionModal(
                            '{{ $student->stid }}',
                            '{{ addslashes($student->admissionno) }}',
                            '{{ addslashes($student->firstname) }}',
                            '{{ addslashes($student->lastname) }}',
                            '{{ addslashes($student->othername ?? '') }}',
                            '{{ addslashes($student->picture ?? '') }}',
                            '{{ addslashes($student->gender ?? '') }}',
                            '{{ addslashes($student->schoolclass) }}',
                            '{{ addslashes($student->schoolarm ?? '') }}',
                            '{{ addslashes($student->session) }}',
                            '{{ $student->termid }}'
                        )">
                    <i class="ri-edit-line"></i>
                </button>
                @if($savedStatus !== '')
                <button type="button"
                        class="btn btn-icon btn-subtle-warning"
                        title="Clear saved decision (undoable)"
                        onclick="clearDecision(
                            '{{ $student->stid }}',
                            {{ $student->schoolclassID }},
                            {{ $student->sessionid }},
                            {{ $student->termid }},
                            '{{ addslashes($student->lastname . ', ' . $student->firstname) }}'
                        )">
                    <i class="ri-eraser-line"></i>
                </button>
                @endif
                <button type="button"
                        class="btn btn-icon btn-subtle-danger"
                        title="Remove from Class"
                        onclick="removeStudent(
                            '{{ $student->stid }}',
                            {{ $student->schoolclassID }},
                            {{ $student->sessionid }},
                            {{ $student->termid }},
                            '{{ addslashes($student->admissionno) }}',
                            '{{ addslashes($student->firstname) }}',
                            '{{ addslashes($student->lastname) }}'
                        )">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="11" class="text-center py-5">
            <div class="empty-state">
                <i class="ri-inbox-line"></i>
                <p class="mb-0">No students found. Select a class and session to load students.</p>
            </div>
        </td>
    </tr>
@endforelse
