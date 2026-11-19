@php
    $star = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';
    $heroPayload = array_map(fn ($h) => [
        'title' => $h['title'] ?? '',
        'url' => $h['url'] ?? '#',
        'trailer' => $h['trailer'] ?? '',
    ], $heroes);
@endphp

<div class="min-h-screen bg-[#141414] text-white pb-28">

    {{-- ================= BILLBOARD ================= --}}
    @if(!empty($heroes))
    <section class="relative w-full h-[72vh] min-h-[480px] md:h-[86vh] md:min-h-[600px] overflow-hidden bg-black"
             x-data="{
                heroes: @js($heroPayload),
                active: 0,
                showVideo: false,
                muted: true,
                timer: null,
                startX: null,
                init() {
                    this.queue();
                    document.addEventListener('visibilitychange', () => {
                        const v = this.$refs.video;
                        if (!v) return;
                        document.hidden ? v.pause() : (this.showVideo && v.play().catch(() => {}));
                    });
                },
                saveData() {
                    return !!(navigator.connection && navigator.connection.saveData);
                },
                queue() {
                    clearTimeout(this.timer);
                    this.showVideo = false;
                    const v = this.$refs.video;
                    if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
                    const trailer = this.heroes[this.active]?.trailer;
                    if (trailer && !this.saveData()) {
                        // Tampilkan gambar dulu, trailer menyusul (ala Netflix).
                        this.timer = setTimeout(() => this.startTrailer(trailer), 2500);
                    } else {
                        this.timer = setTimeout(() => this.next(), 9000);
                    }
                },
                startTrailer(src) {
                    const v = this.$refs.video;
                    if (!v) return;
                    v.muted = this.muted;
                    v.src = src;
                    v.play().then(() => { this.showVideo = true; })
                        .catch(() => { this.timer = setTimeout(() => this.next(), 7000); });
                },
                onEnded() { this.next(); },
                onError() { this.showVideo = false; this.timer = setTimeout(() => this.next(), 6000); },
                next() { this.active = (this.active + 1) % this.heroes.length; this.queue(); },
                prev() { this.active = (this.active - 1 + this.heroes.length) % this.heroes.length; this.queue(); },
                goTo(i) { if (i !== this.active) { this.active = i; this.queue(); } },
                toggleMute() {
                    this.muted = !this.muted;
                    if (this.$refs.video) this.$refs.video.muted = this.muted;
                },
                touchStart(e) { this.startX = e.touches[0].clientX; },
                touchEnd(e) {
                    if (this.startX === null) return;
                    const dx = e.changedTouches[0].clientX - this.startX;
                    if (Math.abs(dx) > 50) { dx < 0 ? this.next() : this.prev(); }
                    this.startX = null;
                }
             }"
             @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)">

        {{-- Gambar latar per hero --}}
        @foreach($heroes as $index => $hero)
            <div class="absolute inset-0 transition-opacity duration-700"
                 :class="active === {{ $index }} && !showVideo ? 'opacity-100' : 'opacity-0'"
                 @if($index !== 0) style="opacity:0" @endif>
                <img src="{{ $hero['banner'] ?: ($hero['thumbnail'] ?? '') }}" alt=""
                     class="h-full w-full object-cover object-[center_20%]"
                     @if($index === 0) fetchpriority="high" @else loading="lazy" @endif>
            </div>
        @endforeach

        {{-- Trailer --}}
        <video x-ref="video" muted playsinline preload="none"
               x-on:ended="onEnded()" x-on:error="onError()"
               class="absolute inset-0 h-full w-full object-cover transition-opacity duration-1000"
               :class="showVideo ? 'opacity-100' : 'opacity-0'"></video>

        {{-- Vignette ala Netflix --}}
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-black/80 via-black/30 to-transparent md:via-black/20"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-black/70 to-transparent"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-[42%] bg-gradient-to-t from-[#141414] via-[#141414]/70 to-transparent"></div>

        {{-- Info judul --}}
        @foreach($heroes as $index => $hero)
            <div x-show="active === {{ $index }}" x-cloak
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute inset-x-0 bottom-[19%] md:bottom-[24%] z-10 px-4 md:px-12"
                 @if($index !== 0) style="display:none" @endif>
                <div class="max-w-[92%] sm:max-w-xl lg:max-w-2xl">
                    <div class="mb-2 flex items-center gap-2 text-[11px] md:text-xs font-bold uppercase tracking-[0.2em] text-zinc-300">
                        <span class="text-[#E50914] text-base md:text-lg font-black leading-none">K</span>
                        {{ ($hero['is_series'] ?? false) ? 'Serial' : 'Film' }}
                    </div>

                    <h1 class="text-[32px] leading-[1.02] sm:text-5xl lg:text-6xl font-black tracking-tight line-clamp-2 drop-shadow-[0_2px_12px_rgba(0,0,0,0.7)]">
                        {{ $hero['title'] ?? '' }}
                    </h1>

                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs md:text-sm font-semibold text-zinc-200">
                        @if(!empty($hero['rating']))
                            <span class="flex items-center gap-1 text-[#46d369]"><svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $star }}"/></svg>{{ $hero['rating'] }}</span>
                        @endif
                        @if(!empty($hero['year']))<span>{{ $hero['year'] }}</span>@endif
                        <span class="rounded-sm border border-white/40 px-1 py-px text-[10px] leading-none">HD</span>
                        @if(!empty($hero['genre']))
                            <span class="text-zinc-300">{{ implode(' • ', array_slice(array_map('trim', explode(',', $hero['genre'])), 0, 3)) }}</span>
                        @endif
                    </div>

                    @if(!empty($hero['description']))
                        <p class="mt-3 hidden sm:block max-w-xl text-sm md:text-[17px] leading-snug text-zinc-100 line-clamp-3 drop-shadow-[0_1px_6px_rgba(0,0,0,0.8)]">{{ $hero['description'] }}</p>
                    @endif

                    <div class="mt-5 flex items-center gap-2.5 md:gap-3">
                        <a href="{{ $hero['url'] }}"
                           class="inline-flex items-center gap-2 rounded-[4px] bg-white px-5 md:px-7 py-2 md:py-2.5 text-sm md:text-lg font-bold text-black transition hover:bg-white/80 active:scale-95">
                            <svg class="w-5 h-5 md:w-7 md:h-7 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                            Putar
                        </a>
                        <a href="{{ $hero['url'] }}"
                           class="inline-flex items-center gap-2 rounded-[4px] bg-[rgba(109,109,110,0.7)] px-5 md:px-7 py-2 md:py-2.5 text-sm md:text-lg font-bold text-white transition hover:bg-[rgba(109,109,110,0.45)] active:scale-95">
                            <svg class="w-5 h-5 md:w-7 md:h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5M12 8h.01"/></svg>
                            <span class="sm:hidden">Info</span><span class="hidden sm:inline">Info Selengkapnya</span>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Kontrol kanan: mute + label usia --}}
        <div class="absolute right-3 top-[86px] md:top-auto md:right-0 md:bottom-[24%] z-20 flex items-center gap-3">
            <button type="button" x-show="showVideo" x-cloak @click="toggleMute()"
                    :aria-label="muted ? 'Nyalakan suara' : 'Matikan suara'"
                    class="grid h-9 w-9 md:h-11 md:w-11 place-items-center rounded-full border-2 border-white/80 bg-black/40 text-white transition hover:bg-white/15">
                <svg x-show="muted" class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5L6 9H2v6h4l5 4V5zM23 9l-6 6M17 9l6 6"/></svg>
                <svg x-show="!muted" class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5L6 9H2v6h4l5 4V5zM15.54 8.46a5 5 0 010 7.07M19.07 4.93a10 10 0 010 14.14"/></svg>
            </button>
            <span class="hidden md:inline-block border-l-[3px] border-zinc-300 bg-[rgba(51,51,51,0.6)] py-1.5 pl-3 pr-10 text-base font-medium text-white">13+</span>
        </div>

        {{-- Indikator slide --}}
        <div class="absolute inset-x-0 bottom-[12%] md:bottom-[15%] z-20 flex justify-start gap-1.5 px-4 md:px-12">
            <template x-for="(h, i) in heroes" :key="i">
                <button type="button" @click="goTo(i)" :aria-label="'Tayangan ' + (i + 1)"
                        class="h-[3px] rounded-full transition-all duration-300"
                        :class="active === i ? 'w-7 bg-white' : 'w-3 bg-white/35 hover:bg-white/60'"></button>
            </template>
        </div>
    </section>
    @else
        <div class="pt-24 md:pt-28"></div>
    @endif

    {{-- ================= RAK ================= --}}
    <div class="relative z-10 -mt-[6vh] md:-mt-[9vh] space-y-6 md:space-y-10">

        {{-- Kategori cepat (mobile, ala chip Netflix) --}}
        @if(!empty($quickLinks))
            <div class="md:hidden flex gap-2 overflow-x-auto px-4 [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                @foreach($quickLinks as $link)
                    <a href="{{ $link['url'] }}" class="shrink-0 rounded-full border border-white/40 bg-black/30 px-3.5 py-1.5 text-[13px] font-semibold text-white backdrop-blur active:bg-white/10">{{ $link['label'] }}</a>
                @endforeach
            </div>
        @endif

        {{-- Lanjutkan Menonton --}}
        @if(!empty($continueWatching))
            <section class="relative">
                <h2 class="mb-2.5 md:mb-3 px-4 md:px-12 text-[17px] md:text-[22px] font-bold tracking-tight text-zinc-100">Lanjutkan Menonton untuk Kamu</h2>
                <div class="flex gap-2 md:gap-2.5 overflow-x-auto px-4 md:px-12 pb-3 pt-1 scroll-px-4 md:scroll-px-12 snap-x [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                    @foreach($continueWatching as $cw)
                        <a href="{{ $cw['url'] }}" class="group w-[112px] sm:w-[136px] md:w-[166px] xl:w-[188px] shrink-0 snap-start">
                            <div class="relative aspect-[2/3] overflow-hidden rounded-t-[4px] bg-zinc-900 transition duration-300 group-hover:scale-[1.04]">
                                @if(!empty($cw['thumbnail']))
                                    <img src="{{ $cw['thumbnail'] }}" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()" class="h-full w-full object-cover">
                                @endif
                                <div class="absolute inset-0 grid place-items-center bg-black/0 opacity-0 transition group-hover:bg-black/40 group-hover:opacity-100">
                                    <span class="grid h-10 w-10 md:h-12 md:w-12 place-items-center rounded-full border-2 border-white bg-black/50">
                                        <svg class="ml-0.5 w-4 h-4 md:w-5 md:h-5 fill-white" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                    </span>
                                </div>
                                <span class="absolute left-1.5 top-1.5 rounded-sm bg-black/70 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-zinc-200">{{ $cw['label'] }}</span>
                            </div>
                            {{-- Progress bar --}}
                            <div class="h-[3px] w-full bg-zinc-700/80">
                                <div class="h-full bg-[#E50914]" style="width: {{ !empty($cw['has_progress']) ? max(6, min(100, $cw['progress_percent'])) : 6 }}%"></div>
                            </div>
                            <div class="rounded-b-[4px] bg-[#181818] px-2 py-1.5">
                                <h3 class="truncate text-[12px] md:text-[13px] font-semibold text-zinc-200" title="{{ $cw['title'] }}">{{ $cw['title'] }}</h3>
                                <p class="truncate text-[10px] md:text-[11px] text-zinc-400">
                                    @if(!empty($cw['episode']) && $cw['label'] !== 'Film')Eps {{ $cw['episode'] }}@if($cw['ago']) · @endif @endif{{ $cw['ago'] }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Top 10 --}}
        @if(!empty($topTen))
            @include('filament.streaming.partials.home-row', [
                'title' => 'Hari Ini di KZSTRM',
                'items' => $topTen,
                'variant' => 'top10',
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
