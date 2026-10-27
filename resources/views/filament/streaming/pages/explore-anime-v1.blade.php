@php
    use App\Filament\Streaming\Pages\AnimeDetail;
    use App\Filament\Streaming\Pages\WatchAnime;
    use App\Filament\Streaming\Pages\AnimeSources;

    $hero = $home['hero'] ?? [];
    $trending = $home['trending'] ?? [];
    $season = $home['season'] ?? [];
    $seasonTitle = $home['season_title'] ?? 'Musim Ini';
    $top = $home['top'] ?? [];
    $showHome = $this->showHome;
    $todayAnime = $weeklySchedule[$currentDayName] ?? [];

    $detailUrl = fn ($slug) => $slug ? AnimeDetail::getUrl(['slug' => $slug], panel: 'streaming') : '#';
    $watchUrl = fn ($slug, $ep) => ($slug && $ep) ? WatchAnime::getUrl(['anime_slug' => $slug, 'episode_slug' => $ep], panel: 'streaming') : '#';
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ============ HEADER: judul + cari + filter cepat ============ --}}
    <section class="relative w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-20 md:pt-28">
        <div class="absolute inset-x-0 -top-10 h-64 bg-gradient-to-b from-red-900/20 via-transparent to-transparent pointer-events-none"></div>

        <div class="relative flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-red-500">Anime Sub Indo</p>
                <h1 class="mt-1 text-3xl md:text-5xl font-black tracking-tight leading-none">Anime</h1>
                <p class="mt-2 text-sm text-zinc-400">
                    @if(count($updates))
                        {{ count($updates) }} anime baru update
                        @if(count($todayAnime)) · {{ count($todayAnime) }} tayang hari {{ $currentDayName }} @endif
                    @else
                        Streaming HD tanpa iklan, update tiap hari
                    @endif
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full lg:max-w-2xl">
                <div class="relative flex-1">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="search" wire:model.live.debounce.500ms="search" placeholder="Cari judul anime..." aria-label="Cari anime"
                           class="w-full h-12 pl-11 pr-4 bg-white/[0.06] border border-white/10 rounded-xl text-sm text-white placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-red-500/50 transition">
                </div>
                <div class="flex gap-2.5">
                    <select wire:model.live="genre" aria-label="Genre"
                            class="flex-1 sm:flex-none h-12 px-3 bg-white/[0.06] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-red-500/50 cursor-pointer">
                        <option value="" class="bg-zinc-900">Semua Genre</option>
                        @foreach($genres as $g)
                            <option value="{{ $g['slug'] }}" class="bg-zinc-900">{{ $g['name'] }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="status" aria-label="Status"
                            class="flex-1 sm:flex-none h-12 px-3 bg-white/[0.06] border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:ring-2 focus:ring-red-500/50 cursor-pointer">
                        <option value="" class="bg-zinc-900">Semua Status</option>
                        <option value="ongoing" class="bg-zinc-900">Ongoing</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Navigasi cepat antar seksi --}}
        <div class="relative mt-5 flex items-center gap-2 overflow-x-auto kz-noscroll pb-1">
            @if(filled($search) || filled($genre) || filled($status))
                <button type="button" wire:click="clearSearch"
                        class="shrink-0 inline-flex items-center gap-1.5 h-9 px-4 rounded-full bg-red-600 text-white text-xs font-bold">
                    ✕ Reset filter
                </button>
            @else
                @foreach([
                    ['#baru-update', 'Baru Update'],
                    ['#jadwal-tayang', 'Jadwal Tayang'],
                    ['#trending', 'Trending'],
                    ['#musim-ini', 'Musim Ini'],
                    ['#katalog', 'Semua Anime'],
                ] as $i => [$href, $label])
                    <a href="{{ $href }}"
                       class="shrink-0 inline-flex items-center h-9 px-4 rounded-full text-xs font-bold transition {{ $i === 0 ? 'bg-white text-black' : 'bg-white/[0.06] text-zinc-300 ring-1 ring-white/10 active:bg-white/10' }}">
                        {{ $label }}
                    </a>
                @endforeach
                @if(count($genres))
                    <span class="shrink-0 w-px h-5 bg-white/10 mx-1" aria-hidden="true"></span>
                    @foreach(array_slice($genres, 0, 10) as $g)
                        <button type="button" wire:click="$set('genre', '{{ $g['slug'] }}')"
                                class="shrink-0 inline-flex items-center h-9 px-3.5 rounded-full bg-transparent text-zinc-400 ring-1 ring-white/10 text-xs font-semibold active:bg-white/10">
                            {{ $g['name'] }}
                        </button>
                    @endforeach
                @endif
            @endif
        </div>
    </section>

    @if($showHome)
        {{-- ============ 1. BARU UPDATE (prioritas utama) ============ --}}
        @if(count($updates))
            <section id="baru-update" class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-8 scroll-mt-24"
                     x-data="{ all: false }">
                <div class="flex items-end justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex w-2.5 h-2.5" aria-hidden="true">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75 animate-ping"></span>
                            <span class="relative inline-flex w-2.5 h-2.5 rounded-full bg-red-500"></span>
                        </span>
                        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Baru Update</h2>
                    </div>
                    <span class="hidden sm:inline text-xs text-zinc-400">Klik poster untuk langsung nonton</span>
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-x-3 gap-y-5">
                    @foreach($updates as $i => $u)
                        <div class="min-w-0" @if($i >= 12) x-show="all" x-cloak @endif>
                            <a href="{{ $watchUrl($u['slug'], $u['episode']) }}"
                               aria-label="Tonton {{ $u['title'] }} episode {{ $u['episode'] }}"
                               class="block relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 ring-1 ring-white/10 active:scale-[0.97] transition">
                                @if(!empty($u['poster']))
                                    <img src="{{ $u['poster'] }}" alt="" loading="{{ $i < 8 ? 'eager' : 'lazy' }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center p-2 text-center text-[11px] text-zinc-500">{{ $u['title'] }}</div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/10 to-transparent"></div>

                                @if($u['is_new'])
                                    <span class="absolute top-1.5 left-1.5 bg-red-600 text-white text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider">Baru</span>
                                @endif
                                @if(!empty($u['rating']) && (float) $u['rating'] > 0)
                                    <span class="absolute top-1.5 right-1.5 bg-black/70 text-yellow-400 text-[10px] font-bold px-1.5 py-0.5 rounded">★ {{ $u['rating'] }}</span>
                                @endif

                                <div class="absolute bottom-0 inset-x-0 p-2 flex items-end justify-between gap-1">
                                    <span class="bg-white text-black text-[11px] font-black px-2 py-0.5 rounded">EP {{ $u['episode'] }}</span>
                                    <span class="w-7 h-7 rounded-full bg-red-600 flex items-center justify-center shadow-lg shadow-black/50" aria-hidden="true">
                                        <svg class="w-3.5 h-3.5 fill-white ml-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </span>
                                </div>
                            </a>
                            <a href="{{ $detailUrl($u['slug']) }}" class="block mt-2">
                                <h3 class="text-xs md:text-[13px] font-semibold text-zinc-100 leading-snug line-clamp-2">{{ $u['title'] }}</h3>
                            </a>
                            <p class="mt-0.5 text-[11px] text-zinc-500 truncate">{{ $u['ago'] }}</p>
                        </div>
                    @endforeach
                </div>

                @if(count($updates) > 12)
                    <div class="mt-5 flex justify-center">
                        <button type="button" @click="all = !all" :aria-expanded="all.toString()"
                                class="inline-flex items-center gap-2 h-10 px-5 rounded-full bg-white/[0.06] ring-1 ring-white/10 text-sm font-bold text-zinc-200 active:bg-white/10">
                            <span x-text="all ? 'Tampilkan lebih sedikit' : 'Lihat semua ({{ count($updates) }})'">Lihat semua ({{ count($updates) }})</span>
                            <svg class="w-4 h-4 transition-transform" :class="all && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                @endif
            </section>
        @endif

        {{-- ============ 2. JADWAL TAYANG ============ --}}
        @if(!empty($weeklySchedule))
            <section id="jadwal-tayang" class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-12 scroll-mt-24">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Jadwal Tayang</h2>
                        <p class="text-xs text-zinc-500 mt-1">Episode baru tiap minggu, pilih harinya</p>
                    </div>
                    <div class="flex items-center gap-2 overflow-x-auto kz-noscroll pb-1 -mx-4 px-4 md:mx-0 md:px-0">
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $d)
                            @php $isSelected = $d === $selectedScheduleDay; @endphp
                            <button type="button" wire:click="selectScheduleDay('{{ $d }}')"
                                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                    class="shrink-0 h-9 px-3.5 rounded-full text-xs font-bold transition flex items-center gap-1.5
                                    {{ $isSelected ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' : 'bg-white/[0.06] text-zinc-300 ring-1 ring-white/10' }}">
                                {{ $d }}
                                <span class="text-[10px] font-mono {{ $isSelected ? 'text-white/80' : 'text-zinc-500' }}">{{ count($weeklySchedule[$d] ?? []) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                @php $activeDayAnime = $weeklySchedule[$selectedScheduleDay] ?? []; @endphp
                @if(!empty($activeDayAnime))
                    <div class="flex gap-3 overflow-x-auto kz-noscroll pb-2 snap-x snap-mandatory -mx-4 px-4 md:mx-0 md:px-0">
                        @foreach($activeDayAnime as $item)
                            <a href="{{ $item['detail_url'] }}" class="snap-start shrink-0 w-[112px] sm:w-[128px] md:w-[144px]">
                                <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 ring-1 ring-white/10">
                                    <img src="{{ $item['poster'] }}" alt="" loading="lazy" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-transparent"></div>
                                    @if(!empty($item['episode']))
                                        <span class="absolute bottom-1.5 left-1.5 bg-black/75 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">{{ $item['episode'] }}</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-xs font-semibold text-zinc-200 line-clamp-2 leading-snug">{{ $item['title'] }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="py-10 text-center text-sm text-zinc-500 rounded-xl border border-dashed border-white/10">
                        Belum ada jadwal untuk hari {{ $selectedScheduleDay }}.
                    </div>
                @endif
            </section>
        @endif

        {{-- ============ 3. SOROTAN (featured, ringkas) ============ --}}
        @if(count($hero))
            <section class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-12">
                <h2 class="text-xl md:text-2xl font-extrabold tracking-tight mb-4">Sorotan</h2>
                <div class="flex gap-4 overflow-x-auto kz-noscroll pb-2 snap-x snap-mandatory -mx-4 px-4 md:mx-0 md:px-0">
                    @foreach($hero as $h)
                        <a href="{{ $detailUrl($h['slug']) }}"
                           class="snap-start shrink-0 relative w-[300px] sm:w-[420px] md:w-[520px] h-[180px] md:h-[220px] rounded-2xl overflow-hidden ring-1 ring-white/10 bg-zinc-900">
                            <div class="absolute inset-0 bg-cover bg-center scale-110 blur-xl opacity-60" style="background-image:url('{{ $h['poster'] }}')"></div>
                            <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/60 to-black/20"></div>
                            <div class="relative h-full flex gap-4 p-4">
                                <img src="{{ $h['poster'] }}" alt="" loading="lazy" class="h-full aspect-[2/3] object-cover rounded-xl shadow-2xl shadow-black/70 ring-1 ring-white/10">
                                <div class="min-w-0 flex flex-col justify-end">
                                    <div class="flex flex-wrap gap-1.5 mb-2">
                                        @foreach(array_slice($h['meta'] ?? [], 0, 3) as $m)
                                            <span class="bg-white/10 text-zinc-200 text-[10px] font-bold px-2 py-0.5 rounded">{{ $m }}</span>
                                        @endforeach
                                    </div>
                                    <h3 class="text-base md:text-xl font-extrabold leading-tight line-clamp-2">{{ $h['title'] }}</h3>
                                    @if(!empty($h['synopsis']))
                                        <p class="hidden sm:block mt-1.5 text-xs text-zinc-400 line-clamp-2">{{ $h['synopsis'] }}</p>
                                    @endif
                                    <span class="mt-3 inline-flex items-center gap-1.5 self-start h-8 px-3.5 rounded-lg bg-red-600 text-white text-xs font-bold">
                                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                        Tonton
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ============ 4. TRENDING (FULL WIDTH) ============ --}}
        @if(count($trending))
            <section id="trending" class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-12 scroll-mt-24">
                <div class="mb-4 flex items-center gap-2.5">
                    <span class="rounded-md bg-red-600 px-2 py-0.5 text-xs font-black tracking-wider text-white">TOP 10</span>
                    <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Trending Minggu Ini</h2>
                </div>
                <div class="flex gap-2 md:gap-3 overflow-x-auto overflow-y-hidden kz-noscroll pb-2 -mx-4 px-4 md:mx-0 md:px-0 snap-x snap-mandatory">
                    @foreach($trending as $t)
                        @php $rankNum = ltrim($t['rank'], '#'); @endphp
                        <a href="{{ $detailUrl($t['slug']) }}" aria-label="#{{ $rankNum }} {{ $t['title'] }}"
                           class="group relative block w-[140px] sm:w-[160px] md:w-[180px] shrink-0 snap-start">
                            <div class="relative flex items-end">
                                <span class="pointer-events-none select-none absolute left-0 bottom-[-6px] md:bottom-[-10px] text-[84px] sm:text-[96px] md:text-[112px] font-black leading-none tracking-tighter text-[#050505]"
                                      style="-webkit-text-stroke: 2.5px rgba(255,255,255,0.55);">{{ $rankNum }}</span>
                                <div class="relative z-10 ml-auto w-[90px] sm:w-[104px] md:w-[116px] aspect-[2/3] overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/10 shadow-xl shadow-black/70 group-active:scale-[0.98] transition">
                                    <img src="{{ $t['poster'] }}" alt="" loading="lazy" class="w-full h-full object-cover transition duration-300 group-hover:scale-105">
                                </div>
                            </div>
                            <div class="ml-auto mt-2 w-[90px] sm:w-[104px] md:w-[116px]">
                                <h3 class="truncate text-xs md:text-[13px] font-semibold text-zinc-200 group-hover:text-white" title="{{ $t['title'] }}">{{ $t['title'] }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ============ 5. MUSIM INI ============ --}}
        @if(count($season))
            <section id="musim-ini" class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-12 scroll-mt-24">
                <div class="flex items-center gap-2.5 mb-4">
                    <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Tayang Musim Ini</h2>
                    @if($seasonTitle)
                        <span class="text-[11px] font-bold text-red-400 bg-red-950/60 ring-1 ring-red-500/30 px-2.5 py-1 rounded-full">{{ $seasonTitle }}</span>
                    @endif
                </div>
                <div class="flex gap-3 overflow-x-auto overflow-y-hidden kz-noscroll pb-2 snap-x snap-mandatory -mx-4 px-4 md:mx-0 md:px-0">
                    @foreach($season as $s)
                        <a href="{{ $detailUrl($s['slug']) }}" class="snap-start shrink-0 w-[112px] sm:w-[128px] md:w-[144px]">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 ring-1 ring-white/10">
                                <img src="{{ $s['poster'] }}" alt="" loading="lazy" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                                @if($s['episodes'])<span class="absolute top-1.5 right-1.5 bg-black/75 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">{{ $s['episodes'] }}</span>@endif
                                @if($s['type'])<span class="absolute bottom-1.5 left-1.5 bg-red-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">{{ $s['type'] }}</span>@endif
                            </div>
                            <p class="mt-2 text-xs font-semibold text-zinc-200 line-clamp-2 leading-snug">{{ $s['title'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ============ 6. TOP RATING ============ --}}
        @if(count($top))
            <section class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-12">
                <div class="flex items-center gap-2.5 mb-4">
                    <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Top Rating</h2>
                    <span class="text-[11px] font-bold text-amber-400 bg-amber-950/60 ring-1 ring-amber-500/30 px-2.5 py-1 rounded-full">★ Paling Populer</span>
                </div>
                <div class="flex gap-3 overflow-x-auto overflow-y-hidden kz-noscroll pb-2 snap-x snap-mandatory -mx-4 px-4 md:mx-0 md:px-0">
                    @foreach($top as $t)
                        <a href="{{ $detailUrl($t['slug']) }}" class="snap-start shrink-0 w-[112px] sm:w-[128px] md:w-[144px]">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 ring-1 ring-white/10 group-active:scale-[0.98] transition">
                                <img src="{{ $t['poster'] }}" alt="" loading="lazy" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                                <span class="absolute top-1.5 left-1.5 bg-black/80 text-white text-[10px] font-black px-1.5 py-0.5 rounded {{ (int)$t['rank'] <= 3 ? 'text-red-400 ring-1 ring-red-500/40' : '' }}">#{{ $t['rank'] }}</span>
                                @if($t['rating'])<span class="absolute top-1.5 right-1.5 bg-black/80 text-yellow-400 text-[10px] font-bold px-1.5 py-0.5 rounded">★ {{ $t['rating'] }}</span>@endif
                            </div>
                            <p class="mt-2 text-xs font-semibold text-zinc-200 line-clamp-2 leading-snug">{{ $t['title'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    {{-- ============ 6. KATALOG / HASIL CARI ============ --}}
    <main id="katalog" class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-12 scroll-mt-24">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">
                @if(filled($search))
                    Hasil untuk "{{ $search }}"
                @elseif(filled($genre))
                    Genre: {{ collect($genres)->firstWhere('slug', $genre)['name'] ?? ucfirst($genre) }}
                @elseif(filled($status))
                    Status: {{ ucfirst($status) }}
                @else
                    Semua Anime
                @endif
            </h2>
            <span class="text-xs text-zinc-500">
                {{ count($animes) }} judul @if(blank($search)) · hal. {{ $pageNumber }} @endif
            </span>
        </div>

        <div wire:loading.flex wire:target="loadAnimes, nextPage, previousPage, search, genre, status, clearSearch"
             class="items-center justify-center py-16">
            <div class="flex items-center gap-3 text-zinc-400 bg-zinc-900/60 px-5 py-3 rounded-full ring-1 ring-white/10">
                <svg class="animate-spin h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-sm font-semibold">Memuat anime...</span>
            </div>
        </div>

        <div wire:loading.remove wire:target="loadAnimes, nextPage, previousPage, search, genre, status, clearSearch">
            @if(empty($animes))
                <div class="flex flex-col items-center justify-center py-16 text-zinc-500 rounded-xl border border-dashed border-white/10">
                    <p class="text-sm font-medium">Anime tidak ditemukan.</p>
                    <p class="text-xs text-zinc-600 mt-1">Coba kata kunci lain atau ubah filter.</p>
                </div>
            @else
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-x-3 gap-y-5">
                    @foreach($animes as $anime)
                        <a href="{{ $detailUrl($anime['slug'] ?? '') }}" class="block min-w-0">
                            <div class="relative aspect-[2/3] overflow-hidden bg-zinc-900 rounded-xl ring-1 ring-white/10">
                                @if(!empty($anime['thumbnail']))
                                    <img src="{{ $anime['thumbnail'] }}" alt="" class="w-full h-full object-cover" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center p-2 text-center text-[11px] text-zinc-500">{{ $anime['title'] }}</div>
                                @endif
                            </div>
                            <h3 class="mt-2 text-xs md:text-[13px] font-semibold text-zinc-200 line-clamp-2 leading-snug">{{ $anime['title'] }}</h3>
                        </a>
                    @endforeach
                </div>

                @if(blank($search))
                    <nav class="mt-10 flex items-center justify-center gap-3" aria-label="Halaman katalog">
                        <button wire:click="previousPage" @if($pageNumber <= 1) disabled @endif
                                class="h-10 px-4 bg-white/[0.06] ring-1 ring-white/10 text-sm font-semibold rounded-xl disabled:opacity-40 disabled:cursor-not-allowed">
                            ← Sebelumnya
                        </button>
                        <span class="h-10 min-w-10 px-3 inline-flex items-center justify-center bg-red-600 rounded-xl text-sm font-bold" aria-current="page">{{ $pageNumber }}</span>
                        <button wire:click="nextPage"
                                class="h-10 px-4 bg-white/[0.06] ring-1 ring-white/10 text-sm font-semibold rounded-xl">
                            Selanjutnya →
                        </button>
                    </nav>
                @endif
            @endif
        </div>

        <p class="mt-10 text-center text-[11px] text-zinc-600">
            Cari server lain? <a href="{{ AnimeSources::getUrl(panel: 'streaming') }}" class="text-zinc-400 underline underline-offset-2">Pilih sumber anime</a>
        </p>
    </main>
</div>
