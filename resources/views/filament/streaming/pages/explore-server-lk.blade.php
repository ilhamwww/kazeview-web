@php
    use App\Filament\Streaming\Pages\WatchServerLk;

    $showSpotlight = empty($search) && empty($selectedCategory) && $pageNumber === 1 && count($featured) >= 2;
    $mainHero = $showSpotlight ? $featured[0] : null;
    $subHeroes = $showSpotlight ? array_slice($featured, 1, 3) : [];
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ================= HERO SPOTLIGHT ================= --}}
    @if($showSpotlight && $mainHero)
        <section class="relative w-full overflow-hidden">
            <div class="absolute inset-0" aria-hidden="true">
                <img src="{{ $mainHero['poster'] ?? '' }}" alt="" class="w-full h-full object-cover opacity-20 blur-3xl scale-125">
                <div class="absolute inset-0 bg-gradient-to-b from-[#050505]/60 via-[#050505]/85 to-[#050505]"></div>
            </div>

            <div class="relative w-full max-w-[1800px] mx-auto px-4 sm:px-6 md:px-12 pt-20 md:pt-28 pb-4">
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="inline-flex items-center gap-1.5 bg-gradient-to-r from-red-600 to-rose-600 text-white text-[10px] font-black px-2.5 py-1 rounded-md uppercase tracking-wider shadow-md shadow-red-900/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>SERVER LK (18+)
                    </span>
                    <span class="bg-red-500/10 text-rose-300 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-rose-500/30">
                        Katalog Khusus 18+ & Semi
                    </span>
                    <span class="inline-flex items-center gap-1 text-emerald-400 text-[10px] font-extrabold px-2 py-0.5 rounded border border-emerald-500/30 bg-emerald-500/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Aktif
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-[1.7fr_1fr] gap-4">
                    {{-- Main Hero Banner --}}
                    <a href="{{ WatchServerLk::getUrl(['slug' => $mainHero['slug']], panel: 'streaming') }}"
                       class="group relative rounded-3xl overflow-hidden bg-zinc-900 ring-1 ring-white/10 min-h-[380px] md:min-h-[460px] flex flex-col justify-end">
                        <img src="{{ $mainHero['poster'] ?? '' }}" alt="{{ $mainHero['title'] }}"
                             class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-105 transition duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
                        
                        <div class="relative p-6 md:p-8 z-10">
                            <div class="flex items-center gap-2 text-xs font-semibold text-zinc-300 mb-2">
                                <span class="bg-red-600 text-white font-black text-[10px] px-2 py-0.5 rounded">PILIHAN HARI INI</span>
                                @if(!empty($mainHero['year']))
                                    <span>{{ $mainHero['year'] }}</span>
                                    <span>•</span>
                                @endif
                                <span class="text-amber-400 font-bold">HD Sub Indo</span>
                            </div>

                            <h1 class="text-2xl sm:text-3xl md:text-5xl font-black tracking-tight leading-tight line-clamp-2 drop-shadow-xl">
                                {{ $mainHero['title'] }}
                            </h1>

                            @if(!empty($mainHero['synopsis']))
                                <p class="mt-2 text-xs sm:text-sm text-zinc-300 line-clamp-2 max-w-2xl">
                                    {{ $mainHero['synopsis'] }}
                                </p>
                            @endif

                            <div class="flex items-center gap-3 mt-4">
                                <span class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-500 text-white rounded-xl font-bold text-sm shadow-lg shadow-red-900/40 transition">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    Nonton Sekarang
                                </span>
                            </div>
                        </div>
                    </a>

                    {{-- Secondary Spotlight Column --}}
                    <div class="flex flex-col gap-3">
                        @foreach($subHeroes as $sub)
                            <a href="{{ WatchServerLk::getUrl(['slug' => $sub['slug']], panel: 'streaming') }}"
                               class="group relative flex-1 min-h-[120px] rounded-2xl overflow-hidden bg-zinc-900 ring-1 ring-white/10 flex items-center p-3.5 gap-4 hover:ring-amber-500/40 transition">
                                <div class="w-20 sm:w-24 h-full min-h-[100px] rounded-xl overflow-hidden flex-shrink-0 bg-zinc-800 relative">
                                    <img src="{{ $sub['poster'] ?? '' }}" alt="{{ $sub['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                    <span class="absolute top-1 left-1 bg-black/70 backdrop-blur-md text-[9px] font-black px-1.5 py-0.5 rounded text-amber-300 border border-amber-400/30">HD</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 text-[11px] text-zinc-400 mb-1">
                                        @if(!empty($sub['year']))
                                            <span class="text-zinc-300 font-bold">{{ $sub['year'] }}</span>
                                            <span>•</span>
                                        @endif
                                        <span class="truncate">{{ implode(', ', $sub['genres'] ?? []) }}</span>
                                    </div>
                                    <h3 class="text-sm sm:text-base font-bold text-white group-hover:text-red-400 transition line-clamp-2 leading-snug">
                                        {{ $sub['title'] }}
                                    </h3>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-400 mt-2">
                                        Putar Film
                                        <svg class="w-3 h-3 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ================= MAIN CONTENT CONTAINER ================= --}}
    <main class="w-full max-w-[1800px] mx-auto px-4 sm:px-6 md:px-12 {{ $showSpotlight ? 'pt-8' : 'pt-24 md:pt-28' }}">

        {{-- Header & Search Bar --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-red-600/20 text-red-400 border border-red-500/30 text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-wider">
                        18+ & Semi Only
                    </span>
                    <span class="text-xs text-zinc-400">Total {{ number_format($totalItems) }} Film 18+ Tersedia</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                    Katalog Khusus 18+ & Semi
                </h2>
            </div>

            {{-- Search Box --}}
            <div class="relative w-full lg:w-96">
                <input type="text"
                       wire:model.live.debounce.400ms="search"
                       placeholder="Cari judul 18+, JAV, atau Semi..."
                       class="w-full bg-[#111111] border border-zinc-800 focus:border-red-500 focus:ring-1 focus:ring-red-500 rounded-2xl px-4 py-2.5 pl-10 text-sm text-white placeholder-zinc-500 transition outline-none shadow-inner">
                <svg class="w-4 h-4 text-zinc-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                @if(!empty($search))
                    <button type="button" wire:click="clearFilters" class="absolute right-3.5 top-3 text-zinc-400 hover:text-white text-xs font-bold bg-zinc-800 w-5 h-5 rounded-full flex items-center justify-center">
                        ✕
                    </button>
                @endif
            </div>
        </div>

        {{-- Genre Filter Bar --}}
        <div class="relative mb-6">
            <div class="flex items-center gap-1.5 overflow-x-auto pb-2 scrollbar-none scroll-smooth">
                @foreach($categories as $cat)
                    @php
                        $isActive = ($selectedCategory === $cat['id']) || (empty($selectedCategory) && empty($cat['id']));
                    @endphp
                    <button type="button"
                            wire:click="setCategory({{ $cat['id'] !== null ? $cat['id'] : 'null' }})"
                            class="px-3.5 py-1.5 rounded-full text-xs font-bold tracking-wide whitespace-nowrap transition cursor-pointer flex-shrink-0 {{ $isActive ? 'bg-red-600 text-white shadow-md shadow-red-900/40 border border-red-500/50' : 'bg-zinc-900/90 hover:bg-zinc-800 text-zinc-300 hover:text-white border border-white/5' }}">
                        {{ $cat['name'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Loading Indicator --}}
        <div wire:loading.flex class="items-center justify-center py-16 text-zinc-400 gap-3">
            <svg class="animate-spin w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-sm font-semibold">Memuat daftar film Server LK...</span>
        </div>

        {{-- Movies Grid --}}
        <div wire:loading.remove>
            @if(empty($movies))
                <div class="py-24 text-center rounded-3xl bg-zinc-950/60 border border-zinc-900 p-8">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-zinc-900 flex items-center justify-center text-zinc-500 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-1">Film Tidak Ditemukan</h3>
                    <p class="text-xs text-zinc-400 max-w-md mx-auto mb-5">
                        Tidak ada film yang cocok dengan filter atau kata kunci pencarian Anda. Coba kata kunci lain atau reset filter.
                    </p>
                    <button type="button" wire:click="clearFilters" class="px-5 py-2.5 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-bold transition">
                        Reset Filter
                    </button>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4 md:gap-5">
                    @foreach($movies as $movie)
                        <a href="{{ WatchServerLk::getUrl(['slug' => $movie['slug']], panel: 'streaming') }}"
                           class="group relative flex flex-col rounded-2xl overflow-hidden bg-[#101012] border border-white/5 hover:border-red-500/40 hover:shadow-xl hover:shadow-red-950/20 transition-all duration-300">
                            
                            {{-- Poster Thumbnail --}}
                            <div class="relative aspect-[2/3] w-full overflow-hidden bg-zinc-900">
                                @if(!empty($movie['poster']))
                                    <img src="{{ $movie['poster'] }}"
                                         alt="{{ $movie['title'] }}"
                                         loading="lazy"
                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-zinc-700 bg-zinc-900">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                    </div>
                                @endif

                                {{-- Top badges --}}
                                <div class="absolute top-2 inset-x-2 flex items-center justify-between gap-1 pointer-events-none">
                                    <span class="bg-black/80 backdrop-blur-md text-[9px] font-black px-1.5 py-0.5 rounded text-amber-300 border border-amber-400/30 uppercase">
                                        HD
                                    </span>
                                    @if(!empty($movie['year']))
                                        <span class="bg-black/80 backdrop-blur-md text-[10px] font-bold px-1.5 py-0.5 rounded text-zinc-200 border border-white/10">
                                            {{ $movie['year'] }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Play Overlay --}}
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                    <div class="w-12 h-12 rounded-full bg-red-600 text-white flex items-center justify-center shadow-lg shadow-red-950/60 transform scale-75 group-hover:scale-100 transition">
                                        <svg class="w-5 h-5 fill-current ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Card Info --}}
                            <div class="p-3 flex flex-col flex-1 justify-between gap-1.5">
                                <div>
                                    @if(!empty($movie['genres']))
                                        <div class="text-[10px] font-semibold text-zinc-400 truncate mb-0.5">
                                            {{ implode(', ', $movie['genres']) }}
                                        </div>
                                    @endif
                                    <h4 class="text-xs sm:text-sm font-bold text-white group-hover:text-red-400 transition line-clamp-2 leading-snug" title="{{ $movie['title'] }}">
                                        {{ $movie['title'] }}
                                    </h4>
                                </div>
                                <div class="pt-1.5 border-t border-white/5 flex items-center justify-between text-[10px] text-zinc-400">
                                    <span class="text-red-400 font-extrabold uppercase">Server LK</span>
                                    <span>Nonton</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination Controls --}}
                @if($totalPages > 1)
                    <div class="mt-12 flex items-center justify-center gap-3">
                        <button type="button"
                                wire:click="previousPage"
                                @if($pageNumber <= 1) disabled @endif
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $pageNumber <= 1 ? 'bg-zinc-900 text-zinc-600 cursor-not-allowed border border-zinc-800/50' : 'bg-[#121215] hover:bg-zinc-800 text-white border border-white/10 active:scale-95' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                            Sebelumnya
                        </button>

                        <span class="px-4 py-2 rounded-xl bg-zinc-900/80 border border-white/5 text-xs font-bold text-zinc-300">
                            Hal <span class="text-white font-black">{{ $pageNumber }}</span> dari <span class="text-zinc-400">{{ $totalPages }}</span>
                        </span>

                        <button type="button"
                                wire:click="nextPage"
                                @if($pageNumber >= $totalPages) disabled @endif
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $pageNumber >= $totalPages ? 'bg-zinc-900 text-zinc-600 cursor-not-allowed border border-zinc-800/50' : 'bg-red-600 hover:bg-red-500 text-white border border-red-500/40 active:scale-95 shadow-lg shadow-red-950/40' }}">
                            Selanjutnya
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </main>
</div>
