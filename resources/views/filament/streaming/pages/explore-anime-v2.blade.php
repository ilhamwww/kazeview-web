@php
    use App\Filament\Streaming\Pages\AnimeDetailV2;
    use App\Filament\Streaming\Pages\AnimeSources;

    $slugOf = function (array $a): string {
        if (!empty($a['slug'])) return $a['slug'];
        $parts = array_filter(explode('/', trim((string) parse_url($a['url'] ?? '', PHP_URL_PATH), '/')));
        return (string) (end($parts) ?: '');
    };
    $detailUrl = fn (array $a) => ($s = $slugOf($a)) ? AnimeDetailV2::getUrl(['slug' => $s], panel: 'streaming') : '#';
    $epNum = fn ($e) => preg_match('/(\d+)/', (string) $e, $m) ? $m[1] : null;
    $showHome = $this->showHome;
    $spotlight = array_slice($ongoing, 0, 5);
    $fresh = array_slice($ongoing, 0, 12);
    $doneRow = array_slice($completed, 0, 14);
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ================= HERO (bento spotlight) ================= --}}
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
                    <span class="bg-blue-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider">SERVER 2</span>
                    <span class="bg-white/10 text-zinc-200 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-white/10">MULTI MIRROR</span>
                    <span class="inline-flex items-center gap-1.5 text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-emerald-500/30 bg-emerald-500/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Online
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-[1.6fr_1fr] gap-4 lg:h-[520px]">
                    {{-- Main spotlight --}}
                    <a href="{{ $detailUrl($main) }}" class="group relative rounded-3xl overflow-hidden bg-zinc-900 ring-1 ring-white/10 min-h-[420px]">
                        <img src="{{ $main['thumbnail'] ?? '' }}" alt="{{ $main['title'] }}" class="absolute inset-0 w-full h-full object-cover object-top group-hover:scale-105 transition duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-6 md:p-8">
                            <span class="inline-flex items-center gap-1.5 bg-blue-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-[0.15em]">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>Rilis Terbaru
                            </span>
                            <h1 class="mt-3 text-3xl md:text-5xl font-black tracking-tight leading-[1] line-clamp-2 drop-shadow-xl">{{ $main['title'] }}</h1>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-3 text-sm font-semibold text-zinc-300">
                                @if(!empty($main['episode']))<span class="text-blue-400">{{ $main['episode'] }}</span>@endif
                                @if(!empty($main['type']))<span class="text-zinc-600">•</span><span>{{ $main['type'] }}</span>@endif
                                @if(!empty($main['date']))<span class="text-zinc-600">•</span><span>{{ $main['date'] }}</span>@endif
                            </div>
                            <span class="inline-flex items-center gap-2 mt-5 pl-4 pr-5 py-2.5 bg-white text-black rounded-lg font-bold text-sm group-hover:bg-zinc-200 transition">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                Tonton Sekarang
                            </span>
                        </div>
                    </a>

                    {{-- Side spotlights --}}
                    <div class="grid grid-cols-2 lg:grid-cols-2 lg:grid-rows-2 gap-4">
                        @foreach(array_slice($spotlight, 1, 4) as $s)
                            <a href="{{ $detailUrl($s) }}" class="group relative rounded-2xl overflow-hidden bg-zinc-900 ring-1 ring-white/10 aspect-[3/4] lg:aspect-auto">
                                <img src="{{ $s['thumbnail'] ?? '' }}" alt="{{ $s['title'] }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-700" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/20 to-transparent"></div>
                                @if($n = $epNum($s['episode'] ?? ''))
                                    <span class="absolute top-2.5 left-2.5 bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded">EP {{ $n }}</span>
                                @endif
                                <div class="absolute inset-x-0 bottom-0 p-3">
                                    <p class="text-sm font-bold leading-snug line-clamp-2 group-hover:text-blue-300 transition">{{ $s['title'] }}</p>
                                    @if(!empty($s['date']))<p class="text-[11px] text-zinc-400 mt-0.5">{{ $s['date'] }}</p>@endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ================= TOOLBAR ================= --}}
    <section class="w-full max-w-[1800px] mx-auto px-6 md:px-12 {{ ($showHome && count($spotlight) >= 3) ? 'pt-6' : 'pt-24 md:pt-28' }}">
        @unless($showHome && count($spotlight) >= 3)
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <a href="{{ AnimeSources::getUrl(panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 text-[11px] font-bold text-zinc-200 bg-black/50 border border-white/15 px-3 py-1 rounded-full hover:text-white transition">← Ganti Sumber</a>
                <span class="bg-blue-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider">SERVER 2</span>
            </div>
            <h1 class="text-3xl md:text-5xl font-black tracking-tight mb-5">Explore Anime V2</h1>
        @endunless

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 p-2 rounded-2xl bg-zinc-900/60 ring-1 ring-white/5 max-w-4xl">
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari judul anime..." aria-label="Cari anime"
                       class="w-full pl-10 pr-4 py-2.5 bg-transparent border-0 rounded-xl text-sm text-white placeholder:text-zinc-500 focus:outline-none focus:ring-1 focus:ring-blue-500/50">
            </div>
            <select wire:model.live="genre" aria-label="Genre" class="px-3 py-2.5 bg-zinc-950 border border-zinc-800 rounded-xl text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500/50 cursor-pointer">
                <option value="">Semua Genre</option>
                @foreach($genres as $g)
                    <option value="{{ $g['slug'] }}">{{ $g['name'] }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" aria-label="Status" class="px-3 py-2.5 bg-zinc-950 border border-zinc-800 rounded-xl text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-500/50 cursor-pointer">
                <option value="">Semua Status</option>
                <option value="ongoing">Ongoing</option>
                <option value="completed">Completed</option>
            </select>
            @if(filled($search) || filled($genre) || filled($status))
                <button wire:click="clearSearch" type="button" class="px-4 py-2.5 bg-red-600/15 border border-red-500/30 text-red-400 hover:bg-red-600/25 text-sm font-bold rounded-xl transition">✕ Reset</button>
            @endif
        </div>
    </section>

    @if($showHome)
        {{-- ================= RILIS TERBARU ================= --}}
        @if(count($fresh))
            <section class="w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-12">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-1 mb-5">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex w-2.5 h-2.5">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75 animate-ping"></span>
                            <span class="relative inline-flex w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        </span>
                        <h2 class="text-xl md:text-2xl font-black tracking-tight">Episode Terbaru</h2>
                    </div>
                    <span class="text-xs text-zinc-500">Anime ongoing yang baru rilis episode</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-x-4 gap-y-6">
                    @foreach($fresh as $a)
                        <a href="{{ $detailUrl($a) }}" class="group block">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 ring-1 ring-white/5 group-hover:ring-blue-500/60 transition">
                                <img src="{{ $a['thumbnail'] ?? '' }}" alt="{{ $a['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent"></div>
                                @if($n = $epNum($a['episode'] ?? ''))
                                    <span class="absolute top-2 left-2 bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded shadow">EP {{ $n }}</span>
                                @endif
                                @if(!empty($a['date']))
                                    <span class="absolute top-2 right-2 bg-black/70 backdrop-blur text-zinc-200 text-[10px] font-bold px-1.5 py-0.5 rounded border border-white/10">{{ $a['date'] }}</span>
                                @endif
                                @if(!empty($a['type']))
                                    <span class="absolute bottom-2 left-2 text-[10px] font-bold text-zinc-300">{{ $a['type'] }}</span>
                                @endif
                            </div>
                            <h3 class="mt-2.5 text-sm font-bold text-zinc-100 group-hover:text-blue-400 transition line-clamp-2 leading-snug">{{ $a['title'] }}</h3>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================= BARU TAMAT ================= --}}
        @if(count($doneRow))
            <section class="w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-14">
                <div class="flex items-end justify-between mb-5">
                    <div>
                        <h2 class="text-xl md:text-2xl font-black tracking-tight">Baru Tamat</h2>
                        <p class="text-xs text-zinc-500 mt-1">Sudah lengkap, siap maraton</p>
                    </div>
                    <button type="button" wire:click="$set('status', 'completed')" class="text-xs font-bold text-blue-400 hover:text-blue-300">Lihat Semua →</button>
                </div>
                <div class="flex gap-4 overflow-x-auto pb-3 snap-x snap-mandatory kz-noscroll [mask-image:linear-gradient(to_right,#000_92%,transparent)]">
                    @foreach($doneRow as $a)
                        @php $r = is_numeric($a['rating'] ?? null) ? (float) $a['rating'] : 0; @endphp
                        <a href="{{ $detailUrl($a) }}" class="group snap-start shrink-0 w-[150px] md:w-[180px]">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 ring-1 ring-white/5 group-hover:ring-blue-500/60 transition">
                                <img src="{{ $a['thumbnail'] ?? '' }}" alt="{{ $a['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent"></div>
                                <span class="absolute top-2 left-2 bg-emerald-600 text-white text-[10px] font-black px-2 py-0.5 rounded">TAMAT</span>
                                @if($r > 0)
                                    <span class="absolute top-2 right-2 bg-black/70 backdrop-blur text-yellow-400 text-[10px] font-black px-1.5 py-0.5 rounded border border-white/10">★ {{ number_format($r, 1) }}</span>
                                @endif
                                @if(!empty($a['episode']))
                                    <span class="absolute bottom-2 left-2 text-[10px] font-bold text-zinc-200">{{ $a['episode'] }}</span>
                                @endif
                            </div>
                            <h3 class="mt-2.5 text-sm font-bold text-zinc-100 group-hover:text-blue-400 transition line-clamp-2 leading-snug">{{ $a['title'] }}</h3>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    {{-- ================= KATALOG ================= --}}
    <main class="w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-14">
        <div class="flex items-center justify-between mb-6 border-b border-zinc-800/80 pb-4">
            <h2 class="text-xl md:text-2xl font-black tracking-tight">
                @if(filled($search)) Hasil: "{{ $search }}"
                @elseif(filled($genre)) Genre: {{ ucfirst($genre) }}
                @elseif(filled($status)) {{ $status === 'completed' ? 'Anime Tamat' : 'Anime Ongoing' }}
                @else Katalog Anime
                @endif
            </h2>
            <div class="flex items-center gap-2">
                @if(blank($search))
                    <span class="text-xs text-zinc-400 bg-zinc-900 px-2.5 py-1 rounded-full border border-zinc-800">Hal. <b class="text-blue-400">{{ $pageNumber }}</b></span>
                @endif
                <span class="hidden sm:inline text-xs text-zinc-400 bg-zinc-900 px-2.5 py-1 rounded-full border border-zinc-800"><b class="text-blue-400">{{ count($animes) }}</b> judul</span>
            </div>
        </div>

        <div wire:loading.flex wire:target="loadAnimes, nextPage, previousPage, search, genre, status, clearSearch" class="items-center justify-center py-20 gap-3 text-zinc-400">
            <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-sm font-semibold">Mengambil data anime...</span>
        </div>

        <div wire:loading.remove wire:target="loadAnimes, nextPage, previousPage, search, genre, status, clearSearch">
            @if(empty($animes))
                <div class="py-16 text-center text-zinc-500 bg-zinc-900/40 rounded-2xl border border-dashed border-zinc-800">
                    <p class="text-sm font-medium">Tidak ada anime yang ditemukan.</p>
                    <p class="text-xs text-zinc-600 mt-1">Coba kata kunci lain atau ubah filter.</p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-x-4 gap-y-6">
                    @foreach($animes as $a)
                        @php $done = strtolower($a['status'] ?? '') === 'completed'; @endphp
                        <a href="{{ $detailUrl($a) }}" class="group block">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 ring-1 ring-white/5 group-hover:ring-blue-500/60 transition">
                                @if(!empty($a['thumbnail']))
                                    <img src="{{ $a['thumbnail'] }}" alt="{{ $a['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-xs text-zinc-600">No Image</div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent"></div>
                                @if(!empty($a['status']))
                                    <span class="absolute top-2 left-2 text-[9px] font-black uppercase px-1.5 py-0.5 rounded {{ $done ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white' }}">{{ $done ? 'Tamat' : 'Ongoing' }}</span>
                                @endif
                                @if(!empty($a['episode']))
                                    <span class="absolute bottom-2 left-2 text-[10px] font-bold text-zinc-200">{{ $a['episode'] }}</span>
                                @endif
                            </div>
                            <h3 class="mt-2.5 text-sm font-bold text-zinc-100 group-hover:text-blue-400 transition line-clamp-2 leading-snug">{{ $a['title'] }}</h3>
                        </a>
                    @endforeach
                </div>

                @if(blank($search))
                    <div class="mt-12 flex items-center justify-center gap-3">
                        <button wire:click="previousPage" type="button" @if($pageNumber <= 1) disabled @endif
                                class="px-4 py-2 bg-zinc-900 border border-zinc-800 text-sm font-semibold rounded-full hover:bg-zinc-800 disabled:opacity-40 disabled:cursor-not-allowed transition">← Sebelumnya</button>
                        <span class="w-10 h-10 inline-flex items-center justify-center bg-blue-600 rounded-full text-sm font-black">{{ $pageNumber }}</span>
                        <button wire:click="nextPage" type="button"
                                class="px-4 py-2 bg-zinc-900 border border-zinc-800 text-sm font-semibold rounded-full hover:bg-zinc-800 transition">Selanjutnya →</button>
                    </div>
                @endif
            @endif
        </div>
    </main>
</div>

@once
    @push('styles')
        <style>
            .kz-noscroll { scrollbar-width: none; }
            .kz-noscroll::-webkit-scrollbar { display: none; }
        </style>
    @endpush
@endonce
