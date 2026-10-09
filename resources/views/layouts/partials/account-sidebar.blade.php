{{-- Sidebar for the customer area. --}}
<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        <a href="{{ route('account.dashboard') }}" class="logo logo-dark"><span class="logo-lg fw-bold fs-5 text-white">{{ config('app.name') }}</span></a>
    </div>
    <div id="scrollbar">
        <div class="container-fluid">
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>MY ACCOUNT</span></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.dashboard') ? 'active' : '' }}" href="{{ route('account.dashboard') }}"><i class="ri-dashboard-2-line"></i> <span>Home</span></a></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.request.new') ? 'active' : '' }}" href="{{ route('account.request.new') }}"><i class="ri-add-circle-line"></i> <span>Ask for a delivery</span></a></li>
                @if(\App\Support\Platform::marketplace())<li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.providers') ? 'active' : '' }}" href="{{ route('account.providers') }}"><i class="ri-store-2-line"></i> <span>Find a provider</span></a></li>@endif
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.request', 'account.requests', 'account.thread') ? 'active' : '' }}" href="{{ route('account.requests') }}"><i class="ri-chat-quote-line"></i> <span>My requests</span></a></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.order*', 'account.pay') ? 'active' : '' }}" href="{{ route('account.orders') }}"><i class="ri-shopping-bag-3-line"></i> <span>My orders</span></a></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.wallet') ? 'active' : '' }}" href="{{ route('account.wallet') }}"><i class="ri-wallet-3-line"></i> <span>Wallet</span></a></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.me*') ? 'active' : '' }}" href="{{ route('account.me') }}"><i class="ri-user-settings-line"></i> <span>My account</span></a></li>
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('account.phone*') ? 'active' : '' }}" href="{{ route('account.phone') }}"><i class="ri-smartphone-line"></i> <span>Phone number</span></a></li>
                @if(\App\Support\Platform::marketplace() && \Illuminate\Support\Facades\Route::has('provider.start'))<li class="nav-item"><a class="nav-link menu-link" href="{{ route('provider.start') }}"><i class="ri-truck-line"></i> <span>Become a provider</span></a></li>@endif
            </ul>
        </div>
    </div>
    @auth
    <div class="sidebar-footer">
        <div class="sidebar-footer-user"><div class="sidebar-footer-user-info"><div class="sidebar-footer-user-name">{{ auth()->user()->name }}</div><div class="sidebar-footer-user-role">Customer</div></div></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="sidebar-logout-btn"><i class="mdi mdi-logout"></i> <span>Sign Out</span></button></form>
    </div>
    @endauth
</div>
