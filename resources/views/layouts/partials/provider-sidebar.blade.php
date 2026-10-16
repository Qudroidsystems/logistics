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
                @if($nav['requests'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.pricing*') ? 'active' : '' }}" href="{{ route('provider.pricing') }}"><i class="ri-calculator-line"></i> <span>Pricing calculator</span></a></li>@endif
                @if($nav['jobs'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.verification*') ? 'active' : '' }}" href="{{ route('provider.verification') }}"><i class="ri-shield-check-line"></i> <span>Delivery checks</span></a></li>@endif
                @if($nav['jobs'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.alerts*') ? 'active' : '' }}" href="{{ route('provider.alerts') }}"><i class="ri-notification-3-line"></i> <span>Arrival alerts</span></a></li>@endif
                @if($nav['jobs'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.dispatch') ? 'active' : '' }}" href="{{ route('provider.dispatch') }}"><i class="ri-layout-grid-line"></i> <span>Dispatch board</span></a></li>@endif
                @if($nav['jobs'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.booking*') ? 'active' : '' }}" href="{{ route('provider.booking.new') }}"><i class="ri-add-box-line"></i> <span>New booking</span></a></li>@endif
                @if($nav['jobs'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.bulk*') ? 'active' : '' }}" href="{{ route('provider.bulk') }}"><i class="ri-file-list-3-line"></i> <span>Bulk booking</span></a></li>@endif
                @if($nav['jobs'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.job*') ? 'active' : '' }}" href="{{ route('provider.jobs') }}"><i class="ri-route-line"></i> <span>Jobs</span></a></li>@endif
                @if($nav['jobs'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.points*') ? 'active' : '' }}" href="{{ route('provider.points') }}"><i class="ri-store-2-line"></i> <span>Pickup points</span></a></li>@endif
                @if(($nav['jobs'] ?? false) || ($nav['money'] ?? false) || ($nav['team'] ?? false))<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.reports*') ? 'active' : '' }}" href="{{ route('provider.reports') }}"><i class="ri-bar-chart-box-line"></i> <span>Reports</span></a></li>@endif
                @if($nav['money'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.accounts*') ? 'active' : '' }}" href="{{ route('provider.accounts') }}"><i class="ri-briefcase-4-line"></i> <span>Business accounts</span></a></li>@endif
                @if($nav['money'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.promos*') ? 'active' : '' }}" href="{{ route('provider.promos') }}"><i class="ri-coupon-3-line"></i> <span>Promo codes</span></a></li>@endif
                @if($nav['money'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.cash*') ? 'active' : '' }}" href="{{ route('provider.cash') }}"><i class="ri-hand-coin-line"></i> <span>Cash on delivery</span></a></li>@endif
                @if($nav['money'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.accounting*') ? 'active' : '' }}" href="{{ route('provider.accounting') }}"><i class="ri-book-2-line"></i> <span>Accounting</span></a></li>@endif
                @if($nav['marketing'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.campaigns*') ? 'active' : '' }}" href="{{ route('provider.campaigns') }}"><i class="ri-megaphone-line"></i> <span>Campaigns and reviews</span></a></li>@endif
                @if($nav['money'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.wallet') ? 'active' : '' }}" href="{{ route('provider.wallet') }}"><i class="ri-wallet-3-line"></i> <span>Wallet and payouts</span></a></li>@endif
                @if($nav['team'] ?? false)<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.team*') ? 'active' : '' }}" href="{{ route('provider.team') }}"><i class="ri-team-line"></i> <span>Team</span></a></li>@endif
                @if(Route::has('provider.onboarding'))<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('provider.onboarding*') || request()->routeIs('provider.profile*') ? 'active' : '' }}" href="{{ route('provider.onboarding') }}"><i class="ri-settings-3-line"></i> <span>Profile and setup</span></a></li>@endif
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.me*') ? 'active' : '' }}" href="{{ route('account.me') }}"><i class="ri-user-settings-line"></i> <span>My account</span></a></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.phone*') ? 'active' : '' }}" href="{{ route('account.phone') }}"><i class="ri-smartphone-line"></i> <span>Phone number</span></a></li>
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
