<x-layouts.public title="Beranda" description="Temukan produk dan profil UMKM binaan Kota Bekasi melalui katalog digital KUPATBekasi.">
    @if ($heroBanner)
        <section class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 sm:py-14 lg:grid-cols-2 lg:items-center lg:px-8">
            @if ($heroBanner->image_path)
                <img class="aspect-[16/9] w-full rounded-xl object-cover" src="{{ Storage::disk('public')->url($heroBanner->image_path) }}" alt="{{ $heroBanner->title }}">
            @endif

            <div>
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $heroBanner->title }}</h1>

                @if ($heroBanner->subtitle)
                    <p class="mt-4 text-base leading-7 text-gray-600 sm:text-lg">{{ $heroBanner->subtitle }}</p>
                @endif

                @if ($heroBanner->button_label && $heroBanner->button_url)
                    <a class="mt-6 inline-flex rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ $heroBanner->button_url }}">{{ $heroBanner->button_label }}</a>
                @endif
            </div>
        </section>
    @else
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 [&>h1]:text-3xl [&>h1]:font-bold [&>h1]:tracking-tight sm:[&>h1]:text-4xl">
            <h1>KUPATBekasi</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-gray-600 sm:text-lg">Halaman publik KUPATBekasi sedang disiapkan.</p>
        </section>
    @endif

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <form action="{{ route('products.index') }}" method="GET" class="flex flex-col gap-3 rounded-xl bg-white p-5 shadow-sm sm:flex-row">
            <label class="sr-only" for="home-search">Cari produk atau mitra</label>
            <input class="min-w-0 flex-1 rounded-lg border border-gray-300 px-4 py-3 focus:border-gray-900 focus:outline-none" id="home-search" name="q" placeholder="Cari produk atau mitra" type="search">
            <button class="rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" type="submit">Cari</button>
        </form>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold">Kategori</h2>
        @if ($categories->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <a class="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:border-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ route('categories.show', $category) }}">{{ $category->name }}</a>
                @endforeach
            </div>
        @else
            <x-ui.empty-state class="mt-6" message="Kategori belum tersedia." />
        @endif
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold">Produk Unggulan</h2>
        @if ($featuredProducts->isNotEmpty())
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    <x-ui.product-card :$product />
                @endforeach
            </div>
        @else
            <x-ui.empty-state class="mt-6" message="Produk unggulan belum tersedia." />
        @endif
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold">Mitra Unggulan</h2>
        @if ($featuredPartners->isNotEmpty())
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredPartners as $partner)
                    <x-ui.partner-card :$partner />
                @endforeach
            </div>
        @else
            <x-ui.empty-state class="mt-6" message="Mitra unggulan belum tersedia." />
        @endif
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold">Produk Terbaru</h2>
        @if ($latestProducts->isNotEmpty())
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latestProducts as $product)
                    <x-ui.product-card :$product />
                @endforeach
            </div>
        @else
            <x-ui.empty-state class="mt-6" message="Produk terbaru belum tersedia." />
        @endif
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-xl bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-2xl font-bold">Tentang Program</h2>
            @if ($aboutSummary)
                <p class="mt-4 max-w-3xl leading-7 text-gray-600">{{ $aboutSummary }}</p>
            @else
                <p class="mt-4 text-gray-600">Informasi program belum tersedia.</p>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 text-center sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold">Jelajahi Produk UMKM</h2>
        <a class="mt-5 inline-flex rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ route('products.index') }}">Lihat Katalog</a>
    </section>
</x-layouts.public>
