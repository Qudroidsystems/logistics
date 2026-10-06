@forelse ($users as $user)
    <tr data-id="{{ $user->id }}">
        <td class="id" data-id="{{ $user->id }}">
            <div class="form-check">
                <input class="form-check-input user-checkbox"
                       type="checkbox"
                       name="user_ids[]"
                       value="{{ $user->id }}"
                       data-user-name="{{ htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') }}">
                <label class="form-check-label"></label>
            </div>
        </td>
        <td class="name">
            <div class="d-flex align-items-center">
                <div class="avatar-xs me-2">
                    <img src="{{ $user->avatar_url }}"
                         alt="{{ $user->name }}"
                         class="rounded-circle avatar-xs"
                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=7F9CF5&background=EBF4FF'">
                </div>
                <div>
                    <h6 class="mb-0">
                        <a href="{{ route('users.show', $user->id) }}" class="text-reset products">{{ $user->name }}</a>
                    </h6>
                    <small class="text-muted">
                        @if($user->isStudent() && $user->student)
                            {{ $user->student->admissionNo ?? '' }}
                        @elseif($user->isStaff() && $user->staffemploymentDetails)
                            {{ $user->staffemploymentDetails->designation ?? 'Staff' }}
                        @endif
                    </small>
                </div>
            </div>
        </td>
        <td class="datereg">{{ $user->created_at->format('Y-m-d') }}</td>
        <td>
            <ul class="d-flex gap-2 list-unstyled mb-0">
                @can('Remove user-role')
                    <li>
                        <a class="dropdown-item remove-item-btn" href="javascript:void(0);"
                           data-bs-toggle="modal"
                           data-bs-target="#deleteRecordModal"
                           data-url="{{ route('roles.removeuserrole', ['userid' => $user->id, 'roleid' => $role->id]) }}"
                           data-user-name="{{ htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') }}">
                            <i class="bi bi-trash3 me-1 align-baseline"></i> Remove User
                        </a>
                    </li>
                @endcan
            </ul>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="noresult text-center">No results found</td>
    </tr>
@endforelse
