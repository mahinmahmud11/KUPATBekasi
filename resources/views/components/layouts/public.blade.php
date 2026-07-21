@props(['title' => null, 'description' => null])

@php
    $siteName = $siteSetting?->site_name ?: config('app.name');
@endphp

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ? $title.' | '.$siteName : $siteName }}</title>
        <meta property="og:title" content="{{ $title ? $title.' | '.$siteName : $siteName }}">
        <link rel="canonical" href="{{ url()->current() }}">
        @if ($description)
            <meta name="description" content="{{ $description }}">
            <meta property="og:description" content="{{ $description }}">
        @endif
        @if ($siteSetting?->favicon_path)
            <link rel="icon" href="{{ Storage::disk('public')->url($siteSetting->favicon_path) }}">
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-gray-50 text-gray-900 antialiased [&>footer]:border-t [&>footer]:border-gray-200 [&>footer]:bg-white [&>header]:border-b [&>header]:border-gray-200 [&>header]:bg-white [&>main]:flex-1">
        <header>
            <div class="mx-auto flex min-h-16 max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900" href="{{ route('home') }}">
                    @if ($siteSetting?->logo_path)
                        <img class="h-12 w-48 object-cover object-center sm:w-56" data-site-logo src="{{ Storage::disk('public')->url($siteSetting->logo_path) }}" alt="{{ $siteName }}">
                    @else
                        <span class="text-lg font-semibold">{{ $siteName }}</span>
                    @endif
                </a>

                <nav aria-label="Navigasi utama">
                    <ul class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-medium">
                        @foreach ([
                            'home' => 'Beranda',
                            'products.index' => 'Produk',
                            'partners.index' => 'Mitra',
                            'about' => 'Tentang',
                            'contact' => 'Kontak',
                        ] as $routeName => $label)
                            <li>
                                <a @class([
                                    'rounded-sm py-2 hover:text-gray-600 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900',
                                    'font-bold underline underline-offset-8' => request()->routeIs($routeName),
                                ]) href="{{ route($routeName) }}">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer>
            <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 text-sm sm:px-6 md:grid-cols-2 lg:px-8">
                <div>
                    <p class="font-semibold">{{ $siteName }}</p>
                    @if ($siteSetting?->tagline)
                        <p class="mt-1 text-gray-600">{{ $siteSetting->tagline }}</p>
                    @endif
                    <p class="mt-3 text-gray-600">Transaksi dilakukan langsung antara pengunjung dan UMKM.</p>
                    <p class="mt-1 text-gray-500">&copy; {{ date('Y') }} {{ $siteName }}</p>
                </div>
                <nav aria-label="Navigasi footer" class="md:justify-self-end">
                    <ul class="flex flex-wrap gap-4">
                        <li><a class="hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900" href="{{ route('about') }}">Tentang</a></li>
                        <li><a class="hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900" href="{{ route('contact') }}">Kontak</a></li>
                        <li><a class="hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900" href="{{ route('privacy') }}">Kebijakan Privasi</a></li>
                    </ul>
                </nav>
            </div>
        </footer>
    </body>
</html>
