<x-layouts.public :title="$partner->name" :description="$partner->short_description">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($partner->cover_path)
            <img class="aspect-[16/5] w-full rounded-xl object-cover" src="{{ Storage::disk('public')->url($partner->cover_path) }}" alt="Sampul {{ $partner->name }}">
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-[14rem_1fr]">
            <div>
                @if ($partner->logo_path)
                    <img class="h-40 w-40 rounded-xl bg-white object-contain p-3 shadow-sm" src="{{ Storage::disk('public')->url($partner->logo_path) }}" alt="Logo {{ $partner->name }}">
                @endif
                <p class="mt-4 inline-flex rounded-full bg-gray-200 px-3 py-1 text-sm font-medium">UMKM Binaan Kota Bekasi</p>
            </div>
            <div>
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $partner->name }}</h1>
                @if ($partner->description)
                    <p class="mt-5 whitespace-pre-line leading-7 text-gray-700">{{ $partner->description }}</p>
                @elseif ($partner->short_description)
                    <p class="mt-5 leading-7 text-gray-700">{{ $partner->short_description }}</p>
                @endif
                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    @if ($partner->district)<div><dt class="text-sm text-gray-500">Kecamatan</dt><dd>{{ $partner->district }}</dd></div>@endif
                    @if ($partner->address)<div><dt class="text-sm text-gray-500">Alamat</dt><dd>{{ $partner->address }}</dd></div>@endif
                    @if ($partner->whatsapp)<div><dt class="text-sm text-gray-500">WhatsApp</dt><dd>{{ $partner->whatsapp }}</dd></div>@endif
                    @if ($partner->instagram_url)<div><dt class="text-sm text-gray-500">Instagram</dt><dd><a class="underline" href="{{ $partner->instagram_url }}" rel="noopener noreferrer" target="_blank">Buka Instagram</a></dd></div>@endif
                </dl>
            </div>
        </div>

        <section class="mt-14">
            <h2 class="text-2xl font-bold">Produk dari {{ $partner->name }}</h2>
            @if ($products->isNotEmpty())
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-ui.product-card :$product />
                    @endforeach
                </div>
                <div class="mt-8">{{ $products->links() }}</div>
            @else
                <x-ui.empty-state class="mt-6" message="Produk aktif dari mitra ini belum tersedia." />
            @endif
        </section>
    </div>
</x-layouts.public>
