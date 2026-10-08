{{-- Sidebar for the merchant area. --}}
<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        <a href="{{ route('merchant.home') }}" class="logo logo-dark"><span class="logo-lg fw-bold fs-5 text-white">{{ config('app.name') }}</span></a>
    </div>
    <div id="scrollbar">
        <div class="container-fluid">
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>MY STORE</span></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('merchant.home') ? 'active' : '' }}" href="{{ route('merchant.home') }}"><i class="ri-dashboard-2-line"></i> <span>Overview</span></a></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('merchant.api') ? 'active' : '' }}" href="{{ route('merchant.api') }}"><i class="ri-key-2-line"></i> <span>API and webhooks</span></a></li>
                <li class="nav-item"><a class="nav-link menu-link" href="{{ route('developers.partner') }}" target="_blank" rel="noopener"><i class="ri-book-open-line"></i> <span>API reference</span></a></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.phone*') ? 'active' : '' }}" href="{{ route('account.phone') }}"><i class="ri-smartphone-line"></i> <span>Phone number</span></a></li>
            </ul>
        </div>
    </div>
    @auth
    <div class="sidebar-footer">
        <div class="sidebar-footer-user"><div class="sidebar-footer-user-info"><div class="sidebar-footer-user-name">{{ auth()->user()->name }}</div><div class="sidebar-footer-user-role">Merchant</div></div></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-logout-btn"><i class="mdi mdi-logout"></i> <span>Sign Out</span></button></form>
    </div>
    @endauth
</div>
