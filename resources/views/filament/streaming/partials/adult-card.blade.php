@php
    /** @var array $item */
    $poster = $item['thumbnail'] ?? ($item['banner'] ?? '');
@endphp

<a href="{{ $item['url'] ?? '#' }}" class="group block">
    <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/10">
        <img src="{{ $poster }}" alt="{{ $item['title'] ?? '' }}" loading="lazy"
             class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
        <span class="absolute left-1.5 top-1.5 rounded bg-red-600 px-1.5 py-0.5 text-[9px] font-black">18+</span>
        @if(!empty($item['rating']))
            <span class="absolute right-1.5 top-1.5 rounded bg-black/70 px-1.5 py-0.5 text-[9px] font-bold text-amber-300">★ {{ $item['rating'] }}</span>
        @endif
    </div>
    <p class="mt-1.5 text-[11.5px] font-semibold leading-snug line-clamp-2 text-zinc-200 group-hover:text-white">{{ $item['title'] ?? 'Tanpa Judul' }}</p>
    @if(!empty($item['year']))
        <p class="text-[10px] text-zinc-500">{{ $item['year'] }}</p>
    @endif
</a>
