@php
    $star = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';
    $heroPayload = array_map(fn ($h) => [
        'title' => $h['title'] ?? '',
        'url' => $h['url'] ?? '#',
        'trailer' => $h['trailer'] ?? '',
    ], $heroes);
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28">

    {{-- ================= BILLBOARD ================= --}}
    @if(!empty($heroes))
    <section class="relative w-full h-[72vh] min-h-[480px] md:h-[86vh] md:min-h-[600px] overflow-hidden bg-black"
             x-data="{
                heroes: @js($heroPayload),
                active: 0,
                showVideo: false,
                muted: true,
                timer: null,
                gen: 0,
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
                    // Naikkan generasi: callback dari slide sebelumnya (promise play yang
                    // dibatalkan, timer lama) akan diabaikan.
                    const gen = ++this.gen;
                    this.showVideo = false;
                    const v = this.$refs.video;
                    if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
                    const trailer = this.heroes[this.active]?.trailer;
                    if (trailer && !this.saveData()) {
                        // Tampilkan gambar dulu, trailer menyusul (ala Netflix).
                        this.timer = setTimeout(() => this.startTrailer(trailer, gen), 2500);
                    } else {
                        this.timer = setTimeout(() => this.next(), 9000);
                    }
                },
                startTrailer(src, gen) {
                    const v = this.$refs.video;
                    if (!v || gen !== this.gen) return;
                    v.muted = this.muted;
                    v.src = src;
                    v.play().then(() => { if (gen === this.gen) this.showVideo = true; })
                        .catch(() => {
                            if (gen !== this.gen) return;
                            clearTimeout(this.timer);
                            this.timer = setTimeout(() => this.next(), 7000);
                        });
                },
                onEnded() { this.next(); },
                onError() {
                    // Mengosongkan src saat ganti slide juga memicu 'error'; abaikan itu
                    // supaya timer trailer slide baru tidak tertimpa.
                    const v = this.$refs.video;
                    if (!v || !v.getAttribute('src')) return;
                    clearTimeout(this.timer);
                    this.showVideo = false;
                    this.timer = setTimeout(() => this.next(), 6000);
                },
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
            {{-- Opacity awal dari server, lalu Alpine mengambil alih lewat :style (bukan class,
                 karena style inline selalu menang atas class). --}}
            <div class="absolute inset-0 transition-opacity duration-700"
                 style="opacity: {{ $index === 0 ? 1 : 0 }}"
                 :style="{ opacity: active === {{ $index }} && !showVideo ? 1 : 0 }">
                <img src="{{ $hero['banner'] ?: ($hero['thumbnail'] ?? '') }}" alt=""
                     class="h-full w-full object-cover object-[center_20%]"
                     @if($index === 0) fetchpriority="high" @endif decoding="async">
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
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-[42%] bg-gradient-to-t from-[#050505] via-[#050505]/70 to-transparent"></div>

        {{-- Info judul --}}
        @foreach($heroes as $index => $hero)
            <div x-show="active === {{ $index }}" x-cloak
                 x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute inset-x-0 bottom-[19%] md:bottom-[24%] z-10 px-4 md:px-12"
                 @if($index !== 0) style="display:none" @endif>
                <div class="max-w-[92%] sm:max-w-xl lg:max-w-2xl">
                    <div class="mb-4 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded bg-red-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.15em] text-white">Sorotan</span>
                        <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-400">{{ ($hero['is_series'] ?? false) ? 'Serial' : 'Film' }}</span>
                    </div>

                    <h1 class="text-4xl font-black leading-[0.95] tracking-tight line-clamp-2 drop-shadow-2xl sm:text-5xl lg:text-6xl xl:text-7xl">
                        {{ $hero['title'] ?? '' }}
                    </h1>

                    <div class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm font-semibold text-zinc-300">
                        @if(!empty($hero['rating']))
                            <span class="flex items-center gap-1 text-yellow-400"><svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $star }}"/></svg>{{ number_format((float) $hero['rating'], 1) }}</span>
                        @endif
                        @if(!empty($hero['year']))<span>{{ $hero['year'] }}</span>@endif
                        <span class="rounded border border-zinc-500 px-1.5 py-0.5 text-[10px] font-black text-zinc-200">HD</span>
                        @if(!empty($hero['genre']))
                            <span class="text-zinc-300">{{ implode(' • ', array_slice(array_map('trim', explode(',', $hero['genre'])), 0, 3)) }}</span>
                        @endif
                    </div>

                    @if(!empty($hero['description']))
                        <p class="mt-3 hidden sm:block max-w-xl text-sm md:text-[17px] leading-snug text-zinc-100 line-clamp-3 drop-shadow-[0_1px_6px_rgba(0,0,0,0.8)]">{{ $hero['description'] }}</p>
                    @endif

                    <div class="mt-5 flex items-center gap-2.5 md:gap-3">
                        <a href="{{ $hero['url'] }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-white pl-5 pr-6 py-3 text-sm font-bold text-black transition hover:bg-zinc-200 active:scale-95">
                            <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                            {{ ($hero['is_series'] ?? false) ? 'Tonton Sekarang' : 'Putar' }}
                        </a>
                        <a href="{{ $hero['url'] }}"
                           class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-zinc-500/30 px-5 py-3 text-sm font-bold text-white backdrop-blur-md transition hover:bg-zinc-500/50 active:scale-95">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                            <span class="sm:hidden">Info</span><span class="hidden sm:inline">Info Lengkap</span>
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
    <div class="relative z-10 -mt-[6vh] md:-mt-[9vh] space-y-7 md:space-y-11">

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
