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
    <body>
        <header>
            @if ($siteSetting?->logo_path)
                <img data-site-logo src="{{ Storage::disk('public')->url($siteSetting->logo_path) }}" alt="{{ $siteName }}">
            @else
                <p>{{ $siteName }}</p>
            @endif
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer>
            <p>{{ $siteName }}</p>
            @if ($siteSetting?->tagline)
                <p>{{ $siteSetting->tagline }}</p>
            @endif
        </footer>
    </body>
</html>
