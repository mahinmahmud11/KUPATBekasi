@props(['title' => null])

@php
    $siteName = $siteSetting?->site_name ?: config('app.name');
@endphp

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ? $title.' | '.$siteName : $siteName }}</title>
        @if ($siteSetting?->favicon_path)
            <link rel="icon" href="{{ Storage::disk('public')->url($siteSetting->favicon_path) }}">
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-gray-50 text-gray-900 antialiased [&>footer]:border-t [&>footer]:border-gray-200 [&>footer]:bg-white [&>header]:border-b [&>header]:border-gray-200 [&>header]:bg-white [&>main]:flex-1">
        <header>
            <div class="mx-auto flex min-h-16 max-w-7xl items-center px-4 py-3 sm:px-6 lg:px-8">
                @if ($siteSetting?->logo_path)
                    <img class="h-12 w-48 object-cover object-center sm:w-56" data-site-logo src="{{ Storage::disk('public')->url($siteSetting->logo_path) }}" alt="{{ $siteName }}">
                @else
                    <p class="text-lg font-semibold">{{ $siteName }}</p>
                @endif
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer>
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <p class="font-semibold">{{ $siteName }}</p>
                @if ($siteSetting?->tagline)
                    <p class="mt-1 text-sm text-gray-600">{{ $siteSetting->tagline }}</p>
                @endif
            </div>
        </footer>
    </body>
</html>
