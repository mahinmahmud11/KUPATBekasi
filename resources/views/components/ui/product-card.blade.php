@props(['product'])

<article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    @if ($product->main_image_path)
        <img class="aspect-[4/3] w-full object-cover" src="{{ Storage::disk('public')->url($product->main_image_path) }}" alt="{{ $product->name }}" loading="lazy">
    @else
        <div class="flex aspect-[4/3] items-center justify-center bg-gray-100 px-4 text-center text-sm text-gray-500">Gambar belum tersedia</div>
    @endif

    <div class="space-y-2 p-5">
        <p class="text-sm text-gray-600">{{ $product->partner->name }}</p>
        <h3 class="text-lg font-semibold"><a class="hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900" href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3>
        @if ($product->category)
            <p class="text-sm text-gray-500">{{ $product->category->name }}</p>
        @endif
        <p class="font-semibold">Rp {{ number_format($product->price, 0, ',', '.') }}@if ($product->unit)<span class="font-normal text-gray-500"> / {{ $product->unit }}</span>@endif</p>
        <p class="text-sm text-gray-600">{{ match ($product->stock_status) { 'available' => 'Tersedia', 'preorder' => 'Pre-order', 'unavailable' => 'Tidak tersedia', default => $product->stock_status } }}</p>
        @if ($product->partner->district)
            <p class="text-sm text-gray-500">{{ $product->partner->district }}</p>
        @endif
    </div>
</article>
