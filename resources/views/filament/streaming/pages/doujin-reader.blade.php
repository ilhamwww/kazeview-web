<div class="min-h-screen bg-[#050505] text-white pb-28 select-none"
     x-data="{
         currentPage: 1,
         totalPages: {{ count($chapter['images'] ?? []) }},
         zoomMode: 'fit-width',
         navVisible: true,
         currentProgress: 0,
         saveTimer: null,
         serverHistory: @js($serverHistory),
         comicId: 'doujin__{{ $mangaSlug }}',
         chapterId: '{{ $chapterId }}',
         chapterNumber: {{ (float) ($chapter['chapter_number'] ?? 0) }},
         chapterLabel: 'Chapter {{ $chapter['chapter_number'] ?? '' }}',
         comicTitle: '{{ addslashes($manga['title'] ?? 'Doujin') }}',
         comicCover: '{{ addslashes($manga['cover'] ?? '') }}',

         toggleNav() {
             this.navVisible = !this.navVisible;
         },

         init() {
             // 1. Pulihkan posisi scroll dari query param (?p= / ?y=) atau riwayat server / localStorage
             const urlParams = new URLSearchParams(window.location.search);
             const pParam = parseInt(urlParams.get('p') || '0', 10);
             const yParam = parseInt(urlParams.get('y') || '0', 10);

             let targetProgress = 0;
             let targetY = 0;

             if (pParam > 0) {
                 targetProgress = pParam;
             } else if (yParam > 0) {
                 targetY = yParam;
             } else if (this.serverHistory && this.serverHistory.chapterId === this.chapterId) {
                 targetProgress = this.serverHistory.progress || 0;
                 targetY = this.serverHistory.scrollY || 0;
             } else {
                 try {
                     const localRaw = localStorage.getItem('doujin_progress_' + this.comicId);
                     if (localRaw) {
                         const localData = JSON.parse(localRaw);
                         if (localData && localData.chapterId === this.chapterId) {
                             targetProgress = localData.progress || 0;
                             targetY = localData.scrollY || 0;
                         }
                     }
                 } catch (e) {}
             }

             if (targetProgress > 0 || targetY > 0) {
                 this.restoreScroll(targetProgress, targetY);
             }

             // 2. Pasang scroll listener dengan throttling untuk catat progress
             window.addEventListener('scroll', () => {
                 this.handleScroll();
             }, { passive: true });
         },

         restoreScroll(progress, targetY) {
             const tryRestore = (attemptsLeft) => {
                 const docH = document.documentElement.scrollHeight - window.innerHeight;
                 if (docH > 400 || attemptsLeft <= 0) {
                     if (targetY > 0 && targetY <= document.documentElement.scrollHeight) {
                         window.scrollTo({ top: targetY, behavior: 'instant' });
                     } else if (progress > 0) {
                         const calcY = Math.round((progress / 100) * docH);
                         window.scrollTo({ top: calcY, behavior: 'instant' });
                     }
                 } else {
                     setTimeout(() => tryRestore(attemptsLeft - 1), 250);
                 }
             };
             setTimeout(() => tryRestore(12), 150);
         },

         handleScroll() {
             const docH = document.documentElement.scrollHeight - window.innerHeight;
             if (docH <= 0) return;

             const scrollY = window.scrollY || window.pageYOffset;
             let percent = Math.min(100, Math.max(0, Math.round((scrollY / docH) * 100)));
             this.currentProgress = percent;

             // Hitung halaman aktif saat ini
             const imgs = document.querySelectorAll('[id^=page-]');
             for (let i = 0; i < imgs.length; i++) {
                 const rect = imgs[i].getBoundingClientRect();
                 if (rect.top <= window.innerHeight / 2 && rect.bottom >= window.innerHeight / 2) {
                     this.currentPage = i + 1;
                     break;
                 }
             }

             // Debounce save ke server dan local
             clearTimeout(this.saveTimer);
             this.saveTimer = setTimeout(() => {
                 this.saveProgress(percent, scrollY);
             }, 800);
         },

         saveProgress(percent, scrollY) {
             const payload = {
                 comic_id: this.comicId,
                 comic_title: this.comicTitle,
                 comic_cover: this.comicCover,
                 chapter_id: this.chapterId,
                 chapter_label: this.chapterLabel,
                 chapter_number: this.chapterNumber,
                 progress: percent,
                 panel_index: this.currentPage,
                 scroll_y: Math.round(scrollY),
                 _token: '{{ csrf_token() }}'
             };

             // Simpan ke localStorage untuk akses instan offline
             try {
                 localStorage.setItem('doujin_progress_' + this.comicId, JSON.stringify({
                     chapterId: this.chapterId,
                     progress: percent,
                     scrollY: Math.round(scrollY),
                     timestamp: Date.now()
                 }));
             } catch (e) {}

             // Sync ke server backend via api/comic-progress
             fetch('/api/comic-progress', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}'
                 },
                 body: JSON.stringify(payload)
             }).catch(() => {});
         }
     }"
     @keydown.window.escape="window.location.href='/doujin/detail/{{ $mangaSlug }}'">

    {{-- ================= PROGRESS BAR AT VERY TOP ================= --}}
    <div class="fixed top-0 inset-x-0 h-1 bg-zinc-800 z-[60] pointer-events-none">
        <div class="h-full bg-gradient-to-r from-red-600 to-rose-400 transition-all duration-150"
             :style="`width: ${currentProgress}%`"></div>
    </div>

    {{-- ================= STICKY TOPBAR CONTROLS ================= --}}
    <header x-show="navVisible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-y-full opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-full opacity-0"
            class="fixed top-0 inset-x-0 z-50 bg-[#050505]/95 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3 flex items-center justify-between gap-3 shadow-2xl">
        <div class="flex items-center gap-3 min-w-0">
            <a href="/doujin/detail/{{ $mangaSlug }}" class="p-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-zinc-300 hover:text-white transition border border-zinc-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
            <div class="min-w-0">
                <h1 class="text-xs sm:text-sm font-bold text-white truncate leading-tight">
                    {{ $manga['title'] ?? 'Doujin' }}
                </h1>
                <p class="text-[10px] text-red-400 font-black mt-0.5 uppercase tracking-wider flex items-center gap-1.5">
                    <span>Chapter {{ $chapter['chapter_number'] }}</span>
                    <span>•</span>
                    <span x-text="`${currentPage} / ${totalPages} Hlm`"></span>
                    <span>•</span>
                    <span class="text-zinc-400" x-text="`${currentProgress}%`"></span>
                </p>
            </div>
        </div>

        {{-- Next / Prev Chapter Navigation --}}
        <div class="flex items-center gap-2">
            @if(!empty($prevChapterId))
                <a href="/doujin/read/{{ $mangaSlug }}/{{ $prevChapterId }}"
                   class="px-3 py-1.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-xs font-bold text-zinc-300 transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    <span class="hidden sm:inline">Sebelumnya</span>
                </a>
            @endif

            @if(!empty($nextChapterId))
                <a href="/doujin/read/{{ $mangaSlug }}/{{ $nextChapterId }}"
                   class="px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-xs font-bold text-white transition flex items-center gap-1 shadow-lg shadow-red-600/30">
                    <span class="hidden sm:inline">Berikutnya</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            @endif
        </div>
    </header>

    {{-- ================= WEBTOON CONTINUOUS IMAGE READER ================= --}}
    <main class="w-full max-w-3xl mx-auto pt-16 px-0 sm:px-2 flex flex-col items-center">
        @forelse($chapter['images'] as $idx => $img)
            <div class="relative w-full bg-zinc-950 flex flex-col items-center border-b border-zinc-900/40"
                 id="page-{{ $idx + 1 }}"
                 @click="toggleNav()">
                <img src="{{ $img['url'] }}"
                     alt="Halaman {{ $img['page_number'] }}"
                     class="w-full h-auto object-contain block leading-none filter brightness-95 hover:brightness-100 transition"
                     loading="{{ $idx < 4 ? 'eager' : 'lazy' }}"
                     referrerpolicy="no-referrer" />
                <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded-md bg-black/70 backdrop-blur-md text-[9px] font-bold text-zinc-400 pointer-events-none">
                    {{ $img['page_number'] }} / {{ count($chapter['images']) }}
                </span>
            </div>
        @empty
            <div class="py-24 text-center text-zinc-500">
                Gambar untuk chapter ini belum dapat dimuat.
            </div>
        @endforelse

        {{-- Bottom Chapter Completed Action --}}
        <div class="w-full py-12 px-6 flex flex-col items-center gap-4 bg-zinc-950/80 border-t border-zinc-800 text-center">
            <h3 class="text-sm font-bold text-zinc-300">Kamu telah menyelesaikan Chapter {{ $chapter['chapter_number'] }}</h3>
            <div class="flex items-center gap-3">
                <a href="/doujin/detail/{{ $mangaSlug }}" class="px-5 py-2.5 rounded-xl bg-zinc-900 border border-zinc-800 text-xs font-bold text-white hover:bg-zinc-800 transition">
                    Kembali ke Detail
                </a>
                @if(!empty($nextChapterId))
                    <a href="/doujin/read/{{ $mangaSlug }}/{{ $nextChapterId }}" class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-xs font-bold text-white transition shadow-lg shadow-red-600/30">
                        Lanjut Chapter Berikutnya
                    </a>
                @endif
            </div>
        </div>
    </main>
</div>