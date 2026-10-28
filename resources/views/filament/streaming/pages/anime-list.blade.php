@php
    use App\Filament\Streaming\Pages\AnimeDetail;
    use App\Filament\Streaming\Pages\ExploreAnime;

    $detailUrl = fn ($slug) => $slug ? AnimeDetail::getUrl(['slug' => $slug], panel: 'streaming') : '#';
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16" x-data="{ mobileFilterOpen: false }">

    {{-- ================= HERO & HEADER ================= --}}
    <section class="relative w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-20 md:pt-28">
        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 text-xs text-zinc-400 mb-4">
            <a href="{{ ExploreAnime::getUrl(panel: 'streaming') }}"
               class="inline-flex items-center gap-1.5 font-bold text-zinc-300 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Beranda Anime
            </a>
            <span>/</span>
            <span class="text-zinc-500">Katalog Lengkap</span>
        </div>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-6 border-b border-zinc-800">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-red-600/20 text-red-400 border border-red-500/30">
                        DIREKTORI RESMI
                    </span>
                    <span class="text-xs text-zinc-500 font-semibold">Mirainime Engine</span>
                </div>
                <h1 class="text-3xl md:text-5xl font-black tracking-tight text-white leading-tight">
                    Daftar Anime Lengkap
                </h1>
                <p class="text-sm text-zinc-400 mt-1 max-w-xl">
                    Jelajahi dan filter ribuan judul anime subtitle Indonesia berdasarkan genre, status, format, dan urutan terpopuler.
                </p>
            </div>

            {{-- Quick Stats & View Switcher --}}
            <div class="flex items-center gap-3 self-start md:self-end shrink-0">
                <div class="inline-flex items-center p-1 bg-zinc-900 border border-zinc-800 rounded-xl">
                    <button type="button" wire:click="setViewMode('grid')"
                            aria-label="Mode Grid"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'grid' ? 'bg-red-600 text-white shadow-md' : 'text-zinc-400 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25z"/></svg>
                        <span class="hidden sm:inline">Grid</span>
                    </button>
                    <button type="button" wire:click="setViewMode('list')"
                            aria-label="Mode List"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'list' ? 'bg-red-600 text-white shadow-md' : 'text-zinc-400 hover:text-white' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        <span class="hidden sm:inline">List</span>
                    </button>
                </div>

                {{-- Mobile Filter Drawer Toggle Button --}}
                <button type="button" @click="mobileFilterOpen = true"
                        class="md:hidden inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-xs font-bold text-zinc-200">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                    <span>Filter</span>
                    @if($this->activeFilterCount > 0)
                        <span class="w-4 h-4 rounded-full bg-red-600 text-white text-[10px] inline-flex items-center justify-center font-black">
                            {{ $this->activeFilterCount }}
                        </span>
                    @endif
                </button>
            </div>
        </div>
    </section>

    {{-- ================= FILTER PANEL (DESKTOP) ================= --}}
    <section class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-6">
        <div class="bg-zinc-900/40 border border-zinc-800/80 rounded-2xl p-4 md:p-5 backdrop-blur-md space-y-4">
            {{-- Search & Sort Bar --}}
            <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center">
                {{-- Search Input --}}
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </span>
                    <input type="text"
                           wire:model.live.debounce.400ms="search"
                           placeholder="Cari judul anime (misal: Naruto, Bleach, Frieren)..."
                           class="w-full pl-10 pr-10 py-2.5 bg-black/60 text-white text-sm rounded-xl border border-zinc-700/80 focus:border-red-500 focus:ring-1 focus:ring-red-500 transition placeholder:text-zinc-500">
                    @if(filled($search))
                        <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-400 hover:text-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endif
                </div>

                {{-- Sort Dropdown --}}
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs font-bold text-zinc-400 hidden lg:inline">Urutkan:</span>
                    <select wire:model.live="sort"
                            class="px-3.5 py-2.5 bg-black/60 text-white text-xs font-bold rounded-xl border border-zinc-700/80 focus:border-red-500 focus:ring-1 focus:ring-red-500 transition">
                        <option value="popularity">★ Paling Populer (Default)</option>
                        <option value="latest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                        <option value="title">Judul (A - Z)</option>
                        <option value="rating">Rating Tertinggi</option>
                    </select>
                </div>
            </div>

            {{-- Secondary Filter Row: Genre, Status, Tipe, Musim, Tahun --}}
            <div class="hidden md:grid md:grid-cols-5 gap-3 pt-2 border-t border-zinc-800/60">
                {{-- Genre --}}
                <div>
                    <label class="block text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Genre</label>
                    <select wire:model.live="genre" class="w-full px-3 py-2 bg-black/60 text-zinc-200 text-xs rounded-xl border border-zinc-700/80 focus:border-red-500">
                        <option value="">Semua Genre</option>
                        @foreach($genresList as $g)
                            <option value="{{ $g['slug'] }}">{{ $g['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Status</label>
                    <select wire:model.live="status" class="w-full px-3 py-2 bg-black/60 text-zinc-200 text-xs rounded-xl border border-zinc-700/80 focus:border-red-500">
                        <option value="">Semua Status</option>
                        <option value="ongoing">Sedang Tayang (Ongoing)</option>
                        <option value="complete">Tamat (Completed)</option>
                        <option value="upcoming">Akan Datang (Upcoming)</option>
                    </select>
                </div>

                {{-- Tipe / Format --}}
                <div>
                    <label class="block text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Tipe</label>
                    <select wire:model.live="type" class="w-full px-3 py-2 bg-black/60 text-zinc-200 text-xs rounded-xl border border-zinc-700/80 focus:border-red-500">
                        <option value="">Semua Tipe</option>
                        <option value="TV">TV Series</option>
                        <option value="Movie">Movie</option>
                        <option value="OVA">OVA</option>
                        <option value="ONA">ONA</option>
                        <option value="Special">Special</option>
                    </select>
                </div>

                {{-- Musim --}}
                <div>
                    <label class="block text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Musim</label>
                    <select wire:model.live="season" class="w-full px-3 py-2 bg-black/60 text-zinc-200 text-xs rounded-xl border border-zinc-700/80 focus:border-red-500">
                        <option value="">Semua Musim</option>
                        @foreach($seasonsList as $s)
                            <option value="{{ $s['value'] }}">{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tahun --}}
                <div>
                    <label class="block text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-1">Tahun</label>
                    <select wire:model.live="year" class="w-full px-3 py-2 bg-black/60 text-zinc-200 text-xs rounded-xl border border-zinc-700/80 focus:border-red-500">
                        <option value="">Semua Tahun</option>
                        @foreach($yearsList as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Active Filter Chips & Clear Button --}}
            @if($this->activeFilterCount > 0)
                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-zinc-800/60 text-xs">
                    <span class="text-zinc-500 font-semibold">Filter aktif:</span>

                    @if(filled($search))
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-600/20 text-red-300 border border-red-500/30">
                            Cari: "{{ $search }}"
                            <button type="button" wire:click="$set('search', '')" class="hover:text-white font-bold ml-1">×</button>
                        </span>
                    @endif

                    @if(filled($genre))
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-600/20 text-red-300 border border-red-500/30">
                            Genre: {{ collect($genresList)->firstWhere('slug', $genre)['name'] ?? $genre }}
                            <button type="button" wire:click="$set('genre', '')" class="hover:text-white font-bold ml-1">×</button>
                        </span>
                    @endif

                    @if(filled($status))
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-600/20 text-red-300 border border-red-500/30">
                            Status: {{ ucfirst($status) }}
                            <button type="button" wire:click="$set('status', '')" class="hover:text-white font-bold ml-1">×</button>
                        </span>
                    @endif

                    @if(filled($type))
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-600/20 text-red-300 border border-red-500/30">
                            Tipe: {{ $type }}
                            <button type="button" wire:click="$set('type', '')" class="hover:text-white font-bold ml-1">×</button>
                        </span>
                    @endif

                    @if(filled($season))
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-600/20 text-red-300 border border-red-500/30">
                            Musim: {{ collect($seasonsList)->firstWhere('value', $season)['label'] ?? $season }}
                            <button type="button" wire:click="$set('season', '')" class="hover:text-white font-bold ml-1">×</button>
                        </span>
                    @endif

                    @if(filled($year))
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-600/20 text-red-300 border border-red-500/30">
                            Tahun: {{ $year }}
                            <button type="button" wire:click="$set('year', '')" class="hover:text-white font-bold ml-1">×</button>
                        </span>
                    @endif

                    @if($sort !== 'popularity')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            Urutan: {{ ucfirst($sort) }}
                            <button type="button" wire:click="$set('sort', 'popularity')" class="hover:text-white font-bold ml-1">×</button>
                        </span>
                    @endif

                    <button type="button" wire:click="resetFilters" class="text-zinc-400 hover:text-red-400 underline ml-2 font-semibold">
                        Reset Semua
                    </button>
                </div>
            @endif
        </div>
    </section>

    {{-- ================= MAIN CONTENT LIST / GRID ================= --}}
    <main class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-8">
        {{-- Section Header --}}
        <div class="flex items-center justify-between gap-3 mb-6">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
                <span class="text-sm font-bold text-zinc-300">
                    Menampilkan <strong class="text-white">{{ count($animes) }}</strong> judul anime
                </span>
            </div>

            <div wire:loading wire:target="search, genre, status, type, season, year, sort, resetFilters" class="text-xs text-red-400 font-bold flex items-center gap-1.5">
                <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span>Memperbarui katalog...</span>
            </div>
        </div>

        {{-- Empty State --}}
        @if(empty($animes))
            <div class="flex flex-col items-center justify-center py-20 text-center bg-zinc-900/30 border border-dashed border-zinc-800 rounded-3xl p-8">
                <div class="w-16 h-16 rounded-2xl bg-zinc-800/80 flex items-center justify-center text-zinc-500 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 15.75-2.489-2.489m0 0a3.375 3.375 0 1 0-4.773-4.773 3.375 3.375 0 0 0 4.774 4.774ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white mb-1">Anime tidak ditemukan</h3>
                <p class="text-xs text-zinc-400 max-w-sm mb-5">
                    Tidak ada judul yang cocok dengan filter atau kata kunci yang Anda masukkan. Coba ganti filter atau reset.
                </p>
                <button type="button" wire:click="resetFilters" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition shadow-lg shadow-red-600/30">
                    Reset Filter
                </button>
            </div>
        @else
            {{-- 1. GRID VIEW MODE --}}
            @if($viewMode === 'grid')
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-3 md:gap-4">
                    @foreach($animes as $item)
                        <a href="{{ $detailUrl($item['slug'] ?? '') }}"
                           class="group flex flex-col min-w-0 transition active:scale-95">
                            <div class="relative aspect-[2/3] rounded-2xl overflow-hidden bg-zinc-900 ring-1 ring-white/10 group-hover:ring-red-500/50 transition duration-300">
                                @if(!empty($item['thumbnail']))
                                    <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}"
                                         loading="lazy"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-zinc-900 text-zinc-600">
                                        <svg class="w-8 h-8 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    </div>
                                @endif

                                {{-- Top Status Badge --}}
                                @if(!empty($item['status']) && $item['status'] !== 'Available')
                                    <span class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider {{ strtolower($item['status']) === 'ongoing' ? 'bg-emerald-600 text-white' : 'bg-black/75 text-zinc-300' }} shadow-md">
                                        {{ $item['status'] }}
                                    </span>
                                @endif

                                {{-- Bottom Rating Badge --}}
                                @if(!empty($item['rating']))
                                    <span class="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 rounded-md text-[10px] font-black bg-black/80 text-amber-400 border border-amber-500/20 backdrop-blur-sm flex items-center gap-0.5">
                                        <span>★</span>
                                        <span>{{ $item['rating'] }}</span>
                                    </span>
                                @endif
                            </div>

                            {{-- Title & Meta --}}
                            <div class="mt-2 min-w-0">
                                <h3 class="text-xs md:text-[13px] font-bold text-zinc-200 group-hover:text-red-400 line-clamp-2 leading-snug transition"
                                    title="{{ $item['title'] }}">
                                    {{ $item['title'] }}
                                </h3>

                                @if(!empty($item['genres']))
                                    <p class="text-[10px] text-zinc-500 truncate mt-0.5 font-medium">
                                        {{ implode(', ', array_slice($item['genres'], 0, 2)) }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            {{-- 2. LIST VIEW MODE --}}
            @else
                <div class="space-y-3">
                    @foreach($animes as $item)
                        <a href="{{ $detailUrl($item['slug'] ?? '') }}"
                           class="group flex items-start gap-4 p-3.5 rounded-2xl bg-zinc-900/40 hover:bg-zinc-900 border border-zinc-800/80 hover:border-red-500/40 transition">
                            {{-- Poster --}}
                            <div class="w-16 sm:w-20 aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 shrink-0 ring-1 ring-white/10">
                                @if(!empty($item['thumbnail']))
                                    <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}" loading="lazy" class="w-full h-full object-cover">
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    @if(!empty($item['status']))
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase {{ strtolower($item['status']) === 'ongoing' ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30' : 'bg-zinc-800 text-zinc-300' }}">
                                            {{ $item['status'] }}
                                        </span>
                                    @endif
                                    @if(!empty($item['rating']))
                                        <span class="text-xs font-bold text-amber-400 flex items-center gap-0.5">
                                            <span>★</span> {{ $item['rating'] }}
                                        </span>
                                    @endif
                                </div>

                                <h3 class="text-sm sm:text-base font-bold text-white group-hover:text-red-400 transition leading-snug">
                                    {{ $item['title'] }}
                                </h3>

                                @if(!empty($item['genres']))
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach(array_slice($item['genres'], 0, 4) as $g)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-zinc-800/80 text-zinc-400">
                                                {{ $g }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if(!empty($item['synopsis']))
                                    <p class="text-xs text-zinc-400 line-clamp-2 mt-2 leading-relaxed hidden sm:block">
                                        {{ $item['synopsis'] }}
                                    </p>
                                @endif
                            </div>

                            {{-- CTA Chevron --}}
                            <div class="shrink-0 self-center hidden sm:flex items-center text-zinc-500 group-hover:text-white transition">
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Load More Action --}}
            <div class="mt-12 flex flex-col items-center justify-center">
                @if($hasMore)
                    <button type="button"
                            wire:click="loadMore"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2.5 px-8 py-3.5 rounded-2xl bg-zinc-900 hover:bg-zinc-800 text-white font-extrabold text-sm border border-zinc-700/80 shadow-xl transition active:scale-95 disabled:opacity-50">
                        <span wire:loading.remove wire:target="loadMore">Muat Lebih Banyak Judul ↓</span>
                        <span wire:loading.flex wire:target="loadMore" class="items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-red-500" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Memuat...
                        </span>
                    </button>
                @else
                    <p class="text-xs text-zinc-500 font-semibold tracking-wider uppercase">
                        — Seluruh katalog anime telah ditampilkan —
                    </p>
                @endif
            </div>
        @endif
    </main>

    {{-- ================= MOBILE FILTER DRAWER (< md) ================= --}}
    <div x-show="mobileFilterOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[160] bg-black/80 backdrop-blur-sm md:hidden"
         @click="mobileFilterOpen = false" style="display: none;"></div>

    <aside x-show="mobileFilterOpen"
           x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
           x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
           class="fixed inset-x-0 bottom-0 z-[161] md:hidden max-h-[85dvh] overflow-y-auto bg-[#0c0c0d] border-t border-white/10 rounded-t-3xl shadow-2xl p-5 pb-8 space-y-4"
           style="display: none;">
        <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
            <h3 class="text-base font-black text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                Filter Katalog
            </h3>
            <button type="button" @click="mobileFilterOpen = false" class="w-8 h-8 rounded-full bg-zinc-800 text-zinc-400 inline-flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-zinc-400 mb-1.5">Genre</label>
                <select wire:model.live="genre" class="w-full px-3 py-2.5 bg-black/80 text-white rounded-xl border border-zinc-700 text-sm">
                    <option value="">Semua Genre</option>
                    @foreach($genresList as $g)
                        <option value="{{ $g['slug'] }}">{{ $g['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-400 mb-1.5">Status</label>
                <select wire:model.live="status" class="w-full px-3 py-2.5 bg-black/80 text-white rounded-xl border border-zinc-700 text-sm">
                    <option value="">Semua Status</option>
                    <option value="ongoing">Sedang Tayang (Ongoing)</option>
                    <option value="complete">Tamat (Completed)</option>
                    <option value="upcoming">Akan Datang (Upcoming)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-400 mb-1.5">Tipe</label>
                <select wire:model.live="type" class="w-full px-3 py-2.5 bg-black/80 text-white rounded-xl border border-zinc-700 text-sm">
                    <option value="">Semua Tipe</option>
                    <option value="TV">TV Series</option>
                    <option value="Movie">Movie</option>
                    <option value="OVA">OVA</option>
                    <option value="ONA">ONA</option>
                    <option value="Special">Special</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-400 mb-1.5">Musim</label>
                <select wire:model.live="season" class="w-full px-3 py-2.5 bg-black/80 text-white rounded-xl border border-zinc-700 text-sm">
                    <option value="">Semua Musim</option>
                    @foreach($seasonsList as $s)
                        <option value="{{ $s['value'] }}">{{ $s['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-zinc-400 mb-1.5">Tahun</label>
                <select wire:model.live="year" class="w-full px-3 py-2.5 bg-black/80 text-white rounded-xl border border-zinc-700 text-sm">
                    <option value="">Semua Tahun</option>
                    @foreach($yearsList as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="button" @click="mobileFilterOpen = false" class="flex-1 py-3 rounded-xl bg-red-600 text-white font-bold text-sm shadow-lg shadow-red-600/30">
                    Terapkan Filter
                </button>
                <button type="button" wire:click="resetFilters" class="px-4 py-3 rounded-xl bg-zinc-800 text-zinc-300 font-bold text-sm">
                    Reset
                </button>
            </div>
        </div>
    </aside>
</div>
