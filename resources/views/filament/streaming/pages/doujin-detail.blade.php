<div class="min-h-screen bg-[#050505] text-white pb-24"
     x-data="{
         viewMode: 'list',
         serverHistory: @js($serverHistory),
         readChapters: @js($readChapters),
         localHistory: null,

         init() {
             try {
                 const raw = localStorage.getItem('doujin_progress_doujin__{{ $mangaSlug }}');
                 if (raw) {
                     this.localHistory = JSON.parse(raw);
                 }
             } catch (e) {}
         },

         getEffectiveHistory() {
             if (this.serverHistory && this.serverHistory.chapterId) {
                 return this.serverHistory;
             }
             if (this.localHistory && this.localHistory.chapterId) {
                 return {
                     chapterId: this.localHistory.chapterId,
                     progress: this.localHistory.progress || 0,
                     scrollY: this.localHistory.scrollY || 0,
                     label: 'Chapter Terakhir'
                 };
             }
             return null;
         },

         getChapterProgress(chapterId) {
             if (this.serverHistory && this.serverHistory.chapterId === chapterId) {
                 return this.serverHistory.progress || 0;
             }
             if (this.localHistory && this.localHistory.chapterId === chapterId) {
                 return this.localHistory.progress || 0;
             }
             if (this.readChapters && this.readChapters[chapterId]) {
                 return this.readChapters[chapterId].progress || (this.readChapters[chapterId].completed ? 100 : 0);
             }
             return null;
         },

         getChapterScrollY(chapterId) {
             if (this.serverHistory && this.serverHistory.chapterId === chapterId) {
                 return this.serverHistory.scrollY || 0;
             }
             if (this.localHistory && this.localHistory.chapterId === chapterId) {
                 return this.localHistory.scrollY || 0;
             }
             if (this.readChapters && this.readChapters[chapterId]) {
                 return this.readChapters[chapterId].scrollY || 0;
             }
             return 0;
         }
     }">

    {{-- ================= HERO DETAIL BANNER ================= --}}
    <section class="relative w-full overflow-hidden bg-zinc-950 border-b border-zinc-800 pt-20 sm:pt-24 lg:pt-32">
        {{-- Background Backdrop Blur --}}
        <div class="absolute inset-0 opacity-20 filter blur-3xl scale-110 pointer-events-none">
            <img src="{{ $detail['cover'] }}" alt="" class="w-full h-full object-cover" referrerpolicy="no-referrer" />
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#050505] via-[#050505]/80 to-transparent"></div>

        <div class="relative w-full max-w-[1800px] mx-auto px-4 sm:px-6 pt-4 md:pt-6">
            <a href="{{ url('/doujin') }}" aria-label="Kembali ke daftar doujin"
               class="inline-flex items-center gap-2 rounded-full bg-white/5 px-3.5 py-2 text-xs font-bold text-zinc-200 ring-1 ring-white/10 transition hover:bg-white/10 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                <span>Kembali</span>
            </a>
        </div>

        <div class="relative w-full max-w-[1800px] mx-auto px-4 sm:px-6 pt-3 pb-10 flex flex-col md:flex-row gap-6 md:gap-8 items-start">
            {{-- Cover Card --}}
            <div class="w-40 sm:w-52 md:w-60 aspect-[3/4] shrink-0 rounded-2xl overflow-hidden bg-zinc-900 border border-white/10 shadow-2xl relative mx-auto md:mx-0">
                <img src="{{ $detail['cover'] }}" alt="{{ $detail['title'] }}"
                     class="w-full h-full object-cover"
                     @if(!empty($detail['raw_cover'])) onerror="if(this.src!=='{{ $detail['raw_cover'] }}'){this.src='{{ $detail['raw_cover'] }}';}" @endif />
                <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md bg-red-600 text-white text-[10px] font-black uppercase tracking-wider shadow">
                    {{ $detail['type'] }}
                </span>
            </div>

            {{-- Metadata & Actions --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-lg bg-zinc-900 border border-white/10 text-xs font-bold text-zinc-300 capitalize">
                        Status: {{ $detail['status'] }}
                    </span>
                    @if(!empty($detail['rating']))
                        <span class="px-2.5 py-0.5 rounded-lg bg-black/60 border border-amber-500/30 text-xs font-bold text-amber-400 flex items-center gap-1">
                            ★ {{ $detail['rating'] }}
                        </span>
                    @endif
                    <span class="text-xs text-zinc-500 font-semibold">
                        {{ $detail['total_chapters'] }} Chapter
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white leading-tight">
                    {{ $detail['title'] }}
                </h1>

                @if(!empty($detail['alternative_titles']))
                    <p class="text-xs sm:text-sm text-zinc-400 mt-1 italic">
                        {{ $detail['alternative_titles'] }}
                    </p>
                @endif

                {{-- Genres Badges --}}
                @if(!empty($detail['genres']))
                    <div class="mt-3 flex items-center gap-1.5 flex-wrap">
                        @foreach($detail['genres'] as $g)
                            <a href="/doujin?genres={{ $g['slug'] }}"
                               class="px-2.5 py-1 rounded-lg bg-zinc-900/80 hover:bg-red-600 hover:text-white border border-zinc-800 text-[11px] font-semibold text-zinc-300 transition">
                                {{ $g['name'] }}
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Synopsis --}}
                @if(!empty($detail['description']))
                    <div class="mt-4 w-full min-w-0 max-w-4xl p-4 rounded-2xl bg-zinc-900/40 border border-zinc-800 text-xs sm:text-sm text-zinc-300 leading-relaxed whitespace-normal break-words [overflow-wrap:anywhere]">
                        {{ $detail['description'] }}
                    </div>
                @endif

                {{-- Action Quick Read & Lanjutkan Membaca --}}
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="toggleFavorite"
                            aria-label="{{ $isFavorited ? 'Hapus dari favorit' : 'Tambahkan ke favorit' }}"
                            class="px-4 py-2.5 rounded-xl border text-xs font-bold transition flex items-center gap-2 {{ $isFavorited ? 'bg-red-600/15 border-red-500/50 text-red-300' : 'bg-zinc-900 border-zinc-800 text-zinc-300 hover:text-white hover:border-red-500/40' }}">
                        <svg class="w-4 h-4" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5.75A1.75 1.75 0 016.75 4h10.5A1.75 1.75 0 0119 5.75V21l-7-4-7 4V5.75z"/></svg>
                        {{ $isFavorited ? 'Favorit Tersimpan' : 'Tambah Favorit' }}
                    </button>
                    @if(!empty($detail['chapters']))
                        @php
                            $firstCh = end($detail['chapters']);
                            $latestCh = $detail['chapters'][0];
                        @endphp

                        {{-- Tombol Lanjutkan Membaca Realtime --}}
                        <template x-if="getEffectiveHistory()">
                            <a :href="`/doujin/read/{{ $detail['slug'] }}/${getEffectiveHistory().chapterId}?p=${getEffectiveHistory().progress || 0}&y=${getEffectiveHistory().scrollY || 0}`"
                               class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white text-xs font-black transition flex items-center gap-2 shadow-lg shadow-red-600/30">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <span>Lanjutkan <span x-text="getEffectiveHistory().label || 'Chapter'"></span> (<span x-text="`${getEffectiveHistory().progress || 0}%`"></span>)</span>
                            </a>
                        </template>

                        <a href="/doujin/read/{{ $detail['slug'] }}/{{ $firstCh['id'] }}"
                           class="px-6 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-white text-xs font-bold transition flex items-center gap-2">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            Baca Awal (Ch. {{ $firstCh['chapter_number'] }})
                        </a>
                        <a href="/doujin/read/{{ $detail['slug'] }}/{{ $latestCh['id'] }}"
                           class="px-5 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-white text-xs font-bold transition">
                            Baca Terbaru (Ch. {{ $latestCh['chapter_number'] }})
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ================= CHAPTER LIST ================= --}}
    <main class="w-full max-w-[1800px] mx-auto px-4 sm:px-6 pt-8">
        <div class="flex items-center justify-between pb-4 border-b border-zinc-800 mb-6">
            <div class="flex items-center gap-2">
                <span class="px-4 py-2 rounded-xl bg-red-600 text-white text-xs font-black shadow-lg shadow-red-600/30">
                    Daftar Chapter
                </span>
            </div>

            <span class="text-xs text-zinc-500 font-semibold">{{ count($detail['chapters'] ?? []) }} Chapter Total</span>
        </div>

        {{-- LIST CHAPTER VIEW DENGAN INDIKATOR PERSENAN & SCROLL JUMP --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @forelse($detail['chapters'] as $ch)
                <a :href="`/doujin/read/{{ $detail['slug'] }}/{{ $ch['id'] }}${getChapterProgress('{{ $ch['id'] }}') !== null ? `?p=${getChapterProgress('{{ $ch['id'] }}')}&y=${getChapterScrollY('{{ $ch['id'] }}')}` : ''}`"
                   class="group relative flex flex-col justify-between p-3.5 rounded-xl bg-zinc-900/60 hover:bg-zinc-800/90 border transition overflow-hidden"
                   :class="getChapterProgress('{{ $ch['id'] }}') !== null ? 'border-red-600/50 bg-zinc-900/90 ring-1 ring-red-600/30' : 'border-zinc-800 hover:border-red-500/40'">

                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 pr-2">
                            <span class="text-xs font-bold text-zinc-200 group-hover:text-red-400 transition truncate block">
                                {{ $ch['title'] ?: 'Chapter ' . $ch['chapter_number'] }}
                            </span>
                            @if(!empty($ch['created_at']))
                                <span class="text-[10px] text-zinc-500 mt-0.5 block">{{ $ch['created_at'] }}</span>
                            @endif
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-black/50 text-red-400 text-[10px] font-black shrink-0 border border-white/5">
                            CH. {{ $ch['chapter_number'] }}
                        </span>
                    </div>

                    {{-- Indikator Persenan Membaca Realtime --}}
                    <template x-if="getChapterProgress('{{ $ch['id'] }}') !== null">
                        <div class="mt-2.5 pt-2 border-t border-zinc-800/80 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                <span class="text-[10px] font-bold text-red-400"
                                      x-text="getChapterProgress('{{ $ch['id'] }}') >= 95 ? 'Selesai Dibaca' : `Terbaca ${getChapterProgress('{{ $ch['id'] }}')}%`"></span>
                            </div>
                            <span class="text-[9px] text-zinc-400 font-semibold group-hover:text-white transition flex items-center gap-1">
                                Lanjut
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </span>
                        </div>
                    </template>

                    {{-- Progress Mini Bar di bagian bawah kartu --}}
                    <template x-if="getChapterProgress('{{ $ch['id'] }}') !== null">
                        <div class="absolute bottom-0 inset-x-0 h-0.5 bg-zinc-800">
                            <div class="h-full bg-red-600 transition-all duration-300"
                                 :style="`width: ${getChapterProgress('{{ $ch['id'] }}')}%`"></div>
                        </div>
                    </template>
                </a>
            @empty
                <div class="col-span-full py-12 text-center text-zinc-500 bg-zinc-900/40 rounded-xl border border-dashed border-zinc-800">
                    Belum ada chapter yang tersedia.
                </div>
            @endforelse
        </div>
    </main>
</div>