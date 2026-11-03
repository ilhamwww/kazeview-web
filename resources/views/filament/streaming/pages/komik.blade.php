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
            if (confirm('Hapus seluruh riwayat komik yang terakhir dibaca?')) {
                localStorage.removeItem('kz_comic_recent_reads');
                this.recentReads = [];
            }
        }
     }">
    {{-- ================= HERO SPOTLIGHT BANNER ================= --}}
    @if(empty($search) && !empty($heroItems))
        @php
            $featured = $heroItems[0] ?? null;
        @endphp
        @if($featured)
            <section class="relative w-full aspect-[16/9] md:aspect-[21/9] lg:aspect-[25/9] max-h-[520px] overflow-hidden bg-zinc-950 border-b border-white/5">
                {{-- Backdrop Image with Vignette & Gradients --}}
                <img src="{{ $featured['cover'] }}"
                     alt="{{ $featured['title'] }}"
                     class="absolute inset-0 w-full h-full object-cover object-top opacity-35 filter blur-sm scale-105"
                     loading="eager" />

                <div class="absolute inset-0 bg-gradient-to-t from-[#050505] via-[#050505]/70 to-transparent"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/80 to-transparent"></div>

                {{-- Hero Content --}}
                <div class="relative max-w-7xl mx-auto h-full px-4 sm:px-6 lg:px-8 flex items-end pb-8 md:pb-12">
                    <div class="flex items-end gap-5 md:gap-8 max-w-3xl">
                        {{-- Poster Card --}}
                        <a href="/komik/{{ $featured['id'] }}" class="shrink-0 w-28 sm:w-36 md:w-44 aspect-[2/3] rounded-2xl overflow-hidden ring-1 ring-white/15 shadow-2xl shadow-black/80 group">
                            <img src="{{ $featured['cover'] }}" alt="{{ $featured['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                        </a>

                        {{-- Metadata & CTA --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-red-600 text-white shadow-lg shadow-red-900/40">
                                    {{ $featured['format'] ?? 'Manhwa' }}
                                </span>
                                @if(!empty($featured['rating']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/30">
                                        ★ {{ $featured['rating'] }}
                                    </span>
                                @endif
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-white/10 text-zinc-300">
                                    {{ $featured['status'] ?? 'Ongoing' }}
                                </span>
                            </div>

                            <h1 class="text-xl sm:text-2xl md:text-4xl font-black text-white tracking-tight line-clamp-2 drop-shadow-md">
                                <a href="/komik/{{ $featured['id'] }}" class="hover:text-red-500 transition">
                                    {{ $featured['title'] }}
                                </a>
                            </h1>

                            <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                                @foreach($featured['genres'] ?? [] as $genre)
                                    <span class="text-xs text-zinc-400 bg-white/5 px-2 py-0.5 rounded-md font-medium">
                                        {{ $genre }}
                                    </span>
                                @endforeach
                            </div>

                            <div class="mt-4 flex items-center gap-3">
                                <a href="/komik/{{ $featured['id'] }}"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-sm tracking-wide shadow-lg shadow-red-900/50 transition active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                                    <span>Mulai Baca</span>
                                </a>
                                @if(!empty($featured['latest_chapter']))
                                    <span class="text-xs text-zinc-400 font-semibold">
                                        Chapter Terbaru: <span class="text-white font-bold">Ch. {{ $featured['latest_chapter'] }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 sm:pt-10">
        {{-- ================= SEARCH BAR & FILTERS ================= --}}
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 pb-6 border-b border-zinc-800/80">
            {{-- Search Bar --}}
            <div class="relative flex-1 max-w-lg">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </div>
                <input type="text"
                       wire:model.live.debounce.350ms="search"
                       placeholder="Cari manhwa, manga, atau manhua..."
                       class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-zinc-900/90 border border-zinc-800 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition shadow-inner" />
                @if(!empty($search))
                    <button type="button" wire:click="clearSearch" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-zinc-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            {{-- Tabs (Populer vs Terbaru) --}}
            <div class="flex items-center gap-1.5 p-1 bg-zinc-900/90 border border-zinc-800 rounded-xl self-start md:self-auto">
                <button type="button" wire:click="setTab('popular')"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold transition {{ $tab === 'popular' ? 'bg-red-600 text-white shadow-md shadow-red-900/40' : 'text-zinc-400 hover:text-white' }}">
                    Populer 🔥
                </button>
                <button type="button" wire:click="setTab('latest')"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold transition {{ $tab === 'latest' ? 'bg-red-600 text-white shadow-md shadow-red-900/40' : 'text-zinc-400 hover:text-white' }}">
                    Rilis Baru ✨
                </button>
            </div>
        </div>

        {{-- ================= FORMAT PILLS (MANHWA, MANGA, MANHUA) ================= --}}
        <div class="flex items-center gap-2 overflow-x-auto py-4 scrollbar-none">
            @foreach($formats as $fmt)
                <button type="button" wire:click="setFormat('{{ $fmt['id'] }}')"
                        class="shrink-0 px-4 py-2 rounded-xl text-xs font-bold tracking-wide transition border {{ $format === $fmt['id'] ? 'bg-white/10 text-white border-red-500 ring-1 ring-red-500/50' : 'bg-zinc-900/60 text-zinc-400 border-zinc-800/80 hover:text-white hover:bg-zinc-800/50' }}">
                    {{ $fmt['label'] }}
                </button>
            @endforeach
        </div>

        {{-- ================= SECTION: TERAKHIR KALI DIBACA (RECENT READS) ================= --}}
        <div x-show="recentReads.length > 0 && @js(empty($search)) && @js($page === 1)"
             class="mt-3 mb-8"
             style="display: none;">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    <h2 class="text-lg md:text-xl font-black text-white tracking-tight">Terakhir Kali Dibaca</h2>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/10 text-zinc-300" x-text="recentReads.length"></span>
                </div>
                <button type="button" @click="clearRecentReads()"
                        class="text-xs font-semibold text-zinc-500 hover:text-red-400 transition cursor-pointer">
                    Hapus Riwayat
                </button>
            </div>

            {{-- Horizontal Cards --}}
            <div class="flex gap-3.5 md:gap-4 overflow-x-auto pb-4 scrollbar-none scroll-smooth">
                <template x-for="item in recentReads" :key="item.comicId">
                    <div class="shrink-0 w-36 sm:w-44 md:w-48 group flex flex-col">
                        <a :href="'/komik/' + item.comicId + '/read/' + item.chapterId"
                           class="block relative aspect-[2/3] w-full rounded-2xl overflow-hidden ring-1 ring-red-500/30 shadow-xl bg-zinc-900 group-hover:ring-red-500 transition duration-300">
                            <img :src="item.comicCover" :alt="item.comicTitle"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                 loading="lazy" />

                            {{-- Badges Top --}}
                            <div class="absolute top-2 inset-x-2 flex items-center justify-between pointer-events-none">
                                <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-black/80 backdrop-blur-md text-white ring-1 ring-white/10"
                                      x-text="item.comicFormat || 'Manhwa'"></span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-red-600 text-white shadow-md"
                                      x-text="(item.progress || 0) + '%'"></span>
                            </div>

                            {{-- Gradient Bottom --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/30 to-transparent"></div>

                            {{-- Progress Bar at bottom of poster --}}
                            <div class="absolute bottom-0 inset-x-0 h-1.5 bg-zinc-800">
                                <div class="h-full bg-red-600 transition-all duration-300 shadow-[0_0_8px_rgba(229,9,20,0.8)]"
                                     :style="'width: ' + (item.progress || 0) + '%'"></div>
                            </div>

                            {{-- Chapter Info --}}
                            <div class="absolute bottom-3 inset-x-2.5">
                                <span class="text-xs font-black text-white drop-shadow truncate block"
                                      x-text="item.chapterLabel"></span>
                            </div>
                        </a>

                        {{-- Comic Title & Details --}}
                        <div class="mt-2.5">
                            <a :href="'/komik/' + item.comicId"
                               class="font-bold text-xs sm:text-sm text-zinc-100 group-hover:text-red-400 transition line-clamp-1"
                               x-text="item.comicTitle"></a>
                            <p class="text-[11px] text-zinc-500 font-medium truncate mt-0.5">
                                Lanjut: panel <span class="text-zinc-300 font-bold" x-text="item.panelIndex || 1"></span> (<span class="text-red-400 font-bold" x-text="(item.progress || 0) + '%'"></span>)
                            </p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ================= SECTION: HORIZONTAL LATEST UPDATES (Jika bukan search) ================= --}}
        @if(empty($search) && !empty($latestHorizontal) && $page === 1)
            <div class="mt-4 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
                        <h2 class="text-lg md:text-xl font-black text-white tracking-tight">Update Chapter Hari Ini</h2>
                    </div>
                </div>

                {{-- Horizontal Rail --}}
                <div class="flex gap-3 md:gap-4 overflow-x-auto pb-4 scrollbar-none scroll-smooth">
                    @foreach($latestHorizontal as $item)
                        <div class="shrink-0 w-32 sm:w-36 md:w-40 group">
                            <a href="/komik/{{ $item['id'] }}" class="block relative aspect-[2/3] rounded-xl overflow-hidden ring-1 ring-white/10 shadow-lg bg-zinc-900">
                                <img src="{{ $item['cover'] }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy" />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-transparent"></div>
                                @if(!empty($item['latest_chapter']))
                                    <div class="absolute bottom-2 inset-x-2">
                                        <span class="block px-2 py-1 rounded-md bg-red-600/90 backdrop-blur-sm text-[10px] font-black text-white text-center leading-none">
                                            Ch. {{ $item['latest_chapter'] }}
                                        </span>
                                    </div>
                                @endif
                            </a>
                            <a href="/komik/{{ $item['id'] }}" class="mt-2 block">
                                <p class="text-xs font-bold text-zinc-200 line-clamp-1 group-hover:text-red-400 transition">
                                    {{ $item['title'] }}
                                </p>
                                <span class="text-[10px] font-semibold text-zinc-500">
                                    {{ $item['format'] }}
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ================= SECTION: MAIN GRID ================= --}}
        <div class="mt-4 scroll-mt-28" id="katalog-grid">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg md:text-xl font-black text-white tracking-tight flex items-center gap-2">
                    <span>
                        @if(!empty($search))
                            Hasil Pencarian: "<span class="text-red-500">{{ $search }}</span>"
                        @elseif($tab === 'latest')
                            Semua Rilis Terbaru
                        @else
                            Koleksi {{ $format !== 'all' ? ucfirst($format) : 'Komik' }} Terpopuler
                        @endif
                    </span>
                    <span wire:loading wire:target="nextPage, prevPage, setFormat, setTab, search" class="inline-flex items-center">
                        <svg class="w-4 h-4 text-red-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </span>
                </h2>
                <span class="text-xs font-semibold text-zinc-500">Halaman {{ $page }}</span>
            </div>

            @if(empty($items))
                <div class="py-16 text-center rounded-2xl bg-zinc-900/40 border border-zinc-800/80">
                    <svg class="w-12 h-12 mx-auto text-zinc-600 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    <p class="text-sm font-bold text-zinc-400">Tidak ada komik yang ditemukan.</p>
                    <button type="button" wire:click="clearSearch" class="mt-3 px-4 py-1.5 rounded-lg bg-white/10 hover:bg-white/15 text-xs font-semibold text-white transition">
                        Reset Pencarian
                    </button>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5 sm:gap-4 md:gap-5 transition-opacity duration-200"
                     wire:loading.class="opacity-50 pointer-events-none"
                     wire:target="nextPage, prevPage, setFormat, setTab, search">
                    @foreach($items as $comic)
                        <div wire:key="comic-card-p{{ $page }}-{{ $comic['id'] }}" class="group flex flex-col">
                            <a href="/komik/{{ $comic['id'] }}" class="relative aspect-[2/3] w-full rounded-2xl overflow-hidden ring-1 ring-white/10 bg-zinc-800/90 shadow-xl group-hover:ring-red-500/50 transition duration-300">
                                <img src="{{ $comic['cover'] }}"
                                     alt="{{ $comic['title'] }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-500 relative z-10"
                                     loading="lazy" />

                                {{-- Badges Overlay --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent z-10"></div>

                                {{-- Top Badges --}}
                                <div class="absolute top-2 inset-x-2 flex items-center justify-between gap-1 pointer-events-none z-20">
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-black/75 backdrop-blur-md text-white ring-1 ring-white/10">
                                        {{ $comic['format'] }}
                                    </span>
                                    @if(!empty($comic['rating']))
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/90 text-black leading-none shadow-sm">
                                            ★ {{ $comic['rating'] }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Bottom Badges --}}
                                <div class="absolute bottom-2.5 inset-x-2.5 z-20">
                                    <span class="inline-block px-2 py-1 rounded-lg bg-red-600/90 backdrop-blur-sm text-[11px] font-black text-white shadow-md">
                                        {{ !empty($comic['latest_chapter']) ? 'Ch. ' . $comic['latest_chapter'] : 'Ongoing' }}
                                    </span>
                                </div>
                            </a>

                            {{-- Title & Info --}}
                            <div class="mt-2.5 flex-1 flex flex-col">
                                <a href="/komik/{{ $comic['id'] }}" class="font-bold text-xs sm:text-sm text-zinc-100 group-hover:text-red-400 transition line-clamp-2 leading-snug">
                                    {{ $comic['title'] }}
                                </a>
                                <div class="mt-1 flex items-center gap-1.5 text-[11px] text-zinc-500">
                                    <span>{{ $comic['status'] }}</span>
                                    @if(!empty($comic['genres'][0]))
                                        <span>·</span>
                                        <span class="line-clamp-1">{{ $comic['genres'][0] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ================= PAGINATION ================= --}}
                <div class="mt-10 flex items-center justify-center gap-3">
                    <button type="button" wire:click="prevPage" @if($page <= 1) disabled @endif
                            wire:loading.attr="disabled"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold transition border {{ $page > 1 ? 'bg-zinc-900 border-zinc-700 text-white hover:bg-zinc-800' : 'bg-zinc-950 border-zinc-900 text-zinc-600 cursor-not-allowed' }}">
                        ← Halaman Sebelumnya
                    </button>
                    <span class="text-xs font-bold text-zinc-400 px-3 py-2 bg-zinc-900/60 rounded-xl border border-zinc-800">
                        {{ $page }} / {{ $totalPage }}
                    </span>
                    <button type="button" wire:click="nextPage" @if(!$hasMore) disabled @endif
                            wire:loading.attr="disabled"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold transition border {{ $hasMore ? 'bg-red-600 border-red-500 text-white hover:bg-red-500 shadow-lg shadow-red-900/40' : 'bg-zinc-950 border-zinc-900 text-zinc-600 cursor-not-allowed' }}">
                        Halaman Selanjutnya →
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
