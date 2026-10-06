@php
$i = isset($students) && $students instanceof \Illuminate\Pagination\LengthAwarePaginator ? ($students->currentPage() - 1) * $students->perPage() : 0;
@endphp
@forelse ($students ?? [] as $sc)
    <tr>
        <td class="id" data-id="{{ $sc->id }}">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="chk_child">
                <label class="form-check-label"></label>
            </div>
        </td>
        <td class="sn">{{ ++$i }}</td>
        <td class="admissionno" data-admissionno="{{ $sc->admissionno }}">{{ $sc->admissionno }}</td>
        <td class="name" data-name="{{ $sc->firstname }} {{ $sc->lastname }}">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <img src="{{ $sc->picture ? asset('storage/student_avatars/' . basename($sc->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                         alt="{{ ($sc->lastname ?? '') . ' ' . ($sc->firstname ?? '') . ' ' . ($sc->othername ?? '') }}"
                         class="rounded-circle avatar-sm"
                         data-bs-toggle="modal"
                         data-bs-target="#imageViewModal"
                         data-image="{{ $sc->picture ? asset('storage/student_avatars/' . basename($sc->picture)) : asset('storage/student_avatars/unnamed.jpg') }}"
                         data-picture="{{ $sc->picture ?? 'none' }}"
                         onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}'; console.log('Image failed to load for admissionno: {{ $sc->admissionno ?? 'unknown' }}, picture: {{ $sc->picture ?? 'none' }}');">
                </div>
                <div>
                    <h6 class="mb-0">
                        <a href="{{ route('subjects.subjectinfo', [$sc->id, $sc->schoolclassid, 1, $sc->sessionid]) }}"
                           class="text-reset">{{ $sc->lastname }} {{ $sc->firstname }} {{ $sc->othername }}</a>
                    </h6>
                </div>
            </div>
        </td>
        <td class="class" data-class="{{ $sc->class_name }} {{ $sc->arm_name }}">
            <span class="badge bg-primary-subtle text-primary">{{ $sc->class_name }} {{ $sc->arm_name }}</span>
        </td>
        <td class="gender" data-gender="{{ $sc->gender }}">{{ $sc->gender }}</td>
        <td>
            <ul class="d-flex gap-2 list-unstyled mb-0">
                @can('Update subject-operation')
                    <li>
                        <a href="{{ route('subjects.subjectinfo', [$sc->id, $sc->schoolclassid, 1, $sc->sessionid]) }}"
                           class="btn btn-subtle-primary btn-icon btn-sm"><i class="ph-eye"></i></a>
                    </li>
                @endcan
            </ul>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7">Select class and session to view students.</td>
    </tr>
@endforelse
