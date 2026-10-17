<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Angkringan') | Loyalty Console</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-base.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-visit-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-usermenu.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ang-dropdown.css') }}">
    @stack('ang-styles')
</head>
<body class="ang-app">
<div class="ang-shell">
    <input type="checkbox" id="ang-nav-toggle" class="ang-nav-toggle" aria-label="Buka menu">
    @include('partials.ang-sidebar')
    <label for="ang-nav-toggle" class="ang-scrim"></label>

    <div class="ang-main">
        @yield('content')
    </div>
</div>
@include('partials.ang-customer-modal')
<script src="{{ asset('assets/js/ang-modal.js') }}"></script>
<script src="{{ asset('assets/js/ang-customer-modal.js') }}"></script>
<script src="{{ asset('assets/js/ang-visit-modal.js') }}"></script>
<script src="{{ asset('assets/js/ang-usermenu.js') }}"></script>
<script src="{{ asset('assets/js/ang-dropdown.js') }}"></script>
@stack('ang-scripts')
</body>
</html>
