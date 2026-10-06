{{-- Child switcher + section tabs for parent pages. Needs $child, $children, $section. --}}
@php
    $sections = ['results' => ['Results', 'ri-file-chart-line'], 'fees' => ['Fees', 'ri-wallet-3-line'],
                 'attendance' => ['Attendance', 'ri-calendar-check-line'], 'timetable' => ['Timetable', 'ri-time-line']];
@endphp
<div class="cb-card mb-3">
    <div class="cb-toolbar flex-wrap gap-2">
        @if($children->count() > 1)
            <div class="term-chips">
                @foreach($children as $k)
                    <a class="term-chip {{ $k->id == $child->id ? 'active' : '' }}" href="{{ route('parent.' . $section, $k->id) }}">{{ $k->firstname }}</a>
                @endforeach
            </div>
        @endif
        <div class="term-chips ms-auto">
            @foreach($sections as $key => [$label, $icon])
                <a class="term-chip {{ $section === $key ? 'active' : '' }}" href="{{ route('parent.' . $key, $child->id) }}"><i class="{{ $icon }} me-1"></i>{{ $label }}</a>
            @endforeach
        </div>
    </div>
</div>
@if(session('success'))<div class="cb-banner info"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div></div>@endif
@if(session('error'))<div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div></div>@endif
