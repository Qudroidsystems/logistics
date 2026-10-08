@if(request()->routeIs('provider.*'))
    @include('layouts.partials.provider-sidebar')
@elseif(request()->routeIs('account.*'))
    @include('layouts.partials.account-sidebar')
@elseif(request()->routeIs('driver.*'))
    @include('layouts.partials.driver-sidebar')
@else
{{-- Admin sidebar. Items are gated by permission and by module feature flags.
     New modules add their block here as they are built. --}}
<div class="app-menu navbar-menu">

    <div class="navbar-brand-box">
        <a href="{{ url('/') }}" class="logo logo-dark">
            <span class="logo-sm"><span class="fw-bold fs-4 text-white">{{ \Illuminate\Support\Str::substr(config('app.name'), 0, 1) }}</span></span>
            <span class="logo-lg"><span class="fw-bold fs-4 text-white">{{ config('app.name') }}</span></span>
        </a>
        <a href="{{ url('/') }}" class="logo logo-light">
            <span class="logo-sm"><span class="fw-bold fs-4 text-white">{{ \Illuminate\Support\Str::substr(config('app.name'), 0, 1) }}</span></span>
            <span class="logo-lg"><span class="fw-bold fs-4 text-white">{{ config('app.name') }}</span></span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-3xl header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">

                <li class="menu-title"><span>Menu</span></li>

                @can('dashboard')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="ri-dashboard-2-line"></i> <span>Dashboard</span>
                    </a>
                </li>
                @endcan

                {{-- ===== Operations (Phase 1+: deliveries, dispatch, tracking) ===== --}}
                @if(auth()->user()->hasAnyPermission(['dashboard','View delivery','View dispute','View kyc','View payment','View settlement','View rating','Manage fraud flags','View vendor','View driver','View shopper']))
                <li class="menu-title"><i class="ri-more-fill"></i> <span>OPERATIONS</span></li>
                @can('dashboard')<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.dashboard') ? 'active' : '' }}" href="{{ route('ops.dashboard') }}"><i class="ri-truck-line"></i> <span>Overview</span></a></li>@endcan
                @can('View delivery')<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.order*') ? 'active' : '' }}" href="{{ route('ops.orders') }}"><i class="ri-shopping-bag-3-line"></i> <span>Orders</span></a></li>@endcan
                @can('View dispute')<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.dispute*') ? 'active' : '' }}" href="{{ route('ops.disputes') }}"><i class="ri-scales-3-line"></i> <span>Disputes</span></a></li>@endcan
                @can('View kyc')<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.application*') ? 'active' : '' }}" href="{{ route('ops.applications') }}"><i class="ri-shield-user-line"></i> <span>Provider applications</span></a></li>@endcan
                @if(auth()->user()->hasAnyPermission(['View vendor','View driver','View shopper']))<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.provider*') ? 'active' : '' }}" href="{{ route('ops.providers') }}"><i class="ri-team-line"></i> <span>Providers</span></a></li>@endif
                @can('View settlement')<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.settlement*') ? 'active' : '' }}" href="{{ route('ops.settlements') }}"><i class="ri-bank-line"></i> <span>Settlements</span></a></li>@endcan
                @can('View payment')<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.refunds') ? 'active' : '' }}" href="{{ route('ops.refunds') }}"><i class="ri-refund-2-line"></i> <span>Refunds</span></a></li>@endcan
                @can('View rating')<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.rating*') ? 'active' : '' }}" href="{{ route('ops.ratings') }}"><i class="ri-star-line"></i> <span>Ratings</span></a></li>@endcan
                @if(auth()->user()->hasAnyPermission(['Manage commission','Manage pricing rules']))<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.settings*') ? 'active' : '' }}" href="{{ route('ops.settings') }}"><i class="ri-settings-4-line"></i> <span>Fees and settings</span></a></li>@endif
                @can('View zone')<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.cit*') ? 'active' : '' }}" href="{{ route('ops.cities') }}"><i class="ri-map-pin-2-line"></i> <span>Cities and zones</span></a></li>@endcan
                @can('Manage fraud flags')<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('ops.risk*') ? 'active' : '' }}" href="{{ route('ops.risk') }}"><i class="ri-alarm-warning-line"></i> <span>Risk events</span></a></li>@endcan
                @endif
                {{-- ===== Marketplace (Phase 2-4: vendors, drivers, shoppers, stores, business) ===== --}}
                {{-- ===== Finance (payments, wallets, settlements) ===== --}}

                {{-- ===== Users & Access ===== --}}
                @if(auth()->user()->can('View user') || auth()->user()->can('View role') || auth()->user()->can('View permission'))
                <li class="menu-title"><i class="ri-more-fill"></i> <span>USERS &amp; ACCESS</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAccess" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAccess">
                        <i class="ri-shield-user-line"></i> <span>Users &amp; Roles</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAccess">
                        <ul class="nav nav-sm flex-column">
                            @can('View user')<li class="nav-item"><a href="{{ route('users.index') }}" class="nav-link">Users</a></li>@endcan
                            @can('View role')<li class="nav-item"><a href="{{ route('roles.index') }}" class="nav-link">Roles</a></li>@endcan
                            @can('View permission')<li class="nav-item"><a href="{{ route('permissions.index') }}" class="nav-link">Permissions</a></li>@endcan
                        </ul>
                    </div>
                </li>
                @endif

                {{-- ===== Platform ===== --}}
                @if(auth()->user()->canAny(['Manage feature flags', 'Manage payment gateways', 'Manage maintenance mode', 'Manage backups', 'View activity log']))
                <li class="menu-title"><i class="ri-more-fill"></i> <span>PLATFORM</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarPlatform" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPlatform">
                        <i class="ri-settings-3-line"></i> <span>Platform Settings</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarPlatform">
                        <ul class="nav nav-sm flex-column">
                            @can('Manage feature flags')<li class="nav-item"><a href="{{ route('feature-flags.index') }}" class="nav-link">Module Access</a></li>@endcan
                            @can('Manage payment gateways')<li class="nav-item"><a href="{{ route('admin.payment-gateways.index') }}" class="nav-link">Payment Gateways</a></li>@endcan
                            @can('Manage maintenance mode')<li class="nav-item"><a href="{{ route('maintenance.settings') }}" class="nav-link">Maintenance Mode</a></li>@endcan
                            @can('Manage backups')<li class="nav-item"><a href="{{ route('admin.backups.index') }}" class="nav-link">Backups</a></li>@endcan
                            @can('View activity log')<li class="nav-item"><a href="{{ route('activity.index') }}" class="nav-link">Activity Log</a></li>@endcan
                        </ul>
                    </div>
                </li>
                @endif

            </ul>
        </div>
    </div>

    @auth
    <div class="sidebar-footer">
        @php $sidebarUser = auth()->user(); @endphp
        <div class="sidebar-footer-user">
            <img src="{{ $sidebarUser->avatar_url }}" alt="{{ $sidebarUser->name }}">
            <div class="sidebar-footer-user-info">
                <div class="sidebar-footer-user-name">{{ $sidebarUser->name }}</div>
                <div class="sidebar-footer-user-role">{{ $sidebarUser->roles->first()->name ?? 'User' }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form">
            @csrf
            <button type="submit" class="sidebar-logout-btn"><i class="mdi mdi-logout"></i> <span>Sign Out</span></button>
        </form>
    </div>
    @endauth

    <div class="sidebar-background"></div>
</div><!-- /app-menu -->
@endif
