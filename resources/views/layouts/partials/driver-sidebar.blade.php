{{-- Sidebar for the driver pages. --}}
<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        <a href="{{ route('driver.home') }}" class="logo logo-dark"><span class="logo-lg fw-bold fs-5 text-white">{{ config('app.name') }}</span></a>
    </div>
    <div id="scrollbar">
        <div class="container-fluid">
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>DRIVING</span></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('driver.home') || request()->routeIs('driver.job') ? 'active' : '' }}" href="{{ route('driver.home') }}"><i class="ri-steering-2-line"></i> <span>Today</span></a></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('driver.history') ? 'active' : '' }}" href="{{ route('driver.history') }}"><i class="ri-history-line"></i> <span>Past jobs</span></a></li>
            </ul>
        </div>
    </div>
    @auth
    <div class="sidebar-footer">
        <div class="sidebar-footer-user"><div class="sidebar-footer-user-info"><div class="sidebar-footer-user-name">{{ auth()->user()->name }}</div><div class="sidebar-footer-user-role">Driver</div></div></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-logout-btn"><i class="mdi mdi-logout"></i> <span>Sign Out</span></button></form>
    </div>
    @endauth
</div>
