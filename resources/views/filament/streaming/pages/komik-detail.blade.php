<div class="min-h-screen bg-[#050505] text-zinc-100 pb-28 selection:bg-red-600 selection:text-white"
     x-data="{
        history: null,
        serverHistory: @js($serverHistory),
        viewMode: 'list', // 'list' (default daftar chapter) | 'kotatsu' (continuous thumbnail grid)
        kotatsuLoadedChapters: [],
        kotatsuLoading: false,
        kotatsuHasMore: true,
        kotatsuBatchIndex: 0,
        previewModalOpen: false,
        previewLoading: false,
        previewChapter: null,
        previewPages: [],
        previewError: null,
        init() {
            try {
                let localHist = null;
                const raw = localStorage.getItem('kz_comic_{{ $comic['id'] }}');
                if (raw) localHist = JSON.parse(raw);

                if (this.serverHistory && (!localHist || (this.serverHistory.updatedAt || 0) >= (localHist.updatedAt || 0))) {
                    this.history = this.serverHistory;
                    localStorage.setItem('kz_comic_{{ $comic['id'] }}', JSON.stringify(this.serverHistory));
                } else if (localHist) {
                    this.history = localHist;
                }
            } catch(e) {}
            this.syncChaptersFromClient();
        },
        setViewMode(mode) {
            this.viewMode = mode;
            if (mode === 'kotatsu' && this.kotatsuLoadedChapters.length === 0) {
                this.loadMoreKotatsuChapters();
            }
        },
        async loadMoreKotatsuChapters() {
            if (this.kotatsuLoading || !this.kotatsuHasMore) return;
            this.kotatsuLoading = true;

            const allChs = @js($chapters);
            if (!Array.isArray(allChs) || allChs.length === 0) {
                this.kotatsuLoading = false;
                this.kotatsuHasMore = false;
                return;
            }

            // Ambil batch 3 chapter berikutnya
            const batchSize = 3;
            const start = this.kotatsuBatchIndex;
            const end = Math.min(allChs.length, start + batchSize);

            if (start >= allChs.length) {
                this.kotatsuHasMore = false;
                this.kotatsuLoading = false;
                return;
            }

            const targetBatch = allChs.slice(start, end);
            this.kotatsuBatchIndex = end;
            if (end >= allChs.length) {
                this.kotatsuHasMore = false;
            }

            const comicId = '{{ $comic['id'] }}';

            for (const ch of targetBatch) {
                let pages = [];
                const upstreamChId = ch.upstream_chapter_id || '';

                try {
                    const url = '/api/comic/chapter-preview?comic_id=' + encodeURIComponent(comicId) + '&chapter_id=' + encodeURIComponent(ch.id) + '&upstream_chapter_id=' + encodeURIComponent(upstreamChId);
                    const res = await fetch(url).then(r => r.ok ? r.json() : null).catch(() => null);

                    if (res && res.ok && Array.isArray(res.pages) && res.pages.length > 0) {
                        pages = res.pages;
                    } else if (upstreamChId) {
                        // Fallback client shinigami
                        const shRes = await fetch('https://api.shngm.io/v1/chapter/detail/' + upstreamChId).then(r => r.ok ? r.json() : null).catch(() => null);
                        if (shRes && shRes.retcode === 0 && shRes.data && shRes.data.chapter && Array.isArray(shRes.data.chapter.data)) {
                            const baseUrl = (shRes.data.base_url || 'https://assets.shngm.id').replace(/\/+$/, '');
                            const path = '/' + String(shRes.data.chapter.path || '').replace(/^\/+|\/+$/g, '') + '/';
                            pages = shRes.data.chapter.data.map((f, idx) => {
                                const rawUrl = baseUrl + path + f;
                                return {
                                    index: idx + 1,
                                    raw_url: rawUrl,
                                    url: '/api/proxy-image?url=' + encodeURIComponent(rawUrl)
                                };
                            });
                        }
                    }
                } catch(e) {}

                this.kotatsuLoadedChapters.push({
                    chapter: ch,
                    pages: pages
                });
            }

            this.kotatsuLoading = false;
        },
        async openChapterPreview(ch) {
            this.previewChapter = ch;
            this.previewPages = [];
            this.previewError = null;
            this.previewLoading = true;
            this.previewModalOpen = true;

            const comicId = '{{ $comic['id'] }}';
            const chapterId = ch.id;
            const upstreamChId = ch.upstream_chapter_id || '';

            try {
                // 1. Coba ambil dari endpoint server cache
                const url = '/api/comic/chapter-preview?comic_id=' + encodeURIComponent(comicId) + '&chapter_id=' + encodeURIComponent(chapterId) + '&upstream_chapter_id=' + encodeURIComponent(upstreamChId);
                const res = await fetch(url).then(r => r.ok ? r.json() : null).catch(() => null);

                if (res && res.ok && Array.isArray(res.pages) && res.pages.length > 0) {
                    this.previewPages = res.pages;
                    this.previewLoading = false;
                    return;
                }

                // 2. Client-side fetch fallback (Shinigami API langsung dari browser jika server kena WAF)
                if (upstreamChId) {
                    const shRes = await fetch('https://api.shngm.io/v1/chapter/detail/' + upstreamChId).then(r => r.ok ? r.json() : null).catch(() => null);
                    if (shRes && shRes.retcode === 0 && shRes.data && shRes.data.chapter && Array.isArray(shRes.data.chapter.data)) {
                        const baseUrl = (shRes.data.base_url || 'https://assets.shngm.id').replace(/\/+$/, '');
                        const path = '/' + String(shRes.data.chapter.path || '').replace(/^\/+|\/+$/g, '') + '/';
                        const files = shRes.data.chapter.data;

                        this.previewPages = files.map((f, idx) => {
                            const rawUrl = baseUrl + path + f;
                            return {
                                index: idx + 1,
                                raw_url: rawUrl,
                                url: '/api/proxy-image?url=' + encodeURIComponent(rawUrl)
                            };
                        });
                        this.previewLoading = false;

                        // Background sync ke database KZSTRM kita
                        fetch('/api/komik/client-sync/chapter', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                slug: '{{ $comic['slug'] ?? '' }}',
                                chapter_id: chapterId,
                                upstream_chapter_id: upstreamChId,
                                base_url: baseUrl,
                                path: shRes.data.chapter.path,
                                data: files
                            })
                        }).catch(() => {});
                        return;
                    }
                }

                if (this.previewPages.length === 0) {
                    this.previewError = 'Belum ada lembaran gambar yang dapat dipratinjau untuk chapter ini.';
                }
            } catch (err) {
                this.previewError = 'Gagal memuat pratinjau chapter. Silakan coba lagi.';
            } finally {
                this.previewLoading = false;
            }
        },
        async syncChaptersFromClient() {
            const upstreamId = '{{ $comic['upstream_id'] ?? '' }}';
            const slug = '{{ $comic['slug'] ?? '' }}';
            if (!upstreamId || !slug) return;
            const syncKey = 'kz_ch_sync_' + slug;
            const lastSync = parseInt(sessionStorage.getItem(syncKey) || '0');
            if (Date.now() - lastSync < 600000) return;

            try {
                const res = await fetch('https://api.shngm.io/v1/chapter/' + upstreamId + '/list?page=1&page_size=1000').then(r => r.ok ? r.json() : null).catch(() => null);
                if (!res || res.retcode !== 0 || !Array.isArray(res.data) || res.data.length === 0) return;
                sessionStorage.setItem(syncKey, String(Date.now()));

                const saveRes = await fetch('/api/komik/client-sync/detail', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        slug: slug,
                        upstream_id: upstreamId,
                        chapters: res.data
                    })
                }).then(r => r.ok ? r.json() : null).catch(() => null);

                if (saveRes && saveRes.added > 0) {
                    window.location.reload();
                }
            } catch (e) {}
        }
     }">

    {{-- ================= BACKDROP BLUR HEADER ================= --}}
    <div class="relative w-full overflow-hidden bg-[#050505]">
        {{-- Background Image with Blur (fade halus ke background halaman, tanpa garis seam) --}}
        <img src="{{ ($comic['banner'] ?? null) ?: ($comic['cover'] ?? '') }}"
             alt=""
             aria-hidden="true"
             class="absolute inset-0 w-full h-full object-cover object-top opacity-35 blur-2xl scale-110 pointer-events-none" />

        <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-[#050505]/70 to-[#050505]"></div>
        <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-[#050505] to-transparent"></div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 md:pt-28 lg:pt-32 pb-6 md:pb-10">
            {{-- Tombol Kembali ke Katalog / Provider Terkait --}}
            @php
                $backUrl = '/komik';
                if (str_starts_with($comic['id'] ?? '', 'native__')) {
                    $backUrl = '/komik/native';
                } elseif (str_starts_with($comic['id'] ?? '', 'wurmz__')) {
                    $backUrl = '/komik/wurmz';
                } elseif (str_starts_with($comic['id'] ?? '', 'izanami__')) {
                    $backUrl = '/komik/izanami';
                } elseif (!empty($comic['id'])) {
                    $backUrl = '/komik/shinigami';
                }
            @endphp
            <div class="mb-5">
                <a href="{{ $backUrl }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-zinc-900/80 hover:bg-zinc-800 border border-white/10 text-xs font-bold text-zinc-300 hover:text-white transition shadow-sm active:scale-95">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    <span>Kembali</span>
                </a>
            </div>

            <div class="flex flex-col md:flex-row gap-6 md:gap-10 items-center md:items-end">
            {{-- Poster Card --}}
            <div class="shrink-0 w-40 sm:w-48 md:w-60 aspect-[2/3] rounded-2xl overflow-hidden ring-1 ring-white/15 shadow-[0_20px_60px_-10px_rgba(0,0,0,0.9)] bg-zinc-900">
                <img src="{{ $comic['cover'] }}" alt="{{ $comic['title'] }}" class="w-full h-full object-cover" />
            </div>

            {{-- Metadata --}}
            <div class="flex-1 min-w-0 w-full flex flex-col text-center md:text-left">
                <div class="flex items-center justify-center md:justify-start gap-2 mb-3 flex-wrap">
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-red-600 text-white shadow-md shadow-red-900/40">
                        {{ $comic['format'] }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-white/10 text-zinc-300">
                        {{ $comic['status'] }}
                    </span>
                    @if(!empty($comic['rating']))
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/30">
                            ★ {{ $comic['rating'] }} / 10
                        </span>
                    @endif
                    @if(!empty($comic['release_year']))
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-zinc-800 text-zinc-400">
                            {{ $comic['release_year'] }}
                        </span>
                    @endif
                </div>

                <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight leading-[1.05]">
                    {{ $comic['title'] }}
                </h1>

                @if(!empty($comic['alternative_title']) || !empty($comic['alt_title']))
                    <p class="text-xs sm:text-sm text-zinc-400 mt-2 font-medium line-clamp-1" dir="auto" style="unicode-bidi: plaintext;"
                       title="{{ $comic['alternative_title'] ?? $comic['alt_title'] }}">
                        {{ $comic['alternative_title'] ?? $comic['alt_title'] }}
                    </p>
                @endif

                {{-- Penulis & Ilustrator: satu baris teks, bukan chip (biar tidak bersaing dengan genre) --}}
                @if(!empty($comic['author']) || !empty($comic['artist']))
                    <dl class="mt-3 flex flex-wrap items-center justify-center md:justify-start gap-x-5 gap-y-1 text-xs sm:text-sm">
                        @if(!empty($comic['author']))
                            <div class="flex items-center gap-1.5 min-w-0">
                                <dt class="text-zinc-500">Penulis</dt>
                                <dd class="text-zinc-200 font-semibold truncate max-w-[16rem]">{{ $comic['author'] }}</dd>
                            </div>
                        @endif
                        @if(!empty($comic['artist']))
                            <div class="flex items-center gap-1.5 min-w-0">
                                <dt class="text-zinc-500">Ilustrator</dt>
                                <dd class="text-zinc-200 font-semibold truncate max-w-[16rem]">{{ $comic['artist'] }}</dd>
                            </div>
                        @endif
                    </dl>
                @endif

                {{-- Genres --}}
                @if(!empty($comic['genres']))
                    <div class="mt-4 flex flex-wrap items-center justify-center md:justify-start gap-1.5">
                        @foreach($comic['genres'] as $g)
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/[0.06] border border-white/10 text-zinc-300">
                                {{ $g }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if(!empty($comic['authors']) || !empty($comic['artists']))
                    <div class="mt-3 text-xs text-zinc-400 flex items-center gap-4 flex-wrap">
                        @if(!empty($comic['authors']))
                            <span>Author: <strong class="text-zinc-200">{{ implode(', ', $comic['authors']) }}</strong></span>
                        @endif
                        @if(!empty($comic['artists']))
                            <span>Artist: <strong class="text-zinc-200">{{ implode(', ', $comic['artists']) }}</strong></span>
                        @endif
                    </div>
                @endif

                {{-- Action CTA Buttons --}}
                <div class="mt-6 flex items-center justify-center md:justify-start gap-2.5 flex-wrap">
                    @php
                        // First chapter (angka terendah)
                        $firstChapter = !empty($chapters) ? end($chapters) : null;
                        // Latest chapter (angka tertinggi)
                        $latestChapter = !empty($chapters) ? $chapters[0] : null;
                    @endphp

                    {{-- Resume button via Alpine.js jika pernah membaca --}}
                    <template x-if="history && history.chapterId">
                        <a :href="'/komik/{{ $comic['id'] }}/read/' + history.chapterId"
                           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-sm tracking-wide shadow-xl shadow-red-900/40 transition active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            <span>Lanjutkan: <span x-text="history.label || 'Chapter Terakhir'"></span> <span class="text-white/80 font-bold" x-text="history.progress ? '(' + history.progress + '%)' : ''"></span></span>
                        </a>
                    </template>

                    {{-- Tombol Baca Pertama Kali --}}
                    @if($firstChapter)
                        <template x-if="!history || !history.chapterId">
                            <a href="/komik/{{ $comic['id'] }}/read/{{ $firstChapter['id'] }}"
                               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-black text-sm tracking-wide shadow-xl shadow-red-900/40 transition active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                                <span>Mulai Baca (Ch. {{ $firstChapter['number'] }})</span>
                            </a>
                        </template>
                    @endif

                    {{-- Disembunyikan kalau tombol Lanjutkan sudah menuju chapter terbaru (hindari dua tombol ke tujuan sama) --}}
                    @if($latestChapter && $latestChapter['id'] !== ($firstChapter['id'] ?? null))
                        <a href="/komik/{{ $comic['id'] }}/read/{{ $latestChapter['id'] }}"
                           x-show="!history || String(history.chapterId) !== @js((string) $latestChapter['id'])"
                           class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white/[0.06] hover:bg-white/10 text-zinc-100 font-bold text-sm border border-white/10 transition">
                            <span>Terbaru · Ch. {{ $latestChapter['number'] }}</span>
                        </a>
                    @endif

                    {{-- Tombol Tambah ke Favorit --}}
                    <button type="button" wire:click="toggleFavorite"
                            aria-pressed="{{ $isFavorited ? 'true' : 'false' }}"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-sm border transition active:scale-95 {{ $isFavorited ? 'bg-red-600/15 border-red-500/60 text-red-400 hover:bg-red-600/25' : 'bg-white/[0.06] hover:bg-white/10 border-white/10 text-zinc-200 hover:text-white' }}">
                        <svg class="w-4 h-4 {{ $isFavorited ? 'fill-current text-red-500' : 'text-zinc-400' }}" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                        <span>{{ $isFavorited ? 'Tersimpan di Favorit' : 'Tambah ke Favorit' }}</span>
                    </button>
                </div>
            </div>
            </div>
        </div>
    </div>

    {{-- ================= SYNOPSIS & CHAPTER LIST ================= --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-2 md:mt-4">
        {{-- Sinopsis Card --}}
        <div class="p-5 md:p-6 rounded-2xl bg-white/[0.03] border border-white/[0.07] mb-10"
             x-data="{ expanded: false }">
            <h2 class="text-lg font-black text-white tracking-tight mb-2.5">Sinopsis</h2>
            <div class="text-sm md:text-base text-zinc-300 leading-relaxed font-normal"
                 :class="expanded ? '' : 'line-clamp-3'">
                {{ $comic['description'] ?: 'Belum ada sinopsis untuk webtoon ini.' }}
            </div>
            @if(strlen($comic['description']) > 180)
                <button type="button" @click="expanded = !expanded"
                        class="mt-3 text-xs font-bold text-red-500 hover:text-red-400 inline-flex items-center gap-1">
                    <span x-text="expanded ? 'Sembunyikan' : 'Baca Selengkapnya'"></span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
            @endif
        </div>

        {{-- Chapter Header & Controls --}}
        <div class="mb-5 space-y-3">
            {{-- Baris 1: judul + jumlah chapter --}}
            <div class="flex items-baseline gap-2.5">
                <h2 class="text-xl md:text-2xl font-black text-white tracking-tight whitespace-nowrap">Daftar Chapter</h2>
                <span class="text-sm font-semibold text-zinc-500 whitespace-nowrap">{{ count($chapters) }} chapter</span>
            </div>

            {{-- Baris 2: toggle tampilan (kiri) + cari & urutkan (kanan). Di HP toggle full width, toolbar di bawahnya --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
                <div class="grid grid-cols-2 p-1 rounded-xl bg-white/[0.04] border border-white/10 text-sm font-bold w-full sm:w-auto sm:min-w-[300px]"
                     role="tablist" aria-label="Mode tampilan chapter">
                    <button type="button" role="tab"
                            @click="setViewMode('list')"
                            :aria-selected="viewMode === 'list'"
                            :class="viewMode === 'list' ? 'bg-red-600 text-white shadow-md shadow-red-900/30' : 'text-zinc-400 hover:text-white'"
                            class="h-9 px-3 rounded-lg transition flex items-center justify-center gap-2 whitespace-nowrap">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        <span>Daftar</span>
                    </button>
                    <button type="button" role="tab"
                            @click="setViewMode('kotatsu')"
                            data-tab-kotatsu="true"
                            :aria-selected="viewMode === 'kotatsu'"
                            :class="viewMode === 'kotatsu' ? 'bg-red-600 text-white shadow-md shadow-red-900/30' : 'text-zinc-400 hover:text-white'"
                            class="h-9 px-3 rounded-lg transition flex items-center justify-center gap-2 whitespace-nowrap">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                        </svg>
                        <span>Grid View</span>
                    </button>
                </div>

                <div class="flex items-center gap-2 sm:ml-auto" x-show="viewMode === 'list'">
                    {{-- Search Chapter Input --}}
                    <label class="relative flex-1 sm:w-64">
                        <span class="sr-only">Cari chapter</span>
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        <input type="text"
                               wire:model.live.debounce.250ms="searchChapter"
                               placeholder="Cari nomor chapter…"
                               class="w-full h-11 sm:h-10 pl-9 pr-3 rounded-xl bg-white/[0.04] border border-white/10 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500" />
                    </label>

                    {{-- Toggle Sort Order Button --}}
                    <button type="button" wire:click="toggleSort"
                            class="shrink-0 h-11 sm:h-10 px-3.5 rounded-xl bg-white/[0.04] hover:bg-white/10 border border-white/10 text-sm font-bold text-zinc-200 flex items-center gap-1.5 transition whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/></svg>
                        <span>{{ $sortOrder === 'desc' ? 'Terbaru' : 'Terlama' }}</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ================= MODE 1: LIST CHAPTER (DEFAULT) ================= --}}
        <div x-show="viewMode === 'list'">
            @php
                $displayChapters = $this->filteredChapters;
            @endphp

            @if(empty($displayChapters))
                <div class="py-12 text-center text-sm font-semibold text-zinc-500 bg-zinc-900/40 rounded-2xl border border-zinc-800/80">
                    Tidak ada chapter yang cocok dengan pencarian "<span class="text-white">{{ $searchChapter }}</span>".
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($displayChapters as $ch)
                        @php
                            $chDate = $ch['release_date'] ?? ($ch['released_at'] ?? null);
                            $chDateText = $chDate ? (strtotime($chDate) ? date('d M Y', strtotime($chDate)) : $chDate) : null;
                        @endphp
                        {{-- Seluruh kartu = link baca; tombol pratinjau di kanan berdiri sendiri (bukan nested di dalam <a>) --}}
                        <div class="group relative flex items-center rounded-xl bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.07] hover:border-red-500/40 transition"
                             :class="history && String(history.chapterId) === @js((string) $ch['id']) ? '!border-red-500/70 !bg-red-950/25' : ''">
                            <a href="/komik/{{ $comic['id'] }}/read/{{ $ch['id'] }}"
                               class="min-w-0 flex-1 flex items-center gap-3 pl-4 pr-2 py-3 min-h-[56px]">
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-sm text-zinc-100 group-hover:text-white truncate">{{ $ch['label'] }}</p>
                                    <div class="flex items-center gap-2 mt-0.5 min-h-[16px]">
                                        @if($chDateText)
                                            <span class="text-[11px] text-zinc-400 font-medium">{{ $chDateText }}</span>
                                        @endif
                                        <template x-if="history && String(history.chapterId) === @js((string) $ch['id'])">
                                            <span class="text-[10px] font-extrabold text-red-400 uppercase tracking-wide"
                                                  x-text="history.progress ? 'Terakhir dibaca · ' + history.progress + '%' : 'Terakhir dibaca'"></span>
                                        </template>
                                    </div>
                                </div>
                            </a>

                            {{-- Tombol pratinjau lembaran (Kotatsu) per chapter, target tap 40px --}}
                            <button type="button"
                                    data-kotatsu-btn="true"
                                    @click="openChapterPreview({{ json_encode($ch) }})"
                                    title="Pratinjau lembaran chapter ini"
                                    aria-label="Pratinjau lembaran {{ $ch['label'] }}"
                                    class="shrink-0 mr-2 w-10 h-10 rounded-lg text-zinc-500 hover:text-white hover:bg-white/10 flex items-center justify-center transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                    <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                    <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                    <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ================= MODE 2: KOTATSU CONTINUOUS CHAPTER GRID ================= --}}
        <div x-show="viewMode === 'kotatsu'" style="display: none;">
            <div class="space-y-8">
                <template x-for="(chData, chIdx) in kotatsuLoadedChapters" :key="chData.chapter.id">
                    <div class="border-b border-zinc-900 pb-8 last:border-b-0">
                        {{-- Bab Divider Header persis di Kotatsu --}}
                        <div class="flex items-center justify-between gap-3 py-2.5 mb-3.5 border-y border-zinc-800/60 bg-zinc-900/40 px-3 rounded-lg">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-red-600"></span>
                                <h3 class="text-sm md:text-base font-extrabold text-white uppercase tracking-wider" x-text="chData.chapter.label"></h3>
                            </div>
                            <a :href="'/komik/{{ $comic['id'] }}/read/' + chData.chapter.id"
                               class="text-xs font-bold text-red-500 hover:text-red-400 transition flex items-center gap-1">
                                <span>Baca Chapter</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </a>
                        </div>

                        {{-- Kotatsu 3-Column Grid per Bab (Sisa baris kosong tidak dipaksa nyambung ke bab berikutnya) --}}
                        <template x-if="chData.pages && chData.pages.length > 0">
                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2.5 sm:gap-3.5">
                                <template x-for="p in chData.pages" :key="p.index">
                                    <a :href="'/komik/{{ $comic['id'] }}/read/' + chData.chapter.id + '#panel-' + p.index"
                                       class="group relative aspect-[3/4] sm:aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 border border-white/5 hover:border-red-500 hover:ring-2 hover:ring-red-500/40 transition-all duration-200 block shadow-md">
                                        <img :src="p.raw_url || p.url"
                                             :data-fallback="p.url"
                                             x-on:error="if ($el.getAttribute('data-fallback') && $el.src !== $el.getAttribute('data-fallback')) { $el.src = $el.getAttribute('data-fallback'); }"
                                             alt=""
                                             loading="lazy"
                                             decoding="async"
                                             referrerpolicy="no-referrer"
                                             class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300" />
                                        
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/10 opacity-60 group-hover:opacity-40 transition-opacity"></div>

                                        {{-- Kotatsu-Style Number Badge di Pojok Kanan Bawah (Reset ke 1 tiap ganti bab) --}}
                                        <div class="absolute bottom-2 right-2 min-w-[24px] h-6 px-1.5 rounded-full bg-black/85 backdrop-blur-md text-white border border-white/10 flex items-center justify-center text-[11px] font-black shadow-lg">
                                            <span x-text="p.index"></span>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </template>

                        <template x-if="!chData.pages || chData.pages.length === 0">
                            <div class="py-8 text-center text-xs font-semibold text-zinc-500 bg-zinc-950/60 rounded-xl border border-dashed border-zinc-800">
                                Lembaran panel untuk chapter ini belum termuat.
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Loading Indicator / Load More Button Kotatsu --}}
                <div class="pt-4 pb-12 flex flex-col items-center justify-center gap-3">
                    <div x-show="kotatsuLoading" class="flex items-center gap-2 text-xs font-bold text-zinc-400">
                        <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin"></div>
                        <span>Memuat chapter berikutnya ke Grid View...</span>
                    </div>

                    <div x-show="!kotatsuLoading && kotatsuHasMore">
                        <button type="button"
                                @click="loadMoreKotatsuChapters()"
                                class="px-6 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 border border-zinc-700 text-xs font-bold text-white transition flex items-center gap-2 shadow-lg">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            <span>Muat Chapter Selanjutnya</span>
                        </button>
                    </div>

                    <div x-show="!kotatsuHasMore && kotatsuLoadedChapters.length > 0" class="text-xs font-bold text-zinc-600">
                        🎉 Semua chapter telah selesai dimuat ke Grid View!
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= KOTATSU-STYLE CHAPTER THUMBNAIL MODAL / BOTTOM SHEET ================= --}}
    <div x-show="previewModalOpen"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/80 backdrop-blur-md"
         style="display: none;"
         @keydown.escape.window="previewModalOpen = false">

        <div @click.away="previewModalOpen = false"
             class="w-full sm:max-w-2xl md:max-w-3xl lg:max-w-4xl h-[88vh] sm:h-[82vh] max-h-[900px] bg-zinc-950 border border-zinc-800 rounded-t-3xl sm:rounded-3xl flex flex-col shadow-2xl overflow-hidden animate-in slide-in-from-bottom duration-300">

            {{-- Handle Bar Kotatsu (Mobile drag hint) --}}
            <div class="sm:hidden pt-3 pb-1 flex justify-center">
                <div class="w-10 h-1 bg-zinc-700 rounded-full"></div>
            </div>

            {{-- Modal Header --}}
            <div class="px-5 py-3.5 border-b border-zinc-800/80 flex items-center justify-between gap-3 bg-zinc-900/60 shrink-0">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-red-600 text-white">Grid View</span>
                        <h3 class="font-extrabold text-sm md:text-base text-white truncate" x-text="previewChapter ? previewChapter.label : 'Pratinjau Lembaran'"></h3>
                    </div>
                    <p class="text-[11px] text-zinc-400 mt-0.5" x-show="previewPages.length > 0">
                        <span x-text="previewPages.length + ' Lembaran panel'"></span> · Klik kartu untuk langsung lompat ke halaman tersebut
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Tombol Langsung Baca --}}
                    <template x-if="previewChapter">
                        <a :href="'/komik/{{ $comic['id'] }}/read/' + previewChapter.id"
                           class="px-3.5 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-red-900/40">
                            <span>Baca Penuh</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </a>
                    </template>

                    {{-- Tombol Tutup --}}
                    <button type="button" @click="previewModalOpen = false" class="p-1.5 text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Modal Content Area --}}
            <div class="flex-1 overflow-y-auto p-3.5 sm:p-5 bg-black/50">
                {{-- Loading State --}}
                <div x-show="previewLoading" class="h-full min-h-[300px] flex flex-col items-center justify-center gap-3">
                    <div class="w-8 h-8 border-3 border-red-600 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-xs font-bold text-zinc-400 animate-pulse">Memuat lembaran panel chapter...</p>
                </div>

                {{-- Error State --}}
                <div x-show="!previewLoading && previewError" class="h-full min-h-[300px] flex flex-col items-center justify-center p-6 text-center">
                    <svg class="w-10 h-10 text-zinc-600 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <p class="text-sm font-semibold text-zinc-400 mb-4" x-text="previewError"></p>
                    <template x-if="previewChapter">
                        <a :href="'/komik/{{ $comic['id'] }}/read/' + previewChapter.id"
                           class="px-5 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-white transition">
                            Buka di Reader Langsung
                        </a>
                    </template>
                </div>

                {{-- Kotatsu 3-Column Thumbnail Grid --}}
                <div x-show="!previewLoading && previewPages.length > 0"
                     class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2.5 sm:gap-3.5">
                    <template x-for="p in previewPages" :key="p.index">
                        <a :href="'/komik/{{ $comic['id'] }}/read/' + (previewChapter ? previewChapter.id : '') + '#panel-' + p.index"
                           class="group relative aspect-[3/4] sm:aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 border border-white/5 hover:border-red-500 hover:ring-2 hover:ring-red-500/40 transition-all duration-200 block shadow-md">
                            {{-- Thumbnail Image with Lazy Loading --}}
                            <img :src="p.raw_url || p.url"
                                 :data-fallback="p.url"
                                 x-on:error="if ($el.getAttribute('data-fallback') && $el.src !== $el.getAttribute('data-fallback')) { $el.src = $el.getAttribute('data-fallback'); }"
                                 alt=""
                                 loading="lazy"
                                 decoding="async"
                                 referrerpolicy="no-referrer"
                                 class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300" />

                            {{-- Gradient Overlay --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/10 opacity-60 group-hover:opacity-40 transition-opacity"></div>

                            {{-- Kotatsu-Style Number Badge di Pojok Kanan Bawah --}}
                            <div class="absolute bottom-2 right-2 min-w-[24px] h-6 px-1.5 rounded-full bg-black/85 backdrop-blur-md text-white border border-white/10 flex items-center justify-center text-[11px] font-black shadow-lg">
                                <span x-text="p.index"></span>
                            </div>

                            {{-- Hover Eye Icon di Tengah --}}
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                <div class="w-8 h-8 rounded-full bg-red-600/90 text-white flex items-center justify-center shadow-xl scale-75 group-hover:scale-100 transition-transform">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
