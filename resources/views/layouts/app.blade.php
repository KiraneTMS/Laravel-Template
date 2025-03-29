<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $webProperty->webname ?? 'TradeFlow' }}</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary-color: {{ $webProperty->color_scheme[0] ?? '#007bff' }};
            --secondary-color: {{ $webProperty->color_scheme[1] ?? '#343a40' }};
            --background-color: {{ $webProperty->color_scheme[2] ?? '#f8f9fa' }};
            --hover-color: {{ $webProperty->color_scheme[3] ?? '#0056b3' }};
            --danger-color: {{ $webProperty->color_scheme[4] ?? '#dc3545' }};
            --secondary-hover-color: {{ $webProperty->color_scheme[5] ?? '#ffc107' }};
            --success-color: {{ $webProperty->color_scheme[6] ?? '#28a745' }};
            --info-color: {{ $webProperty->color_scheme[7] ?? '#17a2b8' }};
        }
    </style>
    @yield('styles')
</head>

<body>
    <div id="app">
        <nav class="navbar navbar-expand-md">
            <div class="navbar-container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="{{ $webProperty->icon ?? '' }}" alt="Icon" class="navbar-brand-icon">
                    {{ $webProperty->webname ?? 'TradeFlow' }}
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent"
                    aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav">
                        {{-- @auth
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('buyers.index') }}">Buyers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('suppliers.index') }}">Suppliers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('sales.index') }}>Sales</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('purchases.index') }}">Purchases</a>
                            </li>
                        @endauth --}}
                    </ul>
                    <div class="navbar-auth">
                        @guest
                            @if (request()->is('login') || request()->route()->named('login'))
                            @else
                                <a href="{{ route('login') }}" class="btn btn-login">Login</a>
                            @endif
                        @else
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-logout">Logout</button>
                            </form>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <main class="main-content">

        @if (session('success'))
            <div class="alert alert-success" id="success-alert">
                {{ session('success') }}
                <button type="button" class="alert-close"
                    onclick="this.parentElement.classList.add('hidden')">×</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" id="error-alert">
                <strong>Error!</strong> Please fix the following issues:
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="alert-close"
                    onclick="this.parentElement.classList.add('hidden')">×</button>
            </div>
        @elseif (session('error'))
            <div class="alert alert-danger" id="error-alert">
                {{ session('error') }}
                <button type="button" class="alert-close"
                    onclick="this.parentElement.classList.add('hidden')">×</button>
            </div>
        @endif
        @yield('content')
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>
