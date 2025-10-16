<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title', 'RSS Feed App')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>

<body>
    @yield('content')
    {{ $slot ?? '' }}
    @livewireScripts
    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>