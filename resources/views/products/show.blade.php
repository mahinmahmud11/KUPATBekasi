@php
    $productMetadataImagePath = $product->main_image_path ?: $product->images->first()?->image_path;
    $productMetadataImage = $productMetadataImagePath ? Storage::disk('public')->url($productMetadataImagePath) : null;
@endphp

<x-layouts.public :title="$product->name" :description="$product->short_description ?: $product->description" :image="$productMetadataImage" type="product">
    <div @class([
        'mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8',
        'pb-28 sm:pb-10' => $whatsappUrl,
    ])>
        <nav class="mb-6 min-w-0" aria-label="Breadcrumb">
            <ol class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600">
                <li><a class="rounded hover:text-gray-900 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ route('home') }}">Beranda</a></li>
                <li aria-hidden="true">/</li>
                <li><a class="rounded hover:text-gray-900 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ route('products.index') }}">Produk</a></li>
                <li aria-hidden="true">/</li>
                <li class="min-w-0 break-words font-medium text-gray-900" aria-current="page">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="grid gap-8 lg:grid-cols-2">
            <div>
                @php
                    $galleryMedia = collect();

                    if ($product->main_image_path) {
                        $galleryMedia->push([
                            'path' => $product->main_image_path,
                            'src' => Storage::disk('public')->url($product->main_image_path),
                            'alt' => $product->name,
                        ]);
                    }

                    foreach ($product->images as $image) {
                        if ($galleryMedia->contains('path', $image->image_path)) {
                            continue;
                        }

                        $galleryMedia->push([
                            'path' => $image->image_path,
                            'src' => Storage::disk('public')->url($image->image_path),
                            'alt' => $image->alt_text ?: $product->name,
                        ]);
                    }

                    $activeMedia = $galleryMedia->first();
                @endphp

                @if ($activeMedia)
                    <div data-product-gallery>
                        <div class="relative">
                            <button class="block w-full rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" type="button" data-gallery-preview-open aria-label="Perbesar gambar {{ $activeMedia['alt'] }}">
                                <img class="aspect-[4/3] w-full rounded-xl object-cover" src="{{ $activeMedia['src'] }}" alt="{{ $activeMedia['alt'] }}" data-gallery-active-image>
                            </button>

                            @if ($galleryMedia->count() > 1)
                                <button class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 px-3 py-2 text-sm font-semibold shadow hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" type="button" data-gallery-previous aria-label="Tampilkan gambar sebelumnya">Sebelumnya</button>
                                <button class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 px-3 py-2 text-sm font-semibold shadow hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" type="button" data-gallery-next aria-label="Tampilkan gambar berikutnya">Berikutnya</button>
                            @endif
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3" aria-label="Pilihan gambar produk">
                            @foreach ($galleryMedia as $index => $media)
                                <button @class([
                                    'w-20 rounded-lg border-2 bg-white p-1 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900 sm:w-24',
                                    'border-gray-900 ring-2 ring-gray-300' => $index === 0,
                                    'border-transparent' => $index !== 0,
                                ]) type="button" data-gallery-thumbnail data-gallery-index="{{ $index }}" data-gallery-src="{{ $media['src'] }}" data-gallery-alt="{{ $media['alt'] }}" aria-label="Tampilkan gambar {{ $index + 1 }}: {{ $media['alt'] }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}">
                                    <img class="aspect-square w-full rounded-md object-cover" src="{{ $media['src'] }}" alt="{{ $media['alt'] }}" loading="lazy">
                                </button>
                            @endforeach
                        </div>

                        <div class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-950/80 p-4" data-gallery-dialog role="dialog" aria-modal="true" aria-label="Preview gambar produk">
                            <div class="relative max-h-full w-full max-w-5xl rounded-xl bg-white p-4 shadow-xl">
                                <button class="absolute right-6 top-6 rounded-lg bg-white px-4 py-2 font-semibold shadow focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" type="button" data-gallery-preview-close>Tutup</button>
                                <img class="max-h-[85vh] w-full rounded-lg object-contain" src="{{ $activeMedia['src'] }}" alt="{{ $activeMedia['alt'] }}" data-gallery-preview-image>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex aspect-[4/3] items-center justify-center rounded-xl bg-gray-100 text-gray-500">Gambar belum tersedia</div>
                @endif
            </div>

            <div class="min-w-0">
                @php
                    $stockStatusLabel = match ($product->stock_status) {
                        'available' => 'Tersedia',
                        'preorder' => 'Pre-order',
                        'unavailable' => 'Tidak tersedia',
                        default => $product->stock_status,
                    };

                    $stockStatusClasses = match ($product->stock_status) {
                        'available' => 'bg-emerald-100 text-emerald-900 ring-emerald-700/20',
                        'preorder' => 'bg-amber-100 text-amber-900 ring-amber-700/20',
                        'unavailable' => 'bg-red-100 text-red-900 ring-red-700/20',
                        default => 'bg-gray-100 text-gray-900 ring-gray-700/20',
                    };
                @endphp

                <div class="flex flex-wrap items-center gap-2">
                    <a class="inline-flex max-w-full break-words rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-800 ring-1 ring-inset ring-gray-300 hover:bg-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ route('categories.show', $product->category) }}" data-product-category-badge>{{ $product->category->name }}</a>
                    <span class="inline-flex max-w-full break-words rounded-full px-3 py-1 text-sm font-semibold ring-1 ring-inset {{ $stockStatusClasses }}" data-product-stock-badge><span class="sr-only">Status stok: </span>{{ $stockStatusLabel }}</span>
                </div>

                <h1 class="mt-4 break-words text-3xl font-bold tracking-tight sm:text-4xl">{{ $product->name }}</h1>
                <p class="mt-5 break-words text-3xl font-bold tracking-tight text-gray-950 sm:text-4xl">Rp {{ number_format($product->price, 0, ',', '.') }}@if ($product->unit)<span class="ml-1 text-base font-medium text-gray-500 sm:text-lg">/ {{ $product->unit }}</span>@endif</p>

                @if ($product->description || $product->short_description)
                    <section class="mt-7" aria-labelledby="product-description-heading">
                        <h2 class="text-lg font-semibold" id="product-description-heading">Deskripsi Produk</h2>
                        <p class="mt-2 break-words whitespace-pre-line leading-7 text-gray-700">{{ $product->description ?: $product->short_description }}</p>
                    </section>
                @endif

                <div class="mt-8 rounded-xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Mitra</p>
                    <h2 class="mt-1 break-words text-xl font-semibold"><a class="rounded hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ route('partners.show', $product->partner) }}">{{ $product->partner->name }}</a></h2>
                    @if ($product->partner->short_description)<p class="mt-2 break-words text-gray-600">{{ $product->partner->short_description }}</p>@endif
                </div>

                @if ($whatsappUrl)
                    <a class="mt-6 inline-flex rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ $whatsappUrl }}" rel="noopener noreferrer" target="_blank">Hubungi via WhatsApp</a>
                @endif
            </div>
        </div>

        <section class="mt-14">
            <h2 class="text-2xl font-bold">Produk Terkait</h2>
            @if ($relatedProducts->isNotEmpty())
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relatedProducts as $relatedProduct)
                        <x-ui.product-card :product="$relatedProduct" />
                    @endforeach
                </div>
            @else
                <x-ui.empty-state class="mt-6" message="Produk terkait belum tersedia." />
            @endif
        </section>
    </div>

    @if ($whatsappUrl)
        <div class="fixed inset-x-4 bottom-4 z-40 sm:hidden" data-mobile-whatsapp-cta>
            <a class="flex w-full justify-center rounded-xl bg-gray-900 px-5 py-3 font-semibold text-white shadow-lg ring-1 ring-white/20 hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ $whatsappUrl }}" rel="noopener noreferrer" target="_blank">Tanya Produk via WhatsApp</a>
        </div>
    @endif
</x-layouts.public>
