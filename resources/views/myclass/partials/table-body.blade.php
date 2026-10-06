@php $i = ($myclass->currentPage() - 1) * $myclass->perPage() @endphp
@forelse ($myclass as $sc)
    <tr>
        <td class="id" data-id="{{ $sc->id }}">
            <div class="form-check form-check-sm form-check-solid">
                <input class="form-check-input" type="checkbox" name="chk_child" />
            </div>
        </td>
        <td class="classid">{{ ++$i }}</td>
        <td class="schoolclass" data-schoolclass="{{ $sc->schoolclass }}">{{ $sc->schoolclass }}</td>
        <td class="schoolarm" data-schoolarm="{{ $sc->schoolarm }}">{{ $sc->schoolarm }}</td>
        <td class="term" data-term="{{ $sc->term }}">{{ $sc->term }}</td>
        <td class="session" data-session="{{ $sc->session }}">{{ $sc->session }}</td>
        <td class="classcategory">
            <a href="{{ route('viewstudent', [$sc->schoolclassid, $sc->termid, $sc->sessionid]) }}" class="btn btn-primary btn-sm">View Students</a>
        </td>
        <td class="updated_at">
            <a href="{{ route('classbroadsheet', [$sc->schoolclassid, $sc->termid, $sc->sessionid]) }}" class="btn btn-info btn-sm">View Broadsheet</a>
        </td>
       
    </tr>
@empty
    <tr>
        <td colspan="9" class="noresult">No classes found</td>
    </tr>
@endforelse