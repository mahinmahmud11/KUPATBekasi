<x-layouts.public :title="$category->name" :description="$category->description">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <p class="text-sm font-medium text-gray-500">Kategori</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ $category->name }}</h1>
        @if ($category->description)<p class="mt-4 max-w-3xl leading-7 text-gray-600">{{ $category->description }}</p>@endif

        @if ($products->isNotEmpty())
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-ui.product-card :$product />
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @else
            <x-ui.empty-state class="mt-8" message="Produk aktif dalam kategori ini belum tersedia." />
        @endif
    </div>
</x-layouts.public>
