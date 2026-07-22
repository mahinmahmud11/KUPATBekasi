<x-layouts.public title="Halaman Tidak Ditemukan" description="Halaman yang Anda cari tidak ditemukan di KUPATBekasi.">
    <section class="mx-auto flex max-w-3xl flex-col items-center px-4 py-16 text-center sm:px-6 sm:py-24 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-widest text-gray-500">404</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Halaman tidak ditemukan</h1>
        <p class="mt-4 max-w-xl leading-7 text-gray-600">Halaman yang Anda tuju mungkin sudah dipindahkan atau tidak tersedia.</p>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a class="rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ route('home') }}">Kembali ke Beranda</a>
            <a class="rounded-lg border border-gray-300 bg-white px-5 py-3 font-semibold text-gray-900 hover:border-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ route('products.index') }}">Lihat Katalog Produk</a>
        </div>
    </section>
</x-layouts.public>
