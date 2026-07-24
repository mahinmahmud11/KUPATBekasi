@props(['title' => null, 'description' => null, 'image' => null, 'type' => 'website'])

@php
    $siteName = $siteSetting?->site_name ?: config('app.name');
    $metadataTitle = $title ? $title.' | '.$siteName : $siteName;
    $canonicalUrl = url()->current();
    $metadataDescription = preg_replace('/\s+/', ' ', strip_tags((string) ($description ?: $siteSetting?->about_summary ?: $siteSetting?->tagline)));
    $metadataDescription = $metadataDescription ? Str::limit(trim($metadataDescription), 160, '') : null;
    $metadataImage = $image ?: ($siteSetting?->logo_path ? Storage::disk('public')->url($siteSetting->logo_path) : null);

    if ($metadataImage && ! Str::startsWith($metadataImage, ['http://', 'https://'])) {
        $metadataImage = url($metadataImage);
    }
@endphp

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $metadataTitle }}</title>
        <meta property="og:title" content="{{ $metadataTitle }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:type" content="{{ $type }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $metadataTitle }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        @if ($metadataDescription)
            <meta name="description" content="{{ $metadataDescription }}">
            <meta property="og:description" content="{{ $metadataDescription }}">
            <meta name="twitter:description" content="{{ $metadataDescription }}">
        @endif
        @if ($metadataImage)
            <meta property="og:image" content="{{ $metadataImage }}">
            <meta name="twitter:image" content="{{ $metadataImage }}">
        @endif
        @if ($siteSetting?->favicon_path)
            <link rel="icon" href="{{ Storage::disk('public')->url($siteSetting->favicon_path) }}">
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-gray-50 text-gray-900 antialiased [&>footer]:border-t [&>footer]:border-gray-200 [&>footer]:bg-white [&>header]:border-b [&>header]:border-gray-200 [&>header]:bg-white [&>main]:flex-1">
        <header>
            <div class="mx-auto flex min-h-16 max-w-7xl flex-wrap items-center justify-between gap-x-2 gap-y-4 px-4 py-3 sm:gap-x-4 sm:px-6 lg:flex-nowrap lg:px-8">
                <a class="order-1 flex-shrink-0 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900" href="{{ route('home') }}">
                    @if ($siteSetting?->logo_path)
                        <img class="h-8 w-32 object-cover object-center sm:h-12 sm:w-48 lg:w-56" data-site-logo src="{{ Storage::disk('public')->url($siteSetting->logo_path) }}" alt="{{ $siteName }}">
                    @else
                        <span class="text-lg font-semibold">{{ $siteName }}</span>
                    @endif
                </a>

                <div class="order-2 flex flex-shrink-0 items-center gap-1.5 sm:gap-3 lg:order-3 lg:ml-4" data-government-brand>
                    <div class="hidden h-10 w-px bg-gray-300 lg:block" aria-hidden="true"></div>
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <div class="flex max-w-[115px] flex-col text-right sm:max-w-[200px] lg:max-w-none" data-government-agency>
                            <span class="text-[9px] leading-none text-gray-500 sm:text-[10px]">Didukung oleh</span>
                            <span class="mt-0.5 text-[9px] font-bold leading-tight text-gray-800 sm:text-xs lg:whitespace-nowrap">Dinas Koperasi Usaha Kecil dan Menengah<br>Pemerintah Kota Bekasi</span>
                        </div>
                        <img class="h-9 w-auto object-contain sm:h-12 lg:h-14 xl:h-16" data-government-logo src="{{ asset('img/logo-kota-bekasi.png') }}" alt="Logo Kota Bekasi">
                        <div class="h-6 w-px bg-gray-200 sm:h-8 lg:h-10" aria-hidden="true"></div>
                        <img class="h-9 w-auto object-contain sm:h-12 lg:h-14 xl:h-16" data-dekranasda-logo src="{{ asset('img/logo-dekranasda-kota-bekasi.png') }}" alt="Logo DEKRANASDA Kota Bekasi">
                    </div>
                </div>

                <nav aria-label="Navigasi utama" class="order-3 w-full border-t border-gray-100 pt-3 lg:order-2 lg:flex lg:w-auto lg:flex-1 lg:justify-end lg:border-none lg:pt-0">
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
