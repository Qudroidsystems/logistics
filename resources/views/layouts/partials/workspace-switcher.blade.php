{{-- Switch between the places this login works in: the platform back office, each provider business, each shop, driver, own account. --}}
@auth
@php
    $wsList = \App\Support\Workspaces::for(auth()->user());
    $wsKey = \App\Support\Workspaces::current(auth()->user());
    $wsNow = collect($wsList)->firstWhere('key', $wsKey);
@endphp
@if(count($wsList) > 1)
<div class="dropdown ms-1 me-1" id="workspace-switcher">
    <button type="button" class="btn btn-sm btn-ghost-secondary d-flex align-items-center gap-2 px-2" data-bs-toggle="dropdown" aria-expanded="false" title="Switch workspace">
        <i class="{{ $wsNow['icon'] ?? 'ri-apps-2-line' }} fs-5"></i>
        <span class="d-none d-md-inline text-start lh-sm">
            <span class="d-block fw-semibold small text-truncate" style="max-width:180px">{{ $wsNow['label'] ?? 'Switch workspace' }}</span>
            <span class="d-block text-muted" style="font-size:10px">{{ $wsNow ? \Illuminate\Support\Str::before($wsNow['sub'], ' ·') : 'Choose where to work' }}</span>
        </span>
        <i class="ri-arrow-down-s-line"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end p-1" style="min-width:290px">
        <div class="px-3 py-2 small text-muted text-uppercase fw-semibold" style="font-size:10px">Switch workspace</div>
        @foreach($wsList as $w)
            <form method="POST" action="{{ route('workspace.switch') }}" class="m-0">@csrf
                <input type="hidden" name="key" value="{{ $w['key'] }}">
                <button class="dropdown-item d-flex align-items-center gap-2 rounded py-2 {{ $w['key'] === $wsKey ? 'active' : '' }}">
                    <i class="{{ $w['icon'] }} fs-5"></i>
                    <span class="lh-sm text-start"><span class="d-block fw-semibold">{{ $w['label'] }}</span><span class="d-block small {{ $w['key'] === $wsKey ? '' : 'text-muted' }}">{{ $w['sub'] }}</span></span>
                    @if($w['key'] === $wsKey)<i class="ri-check-line ms-auto"></i>@endif
                </button>
            </form>
        @endforeach
    </div>
</div>
@endif
@endauth
