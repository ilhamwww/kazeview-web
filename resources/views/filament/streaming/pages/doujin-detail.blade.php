<div class="min-h-screen bg-[#050505] text-white pb-24"
     x-data="{
         viewMode: 'list',
         kotatsuChapters: @js(array_slice($detail['chapters'] ?? [], 0, 10)),
         sortDesc: true
     }">

    {{-- ================= HERO DETAIL BANNER ================= --}}
    <section class="relative w-full overflow-hidden bg-zinc-950 border-b border-zinc-800">
        {{-- Background Backdrop Blur --}}
        <div class="absolute inset-0 opacity-20 filter blur-3xl scale-110 pointer-events-none">
            <img src="{{ $detail['cover'] }}" alt="" class="w-full h-full object-cover" referrerpolicy="no-referrer" />
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#050505] via-[#050505]/80 to-transparent"></div>

        <div class="relative w-full max-w-[1800px] mx-auto px-4 sm:px-6 pt-8 pb-10 flex flex-col md:flex-row gap-6 md:gap-8 items-start">
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
                    <div class="mt-4 p-4 rounded-2xl bg-zinc-900/40 border border-zinc-800 text-xs sm:text-sm text-zinc-300 leading-relaxed max-w-4xl">
                        {{ $detail['description'] }}
                    </div>
                @endif

                {{-- Action Quick Read --}}
                <div class="mt-6 flex items-center gap-3">
                    @if(!empty($detail['chapters']))
                        @php
                            $firstCh = end($detail['chapters']);
                            $latestCh = $detail['chapters'][0];
                        @endphp
                        <a href="/doujin/read/{{ $detail['slug'] }}/{{ $firstCh['id'] }}"
                           class="px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-red-600/30">
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

    {{-- ================= CHAPTER LIST / KOTATSU VIEW ================= --}}
    <main class="w-full max-w-[1800px] mx-auto px-4 sm:px-6 pt-8">
        <div class="flex items-center justify-between pb-4 border-b border-zinc-800 mb-6">
            <div class="flex items-center gap-2">
                <button type="button" @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'bg-zinc-900 text-zinc-400 hover:text-white border border-zinc-800'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition">
                    Daftar Chapter
                </button>
            </div>

            <span class="text-xs text-zinc-500 font-semibold">{{ count($detail['chapters'] ?? []) }} Chapter Total</span>
        </div>

        {{-- LIST CHAPTER VIEW --}}
        <div x-show="viewMode === 'list'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @forelse($detail['chapters'] as $ch)
                <a href="/doujin/read/{{ $detail['slug'] }}/{{ $ch['id'] }}"
                   class="group flex items-center justify-between p-3.5 rounded-xl bg-zinc-900/60 hover:bg-zinc-800/90 border border-zinc-800 hover:border-red-500/40 transition">
                    <div class="min-w-0 pr-3">
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
                </a>
            @empty
                <div class="col-span-full py-12 text-center text-zinc-500 bg-zinc-900/40 rounded-xl border border-dashed border-zinc-800">
                    Belum ada chapter yang tersedia.
                </div>
            @endforelse
        </div>
    </main>
</div>
