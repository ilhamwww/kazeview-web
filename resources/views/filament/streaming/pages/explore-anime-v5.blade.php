@php
    use App\Filament\Streaming\Pages\AnimeDetailV5;
    use App\Filament\Streaming\Pages\AnimeSources;

    $slugOf = function (array $a): string {
        $slug = $a['slug'] ?? '';
        if (empty($slug) && !empty($a['url'])) {
            $parts = array_filter(explode('/', trim((string) parse_url($a['url'], PHP_URL_PATH), '/')));
            $slug = (string) (end($parts) ?: '');
        }
        return preg_replace('/-episode-\d+.*$/i', '', $slug);
    };
    $detailUrl = fn (array $a) => ($s = $slugOf($a)) ? AnimeDetailV5::getUrl(['slug' => $s], panel: 'streaming') : '#';
    $showHome = (isset($this) && isset($this->showHome)) ? $this->showHome : ($showHome ?? (blank($search ?? '') && blank($status ?? '') && ($order ?? 'latest') === 'latest' && ($pageNumber ?? 1) === 1));
    $spotlight = array_slice($latestUpdates ?? [], 0, 5);
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ================= HERO SPOTLIGHT ================= --}}
    @if($showHome && count($spotlight) >= 3)
        @php $main = $spotlight[0]; @endphp
        <section class="relative w-full overflow-hidden">
            <div class="absolute inset-0" aria-hidden="true">
                <img src="{{ $main['thumbnail'] ?? '' }}" alt="" class="w-full h-full object-cover opacity-25 blur-3xl scale-125">
                <div class="absolute inset-0 bg-gradient-to-b from-[#050505]/60 via-[#050505]/85 to-[#050505]"></div>
            </div>

            <div class="relative w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-24 md:pt-28 pb-6">
                <div class="flex flex-wrap items-center gap-2 mb-5">
                    <a href="{{ AnimeSources::getUrl(panel: 'streaming') }}"
                       class="inline-flex items-center gap-1.5 text-[11px] font-bold text-zinc-200 bg-black/50 border border-white/15 px-3 py-1 rounded-full hover:text-white hover:border-white/30 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Ganti Sumber
                    </a>
                    <span class="bg-rose-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider">SERVER 5</span>
                    <span class="bg-white/10 text-rose-300 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-rose-500/30">1080P FHD & STREAM RELAY</span>
                    <span class="inline-flex items-center gap-1.5 text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-emerald-500/30 bg-emerald-500/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Online
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-[1.6fr_1fr] gap-4 lg:h-[500px]">
                    {{-- Main spotlight --}}
                    <a href="{{ $detailUrl($main) }}" class="group relative rounded-3xl overflow-hidden bg-zinc-900 ring-1 ring-white/10 min-h-[380px]">
                        <img src="{{ $main['thumbnail'] ?? '' }}" alt="{{ $main['title'] }}" class="absolute inset-0 w-full h-full object-cover object-top group-hover:scale-105 transition duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6 md:p-8">
                            <span class="inline-flex items-center gap-1.5 bg-rose-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-[0.15em]">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>Rilis Terbaru
                            </span>
                            <h1 class="mt-3 text-2xl md:text-4xl font-black tracking-tight leading-tight line-clamp-2 drop-shadow-xl">{{ $main['anime_title'] ?? $main['title'] }}</h1>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-sm font-semibold text-zinc-300">
                                @if(!empty($main['episode']))<span class="text-rose-400">{{ $main['episode'] }}</span>@endif
                                @if(!empty($main['type']))<span class="text-zinc-600">•</span><span>{{ $main['type'] }}</span>@endif
                            </div>
                            <span class="inline-flex items-center gap-2 mt-4 pl-4 pr-5 py-2.5 bg-white text-black rounded-lg font-bold text-sm group-hover:bg-zinc-200 transition">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                Tonton Sekarang
                            </span>
                        </div>
                    </a>

                    {{-- Side spotlight grid --}}
                    <div class="grid grid-cols-2 gap-3">
                        @foreach(array_slice($spotlight, 1, 4) as $side)
                            <a href="{{ $detailUrl($side) }}" class="group relative rounded-2xl overflow-hidden bg-zinc-900 ring-1 ring-white/10 flex flex-col justify-end p-4">
                                <img src="{{ $side['thumbnail'] ?? '' }}" alt="{{ $side['title'] }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
                                <div class="relative z-10">
                                    <span class="text-[9px] font-black uppercase text-rose-400 tracking-wider">{{ $side['type'] ?? 'ANIME' }}</span>
                                    <h3 class="text-xs md:text-sm font-bold text-white line-clamp-2 leading-snug group-hover:text-rose-300 transition">{{ $side['anime_title'] ?? $side['title'] }}</h3>
                                    @if(!empty($side['episode']))
                                        <p class="text-[10px] text-zinc-400 mt-1 font-semibold">{{ $side['episode'] }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ================= CONTROLS & SEARCH BAR ================= --}}
    <div class="w-full max-w-[1800px] mx-auto px-6 md:px-12 {{ $showHome ? 'mt-8' : 'pt-24 md:pt-28' }} space-y-6">
        @if(!$showHome)
            <div class="flex items-center justify-between flex-wrap gap-3 pb-2 border-b border-zinc-800">
                <div class="flex items-center gap-2">
                    <a href="{{ AnimeSources::getUrl(panel: 'streaming') }}"
                       class="inline-flex items-center gap-1.5 text-xs font-bold text-zinc-400 hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Kembali ke Pilihan Server
                    </a>
                    <span class="text-zinc-600">/</span>
                    <span class="text-xs font-bold text-rose-400 uppercase tracking-wider">Server 5 (NontonAnimeID)</span>
                </div>
            </div>
        @endif

        {{-- Search Input & Filter Pills --}}
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            {{-- Search Box --}}
            <div class="relative flex-1 max-w-xl">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input wire:model.live.debounce.400ms="search"
                       type="text"
                       placeholder="Cari anime atau donghua di Server 5..."
                       class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-zinc-900 border border-zinc-800 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 transition">
                @if(filled($search))
                    <button wire:click="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            {{-- Filters: Status & Order --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                {{-- Status Filter --}}
                <div class="inline-flex rounded-xl bg-zinc-900 p-1 border border-zinc-800 text-xs font-bold">
                    <button wire:click="$set('status', '')"
                            class="px-3 py-1.5 rounded-lg transition {{ blank($status) ? 'bg-rose-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Semua
                    </button>
                    <button wire:click="$set('status', 'ongoing')"
                            class="px-3 py-1.5 rounded-lg transition {{ $status === 'ongoing' ? 'bg-rose-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Ongoing
                    </button>
                    <button wire:click="$set('status', 'completed')"
                            class="px-3 py-1.5 rounded-lg transition {{ $status === 'completed' ? 'bg-rose-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Tamat
                    </button>
                </div>

                {{-- Order Filter --}}
                <div class="inline-flex rounded-xl bg-zinc-900 p-1 border border-zinc-800 text-xs font-bold">
                    <button wire:click="$set('order', 'latest')"
                            class="px-3 py-1.5 rounded-lg transition {{ $order === 'latest' ? 'bg-rose-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Terbaru
                    </button>
                    <button wire:click="$set('order', 'popular')"
                            class="px-3 py-1.5 rounded-lg transition {{ $order === 'popular' ? 'bg-rose-600 text-white' : 'text-zinc-400 hover:text-white' }}">
                        Populer
                    </button>
                </div>
            </div>
        </div>

        {{-- Section Title --}}
        <div class="flex items-center justify-between pt-2">
            <div>
                <h2 class="text-xl md:text-2xl font-black text-white flex items-center gap-2">
                    @if(filled($search))
                        <span>Hasil Pencarian: "{{ $search }}"</span>
                    @elseif($status === 'ongoing')
                        <span>Anime Ongoing</span>
                    @elseif($status === 'completed')
                        <span>Anime Tamat</span>
                    @elseif($order === 'popular')
                        <span>Anime Paling Populer</span>
                    @else
                        <span>Daftar Anime & Donghua</span>
                    @endif
                </h2>
                <p class="text-xs text-zinc-400 mt-1">
                    Multi-resolusi 360p - 1080p FHD dengan relay stream video aman & cepat
                </p>
            </div>
        </div>

        {{-- ================= ANIME GRID ================= --}}
        <div wire:loading.class="opacity-50 pointer-events-none" class="transition">
            @if(empty($animes))
                <div class="text-center py-20 bg-zinc-900/50 rounded-2xl border border-zinc-800">
                    <svg class="w-12 h-12 text-zinc-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-base font-bold text-zinc-300">Tidak ada anime yang ditemukan.</p>
                    <p class="text-xs text-zinc-500 mt-1">Coba gunakan kata kunci pencarian yang lain.</p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-5">
                    @foreach($animes as $anime)
                        <a href="{{ $detailUrl($anime) }}"
                           class="group relative flex flex-col rounded-xl overflow-hidden bg-zinc-900/90 border border-zinc-800/80 hover:border-rose-500/50 transition duration-300 hover:shadow-xl hover:shadow-rose-950/20">
                            {{-- Poster Image --}}
                            <div class="relative aspect-[3/4] w-full overflow-hidden bg-zinc-950">
                                <img src="{{ $anime['thumbnail'] ?? '' }}"
                                     alt="{{ $anime['title'] ?? 'Anime' }}"
                                     loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>

                                {{-- Type Badge --}}
                                @if(!empty($anime['type']))
                                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-black/70 text-rose-400 border border-rose-500/30 backdrop-blur-sm">
                                        {{ $anime['type'] }}
                                    </span>
                                @endif

                                {{-- Status / Episode Badge --}}
                                @if(!empty($anime['status']))
                                    <span class="absolute top-2 right-2 px-1.5 py-0.5 rounded text-[9px] font-bold bg-zinc-900/80 text-zinc-300 border border-zinc-700 backdrop-blur-sm">
                                        {{ $anime['status'] }}
                                    </span>
                                @endif

                                {{-- Play Hover Icon --}}
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300 bg-black/40">
                                    <div class="w-10 h-10 rounded-full bg-rose-600 text-white flex items-center justify-center shadow-lg shadow-rose-600/50 transform group-hover:scale-110 transition">
                                        <svg class="w-5 h-5 fill-current ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Title & Info --}}
                            <div class="p-3 flex-1 flex flex-col justify-between">
                                <h3 class="text-xs md:text-sm font-bold text-zinc-100 group-hover:text-rose-400 transition line-clamp-2 leading-tight">
                                    {{ $anime['title'] ?? 'Anime Title' }}
                                </h3>
                                @if(!empty($anime['episode']))
                                    <p class="text-[10px] text-zinc-400 mt-2 font-semibold">
                                        {{ $anime['episode'] }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- ================= PAGINATION ================= --}}
                <div class="flex items-center justify-center gap-3 pt-8 pb-4">
                    <button wire:click="previousPage"
                            @if($pageNumber <= 1) disabled @endif
                            class="px-4 py-2 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 {{ $pageNumber <= 1 ? 'border-zinc-800 text-zinc-600 cursor-not-allowed bg-zinc-950' : 'border-zinc-700 text-zinc-200 hover:text-white bg-zinc-900 hover:bg-zinc-800' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        Sebelumnya
                    </button>

                    <span class="px-3.5 py-1.5 rounded-xl bg-zinc-900 border border-zinc-800 text-xs font-mono font-bold text-rose-400">
                        Hal {{ $pageNumber }}
                    </span>

                    <button wire:click="nextPage"
                            @if(!$hasNextPage) disabled @endif
                            class="px-4 py-2 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 {{ !$hasNextPage ? 'border-zinc-800 text-zinc-600 cursor-not-allowed bg-zinc-950' : 'border-zinc-700 text-zinc-200 hover:text-white bg-zinc-900 hover:bg-zinc-800' }}">
                        Selanjutnya
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
