<div class="min-h-screen bg-[#050505] text-zinc-100 pb-28 selection:bg-red-600 selection:text-white"
     x-data="{
        history: null,
        init() {
            try {
                const raw = localStorage.getItem('kz_comic_{{ $comic['id'] }}');
                if (raw) this.history = JSON.parse(raw);
            } catch(e) {}
        }
     }">

    {{-- ================= BACKDROP BLUR HEADER ================= --}}
    <div class="relative w-full min-h-[360px] md:min-h-[420px] overflow-hidden border-b border-white/5 bg-zinc-950">
        {{-- Background Image with Blur --}}
        <img src="{{ ($comic['banner'] ?? null) ?: ($comic['cover'] ?? '') }}"
             alt="{{ $comic['title'] ?? '' }}"
             class="absolute inset-0 w-full h-full object-cover object-top opacity-30 filter blur-xl scale-110" />

        <div class="absolute inset-0 bg-gradient-to-t from-[#050505] via-[#050505]/75 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-black/50 via-transparent to-[#050505]"></div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 md:pt-28 lg:pt-32 pb-8">
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

            <div class="flex flex-col md:flex-row gap-6 md:gap-10 items-start">
            {{-- Poster Card --}}
            <div class="shrink-0 w-36 sm:w-44 md:w-56 aspect-[2/3] rounded-2xl overflow-hidden ring-1 ring-white/15 shadow-2xl shadow-black/90 bg-zinc-900 mx-auto md:mx-0">
                <img src="{{ $comic['cover'] }}" alt="{{ $comic['title'] }}" class="w-full h-full object-cover" />
            </div>

            {{-- Metadata --}}
            <div class="flex-1 min-w-0 flex flex-col justify-end">
                <div class="flex items-center gap-2 mb-2.5 flex-wrap">
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

                <h1 class="text-2xl sm:text-3xl md:text-5xl font-black text-white tracking-tight leading-tight">
                    {{ $comic['title'] }}
                </h1>

                @if(!empty($comic['alternative_title']) || !empty($comic['alt_title']))
                    <p class="text-xs sm:text-sm text-zinc-400 mt-1.5 font-medium line-clamp-1">
                        {{ $comic['alternative_title'] ?? $comic['alt_title'] }}
                    </p>
                @endif

                {{-- Author & Artist Badges --}}
                @if(!empty($comic['author']) || !empty($comic['artist']))
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        @if(!empty($comic['author']))
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300">
                                <span class="text-zinc-500">Author:</span> <strong class="text-white">{{ $comic['author'] }}</strong>
                            </span>
                        @endif
                        @if(!empty($comic['artist']))
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300">
                                <span class="text-zinc-500">Artist:</span> <strong class="text-white">{{ $comic['artist'] }}</strong>
                            </span>
                        @endif
                    </div>
                @endif

                {{-- Authors & Genres --}}
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @foreach($comic['genres'] ?? [] as $g)
                        <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white/5 border border-white/5 text-zinc-300">
                            {{ $g }}
                        </span>
                    @endforeach
                </div>

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
                <div class="mt-6 flex items-center gap-3 flex-wrap">
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

                    @if($latestChapter && $latestChapter['id'] !== ($firstChapter['id'] ?? null))
                        <a href="/komik/{{ $comic['id'] }}/read/{{ $latestChapter['id'] }}"
                           class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-zinc-200 font-bold text-sm border border-zinc-700 transition">
                            <span>Chapter Terbaru (Ch. {{ $latestChapter['number'] }})</span>
                        </a>
                    @endif

                    {{-- Tombol Tambah ke Favorit --}}
                    <button type="button" wire:click="toggleFavorite"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-sm border transition active:scale-95 {{ $isFavorited ? 'bg-red-600/20 border-red-500 text-red-400 hover:bg-red-600/30' : 'bg-zinc-900 hover:bg-zinc-800 border-zinc-700 text-zinc-300 hover:text-white' }}">
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
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        {{-- Sinopsis Card --}}
        <div class="p-5 md:p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800/80 mb-10"
             x-data="{ expanded: false }">
            <h2 class="text-base font-black text-white tracking-wide uppercase mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                <span>Sinopsis Cerita</span>
            </h2>
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
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pb-4 border-b border-zinc-800/80 mb-6">
            <div class="flex items-center gap-3">
                <h2 class="text-xl md:text-2xl font-black text-white tracking-tight">Daftar Chapter</h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-zinc-800 text-zinc-400">
                    {{ count($chapters) }} Chapter
                </span>
            </div>

            <div class="flex items-center gap-2">
                {{-- Search Chapter Input --}}
                <div class="relative flex-1 sm:w-56">
                    <input type="text"
                           wire:model.live.debounce.250ms="searchChapter"
                           placeholder="Cari chapter..."
                           class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500" />
                    <svg class="w-3.5 h-3.5 absolute left-3 top-2.5 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </div>

                {{-- Toggle Sort Order Button --}}
                <button type="button" wire:click="toggleSort"
                        class="shrink-0 px-3 py-1.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-xs font-bold text-zinc-300 flex items-center gap-1.5 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/></svg>
                    <span>{{ $sortOrder === 'desc' ? 'Terbaru' : 'Terlama' }}</span>
                </button>
            </div>
        </div>

        {{-- Chapter Grid Cards --}}
        @php
            $displayChapters = $this->filteredChapters;
        @endphp

        @if(empty($displayChapters))
            <div class="py-12 text-center text-sm font-semibold text-zinc-500 bg-zinc-900/40 rounded-2xl border border-zinc-800/80">
                Tidak ada chapter yang cocok dengan pencarian "<span class="text-white">{{ $searchChapter }}</span>".
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($displayChapters as $ch)
                    <a href="/komik/{{ $comic['id'] }}/read/{{ $ch['id'] }}"
                       class="group p-3.5 rounded-xl bg-zinc-900/80 hover:bg-zinc-800/90 border border-zinc-800/80 hover:border-red-500/50 flex items-center justify-between gap-3 transition shadow-sm active:scale-[0.99]"
                       :class="history && history.chapterId === '{{ $ch['id'] }}' ? 'border-red-500 ring-1 ring-red-500/40 bg-red-950/20' : ''">
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-sm text-zinc-200 group-hover:text-red-400 transition truncate">
                                {{ $ch['label'] }}
                            </p>
                            <div class="flex items-center gap-2 mt-0.5">
                                @if(!empty($ch['release_date']))
                                    <span class="text-[11px] text-zinc-500 font-medium">
                                        {{ date('d M Y', strtotime($ch['release_date'])) }}
                                    </span>
                                @endif
                                <template x-if="history && history.chapterId === '{{ $ch['id'] }}' && history.progress">
                                    <span class="text-[10px] font-extrabold text-red-400 bg-red-950/60 px-2 py-0.5 rounded-full ring-1 ring-red-500/40"
                                          x-text="'Terakhir: ' + history.progress + '%'"></span>
                                </template>
                            </div>
                        </div>

                        <span class="shrink-0 w-7 h-7 rounded-lg bg-white/5 group-hover:bg-red-600 group-hover:text-white flex items-center justify-center text-zinc-400 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
