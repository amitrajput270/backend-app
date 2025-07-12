<head>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
@livewireStyles
</head>
<body>
    {{ $slot }}
    @livewireScripts
    <script src="{{ asset('js/app.js') }}"></script>
</body>
