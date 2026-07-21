@props(['partner'])

<article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
    @if ($partner->cover_path)
        <img class="aspect-[16/7] w-full object-cover" src="{{ Storage::disk('public')->url($partner->cover_path) }}" alt="Sampul {{ $partner->name }}" loading="lazy">
    @elseif ($partner->logo_path)
        <div class="flex aspect-[16/7] items-center justify-center bg-gray-50 p-5">
            <img class="h-full max-w-full object-contain" src="{{ Storage::disk('public')->url($partner->logo_path) }}" alt="Logo {{ $partner->name }}" loading="lazy">
        </div>
    @else
        <div class="flex aspect-[16/7] items-center justify-center bg-gray-100 px-4 text-center text-sm text-gray-500">Media belum tersedia</div>
    @endif

    <div class="space-y-2 p-5">
        <h3 class="text-lg font-semibold"><a class="hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gray-900" href="{{ route('partners.show', $partner) }}">{{ $partner->name }}</a></h3>
        @if ($partner->short_description)
            <p class="text-sm leading-6 text-gray-600">{{ $partner->short_description }}</p>
        @endif
        @if ($partner->district)
            <p class="text-sm text-gray-500">{{ $partner->district }}</p>
        @endif
        @if (isset($partner->products_count))
            <p class="text-sm text-gray-500">{{ $partner->products_count }} produk aktif</p>
        @endif
    </div>
</article>
