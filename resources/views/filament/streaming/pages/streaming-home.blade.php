@php
    $star = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';
    $heroPayload = array_map(function ($h) {
        $genres = is_array($h['genres'] ?? null)
            ? $h['genres']
            : array_filter(array_map('trim', explode(',', (string) ($h['genre'] ?? ''))));

        return [
            'title' => $h['title'] ?? '',
            'url' => $h['url'] ?? '#',
            'banner' => $h['banner'] ?: ($h['thumbnail'] ?? ''),
            'poster' => $h['thumbnail'] ?? '',
            'desc' => $h['description'] ?? ($h['desc'] ?? ''),
            'year' => (string) ($h['year'] ?? ''),
            'rating' => !empty($h['rating']) ? number_format((float) $h['rating'], 1) : '',
            'genres' => array_slice(array_values($genres), 0, 3),
            'is_series' => (bool) ($h['is_series'] ?? false),
        ];
    }, $heroes);
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28">

    {{-- ================= BILLBOARD (gaya sama dengan Movies / TV Series) ================= --}}
    @if(!empty($heroPayload))
    <section x-data="{
                slides: @js($heroPayload),
                active: 0,
                total: @js(count($heroPayload)),
                t: null,
                sx: 0,
                start() { this.stop(); this.t = setInterval(() => { if (!document.hidden) this.active = (this.active + 1) % this.total }, 7000) },
                stop() { if (this.t) clearInterval(this.t) },
                next() { this.active = (this.active + 1) % this.total },
                prev() { this.active = (this.active - 1 + this.total) % this.total },
                go(i) { this.active = i; this.start() }
             }"
             x-init="start()"
             @mouseenter="stop()"
             @mouseleave="start()"
             @touchstart.passive="sx = $event.touches[0].clientX"
             @touchend.passive="let dx = $event.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) { dx < 0 ? next() : prev(); start() }"
             class="relative w-full h-[78vh] min-h-[520px] md:h-[86vh] md:min-h-[600px] md:max-h-[920px] overflow-hidden bg-black">

        <template x-for="(s, i) in slides" :key="'slide' + i">
            <div x-show="active === i"
                 class="absolute inset-0 transition-opacity duration-700 ease-in-out"
                 :class="active === i ? 'opacity-100 z-10 pointer-events-auto' : 'opacity-0 z-0 pointer-events-none'">
                <img :src="s.banner" :alt="s.title" class="absolute inset-0 h-full w-full object-cover object-[center_15%]"
                     :loading="i === 0 ? 'eager' : 'lazy'" :fetchpriority="i === 0 ? 'high' : 'auto'">
                <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/70 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 h-[55%] bg-gradient-to-t from-[#050505] via-[#050505]/70 to-transparent"></div>
                <div class="absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-[#050505]/90 to-transparent"></div>

                <div class="relative flex h-full w-full max-w-[1800px] mx-auto items-end px-6 pb-32 md:px-12 md:pb-40">
                    <div class="max-w-2xl">
                        <div class="mb-4 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded bg-red-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.15em] text-white">Sorotan</span>
                            <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-400" x-text="s.is_series ? 'Serial' : 'Film'"></span>
                        </div>

                        <h1 class="line-clamp-2 text-4xl font-black leading-[0.95] tracking-tight text-white drop-shadow-2xl sm:text-5xl md:text-6xl lg:text-7xl" x-text="s.title"></h1>

                        <div class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm font-semibold text-zinc-300">
                            <template x-if="s.rating">
                                <span class="inline-flex items-center gap-1 text-yellow-400">
                                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $star }}"/></svg>
                                    <span x-text="s.rating"></span>
                                </span>
                            </template>
                            <template x-if="s.year">
                                <span class="flex items-center gap-3"><span class="text-zinc-500">•</span><span x-text="s.year"></span></span>
                            </template>
                        </div>

                        <p class="mt-3 text-sm text-zinc-400" x-show="s.genres.length" x-text="s.genres.join('  ·  ')"></p>

                        <p class="mt-4 hidden max-w-xl text-sm leading-relaxed text-zinc-300/90 line-clamp-3 sm:block md:text-base" x-text="s.desc"></p>

                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            <a :href="s.url" class="inline-flex items-center gap-2 rounded-lg bg-white py-3 pl-5 pr-6 text-sm font-bold text-black transition hover:bg-zinc-200 active:scale-95">
                                <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                <span x-text="s.is_series ? 'Tonton Sekarang' : 'Putar'"></span>
                            </a>
                            <a :href="s.url" class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-zinc-500/30 px-5 py-3 text-sm font-bold text-white backdrop-blur-md transition hover:bg-zinc-500/50">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                                Info Lengkap
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Dots (mobile) + pemilih thumbnail (desktop) --}}
        <div class="absolute inset-x-0 bottom-6 z-20 md:bottom-10">
            <div class="mx-auto flex w-full max-w-[1800px] items-end justify-between gap-6 px-6 md:px-12">
                <div class="mx-auto flex items-center gap-2 md:hidden">
                    <template x-for="(s, i) in slides" :key="'dot' + i">
                        <button type="button" @click="go(i)" :aria-label="'Slide ' + (i + 1)"
                                :class="active === i ? 'w-7 bg-red-600' : 'w-2 bg-white/30'"
                                class="h-2 rounded-full transition-all duration-300"></button>
                    </template>
                </div>
                <div class="ml-auto hidden items-center gap-3 md:flex">
                    <template x-for="(s, i) in slides" :key="'thumb' + i">
                        <button type="button" @click="go(i)" :aria-label="s.title"
                                :class="active === i ? 'ring-2 ring-red-600 opacity-100 scale-105' : 'opacity-50 hover:opacity-90'"
                                class="relative aspect-video w-36 overflow-hidden rounded-lg bg-zinc-900 shadow-xl transition-all duration-300 lg:w-44">
                            <img :src="s.banner" alt="" class="h-full w-full object-cover" loading="lazy">
                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent px-2 py-1 text-left text-[10px] font-bold line-clamp-1" x-text="s.title"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ================= RAK ================= --}}
    <div class="relative z-10 -mt-2 md:-mt-4 space-y-7 md:space-y-11">

        {{-- Kategori cepat (mobile, ala chip Netflix) --}}
        @if(!empty($quickLinks))
            <div class="md:hidden flex gap-2 overflow-x-auto px-6 [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                @foreach($quickLinks as $link)
                    <a href="{{ $link['url'] }}" class="shrink-0 rounded-xl border border-white/10 bg-white/5 px-3.5 py-1.5 text-xs font-bold tracking-wide text-zinc-300 backdrop-blur transition hover:bg-white/10 hover:text-white">{{ $link['label'] }}</a>
                @endforeach
            </div>
        @endif

        {{-- Lanjutkan Menonton --}}
        @if(!empty($continueWatching))
            <section class="relative">
                <h2 class="mb-3 px-6 md:px-12 text-xl md:text-2xl font-black tracking-tight text-white">Lanjutkan Menonton untuk Kamu</h2>
                <div class="flex gap-x-4 overflow-x-auto overflow-y-hidden px-6 md:px-12 pb-6 pt-1 scroll-px-6 md:scroll-px-12 snap-x [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                    @foreach($continueWatching as $cw)
                        <a href="{{ $cw['url'] }}" class="group block w-[136px] sm:w-[158px] md:w-[182px] xl:w-[200px] shrink-0 snap-start">
                            <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/5 shadow-lg shadow-black/40 transition group-hover:ring-red-600/60">
                                @if(!empty($cw['thumbnail']))
                                    <img src="{{ $cw['thumbnail'] }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()"
                                         class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @endif

                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/90 via-black/0 to-black/0 opacity-70 transition group-hover:opacity-100"></div>

                                <div class="absolute inset-0 grid place-items-center opacity-0 transition group-hover:opacity-100">
                                    <span class="grid h-11 w-11 place-items-center rounded-full border-2 border-white bg-black/55">
                                        <svg class="ml-0.5 h-4 w-4 fill-white" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                    </span>
                                </div>

                                <span class="absolute left-2 top-2 rounded bg-black/70 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-zinc-200">{{ $cw['label'] }}</span>

                                <div class="absolute inset-x-0 bottom-0 h-1 bg-white/20">
                                    <div class="h-full bg-[#E50914]" style="width: {{ !empty($cw['has_progress']) ? max(4, min(100, $cw['progress_percent'])) : 4 }}%"></div>
                                </div>
                            </div>

                            <h3 class="mt-2 truncate text-xs md:text-[13px] font-semibold text-zinc-200 transition group-hover:text-red-500" title="{{ $cw['title'] }}">{{ $cw['title'] }}</h3>
                            <p class="truncate text-[11px] text-zinc-500">
                                @if(!empty($cw['episode']) && $cw['label'] !== 'Film')Episode {{ $cw['episode'] }}@if($cw['ago']) · @endif @endif{{ $cw['ago'] }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Top 10 --}}
        @if(!empty($topTen))
            @include('filament.streaming.partials.home-row', [
                'title' => 'Top 10 Minggu Ini',
                'items' => $topTen,
                'variant' => 'top10',
            ])
        @endif

        {{-- Karena kamu menonton ... --}}
        @if(!empty($because['items']))
            @include('filament.streaming.partials.home-row', [
                'title' => $because['title'],
                'items' => $because['items'],
                'variant' => 'poster',
            ])
        @endif

        {{-- Rak lainnya --}}
        @foreach($sections as $section)
            @include('filament.streaming.partials.home-row', [
                'title' => $section['title'],
                'items' => $section['items'],
                'moreUrl' => $section['more_url'] ?? null,
                'variant' => 'poster',
            ])
        @endforeach
    </div>
</div>
