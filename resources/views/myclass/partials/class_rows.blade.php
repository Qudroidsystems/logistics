@forelse ($students as $sc)
    <tr>
        <td class="id" data-id="{{ $sc->student_id }}">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="chk_child">
                <label class="form-check-label"></label>
            </div>
        </td>
        <td class="admission_no" data-admission_no="{{ $sc->admission_no }}">{{ $sc->admission_no }}</td>
        <td class="first_name" data-first_name="{{ $sc->first_name }}">{{ $sc->first_name }}</td>
        <td class="last_name" data-last_name="{{ $sc->last_name }}">{{ $sc->last_name }}</td>
        <td class="other_name" data-other_name="{{ $sc->other_name }}">{{ $sc->other_name }}</td>
        <td class="gender" data-gender="{{ $sc->gender }}">{{ $sc->gender }}</td>
        <td class="picture" data-picture="{{ $sc->picture }}">
            @if ($sc->picture)
                <img src="{{ asset('storage/' . $sc->picture) }}" alt="Student Picture" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
            @else
                <span>No Image</span>
            @endif
        </td>
        <td class="schoolclass" data-schoolclass="{{ $sc->schoolclass }}">{{ $sc->schoolclass }}</td>
        <td class="schoolarm" data-schoolarm="{{ $sc->schoolarm }}">{{ $sc->schoolarm }}</td>
        {{-- <td class="term" data-term="{{ $sc->term }}">{{ $sc->term }}</td> --}}
        <td class="session" data-session="{{ $sc->session }}">{{ $sc->session }}</td>
        <td>
          <div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-gear-fill me-1"></i> Actions
    </button>
    <ul class="dropdown-menu shadow-sm" style="min-width: 12rem;">
        @can('View my-class')
            <li><h6 class="dropdown-header">Student Reports</h6></li>
            <li>
                <a class="dropdown-item d-flex align-items-center" href="{{ route('viewstudent', [$sc->schoolclassID, 1, $sc->sessionid]) }}">
                    <i class="bi bi-person me-2"></i> View Student Term 1
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center" href="{{ route('viewstudent', [$sc->schoolclassID, 2, $sc->sessionid]) }}">
                    <i class="bi bi-person me-2"></i> View Student Term 2
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center" href="{{ route('viewstudent', [$sc->schoolclassID, 3, $sc->sessionid]) }}">
                    <i class="bi bi-person me-2"></i> View Student Term 3
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header">Broadsheets</h6></li>
            <li>
                <a class="dropdown-item d-flex align-items-center" href="{{ route('classbroadsheet', [$sc->schoolclassID, $sc->sessionid, 1]) }}">
                    <i class="bi bi-file-earmark-text me-2"></i> Broadsheet Term 1
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center" href="{{ route('classbroadsheet', [$sc->schoolclassID, $sc->sessionid, 2]) }}">
                    <i class="bi bi-file-earmark-text me-2"></i> Broadsheet Term 2
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center" href="{{ route('classbroadsheet', [$sc->schoolclassID, $sc->sessionid, 3]) }}">
                    <i class="bi bi-file-earmark-text me-2"></i> Broadsheet Term 3
                </a>
            </li>
        @endcan
        @can('Update my-class')
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item d-flex align-items-center edit-item-btn" href="javascript:void(0);">
                    <i class="bi bi-pencil me-2"></i> Edit
                </a>
            </li>
        @endcan
        @can('Delete my-class')
            <li>
                <a class="dropdown-item d-flex align-items-center text-danger remove-item-btn" href="javascript:void(0);">
                    <i class="bi bi-trash me-2"></i> Delete
                </a>
            </li>
        @endcan
    </ul>
</div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="12" class="noresult" style="display: block;">No students found</td>
    </tr>
@endforelse
