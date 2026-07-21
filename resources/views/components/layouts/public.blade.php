@props(['title' => null])

@php
    $applicationName = config('app.name');
@endphp

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ? $title.' | '.$applicationName : $applicationName }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header>
            <p>KUPATBekasi</p>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer>
            <p>KUPATBekasi</p>
        </footer>
    </body>
</html>
