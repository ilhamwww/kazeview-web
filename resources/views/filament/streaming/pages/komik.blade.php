@php
    // Format angka ringkas: 8926626 -> 8,9 jt
    $kzNum = function ($n) {
        $n = (int) $n;
        if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1, ',', ''), '0'), ',') . ' jt';
        if ($n >= 1000) return rtrim(rtrim(number_format($n / 1000, 1, ',', ''), '0'), ',') . ' rb';
        return (string) $n;
    };
    // Waktu relatif: "2 jam yang lalu"
    $kzAgo = function ($t) {
        if (empty($t)) return null;
        try { return \Carbon\Carbon::parse($t)->locale('id')->diffForHumans(); } catch (\Throwable $e) { return null; }
    };
    $isLanding = empty($search) && $page === 1;
@endphp

<div class="min-h-screen bg-[#050505] text-zinc-100 pb-24 selection:bg-red-600 selection:text-white"
     @scroll-to-catalog.window="document.getElementById('katalog-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
     x-data="{
        recentReads: [],
        init() {
            try {
                const raw = localStorage.getItem('kz_comic_recent_reads');
                if (raw) this.recentReads = JSON.parse(raw);
            } catch(e) {}
        },
        clearRecentReads() {
            if (confirm('Hapus seluruh riwayat webtoon yang terakhir dibaca?')) {
                localStorage.removeItem('kz_comic_recent_reads');
                this.recentReads = [];
            }
        },
        scrollRail(id, dir) {
            const el = document.getElementById(id);
            if (el) el.scrollBy({ left: dir * el.clientWidth * 0.8, behavior: 'smooth' });
        }
     }">
    <style>
        .scrollbar-none { scrollbar-width: none; -ms-overflow-style: none; }
        .scrollbar-none::-webkit-scrollbar { display: none; width: 0; height: 0; }
    </style>

    @if($provider === 'picker')
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-red-900/20 via-transparent to-violet-900/10" aria-hidden="true"></div>
            <div class="relative max-w-7xl mx-auto px-5 md:px-10 pt-24 md:pt-28 lg:pt-32 pb-8 md:pb-12">
                <span class="inline-flex px-3 py-1 rounded-full bg-red-600/10 border border-red-600/30 text-red-400 text-[10px] font-black uppercase tracking-widest">Webtoon · Manga · Manhwa</span>
                <h1 class="mt-4 text-4xl md:text-6xl font-black tracking-tight">Webtoon</h1>
                <p class="mt-3 max-w-xl text-sm md:text-base text-zinc-400 leading-relaxed">Pilih provider dulu, lalu jelajahi koleksi dan lanjutkan membaca komik favoritmu.</p>
            </div>
        </section>
        <section id="provider-picker" class="max-w-7xl mx-auto px-5 md:px-10 pb-12">
            @include('filament.streaming.partials.comic-history')
            <h2 class="text-lg md:text-xl font-black mb-4">Pilih Provider</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach(['native', 'wurmz', 'izanami', 'shinigami'] as $providerId)
                    @php($source = collect($providers)->firstWhere('id', $providerId))
                    <a href="/komik/{{ $source['id'] }}/" data-provider="{{ $source['id'] }}"
                       class="relative overflow-hidden rounded-2xl bg-zinc-900/70 border border-white/10 hover:border-red-500/60 active:scale-[0.98] transition p-5 flex flex-col gap-5 min-h-[210px]">
                        <div class="flex items-start justify-between gap-3">
                            <span class="w-14 h-14 rounded-2xl bg-red-600/15 border border-red-600/20 flex items-center justify-center text-2xl font-black text-red-400">{{ mb_substr($source['name'], 0, 1) }}</span>
                            <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-white/5 text-zinc-400">{{ $source['is_nuc'] ? 'Via NUC' : 'Direct VPS' }}</span>
                        </div>
                        <div class="mt-auto">
                            <h3 class="text-xl font-black">{{ $source['name'] }}</h3>
                            <p class="mt-2 text-xs leading-relaxed text-zinc-400">
                                @if($source['id'] === 'native')
                                    Koleksi database mandiri KZSTRM dengan poster lokal super cepat dan anti down.
                                @elseif($source['id'] === 'wurmz')
                                    Ribuan Manga & Manhwa lengkap langsung dari Wurmz (HD Natsu CDN).
                                @elseif($source['id'] === 'izanami')
                                    Baca koleksi manhua dan manhwa dari Izanami Scans.
                                @else
                                    Memerlukan koneksi NUC. Jika tidak tersedia, pilih provider lain.
                                @endif
                            </p>
                        </div>
                        <span class="text-xs font-bold text-red-400">Jelajahi koleksi →</span>
                    </a>
                @endforeach
            </div>
            <p class="mt-5 text-xs text-zinc-500">Ketersediaan judul dan chapter mengikuti masing-masing provider.</p>
        </section>
    @else
    {{-- ================= HERO CAROUSEL ================= --}}
    @if($isLanding && !empty($heroItems))
        <section class="relative w-full overflow-hidden bg-[#050505]"
                 wire:ignore
                 x-data="{
                    active: 0,
                    total: {{ count($heroItems) }},
                    timer: null,
                    start() { this.stop(); this.timer = setInterval(() => this.next(), 7000); },
                    stop() { if (this.timer) clearInterval(this.timer); },
                    next() { this.active = (this.active + 1) % this.total; },
                    prev() { this.active = (this.active - 1 + this.total) % this.total; },
                    go(i) { this.active = i; this.start(); }
                 }"
                 x-init="start()">
            {{-- Swipe horizontal di HP; geser vertikal tetap scroll halaman --}}
            <div class="relative h-[460px] sm:h-[500px] md:h-[560px] touch-pan-y select-none"
                 x-data="{ sx: 0, sy: 0 }"
                 @touchstart.passive="sx = $event.touches[0].clientX; sy = $event.touches[0].clientY; stop()"
                 @touchend="
                    const dx = $event.changedTouches[0].clientX - sx;
                    const dy = $event.changedTouches[0].clientY - sy;
                    if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) { dx < 0 ? next() : prev(); }
                    start();
                 ">
                @foreach($heroItems as $i => $hero)
                    {{-- Inline opacity awal mencegah slide bertumpuk sebelum Alpine siap; :style yang mengambil alih setelahnya --}}
                    <div class="absolute inset-0 transition-opacity duration-700"
                         :class="active === {{ $i }} ? 'z-10' : 'z-0 pointer-events-none'"
                         :style="{ opacity: active === {{ $i }} ? 1 : 0 }"
                         style="opacity: {{ $i === 0 ? 1 : 0 }}">
                        {{-- Backdrop --}}
                        <img src="{{ $hero['cover'] }}" alt=""
                             class="absolute inset-0 w-full h-full object-cover object-top sm:object-center sm:scale-110 sm:blur-2xl opacity-60 sm:opacity-40"
                             loading="{{ $i === 0 ? 'eager' : 'lazy' }}" aria-hidden="true" />
                        <div class="absolute inset-0 bg-gradient-to-t from-[#050505] via-[#050505]/75 to-[#050505]/10 sm:via-[#050505]/60 sm:to-[#050505]/30"></div>
                        <div class="hidden sm:block absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/70 to-transparent"></div>

                        {{-- Content --}}
                        <div class="relative h-full max-w-[1800px] mx-auto px-5 md:px-10 flex items-end pb-20 md:pb-20 pt-24">
                            <div class="flex items-end gap-5 md:gap-10 w-full">
                                <a href="/komik/detail/{{ $hero['id'] }}"
                                   class="hidden sm:block shrink-0 w-36 md:w-52 lg:w-60 aspect-[2/3] rounded-2xl overflow-hidden ring-1 ring-white/15 shadow-2xl shadow-black bg-zinc-900">
                                    <img src="{{ $hero['cover'] }}" alt="{{ $hero['title'] }}" class="w-full h-full object-cover object-top" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" />
                                </a>

                                <div class="flex-1 min-w-0 max-w-2xl">
                                    <div class="flex items-center gap-2 mb-3 flex-wrap text-[11px] font-bold">
                                        <span class="px-2.5 py-1 rounded-md bg-red-600 text-white uppercase tracking-wider">#{{ $i + 1 }} Populer</span>
                                        <span class="px-2 py-1 rounded-md bg-white/10 text-zinc-200 uppercase tracking-wider">{{ $hero['format'] }}</span>
                                        @if(!empty($hero['rating']) && (float) $hero['rating'] > 0)
                                            <span class="px-2 py-1 rounded-md bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/30">★ {{ $hero['rating'] }}</span>
                                        @endif
                                        <span class="px-2 py-1 rounded-md bg-white/5 text-zinc-400">{{ $hero['status'] }}</span>
                                    </div>

                                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight leading-[1.05] line-clamp-2">
                                        {{ $hero['title'] }}
                                    </h1>

                                    <div class="mt-3 flex items-center gap-x-3 gap-y-1 flex-wrap text-xs text-zinc-400 font-medium">
                                        @if(!empty($hero['genres']))
                                            <span>{{ implode(' • ', $hero['genres']) }}</span>
                                        @endif
                                        @if(!empty($hero['views']))
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                {{ $kzNum($hero['views']) }} dibaca
                                            </span>
                                        @endif
                                    </div>

                                    @if(!empty($hero['description']))
                                        <p class="mt-3 text-sm text-zinc-300/90 leading-relaxed line-clamp-2 md:line-clamp-3">{{ $hero['description'] }}</p>
                                    @endif

                                    <div class="mt-5 grid grid-cols-2 sm:flex sm:items-center gap-3">
                                        <a href="/komik/detail/{{ $hero['id'] }}"
                                           class="inline-flex items-center justify-center gap-2 px-4 sm:px-6 py-3 rounded-xl whitespace-nowrap bg-red-600 hover:bg-red-500 text-white font-bold text-sm shadow-lg shadow-red-900/40 transition active:scale-95">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                                            Baca Sekarang
                                        </a>
                                        @if(!empty($hero['latest_chapter']) && !empty($hero['latest_chapter_id']))
                                            <a href="/komik/{{ $hero['id'] }}/read/{{ $hero['latest_chapter_id'] }}"
                                               class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-3 rounded-xl whitespace-nowrap bg-white/10 hover:bg-white/15 ring-1 ring-white/10 text-white font-bold text-sm transition active:scale-95">
                                                Ch. {{ $hero['latest_chapter'] }} Terbaru
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Controls --}}
                @if(count($heroItems) > 1)
                    <div class="absolute bottom-6 inset-x-0 z-20">
                        <div class="max-w-[1800px] mx-auto px-5 md:px-10 flex items-center gap-3">
                            <div class="flex items-center gap-1.5">
                                @foreach($heroItems as $i => $hero)
                                    <button type="button" @click="go({{ $i }})"
                                            aria-label="Tampilkan {{ $hero['title'] }}"
                                            class="h-1.5 rounded-full transition-all duration-300"
                                            :class="active === {{ $i }} ? 'w-8 bg-red-600' : 'w-3 bg-white/30'"></button>
                                @endforeach
                            </div>
                            <div class="ml-auto hidden sm:flex items-center gap-2">
                                <button type="button" @click="prev(); start()" aria-label="Sebelumnya"
                                        class="w-9 h-9 rounded-full bg-black/50 ring-1 ring-white/15 text-white flex items-center justify-center active:scale-90 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                                </button>
                                <button type="button" @click="next(); start()" aria-label="Berikutnya"
                                        class="w-9 h-9 rounded-full bg-black/50 ring-1 ring-white/15 text-white flex items-center justify-center active:scale-90 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <div class="max-w-[1800px] mx-auto px-5 md:px-10 {{ $isLanding && !empty($heroItems) ? 'pt-4' : 'pt-24 md:pt-28' }} space-y-12">

        {{-- ================= STICKY TOOLBAR: SEARCH + TAB + FORMAT ================= --}}
        <div class="rounded-2xl bg-zinc-900/60 ring-1 ring-white/5 p-3 md:p-4 space-y-3">
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text"
                           wire:model.live.debounce.400ms="search"
                           aria-label="Cari webtoon"
                           placeholder="Cari judul webtoon..."
                           class="w-full pl-10 pr-10 py-3 rounded-xl bg-black/60 border border-white/5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600 transition" />
                    @if(!empty($search))
                        <button type="button" wire:click="clearSearch" aria-label="Hapus pencarian"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-lg flex items-center justify-center text-zinc-400 hover:text-white hover:bg-white/10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endif
                </div>

                <div class="flex items-center p-1 bg-black/60 rounded-xl ring-1 ring-white/5 self-start md:self-auto">
                    <button type="button" wire:click="setTab('popular')"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $tab === 'popular' ? 'bg-red-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Populer
                    </button>
                    <button type="button" wire:click="setTab('latest')"
                            class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $tab === 'latest' ? 'bg-red-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Rilis Baru
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto overflow-y-hidden scrollbar-none -mx-3 px-3 md:-mx-4 md:px-4">
                @foreach($formats as $fmt)
                    <button type="button" wire:click="setFormat('{{ $fmt['id'] }}')"
                            class="shrink-0 px-4 py-1.5 rounded-full text-xs font-semibold transition {{ $format === $fmt['id'] ? 'bg-white text-black' : 'bg-white/5 text-zinc-400 hover:text-white hover:bg-white/10' }}">
                        {{ $fmt['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ================= TERAKHIR KALI DIBACA ================= --}}
        <section x-show="recentReads.length > 0 && @js($isLanding)" style="display: none;">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Terakhir Kali Dibaca</h2>
                <button type="button" @click="clearRecentReads()" class="text-xs font-semibold text-zinc-500 hover:text-red-400 transition">
                    Hapus Riwayat
                </button>
            </div>

            <div class="flex gap-3 md:gap-4 overflow-x-auto overflow-y-hidden overscroll-x-contain scrollbar-none snap-x">
                <template x-for="item in recentReads" :key="item.comicId">
                    <a :href="'/komik/detail/' + item.comicId"
                       class="snap-start shrink-0 w-[260px] sm:w-[300px] flex gap-3 p-2.5 rounded-2xl bg-zinc-900/70 ring-1 ring-white/5 hover:ring-red-600/50 transition group">
                        <div class="relative shrink-0 w-16 aspect-[2/3] rounded-lg overflow-hidden bg-zinc-800">
                            <img x-show="item.comicCover" :src="item.comicCover" alt="" class="relative z-10 w-full h-full object-cover" loading="lazy" x-on:error="$el.style.display='none'" />
                            <span class="absolute inset-0 flex items-center justify-center text-lg font-black text-zinc-600 -z-0" x-text="(item.comicTitle||'?').charAt(0)" aria-hidden="true"></span>
                        </div>
                        <div class="flex-1 min-w-0 flex flex-col justify-center">
                            <p class="text-sm font-bold text-white line-clamp-1 group-hover:text-red-400 transition" x-text="item.comicTitle"></p>
                            <p class="mt-0.5 text-xs text-zinc-400 line-clamp-1" x-text="item.chapterLabel"></p>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="flex-1 h-1 rounded-full bg-zinc-800 overflow-hidden">
                                    <div class="h-full bg-red-600 rounded-full" :style="'width: ' + (item.progress || 0) + '%'"></div>
                                </div>
                                <span class="text-[10px] font-bold text-red-400" x-text="(item.progress || 0) + '%'"></span>
                            </div>
                            <p class="mt-1 text-[10px] text-zinc-500">Lanjut dari panel <span class="text-zinc-300 font-semibold" x-text="item.panelIndex || 1"></span></p>
                        </div>
                    </a>
                </template>
            </div>
        </section>

        {{-- ================= TOP 10 HARI INI ================= --}}
        @if($isLanding && $format === 'all' && !empty($topRanked))
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Top 10 Terpopuler</h2>
                    <div class="hidden md:flex items-center gap-2">
                        <button type="button" @click="scrollRail('rail-top10', -1)" aria-label="Geser kiri" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        </button>
                        <button type="button" @click="scrollRail('rail-top10', 1)" aria-label="Geser kanan" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </button>
                    </div>
                </div>

                <div id="rail-top10" class="flex items-end gap-5 md:gap-7 pt-3 pb-3 overflow-x-auto overflow-y-hidden overscroll-x-contain scrollbar-none snap-x scroll-smooth">
                    @foreach($topRanked as $rank => $item)
                        <a href="/komik/detail/{{ $item['id'] }}" class="snap-start shrink-0 flex items-end overflow-hidden py-1 group">
                            <span class="text-[84px] md:text-[112px] font-black leading-[0.78] tracking-tighter text-[#050505] select-none -mr-1 md:-mr-2 z-0"
                                  style="-webkit-text-stroke: 3px rgba(255,255,255,0.55);" aria-hidden="true">{{ $rank + 1 }}</span>
                            <div class="relative z-10 w-28 md:w-36 aspect-[2/3] rounded-xl overflow-hidden ring-1 ring-white/10 bg-zinc-800 shadow-xl group-hover:ring-red-600/60 transition">
                                <img src="{{ $item['cover'] }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover" loading="lazy" />
                                <div class="absolute inset-x-0 bottom-0 pt-8 p-2 bg-gradient-to-t from-black via-black/85 to-transparent">
                                    <p class="text-[11px] font-bold text-white line-clamp-2 leading-tight min-h-[2.5em]">{{ $item['title'] }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================= UPDATE TERBARU (LIST) ================= --}}
        @if($isLanding && !empty($latestHorizontal))
            <section>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex w-2 h-2">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75 animate-ping"></span>
                            <span class="relative inline-flex w-2 h-2 rounded-full bg-red-600"></span>
                        </span>
                        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Baru Update</h2>
                    </div>
                    <button type="button" wire:click="setTab('latest')" class="text-xs md:text-sm font-semibold text-zinc-400 hover:text-white transition">
                        Lihat Semua →
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2.5">
                    @foreach(array_slice($latestHorizontal, 0, 8) as $item)
                        <div class="flex gap-3 p-2.5 rounded-2xl bg-zinc-900/50 ring-1 ring-white/5 hover:bg-zinc-900 transition">
                            <a href="/komik/detail/{{ $item['id'] }}" class="shrink-0 w-16 aspect-[2/3] rounded-lg overflow-hidden bg-zinc-800">
                                <img src="{{ $item['cover'] }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover" loading="lazy" />
                            </a>
                            <div class="flex-1 min-w-0 flex flex-col">
                                <a href="/komik/detail/{{ $item['id'] }}" title="{{ $item['title'] }}" class="text-sm font-bold text-white line-clamp-2 leading-snug hover:text-red-400 transition">{{ $item['title'] }}</a>
                                <p class="mt-0.5 text-[11px] text-zinc-500">{{ $item['format'] }}{{ !empty($item['genres'][0]) ? ' • ' . $item['genres'][0] : '' }}</p>
                                <div class="mt-auto pt-2 flex items-center justify-between gap-2">
                                    @if(!empty($item['latest_chapter_id']))
                                        <a href="/komik/{{ $item['id'] }}/read/{{ $item['latest_chapter_id'] }}"
                                           class="px-2.5 py-1 rounded-md bg-red-600/15 text-red-400 ring-1 ring-red-600/30 text-[11px] font-bold hover:bg-red-600 hover:text-white transition">
                                            Ch. {{ $item['latest_chapter'] }}
                                        </a>
                                    @endif
                                    @if($ago = $kzAgo($item['latest_chapter_time'] ?? null))
                                        <span class="text-[10px] text-zinc-500 truncate">{{ $ago }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================= KATALOG GRID ================= --}}
        <section id="katalog-grid" class="scroll-mt-24">
            <div class="flex items-end justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-xl md:text-2xl font-extrabold tracking-tight flex items-center gap-2">
                        @if(!empty($search))
                            Hasil untuk "<span class="text-red-500">{{ $search }}</span>"
                        @elseif($tab === 'latest')
                            Rilis Terbaru · <span class="text-red-500">{{ collect($providers)->firstWhere('id', $provider)['name'] ?? 'Webtoon' }}</span>
                        @else
                            Jelajahi {{ $format !== 'all' ? ucfirst($format) : 'Semua Webtoon' }} (<span class="text-red-500">{{ collect($providers)->firstWhere('id', $provider)['name'] ?? 'Webtoon' }}</span>)
                        @endif
                        <svg wire:loading wire:target="nextPage, prevPage, setProvider, setFormat, setTab, search, clearSearch" class="w-4 h-4 text-red-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </h2>
                    <p class="mt-1 text-xs text-zinc-500">Halaman {{ $page }} dari {{ $totalPage }}</p>
                </div>
            </div>

            @if(empty($items))
                <div class="py-20 text-center rounded-2xl bg-zinc-900/40 ring-1 ring-white/5 p-6">
                    @if($provider === 'shinigami')
                        <div class="w-12 h-12 rounded-full bg-amber-500/10 text-amber-400 mx-auto flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <p class="text-base font-bold text-white">Server Shinigami (NUC Relay) Sedang Offline</p>
                        <p class="mt-1 text-xs text-zinc-400 max-w-md mx-auto">Shinigami belum dapat diakses. Kamu bisa mencoba koleksi dari <strong class="text-red-400">Izanami Scans</strong>.</p>
                        <div class="mt-4 flex items-center justify-center gap-2 flex-wrap">
                            <a href="/komik/wurmz/" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-xs font-bold text-white transition shadow-lg shadow-red-900/40">
                                Beralih ke Wurmz Scans
                            </a>
                            <a href="/komik/izanami/" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-xs font-bold text-white transition">
                                Beralih ke Izanami Scans
                            </a>
                        </div>
                    @else
                        <p class="text-sm font-bold text-zinc-300">Webtoon tidak ditemukan</p>
                        <p class="mt-1 text-xs text-zinc-500">Coba kata kunci lain atau ganti filter tipe.</p>
                        <button type="button" wire:click="clearSearch" class="mt-4 px-4 py-2 rounded-lg bg-white/10 hover:bg-white/15 text-xs font-semibold text-white transition">
                            Reset Pencarian
                        </button>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 2xl:grid-cols-8 gap-x-3 gap-y-6 md:gap-x-4 transition-opacity duration-200"
                     wire:loading.class="opacity-40 pointer-events-none"
                     wire:target="nextPage, prevPage, setFormat, setTab, search, clearSearch">
                    @foreach($items as $comic)
                        <a wire:key="comic-card-p{{ $page }}-{{ $comic['id'] }}" href="/komik/detail/{{ $comic['id'] }}" class="group flex flex-col">
                            <div class="relative aspect-[2/3] w-full rounded-xl overflow-hidden ring-1 ring-white/10 bg-zinc-800 group-hover:ring-red-600/60 transition">
                                <img src="{{ $comic['cover'] }}" alt="{{ $comic['title'] }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                     loading="lazy" />
                                <div class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/80 to-transparent pointer-events-none"></div>

                                <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-black/70 text-white">
                                    {{ $comic['format'] }}
                                </span>
                                @if(!empty($comic['rating']) && (float) $comic['rating'] > 0)
                                    <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-black/70 text-amber-400">
                                        ★ {{ $comic['rating'] }}
                                    </span>
                                @endif
                                <div class="absolute bottom-1.5 inset-x-1.5 flex items-center justify-between gap-1">
                                    <span class="px-1.5 py-0.5 rounded bg-red-600 text-[10px] font-black text-white">
                                        {{ !empty($comic['latest_chapter']) ? (str_starts_with(strtolower(trim($comic['latest_chapter'])), 'ch') ? $comic['latest_chapter'] : 'Ch. ' . $comic['latest_chapter']) : $comic['status'] }}
                                    </span>
                                    @if(!empty($comic['views']))
                                        <span class="hidden sm:inline px-1.5 py-0.5 rounded bg-black/70 text-[10px] font-semibold text-zinc-200">{{ $kzNum($comic['views']) }}</span>
                                    @endif
                                </div>
                            </div>
                            <p class="mt-2 text-xs md:text-sm font-semibold text-zinc-100 group-hover:text-red-400 transition line-clamp-2 leading-snug min-h-[2.75em]">
                                {{ $comic['title'] }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-zinc-500 line-clamp-1">
                                {{ $comic['status'] }}{{ !empty($comic['genres'][0]) ? ' • ' . $comic['genres'][0] : '' }}
                            </p>
                        </a>
                    @endforeach
                </div>

                {{-- ================= PAGINATION ================= --}}
                <div class="mt-10 flex items-center justify-center gap-2">
                    <button type="button" wire:click="prevPage" @disabled($page <= 1) wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $page > 1 ? 'bg-zinc-900 ring-1 ring-white/10 text-white hover:bg-zinc-800' : 'bg-zinc-950 text-zinc-700 cursor-not-allowed' }}">
                        ← Sebelumnya
                    </button>
                    <span class="px-4 py-2.5 rounded-xl bg-black ring-1 ring-white/5 text-xs font-bold text-zinc-300 tabular-nums">
                        {{ $page }} <span class="text-zinc-600">/ {{ $totalPage }}</span>
                    </span>
                    <button type="button" wire:click="nextPage" @disabled(!$hasMore) wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold transition {{ $hasMore ? 'bg-red-600 text-white hover:bg-red-500' : 'bg-zinc-950 text-zinc-700 cursor-not-allowed' }}">
                        Selanjutnya →
                    </button>
                </div>
            @endif
        </section>
    </div>
    @endif
</div>
