<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin')</title>

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


    @stack('styles')
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Crypto Krot</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin"
                aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('crypto-requests.*')) active @endif"
                       href="{{ route('crypto-requests.index') }}">Заявки</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('crypto-trades.*')) active @endif"
                       href="{{ route('crypto-trades.index') }}">Сделки</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(request()->routeIs('cash-desks.*')) active @endif"
                       href="{{ route('cash-desks.index') }}">Кассы</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle @if(
                        request()->routeIs('statuses.*')
                        || request()->routeIs('partners.*')
                        || request()->routeIs('sources.*')
                        || request()->routeIs('currency-exchanges.*')
                    ) active @endif"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Справочники
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('currency-exchanges.index') }}">Биржи</a></li>
                        <li><a class="dropdown-item" href="{{ route('sources.index') }}">Источники</a></li>
                        <li><a class="dropdown-item" href="{{ route('partners.index') }}">Партнёры</a></li>
                        <li><a class="dropdown-item" href="{{ route('statuses.index') }}">Статусы заявок</a></li>
                    </ul>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
    <span class="navbar-text text-white-50">
        {{ auth()->user()->name ?? 'Admin' }}
    </span>

                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-light">Выйти</button>
                    </form>
                @endauth
            </div>

        </div>
    </div>
</nav>

<main>
    @yield('content')
</main>

<footer class="border-top mt-5 py-3">
    <div class="container text-muted small">
        © {{ date('Y') }} Crypto Krot
    </div>
</footer>

{{-- jQuery (для твоих модалок/обработчиков) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

{{-- Bootstrap JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')
</body>
</html>
