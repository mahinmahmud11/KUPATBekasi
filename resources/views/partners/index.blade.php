<x-layouts.public title="Mitra" description="Daftar mitra UMKM KUPATBekasi.">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight">Mitra UMKM</h1>

        <form action="{{ route('partners.index') }}" method="GET" class="mt-6 flex flex-col gap-3 rounded-xl bg-white p-5 shadow-sm sm:flex-row">
            <div class="flex-1">
                <label class="block text-sm font-medium" for="partner-search">Cari mitra</label>
                <input class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-gray-900 focus:outline-none" id="partner-search" name="q" type="search" value="{{ $search }}">
            </div>
            <button class="self-end rounded-lg bg-gray-900 px-5 py-3 font-semibold text-white hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" type="submit">Cari</button>
            <a class="self-end rounded-lg border border-gray-300 px-5 py-3 font-semibold hover:border-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-900" href="{{ route('partners.index') }}">Reset</a>
        </form>

        @if ($partners->isNotEmpty())
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($partners as $partner)
                    <x-ui.partner-card :$partner />
                @endforeach
            </div>
            <div class="mt-8">{{ $partners->links() }}</div>
        @else
            <x-ui.empty-state class="mt-8" message="Mitra yang dicari belum tersedia." />
        @endif
    </div>
</x-layouts.public>
