{{-- Sidebar for the provider workspace. Items show or hide by team role (set by WorkspaceController). --}}
<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        <a href="{{ route('provider.dashboard') }}" class="logo logo-dark"><span class="logo-lg fw-bold fs-5 text-white">{{ config('app.name') }}</span></a>
    </div>
    <div id="scrollbar">
        <div class="container-fluid">
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>MY BUSINESS</span></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.dashboard') ? 'active' : '' }}" href="{{ route('provider.dashboard') }}"><i class="ri-dashboard-2-line"></i> <span>Dashboard</span></a></li>
                @if($nav['requests'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.request*') || request()->routeIs('provider.thread*') ? 'active' : '' }}" href="{{ route('provider.requests') }}"><i class="ri-inbox-line"></i> <span>Requests</span></a></li>@endif
                @if($nav['jobs'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.job*') ? 'active' : '' }}" href="{{ route('provider.jobs') }}"><i class="ri-route-line"></i> <span>Jobs</span></a></li>@endif
                @if($nav['money'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.wallet') ? 'active' : '' }}" href="{{ route('provider.wallet') }}"><i class="ri-wallet-3-line"></i> <span>Wallet and payouts</span></a></li>@endif
                @if($nav['team'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.team*') ? 'active' : '' }}" href="{{ route('provider.team') }}"><i class="ri-team-line"></i> <span>Team</span></a></li>@endif
                @if(Route::has('provider.onboarding'))<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.onboarding*') || request()->routeIs('provider.profile*') ? 'active' : '' }}" href="{{ route('provider.onboarding') }}"><i class="ri-settings-3-line"></i> <span>Profile and setup</span></a></li>@endif
            </ul>
        </div>
    </div>
    @auth
    <div class="sidebar-footer">
        <div class="sidebar-footer-user">
            <div class="sidebar-footer-user-info">
                <div class="sidebar-footer-user-name">{{ auth()->user()->name }}</div>
                <div class="sidebar-footer-user-role">{{ ucwords(str_replace('_', ' ', $role ?? 'member')) }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-logout-btn"><i class="mdi mdi-logout"></i> <span>Sign Out</span></button></form>
    </div>
    @endauth
</div>
