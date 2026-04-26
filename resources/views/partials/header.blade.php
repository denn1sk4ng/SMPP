<header class="topbar">
    <a href="{{ route('home') }}" class="logo-box logo-link">
        <span class="logo-text">SMPP</span>
    </a>

    <nav class="topnav">
        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
            <span class="icon-wrap">
                <img src="{{ asset('icons/home_icon_white.svg') }}" class="tab-icon icon-white" alt="">
                <img src="{{ asset('icons/home_icon_black.svg') }}" class="tab-icon icon-black" alt="">
            </span>
            Home
        </a>

        @auth
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="icon-wrap">
                    <img src="{{ asset('icons/dashboard_icon_white.svg') }}" class="tab-icon icon-white" alt="">
                    <img src="{{ asset('icons/dashboard_icon_black.svg') }}" class="tab-icon icon-black" alt="">
                </span>
                Dashboard
            </a>

            <a href="{{ route('datasets.create') }}" class="nav-link {{ request()->routeIs('datasets.*') ? 'active' : '' }}">
                <span class="icon-wrap">
                    <img src="{{ asset('icons/upload_dataset_white.svg') }}" class="tab-icon icon-white" alt="">
                    <img src="{{ asset('icons/upload_dataset_black.svg') }}" class="tab-icon icon-black" alt="">
                </span>
                Upload Dataset
            </a>

            <a href="{{ route('models.index') }}" class="nav-link {{ request()->routeIs('models.*') ? 'active' : '' }}">
                <span class="icon-wrap">
                    <img src="{{ asset('icons/microchip_white.svg') }}" class="tab-icon icon-white" alt="">
                    <img src="{{ asset('icons/microchip_black.svg') }}" class="tab-icon icon-black" alt="">
                </span>
                AI Models
            </a>

            <a href="{{ route('predictions.index') }}" class="nav-link {{ request()->routeIs('predictions.*') ? 'active' : '' }}">
                <span class="icon-wrap">
                    <img src="{{ asset('icons/predicted_results_white.svg') }}" class="tab-icon icon-white" alt="">
                    <img src="{{ asset('icons/predicted_results_black.svg') }}" class="tab-icon icon-black" alt="">
                </span>
                Predictions
            </a>

            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="nav-link logout-btn">
                    <span class="icon-wrap">
                        <img src="{{ asset('icons/logout_white.svg') }}" class="tab-icon icon-white" alt="">
                        <img src="{{ asset('icons/logout_black.svg') }}" class="tab-icon icon-black" alt="">
                    </span>
                    Logout
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
                <img src="{{ asset('icons/login_icon_white.svg') }}" class="tab-icon icon-white" alt="">
                Login
            </a>

            <a href="{{ route('register') }}" class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}">
                <img src="{{ asset('icons/register_icon_white.svg') }}" class="tab-icon icon-white" alt="">
                Register
            </a>
        @endauth
    </nav>
</header>