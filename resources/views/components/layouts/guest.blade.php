<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased min-h-screen bg-surface-main flex items-center justify-center p-6">

    <div class="w-full max-w-sm">
        <div class="flex justify-center mb-8">
            <img src="{{ asset('images/logo_muulsinik.svg') }}" alt="Muulsinik ERP" class="object-contain" style="height: clamp(3rem, 5vw + 1.5rem, 5rem);">
        </div>

        {{ $slot }}
    </div>

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
</body>

</html>
