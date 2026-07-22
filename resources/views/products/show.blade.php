<x-layouts.public :title="$product->name" :description="$product->short_description">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
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

            <div>
                <p class="text-sm font-medium text-gray-600"><a class="hover:underline" href="{{ route('categories.show', $product->category) }}">{{ $product->category->name }}</a></p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ $product->name }}</h1>
                <p class="mt-4 text-2xl font-bold">Rp {{ number_format($product->price, 0, ',', '.') }}@if ($product->unit)<span class="text-base font-normal text-gray-500"> / {{ $product->unit }}</span>@endif</p>
                <p class="mt-3 text-gray-600">Status: {{ match ($product->stock_status) { 'available' => 'Tersedia', 'preorder' => 'Pre-order', 'unavailable' => 'Tidak tersedia', default => $product->stock_status } }}</p>
                @if ($product->description)
                    <p class="mt-6 whitespace-pre-line leading-7 text-gray-700">{{ $product->description }}</p>
                @elseif ($product->short_description)
                    <p class="mt-6 leading-7 text-gray-700">{{ $product->short_description }}</p>
                @endif

                <div class="mt-8 rounded-xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Mitra</p>
                    <h2 class="mt-1 text-xl font-semibold"><a class="hover:underline" href="{{ route('partners.show', $product->partner) }}">{{ $product->partner->name }}</a></h2>
                    @if ($product->partner->short_description)<p class="mt-2 text-gray-600">{{ $product->partner->short_description }}</p>@endif
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
</x-layouts.public>
