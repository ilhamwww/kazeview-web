<div class="min-h-screen bg-black text-zinc-100 selection:bg-red-600 selection:text-white"
     x-data="{
        navVisible: true,
        lastScrollY: 0,
        modalOpen: false,
        fullscreen: false,
        init() {
            // Simpan riwayat baca ke localStorage
            try {
                const historyData = {
                    chapterId: '{{ $chapterId }}',
                    label: '{{ addslashes($currentChapter['label']) }}',
                    number: {{ (float) ($currentChapter['number'] ?? 0) }},
                    updatedAt: Date.now()
                };
                localStorage.setItem('kz_comic_{{ $mangaId }}', JSON.stringify(historyData));
            } catch(e) {}
        },
        onScroll() {
            const y = window.scrollY;
            if (this.modalOpen) {
                this.navVisible = true;
                return;
            }
            if (y > 150 && y > this.lastScrollY + 10) {
                // Scroll ke bawah: sembunyikan bar navigasi agar baca immersive
                this.navVisible = false;
            } else if (y < this.lastScrollY - 10 || y < 150) {
                // Scroll ke atas: tampilkan bar navigasi
                this.navVisible = true;
            }
            this.lastScrollY = y;
        },
        toggleNav() {
            this.navVisible = !this.navVisible;
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
     @scroll.window.throttle.80ms="onScroll()">

    {{-- ================= TOP HEADER (MINIMALIST) ================= --}}
    <header class="fixed top-0 inset-x-0 z-40 h-14 bg-black/85 backdrop-blur-xl border-b border-white/10 px-4 flex items-center justify-between transition-transform duration-300"
            :class="navVisible ? 'translate-y-0' : '-translate-y-full'">
        <div class="flex items-center gap-3 min-w-0">
            <a href="/komik/{{ $comic['id'] }}" class="w-9 h-9 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-300 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-bold text-white truncate max-w-[200px] sm:max-w-md">
                    {{ $comic['title'] }}
                </h1>
                <p class="text-[11px] font-semibold text-red-500 truncate">
                    {{ $currentChapter['label'] }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
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
    <main class="w-full max-w-3xl mx-auto pt-16 pb-32 flex flex-col items-center bg-black min-h-screen"
          @click="toggleNav">
        @if(empty($pages))
            <div class="py-32 text-center text-zinc-500">
                <p class="text-sm font-bold">Halaman chapter sedang dimuat atau tidak tersedia.</p>
                <a href="/komik/{{ $comic['id'] }}" class="mt-4 inline-block px-5 py-2 rounded-xl bg-red-600 text-white text-xs font-bold">
                    Kembali ke Detail Komik
                </a>
            </div>
        @else
            @foreach($pages as $p)
                <div class="w-full relative flex items-center justify-center bg-black overflow-hidden"
                     style="margin: 0; padding: 0; line-height: 0;">
                    <img src="{{ $p['url'] }}"
                         alt="Halaman {{ $p['index'] }}"
                         loading="{{ $p['index'] <= 4 ? 'eager' : 'lazy' }}"
                         decoding="async"
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

            {{-- Chapter Modal Trigger --}}
            <button type="button" @click="modalOpen = true"
                    class="px-3.5 py-1.5 rounded-full bg-white/5 hover:bg-white/10 text-xs font-bold text-zinc-200 flex items-center gap-1.5 transition">
                <span>Ch. {{ $currentChapter['number'] }}</span>
                <span class="text-zinc-500">/ {{ count($allChapters) }}</span>
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
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-black/30 text-white">Sedang Dibaca</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
