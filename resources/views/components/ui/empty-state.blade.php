@props(['message' => 'Belum ada data yang tersedia.'])

<div {{ $attributes->class(['rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center text-gray-600']) }}>
    <p>{{ $message }}</p>
</div>
