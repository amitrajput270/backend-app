<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'RSS Feed App')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/sweetalert-2.js') }}"></script>
    @livewireStyles
</head>

<body>
    @yield('content')
    {{ $slot ?? '' }}
    @livewireScripts
    @include('sweetalert2::index')
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>