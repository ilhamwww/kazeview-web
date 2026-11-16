<div class="min-h-screen bg-[#050505] text-white pb-28 select-none"
     x-data="{
         currentPage: 1,
         totalPages: {{ count($chapter['images'] ?? []) }},
         zoomMode: 'fit-width', // fit-width, original
         navVisible: true,
         toggleNav() {
             this.navVisible = !this.navVisible;
         }
     }"
     @keydown.window.escape="window.location.href='/doujin/detail/{{ $mangaSlug }}'">

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
                <p class="text-[10px] text-red-400 font-black mt-0.5 uppercase tracking-wider">
                    Chapter {{ $chapter['chapter_number'] }} • {{ count($chapter['images'] ?? []) }} Halaman
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
