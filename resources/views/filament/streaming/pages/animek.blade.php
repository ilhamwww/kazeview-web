@php
    $heroPayload = array_map(fn ($h) => [
        'title' => $h['title'],
        'url' => $h['url'],
        'poster' => $h['thumbnail'],
        'desc' => $h['desc'],
        'year' => $h['year'],
        'rating' => $h['rating'] ? number_format($h['rating'], 1) : '',
        'genres' => array_slice($h['genres'], 0, 3),
    ], $sliders);
@endphp

<div class="min-h-screen bg-[#0b0b0f] pb-28 text-zinc-100">
    @include('filament.streaming.partials.animek-subnav', ['active' => 'home'])

    <div class="mx-auto max-w-[1600px] px-4 md:px-8">

        {{-- ================= HERO ================= --}}
        @if(!empty($heroPayload))
        <section x-data="{
                    slides: @js($heroPayload),
                    i: 0,
                    timer: null,
                    startX: null,
                    go(n) { this.i = (n + this.slides.length) % this.slides.length; this.restart(); },
                    restart() { clearInterval(this.timer); this.timer = setInterval(() => { if (!document.hidden) this.i = (this.i + 1) % this.slides.length; }, 7000); },
                    init() { this.restart(); }
                 }"
                 @touchstart.passive="startX = $event.touches[0].clientX"
                 @touchend.passive="if (startX !== null) { const dx = $event.changedTouches[0].clientX - startX; if (Math.abs(dx) > 45) go(i + (dx < 0 ? 1 : -1)); startX = null; }"
                 class="relative mt-1 overflow-hidden rounded-2xl border border-white/[0.06] bg-zinc-950 md:rounded-3xl">

            <template x-for="(s, n) in slides" :key="'bg'+n">
                <img :src="s.poster" alt="" aria-hidden="true"
                     class="absolute inset-0 h-full w-full scale-110 object-cover blur-2xl transition-opacity duration-700"
                     :class="i === n ? 'opacity-60' : 'opacity-0'">
            </template>
            <div class="absolute inset-0 bg-gradient-to-r from-[#0b0b0f] via-[#0b0b0f]/80 to-[#0b0b0f]/10"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-[#0b0b0f] via-transparent to-transparent"></div>

            <div class="relative grid min-h-[400px] items-center gap-6 p-5 pb-12 sm:min-h-[420px] sm:p-8 sm:pb-14 md:min-h-[440px] md:grid-cols-[minmax(0,1fr)_auto] md:p-10">
                <div class="min-w-0 max-w-2xl min-h-[268px] sm:min-h-[276px] md:min-h-[300px]">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-600/15 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-red-400 ring-1 ring-red-500/30">
                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Sorotan
                    </span>
                    <a :href="slides[i].url" class="mt-4 block">
                        <h1 class="line-clamp-2 text-3xl font-black leading-[1.05] tracking-tight text-white sm:text-4xl md:text-5xl" x-text="slides[i].title"></h1>
                    </a>
                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[13px] text-zinc-300">
                        <template x-if="slides[i].rating"><span class="font-bold text-yellow-400">★ <span x-text="slides[i].rating"></span></span></template>
                        <template x-if="slides[i].year"><span x-text="slides[i].year"></span></template>
                        <template x-for="g in slides[i].genres" :key="g"><span class="rounded-md bg-white/10 px-2 py-0.5 text-[11.5px] font-semibold text-zinc-200" x-text="g"></span></template>
                    </div>
                    <p class="mt-4 line-clamp-3 max-w-xl text-sm leading-relaxed text-zinc-300/90 md:text-[15px]" x-text="slides[i].desc"></p>
                    <div class="mt-6 flex flex-wrap gap-2.5">
                        <a :href="slides[i].url" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-black transition hover:bg-zinc-200">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg> Tonton Sekarang
                        </a>
                        <a :href="slides[i].url" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20">Detail</a>
                    </div>
                </div>

                <div class="relative hidden md:block">
                    <template x-for="(s, n) in slides" :key="'p'+n">
                        <a :href="s.url" x-show="i === n" x-transition.opacity.duration.500ms
                           class="block w-[230px] overflow-hidden rounded-2xl shadow-2xl shadow-black/70 ring-1 ring-white/15 lg:w-[260px]">
                            <img :src="s.poster" :alt="s.title" class="aspect-[2/3] w-full object-cover">
                        </a>
                    </template>
                </div>
            </div>

            <div class="absolute bottom-4 left-5 flex items-center gap-1.5 sm:left-8 md:left-12">
                <template x-for="(s, n) in slides" :key="'d'+n">
                    <button type="button" @click="go(n)" :aria-label="'Slide ' + (n + 1)"
                            class="h-1.5 rounded-full transition-all duration-300"
                            :class="i === n ? 'w-7 bg-white' : 'w-2.5 bg-white/30 hover:bg-white/60'"></button>
                </template>
            </div>
            <div class="absolute bottom-3 right-4 hidden gap-2 md:flex">
                <button type="button" @click="go(i - 1)" aria-label="Sebelumnya" class="grid h-9 w-9 place-items-center rounded-full bg-black/40 text-lg text-white ring-1 ring-white/15 transition hover:bg-black/70">‹</button>
                <button type="button" @click="go(i + 1)" aria-label="Berikutnya" class="grid h-9 w-9 place-items-center rounded-full bg-black/40 text-lg text-white ring-1 ring-white/15 transition hover:bg-black/70">›</button>
            </div>
        </section>
        @endif

        {{-- ================= GENRE ================= --}}
        @if(!empty($genres))
        <div class="-mx-4 mt-6 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] md:-mx-8 md:px-8 [&::-webkit-scrollbar]:hidden">
            @foreach($genres as $g)
                <a href="/animek/genre?g={{ $g['id'] }}"
                   class="shrink-0 rounded-full border border-white/10 bg-white/[0.04] px-3.5 py-1.5 text-[12.5px] font-semibold text-zinc-300 transition hover:border-red-500/50 hover:bg-red-600/10 hover:text-white">{{ $g['name'] }}</a>
            @endforeach
            <a href="/animek/genre" class="shrink-0 rounded-full bg-white/10 px-3.5 py-1.5 text-[12.5px] font-bold text-white transition hover:bg-white/20">Semua genre →</a>
        </div>
        @endif

        {{-- ================= TOP 10 ================= --}}
        @if(!empty($trending))
        <section class="mt-10">
            <div class="mb-3">
                <h2 class="text-[17px] font-bold tracking-tight md:text-[21px]">Top 10 Hari Ini</h2>
                <p class="mt-0.5 text-xs text-zinc-500">Paling banyak ditonton hari ini</p>
            </div>
            <div class="flex snap-x items-end gap-1 overflow-x-auto overflow-y-hidden pb-2 pt-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach($trending as $n => $item)
                    <a href="{{ $item['url'] }}" class="group relative flex shrink-0 snap-start items-end" title="{{ $item['title'] }}">
                        <span class="pointer-events-none -mr-5 select-none text-[80px] font-black leading-[0.72] text-transparent [-webkit-text-stroke:2px_rgba(255,255,255,0.28)] md:text-[104px]">{{ $n + 1 }}</span>
                        <div class="relative w-[112px] overflow-hidden rounded-xl ring-1 ring-white/10 transition duration-300 group-hover:-translate-y-0.5 group-hover:ring-2 group-hover:ring-red-500/70 md:w-[138px]">
                            <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}" loading="lazy" class="aspect-[2/3] w-full object-cover">
                            @if(!empty($item['episode']))
                                <span class="absolute bottom-1.5 right-1.5 rounded-md bg-white px-1.5 py-0.5 text-[10px] font-black text-black">EP {{ $item['episode'] }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- ================= EPISODE TERBARU ================= --}}
        @if(!empty($latest))
        <section class="mt-10">
            <div class="mb-3">
                <h2 class="text-[17px] font-bold tracking-tight md:text-[21px]">Episode Terbaru</h2>
                <p class="mt-0.5 text-xs text-zinc-500">Langsung putar episode yang baru rilis</p>
            </div>
            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach(array_slice($latest, 0, 12) as $item)
                    <a href="{{ $item['episode_url'] ?? $item['url'] }}"
                       class="group flex items-center gap-3 rounded-xl border border-white/[0.06] bg-white/[0.03] p-2 transition hover:border-red-500/40 hover:bg-white/[0.06]">
                        <div class="relative w-[54px] shrink-0 overflow-hidden rounded-lg">
                            <img src="{{ $item['thumbnail'] }}" alt="" loading="lazy" class="aspect-[2/3] w-full object-cover">
                            <span class="absolute inset-0 grid place-items-center bg-black/40 opacity-0 transition group-hover:opacity-100">
                                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="line-clamp-2 text-[13px] font-semibold leading-snug text-zinc-100 group-hover:text-white">{{ $item['title'] }}</h3>
                            @if(!empty($item['episode']))
                                <p class="mt-1 text-[11.5px] font-bold text-red-400">Episode {{ $item['episode'] }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
        @endif

        @include('filament.streaming.partials.animek-rail', [
            'title' => 'Tayang Hari Ini',
            'subtitle' => $todayName . ' · ' . count($today) . ' judul · jam WIB',
            'items' => $today,
            'more' => '/animek/jadwal',
        ])

        @include('filament.streaming.partials.animek-rail', [
            'title' => 'Sedang Tayang',
            'subtitle' => 'Anime ongoing dengan rating tertinggi',
            'items' => $rails['ongoing'] ?? [],
            'more' => '/animek/jelajah?status=ongoing&sort=rating',
        ])

        @include('filament.streaming.partials.animek-rail', [
            'title' => 'Rating Tertinggi Sepanjang Masa',
            'items' => $rails['top'] ?? [],
            'more' => '/animek/jelajah?sort=rating',
        ])

        @include('filament.streaming.partials.animek-rail', [
            'title' => 'Movie Anime',
            'items' => $rails['movies'] ?? [],
            'more' => '/animek/jelajah?kind=Movie&sort=rating',
        ])

        <section class="mt-12 overflow-hidden rounded-2xl border border-white/[0.06] bg-gradient-to-br from-red-600/20 via-fuchsia-600/10 to-transparent p-6 md:p-10">
            <div class="flex flex-col items-start justify-between gap-5 md:flex-row md:items-center">
                <div>
                    <h2 class="text-xl font-black tracking-tight md:text-2xl">{{ number_format($total, 0, ',', '.') }} judul anime menunggu</h2>
                    <p class="mt-1 text-sm text-zinc-400">Saring berdasarkan tipe, status, tahun, lalu urutkan sesukamu.</p>
                </div>
                <a href="/animek/jelajah" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-black transition hover:bg-zinc-200">Jelajahi katalog →</a>
            </div>
        </section>
    </div>
</div>
