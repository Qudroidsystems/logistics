<tbody id="studentTableBody">
    @forelse ($allstudents as $student)
        <tr>
            <td class="id" data-id="{{ $student->stid }}">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $student->stid }}">
                    <label class="form-check-label"></label>
                </div>
            </td>
            <td class="admissionno" data-admissionno="{{ $student->admissionno }}">{{ $student->admissionno }}</td>
             <td class="picture" data-picture="{{ $student->picture }}">
                @if ($student->picture)
                    <a href="#" data-bs-toggle="modal" data-bs-target="#imageViewModal" data-image="{{ asset('storage/' . $student->picture) }}">
                        <img src="{{ asset('storage/' . $student->picture) }}" alt="{{ $student->firstname }}'s picture" width="50" height="50" class="rounded-circle" onerror="this.src='{{ asset('storage/student_avatars/unnamed.jpg') }}';">
                    </a>
                @else   
                    <span>No Picture</span>
                @endif
            </td>
             <td class="lastname" data-lastname="{{ $student->lastname }}">{{ $student->lastname }}</td>
            <td class="firstname" data-firstname="{{ $student->firstname }}">{{ $student->firstname }}</td>
            <td class="othername" data-othername="{{ $student->othername }}">{{ $student->othername ?? '-' }}</td>
            <td class="gender" data-gender="{{ $student->gender }}">{{ $student->gender }}</td>
            <td class="schoolclass" data-schoolclass="{{ $student->schoolclass }}">{{ $student->schoolclass }}</td>
            <td class="schoolarm" data-schoolarm="{{ $student->schoolarm }}">{{ $student->schoolarm }}</td>
            <td class="session" data-session="{{ $student->session }}">{{ $student->session }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="10" class="text-center">Select class and session to view students.</td>
        </tr>
    @endforelse
</tbody>
