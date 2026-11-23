<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@hasSection('title')@yield('title')@else{{ $title ?? config('app.name') }}@endif</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:shadow-lg dark:focus:bg-zinc-900">Saltar al contenido</a>

    @auth
        <header class="sticky top-0 z-20 border-b border-zinc-200/80 bg-white/80 backdrop-blur dark:border-zinc-800/80 dark:bg-zinc-950/80">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
                <nav class="flex items-center gap-1" aria-label="Principal">
                    <a href="{{ route('pipeline') }}" class="mr-3 flex items-center gap-2 rounded-md font-semibold tracking-tight">
                        <span aria-hidden="true" class="size-2.5 rounded-sm bg-accent-600 dark:bg-accent-400"></span>{{ config('app.name') }}
                    </a>
                    <a href="{{ route('pipeline') }}" @if (request()->routeIs('pipeline')) aria-current="page" @endif
                       class="rounded-md px-2.5 py-1.5 text-sm text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 aria-[current=page]:bg-zinc-100 aria-[current=page]:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 dark:aria-[current=page]:bg-zinc-800 dark:aria-[current=page]:text-zinc-100">Pipeline</a>
                    <a href="{{ route('leads.index') }}" @if (request()->routeIs('leads.*')) aria-current="page" @endif
                       class="rounded-md px-2.5 py-1.5 text-sm text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 aria-[current=page]:bg-zinc-100 aria-[current=page]:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 dark:aria-[current=page]:bg-zinc-800 dark:aria-[current=page]:text-zinc-100">Leads</a>
                </nav>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md px-2.5 py-1.5 text-sm text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">Cerrar sesión</button>
                </form>
            </div>
        </header>
    @endauth

    <main id="contenido" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>
</body>
</html>
