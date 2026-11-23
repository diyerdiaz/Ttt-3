<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title')@else{{ $title ?? config('app.name') }}@endif</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    @auth
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <nav class="flex items-center gap-4">
                    <a href="{{ route('pipeline') }}" class="font-semibold">{{ config('app.name') }}</a>
                    <a href="{{ route('pipeline') }}" class="text-sm text-gray-600 hover:text-gray-900">Pipeline</a>
                    <a href="{{ route('leads.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Leads</a>
                </nav>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">Cerrar sesión</button>
                </form>
            </div>
        </header>
    @endauth

    <main class="mx-auto max-w-6xl px-4 py-8">
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>
</body>
</html>
