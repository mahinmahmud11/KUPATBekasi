<x-layouts.public title="Tentang" description="Informasi tentang program KUPATBekasi.">
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight">Tentang</h1>
        @if ($siteSetting?->about_summary)
            <p class="mt-6 whitespace-pre-line leading-7 text-gray-700">{{ $siteSetting->about_summary }}</p>
        @else
            <x-ui.empty-state class="mt-6" message="Informasi tentang program belum tersedia." />
        @endif
    </article>
</x-layouts.public>
