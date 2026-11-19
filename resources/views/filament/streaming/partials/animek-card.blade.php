@php
    /** @var array $item */
    $href = ($useEpisode ?? false) && !empty($item['episode_url']) ? $item['episode_url'] : $item['url'];
    $kindLabel = $item['kind'] ?? '';
@endphp
<a href="{{ $href }}" class="group block min-w-0" title="{{ $item['title'] }}">
    <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/[0.06] transition duration-300 group-hover:-translate-y-0.5 group-hover:ring-2 group-hover:ring-red-500/70">
        @if(!empty($item['thumbnail']))
            <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}" loading="lazy" decoding="async"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]">
        @endif
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>

        <div class="absolute left-1.5 top-1.5 flex flex-wrap gap-1">
            @if($kindLabel !== '')
                <span class="rounded-md bg-black/65 px-1.5 py-0.5 text-[9.5px] font-bold uppercase tracking-wide text-zinc-100 backdrop-blur-sm">{{ $kindLabel }}</span>
            @endif
        </div>

        @if(($item['status'] ?? '') === 'ongoing')
            <span class="absolute right-1.5 top-1.5 flex items-center gap-1 rounded-md bg-red-600/90 px-1.5 py-0.5 text-[9.5px] font-bold uppercase tracking-wide text-white">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>Tayang
            </span>
        @endif

        <div class="absolute inset-x-1.5 bottom-1.5 flex items-end justify-between gap-1">
            @if(!empty($item['rating']))
                <span class="flex items-center gap-0.5 text-[11px] font-bold text-white drop-shadow">
                    <svg class="h-3 w-3 fill-yellow-400" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>{{ number_format($item['rating'], 1) }}
                </span>
            @else
                <span></span>
            @endif
            @if(!empty($item['episode']))
                <span class="rounded-md bg-white px-1.5 py-0.5 text-[10px] font-black text-black">EP {{ $item['episode'] }}</span>
            @elseif(!empty($item['time']))
                <span class="rounded-md bg-white/90 px-1.5 py-0.5 text-[10px] font-black text-black">{{ $item['time'] }}</span>
            @endif
        </div>
    </div>
    <h3 class="mt-2 line-clamp-2 text-[12.5px] font-semibold leading-snug text-zinc-200 transition group-hover:text-white">{{ $item['title'] }}</h3>
    @if(!empty($item['year']) || !empty($item['genres']))
        <p class="mt-0.5 truncate text-[11px] text-zinc-500">
            {{ implode(' · ', array_filter([$item['year'] ?? '', $item['genres'][0] ?? ''])) }}
        </p>
    @endif
</a>
