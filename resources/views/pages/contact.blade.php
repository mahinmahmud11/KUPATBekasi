<x-layouts.public title="Kontak" description="Informasi kontak KUPATBekasi.">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight">Kontak</h1>

        @if ($siteSetting && collect([$siteSetting->contact_whatsapp, $siteSetting->contact_email, $siteSetting->address, $siteSetting->instagram_url])->filter()->isNotEmpty())
            <dl class="mt-6 grid gap-5 rounded-xl bg-white p-6 shadow-sm">
                @if ($siteSetting->contact_whatsapp)<div><dt class="text-sm text-gray-500">WhatsApp</dt><dd class="mt-1">{{ $siteSetting->contact_whatsapp }}</dd></div>@endif
                @if ($siteSetting->contact_email)<div><dt class="text-sm text-gray-500">Email</dt><dd class="mt-1"><a class="underline" href="mailto:{{ $siteSetting->contact_email }}">{{ $siteSetting->contact_email }}</a></dd></div>@endif
                @if ($siteSetting->address)<div><dt class="text-sm text-gray-500">Alamat</dt><dd class="mt-1">{{ $siteSetting->address }}</dd></div>@endif
                @if ($siteSetting->instagram_url)<div><dt class="text-sm text-gray-500">Instagram</dt><dd class="mt-1"><a class="underline" href="{{ $siteSetting->instagram_url }}" rel="noopener noreferrer" target="_blank">Buka Instagram</a></dd></div>@endif
            </dl>
        @else
            <x-ui.empty-state class="mt-6" message="Informasi kontak belum tersedia." />
        @endif
    </div>
</x-layouts.public>
