@php
    $star = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';
@endphp

<div class="min-h-screen bg-[#0b0b0d] pb-24 text-zinc-100">
    <div class="mx-auto max-w-[1600px] px-4 pt-6 md:px-8 md:pt-10">

        <nav class="mb-6 flex items-center gap-2 text-xs text-zinc-400">
            <a href="/" class="hover:text-white">Home</a>
            <span>/</span>
            <span class="text-zinc-300">Anime</span>
        </nav>

        <div class="mb-8 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black tracking-tight md:text-4xl">Anime</h1>
                <p class="mt-2 max-w-2xl text-sm text-zinc-400">
                    Anime subtitle Indonesia, kualitas sampai 720p, tanpa iklan.
                </p>
            </div>
            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-zinc-300">
                {{ count($all) }} judul
            </span>
        </div>

        @if(!empty($trending))
            <section class="mb-10">
                <h2 class="mb-3 text-[17px] font-bold tracking-tight md:text-[22px]">Sedang Populer</h2>
                <div class="flex gap-2.5 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach($trending as $item)
                        <a href="{{ $item['url'] }}" class="group w-[118px] shrink-0 sm:w-[140px] md:w-[164px]">
                            <div class="relative aspect-[2/3] overflow-hidden rounded-lg bg-zinc-900 ring-1 ring-white/5 transition group-hover:ring-2 group-hover:ring-red-500/60">
                                <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}" loading="lazy"
                                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @if(!empty($item['rating']))
                                    <span class="absolute bottom-1.5 left-1.5 flex items-center gap-0.5 rounded bg-black/70 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                        <svg class="h-3 w-3 fill-yellow-400" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $star }}"/></svg>{{ number_format($item['rating'], 1) }}
                                    </span>
                                @endif
                            </div>
                            <h3 class="mt-1.5 truncate text-[12px] font-semibold text-zinc-300 group-hover:text-white" title="{{ $item['title'] }}">{{ $item['title'] }}</h3>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <h2 class="mb-4 text-[17px] font-bold tracking-tight md:text-[22px]">Semua Anime</h2>

        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7">
            @foreach($all as $item)
                <a href="{{ $item['url'] }}" class="group">
                    <div class="relative aspect-[2/3] overflow-hidden rounded-lg bg-zinc-900 ring-1 ring-white/5 transition duration-300 group-hover:ring-2 group-hover:ring-red-500/70">
                        <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}" loading="lazy"
                             class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/85 to-transparent"></div>
                        @if(!empty($item['rating']))
                            <span class="absolute bottom-1.5 left-1.5 flex items-center gap-0.5 text-[10px] font-bold text-white">
                                <svg class="h-3 w-3 fill-yellow-400" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $star }}"/></svg>{{ number_format($item['rating'], 1) }}
                            </span>
                        @endif
                    </div>
                    <h3 class="mt-1.5 truncate text-[12.5px] font-semibold text-zinc-200 group-hover:text-white" title="{{ $item['title'] }}">{{ $item['title'] }}</h3>
                </a>
            @endforeach
        </div>
    </div>
</div>
