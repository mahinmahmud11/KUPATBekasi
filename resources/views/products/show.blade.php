<x-layouts.public :title="$product->name" :description="$product->short_description">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-2">
            <div>
                @if ($product->main_image_path)
                    <img class="aspect-[4/3] w-full rounded-xl object-cover" src="{{ Storage::disk('public')->url($product->main_image_path) }}" alt="{{ $product->name }}">
                @else
                    <div class="flex aspect-[4/3] items-center justify-center rounded-xl bg-gray-100 text-gray-500">Gambar belum tersedia</div>
                @endif

                @if ($product->images->isNotEmpty())
                    <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4">
                        @foreach ($product->images as $image)
                            <img class="aspect-square w-full rounded-lg object-cover" src="{{ Storage::disk('public')->url($image->image_path) }}" alt="{{ $image->alt_text ?: $product->name }}" loading="lazy">
                        @endforeach
                    </div>
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
