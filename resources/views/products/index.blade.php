<x-layouts.public title="Produk" description="Katalog produk UMKM KUPATBekasi.">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight">Katalog Produk</h1>

        <form action="{{ route('products.index') }}" method="GET" class="mt-6 grid gap-4 rounded-xl bg-white p-5 shadow-sm md:grid-cols-[1fr_12rem_12rem_auto_auto] md:items-end">
            <div>
                <label class="block text-sm font-medium" for="q">Cari produk atau mitra</label>
                <input class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-gray-900 focus:outline-none" id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari produk atau nama UMKM">
            </div>
            <div>
                <label class="block text-sm font-medium" for="category">Kategori</label>
                <select class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-gray-900 focus:outline-none" id="category" name="category">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected($categorySlug === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium" for="partner">Mitra / UMKM</label>
                <select class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-gray-900 focus:outline-none" id="partner" name="partner">
                    <option value="">Semua mitra</option>
                    @foreach ($partners as $partner)
                        <option value="{{ $partner->slug }}" @selected($partnerSlug === $partner->slug)>{{ $partner->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" type="submit">Cari</button>
            @if ($search !== '' || $categorySlug !== '' || $partnerSlug !== '')
                <a class="rounded-lg border border-gray-300 px-5 py-3 text-center font-semibold hover:border-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ route('products.index') }}">Reset filter</a>
            @endif
        </form>

        @if ($products->isNotEmpty())
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-ui.product-card :$product />
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @else
            <x-ui.empty-state class="mt-8" message="Produk yang dicari belum tersedia." />
        @endif
    </div>
</x-layouts.public>
