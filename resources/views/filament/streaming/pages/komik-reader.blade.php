<div class="min-h-screen bg-black text-zinc-100 selection:bg-red-600 selection:text-white"
     x-data="{
        navVisible: true,
        lastScrollY: 0,
        modalOpen: false,
        fullscreen: false,
        currentProgress: 0,
        currentPanel: 1,
        showToast: false,
        toastMsg: '',
        saveTimer: null,
        expSent: false,
        clientPages: [],
        clientLoading: {{ empty($pages) && !empty($currentChapter['upstream_chapter_id']) ? 'true' : 'false' }},
        init() {
            this.fetchChapterFromClientIfEmpty();
            // Cek apakah ada riwayat tersimpan untuk chapter ini
            try {
                const raw = localStorage.getItem('kz_comic_{{ $mangaId }}');
                if (raw) {
                    const h = JSON.parse(raw);
                    if (h.chapterId === '{{ $chapterId }}' && (h.panelIndex > 1 || h.progress > 5)) {
                        this.currentProgress = h.progress || 0;
                        this.currentPanel = h.panelIndex || 1;
                        this.toastMsg = 'Melanjutkan dari panel ' + this.currentPanel + ' (' + this.currentProgress + '%)';
                        this.showToast = true;
                        setTimeout(() => { this.showToast = false; }, 3500);

                        // Pulihkan posisi scroll secara bertahap saat gambar dimuat
                        const restoreScroll = (retry = 0) => {
                            const el = document.getElementById('panel-' + h.panelIndex);
                            if (el) {
                                el.scrollIntoView({ behavior: 'auto', block: 'start' });
                            } else if (h.scrollY > 0) {
                                window.scrollTo({ top: h.scrollY, behavior: 'auto' });
                            }
                            if (retry < 5) {
                                setTimeout(() => restoreScroll(retry + 1), 300);
                            }
                        };
                        this.$nextTick(() => {
                            setTimeout(() => restoreScroll(0), 100);
                        });
                    }
                }
            } catch(e) {}
        },
        onScroll() {
            const y = window.scrollY;
            const total = document.documentElement.scrollHeight - window.innerHeight;
            if (total > 0) {
                this.currentProgress = Math.min(100, Math.max(0, Math.round((y / total) * 100)));
            }

            // EXP komik: kirim sekali saat chapter dibaca sampai >= 90% (server menolak duplikat & cap harian)
            if (!this.expSent && {{ auth()->check() ? 'true' : 'false' }} && this.currentProgress >= 90) {
                this.expSent = true;
                fetch('/api/comic-exp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ comic_id: '{{ $mangaId }}', chapter_id: '{{ $chapterId }}', progress: this.currentProgress })
                }).then(r => r.ok ? r.json() : null).then(d => {
                    if (d && d.exp > 0) { this.toastMsg = '+' + d.exp + ' EXP'; this.showToast = true; setTimeout(() => this.showToast = false, 2500); }
                }).catch(() => {});
            }

            // Deteksi panel/halaman mana yang sedang aktif di viewport
            const panels = document.querySelectorAll('[data-panel-index]');
            for (let i = 0; i < panels.length; i++) {
                const r = panels[i].getBoundingClientRect();
                if (r.bottom > 150) {
                    this.currentPanel = parseInt(panels[i].getAttribute('data-panel-index')) || 1;
                    break;
                }
            }

            // Simpan posisi terakhir ke localStorage (debounced 200ms)
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => {
                try {
                    const historyData = {
                        chapterId: '{{ $chapterId }}',
                        label: '{{ addslashes($currentChapter['label']) }}',
                        number: {{ (float) ($currentChapter['number'] ?? 0) }},
                        progress: this.currentProgress,
                        panelIndex: this.currentPanel,
                        scrollY: y,
                        updatedAt: Date.now()
                    };
                    localStorage.setItem('kz_comic_{{ $mangaId }}', JSON.stringify(historyData));

                    // Simpan ke daftar riwayat baca global untuk katalog /komik
                    let list = [];
                    const rawList = localStorage.getItem('kz_comic_recent_reads');
                    if (rawList) list = JSON.parse(rawList);
                    list = list.filter(item => item.comicId !== '{{ $mangaId }}');
                    list.unshift({
                        comicId: '{{ $mangaId }}',
                        comicTitle: '{{ addslashes($comic['title'] ?? '') }}',
                        comicCover: '{{ $comic['cover'] ?? '' }}',
                        comicFormat: '{{ $comic['format'] ?? 'Manhwa' }}',
                        chapterId: '{{ $chapterId }}',
                        chapterLabel: '{{ addslashes($currentChapter['label']) }}',
                        chapterNumber: {{ (float) ($currentChapter['number'] ?? 0) }},
                        progress: this.currentProgress,
                        panelIndex: this.currentPanel,
                        updatedAt: Date.now()
                    });
                    localStorage.setItem('kz_comic_recent_reads', JSON.stringify(list.slice(0, 10)));
                } catch(e) {}
            }, 200);

            // Nav visibility logic
            if (this.modalOpen) {
                this.navVisible = true;
                return;
            }
            if (y > 150 && y > this.lastScrollY + 12) {
                this.navVisible = false;
            } else if (y < this.lastScrollY - 12 || y < 150) {
                this.navVisible = true;
            }
            this.lastScrollY = y;
        },
        toggleNav() {
            this.navVisible = !this.navVisible;
        },
        async fetchChapterFromClientIfEmpty() {
            const hasServerPages = {{ !empty($pages) ? 'true' : 'false' }};
            const upstreamChId = '{{ $currentChapter['upstream_chapter_id'] ?? '' }}';
            const slug = '{{ $comic['slug'] ?? '' }}';
            const chId = '{{ $chapterId }}';
            if (hasServerPages || !upstreamChId) {
                this.clientLoading = false;
                return;
            }
            try {
                const res = await fetch('https://api.shngm.io/v1/chapter/detail/' + upstreamChId).then(r => r.ok ? r.json() : null).catch(() => null);
                if (res && res.retcode === 0 && res.data && res.data.chapter && Array.isArray(res.data.chapter.data)) {
                    const baseUrl = (res.data.base_url || 'https://assets.shngm.id').replace(/\/+$/, '');
                    const path = '/' + String(res.data.chapter.path || '').replace(/^\/+|\/+$/g, '') + '/';
                    const files = res.data.chapter.data;
                    this.clientPages = files.map((f, idx) => {
                        const rawUrl = baseUrl + path + f;
                        return {
                            index: idx + 1,
                            raw_url: rawUrl,
                            url: '/api/proxy-image?url=' + encodeURIComponent(rawUrl)
                        };
                    });
                    this.clientLoading = false;

                    // Kirim ke server KZSTRM kita agar tersimpan permanen di DB untuk pembaca berikutnya
                    fetch('/api/komik/client-sync/chapter', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            slug: slug,
                            chapter_id: chId,
                            upstream_chapter_id: upstreamChId,
                            base_url: baseUrl,
                            path: res.data.chapter.path,
                            data: files
                        })
                    }).catch(() => {});
                    return;
                }
            } catch (e) {}
            this.clientLoading = false;
        },
        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
                this.fullscreen = true;
            } else {
                document.exitFullscreen().catch(() => {});
                this.fullscreen = false;
            }
        }
     }"
     @scroll.window.throttle.60ms="onScroll()">

    {{-- ================= THIN READING PROGRESS BAR (TOP) ================= --}}
    <div class="fixed top-0 inset-x-0 z-50 h-1 bg-zinc-900 pointer-events-none">
        <div class="h-full bg-red-600 transition-all duration-150 shadow-[0_0_8px_rgba(229,9,20,0.8)]"
             :style="'width: ' + currentProgress + '%'"></div>
    </div>

    {{-- ================= RESUME TOAST NOTIFICATION (BOTTOM FLOATING GLASS) ================= --}}
    <div x-show="showToast"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="fixed bottom-20 inset-x-0 z-50 flex justify-center px-4 pointer-events-none"
         style="display: none;">
        <div class="px-4 py-2 rounded-full bg-zinc-900/90 backdrop-blur-xl text-zinc-100 text-xs font-bold shadow-[0_12px_36px_rgba(0,0,0,0.85)] flex items-center gap-2 border border-white/10 pointer-events-auto">
            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
            <span x-text="toastMsg"></span>
        </div>
    </div>

    {{-- ================= TOP HEADER (MINIMALIST - MOBILE ONLY) ================= --}}
    <header class="lg:hidden fixed top-0 inset-x-0 z-40 h-14 bg-black/85 backdrop-blur-xl border-b border-white/10 px-4 flex items-center justify-between transition-transform duration-300"
            :class="navVisible ? 'translate-y-0' : '-translate-y-full'">
        <div class="flex items-center gap-3 min-w-0">
            <a href="/komik/detail/{{ $comic['id'] }}" class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-300 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-bold text-white truncate max-w-[180px] sm:max-w-md">
                    {{ $comic['title'] }}
                </h1>
                <p class="text-[11px] font-semibold text-red-500 truncate">
                    {{ $currentChapter['label'] }} · <span x-text="currentProgress + '%'"></span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="button" x-on:click.stop="window.location.reload()"
                    aria-label="Refresh halaman komik" title="Refresh halaman"
                    class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-300 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7v5h-5M4 17v-5h5M6.1 7a7 7 0 0111.55-2L20 8M4 16l2.35 3A7 7 0 0017.9 17"/></svg>
            </button>
            <button type="button" @click="modalOpen = true"
                    class="px-3 py-1.5 rounded-lg bg-zinc-900 border border-zinc-700 hover:border-zinc-500 text-xs font-bold text-zinc-200 transition flex items-center gap-1.5">
                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                <span class="hidden sm:inline">Pilih Chapter</span>
            </button>
            <button type="button" @click="toggleFullscreen"
                    class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-300 hover:text-white transition"
                    title="Layar Penuh">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
            </button>
        </div>
    </header>

    {{-- ================= WEBCOMIC LONG-STRIP IMAGES ================= --}}
    <main class="w-full max-w-3xl mx-auto pt-16 md:pt-24 lg:pt-32 xl:pt-36 pb-32 flex flex-col items-center bg-black min-h-screen"
          @click="toggleNav">
        {{-- Desktop Top Bar Sub-Header --}}
        <div class="hidden lg:flex w-full items-center justify-between mb-6 px-2">
            <a href="/komik/detail/{{ $comic['id'] }}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-zinc-900/80 hover:bg-zinc-800 border border-white/10 text-xs font-bold text-zinc-300 hover:text-white transition">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                <span>Kembali</span>
            </a>
            <div class="text-right">
                <h1 class="text-sm font-bold text-white truncate max-w-md">
                    {{ $comic['title'] }}
                </h1>
                <p class="text-xs font-bold text-red-500">
                    {{ $currentChapter['label'] }} · Progres: <span x-text="currentProgress + '%'"></span>
                </p>
            </div>
        </div>
        @if(empty($pages))
            {{-- Loading spinner saat browser user sedang mengambil halaman langsung dari upstream --}}
            <div x-show="clientLoading" class="py-32 text-center text-zinc-400">
                <div class="w-10 h-10 mx-auto mb-4 border-4 border-red-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm font-bold">Menyinkronkan lembaran komik langsung dari sumber...</p>
            </div>

            {{-- Render lembaran yang berhasil diambil oleh browser user --}}
            <template x-for="p in clientPages" :key="p.index">
                <div :id="'panel-' + p.index"
                     :data-panel-index="p.index"
                     class="w-full relative flex items-center justify-center bg-black overflow-hidden"
                     style="margin: 0; padding: 0; line-height: 0;">
                    <img :src="p.raw_url || p.url"
                         :data-fallback-proxy="p.url"
                         :alt="'Halaman ' + p.index"
                         :loading="p.index <= 4 ? 'eager' : 'lazy'"
                         decoding="async"
                         referrerpolicy="no-referrer"
                         x-on:error="if ($el.getAttribute('data-fallback-proxy') && $el.src !== $el.getAttribute('data-fallback-proxy')) { $el.src = $el.getAttribute('data-fallback-proxy'); }"
                         class="w-full h-auto block select-none pointer-events-none"
                         style="display: block; margin: 0; padding: 0; border: 0;" />
                </div>
            </template>

            {{-- Jika benar-benar kosong setelah client fetch --}}
            <div x-show="!clientLoading && clientPages.length === 0" class="py-32 text-center text-zinc-500" style="display: none;">
                <p class="text-sm font-bold">Halaman chapter sedang dimuat atau tidak tersedia.</p>
                <a href="/komik/detail/{{ $comic['id'] }}" class="mt-4 inline-block px-5 py-2 rounded-xl bg-red-600 text-white text-xs font-bold">
                    Kembali
                </a>
            </div>
        @else
            @foreach($pages as $p)
                <div id="panel-{{ $p['index'] }}"
                     data-panel-index="{{ $p['index'] }}"
                     class="w-full relative flex items-center justify-center bg-black overflow-hidden"
                     style="margin: 0; padding: 0; line-height: 0;">
                    <img src="{{ !empty($p['raw_url']) ? $p['raw_url'] : $p['url'] }}"
                         data-fallback-proxy="{{ $p['url'] }}"
                         alt="Halaman {{ $p['index'] }}"
                         loading="{{ $p['index'] <= 4 ? 'eager' : 'lazy' }}"
                         decoding="async"
                         referrerpolicy="no-referrer"
                         x-on:error="if ($el.getAttribute('data-fallback-proxy') && $el.src !== $el.getAttribute('data-fallback-proxy')) { $el.src = $el.getAttribute('data-fallback-proxy'); }"
                         class="w-full h-auto block select-none pointer-events-none"
                         style="display: block; margin: 0; padding: 0; border: 0;" />
                </div>
            @endforeach

            {{-- Chapter Completion Bottom Banner --}}
            <div class="w-full px-4 py-12 text-center border-t border-zinc-900 mt-6">
                <p class="text-xs font-semibold text-zinc-500 uppercase tracking-widest mb-1">Akhir dari</p>
                <h3 class="text-lg md:text-xl font-black text-white mb-6">
                    {{ $currentChapter['label'] }}
                </h3>

                <div class="flex items-center justify-center gap-4 flex-wrap">
                    @if($prevChapter)
                        <a href="/komik/{{ $comic['id'] }}/read/{{ $prevChapter['id'] }}"
                           class="px-5 py-3 rounded-xl bg-zinc-900 hover:bg-zinc-800 border border-zinc-700 text-xs font-bold text-white transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                            <span>{{ $prevChapter['label'] }}</span>
                        </a>
                    @endif

                    @if($nextChapter)
                        <a href="/komik/{{ $comic['id'] }}/read/{{ $nextChapter['id'] }}"
                           class="px-6 py-3 rounded-xl bg-red-600 hover:bg-red-500 text-xs font-black text-white shadow-xl shadow-red-900/40 transition flex items-center gap-2">
                            <span>Lanjut: {{ $nextChapter['label'] }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </a>
                    @else
                        <span class="px-5 py-3 rounded-xl bg-zinc-900 border border-zinc-800 text-xs font-bold text-zinc-400">
                            🎉 Kamu sudah membaca chapter paling akhir!
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </main>

    {{-- ================= FLOATING BOTTOM NAVIGATION BAR ================= --}}
    <div class="fixed bottom-4 inset-x-0 z-40 flex justify-center px-4 pointer-events-none transition-transform duration-300"
         :class="navVisible ? 'translate-y-0' : 'translate-y-24'">
        <div class="pointer-events-auto h-13 px-4 py-2 bg-zinc-950/90 backdrop-blur-2xl border border-white/10 rounded-full flex items-center gap-3 shadow-[0_12px_40px_0_rgba(0,0,0,0.9)]">
            {{-- Prev Chapter --}}
            @if($prevChapter)
                <a href="/komik/{{ $comic['id'] }}/read/{{ $prevChapter['id'] }}"
                   class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/15 flex items-center justify-center text-zinc-200 transition"
                   title="Chapter Sebelumnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                </a>
            @else
                <button type="button" disabled class="w-9 h-9 rounded-full bg-white/5 flex items-center justify-center text-zinc-600 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                </button>
            @endif

            {{-- Chapter Modal Trigger with Realtime Progress --}}
            <button type="button" @click="modalOpen = true"
                    class="px-3.5 py-1.5 rounded-full bg-white/5 hover:bg-white/10 text-xs font-bold text-zinc-200 flex items-center gap-1.5 transition">
                <span>Ch. {{ $currentChapter['number'] }}</span>
                <span class="text-red-500 font-extrabold" x-text="'· ' + currentProgress + '%'"></span>
                <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
            </button>

            {{-- Next Chapter --}}
            @if($nextChapter)
                <a href="/komik/{{ $comic['id'] }}/read/{{ $nextChapter['id'] }}"
                   class="w-9 h-9 rounded-full bg-red-600 hover:bg-red-500 text-white shadow-md shadow-red-900/50 flex items-center justify-center transition"
                   title="Chapter Selanjutnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            @else
                <button type="button" disabled class="w-9 h-9 rounded-full bg-white/5 flex items-center justify-center text-zinc-600 cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </button>
            @endif
        </div>
    </div>

    {{-- ================= CHAPTER LIST MODAL ================= --}}
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
         style="display: none;"
         @keydown.escape.window="modalOpen = false">

        <div @click.away="modalOpen = false"
             class="w-full max-w-md max-h-[80vh] bg-zinc-950 border border-zinc-800 rounded-3xl p-5 flex flex-col shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
                <h3 class="font-black text-sm text-white">Daftar Chapter ({{ count($allChapters) }})</h3>
                <button type="button" @click="modalOpen = false" class="text-zinc-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="overflow-y-auto py-3 space-y-1.5 flex-1 pr-1"
                 x-data="{ query: '' }">
                <input type="text" x-model="query" placeholder="Cari nomor chapter..."
                       class="w-full mb-3 px-3 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-red-500" />

                @foreach(array_reverse($allChapters) as $c)
                    <a href="/komik/{{ $comic['id'] }}/read/{{ $c['id'] }}"
                       x-show="!query || '{{ $c['number'] }}'.includes(query) || '{{ strtolower(addslashes($c['label'])) }}'.includes(query.toLowerCase())"
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ $c['id'] === $chapterId ? 'bg-red-600 text-white' : 'bg-white/[0.03] hover:bg-white/[0.08] text-zinc-300' }}">
                        <span>{{ $c['label'] }}</span>
                        @if($c['id'] === $chapterId)
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-black/30 text-white" x-text="currentProgress + '%'"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
