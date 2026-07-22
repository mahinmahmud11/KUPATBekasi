<x-layouts.public :title="$partner->name" :description="$partner->short_description">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <section class="relative overflow-hidden rounded-2xl bg-gray-800 shadow-sm" data-partner-hero>
            @if ($partner->cover_path)
                <img class="h-[260px] w-full object-cover sm:h-[320px] lg:h-[360px]" src="{{ Storage::disk('public')->url($partner->cover_path) }}" alt="Sampul {{ $partner->name }}" data-partner-cover>
            @else
                <div class="h-[260px] w-full bg-gradient-to-br from-gray-600 to-gray-900 sm:h-[320px] lg:h-[360px]" data-partner-cover-fallback aria-hidden="true"></div>
            @endif

            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent" aria-hidden="true"></div>

            <div class="absolute inset-x-0 bottom-0 flex items-end gap-4 p-5 text-white sm:gap-6 sm:p-8">
                @if ($partner->logo_path)
                    <img class="h-24 w-24 shrink-0 rounded-full border-4 border-white bg-white object-contain shadow-lg sm:h-32 sm:w-32" src="{{ Storage::disk('public')->url($partner->logo_path) }}" alt="Logo {{ $partner->name }}" data-partner-logo>
                @else
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full border-4 border-white bg-gray-100 text-3xl font-bold text-gray-800 shadow-lg sm:h-32 sm:w-32 sm:text-4xl" data-partner-logo-fallback aria-label="Logo {{ $partner->name }}">
                        {{ Str::upper(Str::substr($partner->name, 0, 1)) }}
                    </div>
                @endif

                <div class="min-w-0 pb-1">
                    <p class="mb-2 text-sm font-medium text-gray-100">UMKM Binaan Kota Bekasi</p>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">{{ $partner->name }}</h1>
                    @if ($partner->short_description)
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-100 sm:text-base">{{ $partner->short_description }}</p>
                    @endif
                    <span class="mt-3 inline-flex rounded-full bg-white/15 px-3 py-1 text-sm font-semibold ring-1 ring-inset ring-white/30">{{ $products->total() }} produk aktif</span>
                </div>
            </div>
        </section>

        <section class="mt-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="partner-information-heading">
            <h2 class="text-2xl font-bold tracking-tight" id="partner-information-heading">Informasi Mitra</h2>

            @if ($partner->description || $partner->short_description)
                <div class="mt-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Tentang Usaha</h3>
                    <p class="mt-2 whitespace-pre-line leading-7 text-gray-700">{{ $partner->description ?: $partner->short_description }}</p>
                </div>
            @endif

            @if ($partner->owner_name || $partner->address || $partner->district || $partner->instagram_url || $partner->whatsapp)
                <dl class="mt-6 grid gap-5 border-t border-gray-200 pt-6 sm:grid-cols-2 lg:grid-cols-3">
                    @if ($partner->owner_name)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nama Pemilik</dt>
                            <dd class="mt-1 text-gray-900">{{ $partner->owner_name }}</dd>
                        </div>
                    @endif
                    @if ($partner->address)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Alamat</dt>
                            <dd class="mt-1 text-gray-900">{{ $partner->address }}</dd>
                        </div>
                    @endif
                    @if ($partner->district)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Kecamatan/Wilayah</dt>
                            <dd class="mt-1 text-gray-900">{{ $partner->district }}</dd>
                        </div>
                    @endif
                    @if ($partner->instagram_url)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Instagram</dt>
                            <dd class="mt-2"><a class="font-semibold text-gray-900 underline decoration-gray-300 underline-offset-4 hover:decoration-gray-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900" href="{{ $partner->instagram_url }}" rel="noopener noreferrer" target="_blank">Buka Instagram</a></dd>
                        </div>
                    @endif
                    @if ($partner->whatsapp)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">WhatsApp</dt>
                            <dd class="mt-2"><a class="inline-flex rounded-lg bg-gray-900 px-4 py-2 font-semibold text-white hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="https://wa.me/{{ $partner->whatsapp }}" rel="noopener noreferrer" target="_blank">Hubungi via WhatsApp</a></dd>
                        </div>
                    @endif
                </dl>
            @endif
        </section>

        <section class="mt-12" aria-labelledby="partner-products-heading">
            <h2 class="text-2xl font-bold tracking-tight" id="partner-products-heading">Produk dari Mitra Ini</h2>
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
