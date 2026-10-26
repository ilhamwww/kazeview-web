@php
    use App\Filament\Streaming\Pages\AnimeDetail;
    use App\Filament\Streaming\Pages\WatchAnime;
    use App\Filament\Streaming\Pages\AnimeSources;

    $hero = $home['hero'] ?? [];
    $latest = $home['latest'] ?? [];
    $trending = $home['trending'] ?? [];
    $season = $home['season'] ?? [];
    $seasonTitle = $home['season_title'] ?? 'Musim Ini';
    $top = $home['top'] ?? [];
    $showHome = $this->showHome;

    $detailUrl = fn ($slug) => $slug ? AnimeDetail::getUrl(['slug' => $slug], panel: 'streaming') : '#';
    $watchUrl = fn ($slug, $ep) => ($slug && $ep) ? WatchAnime::getUrl(['anime_slug' => $slug, 'episode_slug' => $ep], panel: 'streaming') : '#';
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ============ HERO SLIDER ============ --}}
    @if($showHome && count($hero))
        <section wire:ignore
                 x-data="{ active: 0, total: {{ count($hero) }}, timer: null,
                    start() { this.timer = setInterval(() => this.next(), 7000) },
                    stop() { clearInterval(this.timer) },
                    next() { this.active = (this.active + 1) % this.total },
                    prev() { this.active = (this.active - 1 + this.total) % this.total },
                    go(i) { this.active = i; this.stop(); this.start() },
                    sx: 0 }"
                 x-init="start()"
                 @touchstart.passive="sx = $event.touches[0].clientX"
                 @touchend.passive="let dx = $event.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) { dx < 0 ? next() : prev(); stop(); start() }"
                 class="relative w-full h-[640px] md:h-[78vh] md:min-h-[600px] overflow-hidden bg-black">
            @foreach($hero as $i => $h)
                <div x-show="active === {{ $i }}"
                     x-transition:enter="transition duration-700 ease-out"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition duration-500 ease-in"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @if($i > 0) style="display:none" @endif
                     class="absolute inset-0">
                    <div class="absolute inset-0 bg-cover bg-center scale-110 blur-2xl opacity-50" style="background-image:url('{{ $h['poster'] }}')"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/80 to-[#050505]/30"></div>
                    <div class="absolute bottom-0 inset-x-0 h-72 bg-gradient-to-t from-[#050505] via-[#050505]/80 to-transparent"></div>
                    <div class="absolute top-0 inset-x-0 h-40 bg-gradient-to-b from-[#050505]/90 to-transparent"></div>

                    <div class="relative h-full w-full max-w-[1800px] mx-auto px-6 md:px-20 pt-24 md:pt-28 pb-16 flex items-end md:items-center">
                        <div class="flex flex-col md:flex-row items-center md:items-center gap-6 md:gap-12 w-full">
                            <a href="{{ $detailUrl($h['slug']) }}" class="shrink-0 w-32 sm:w-40 md:w-56 lg:w-64 aspect-[3/4] rounded-2xl overflow-hidden shadow-2xl shadow-black/70 ring-1 ring-white/10">
                                <img src="{{ $h['poster'] }}" alt="{{ $h['title'] }}" class="w-full h-full object-cover" @if($i > 0) loading="lazy" @endif>
                            </a>
                            <div class="flex-1 min-w-0 text-center md:text-left">
                                <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 mb-3">
                                    <span class="bg-red-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider">Sorotan</span>
                                    @foreach($h['meta'] as $m)
                                        <span class="bg-white/10 text-zinc-200 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-white/10">{{ $m }}</span>
                                    @endforeach
                                </div>
                                <h1 class="text-2xl sm:text-3xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight line-clamp-2">{{ $h['title'] }}</h1>
                                @if(!empty($h['alt_title']))
                                    <p class="text-sm text-zinc-400 mt-1 line-clamp-1">{{ $h['alt_title'] }}</p>
                                @endif
                                @if(!empty($h['genres']))
                                    <div class="flex flex-wrap justify-center md:justify-start gap-2 mt-3">
                                        @foreach($h['genres'] as $g)
                                            <span class="px-2.5 py-1 text-xs font-bold text-red-300 bg-red-600/15 border border-red-500/20 rounded-full">{{ $g }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if(!empty($h['synopsis']))
                                    <p class="hidden sm:block max-w-2xl mt-4 text-sm md:text-base text-zinc-300 leading-relaxed line-clamp-3">{{ $h['synopsis'] }}</p>
                                @endif
                                <div class="flex flex-wrap justify-center md:justify-start gap-3 mt-6">
                                    <a href="{{ $detailUrl($h['slug']) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-red-600/30 transition">
                                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                        Tonton Sekarang
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Controls --}}
            <div class="absolute bottom-6 inset-x-0 z-20 flex items-center justify-center gap-2">
                @foreach($hero as $i => $h)
                    <button type="button" @click="go({{ $i }})" aria-label="Slide {{ $i + 1 }}"
                            :class="active === {{ $i }} ? 'w-8 bg-red-500' : 'w-2.5 bg-white/30'"
                            class="h-2.5 rounded-full transition-all duration-300"></button>
                @endforeach
            </div>
            <button type="button" @click="prev(); stop(); start()" aria-label="Sebelumnya"
                    class="hidden md:flex absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 items-center justify-center rounded-full bg-black/50 border border-white/10 text-white hover:bg-red-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" @click="next(); stop(); start()" aria-label="Berikutnya"
                    class="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 items-center justify-center rounded-full bg-black/50 border border-white/10 text-white hover:bg-red-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </section>
    @endif

    {{-- ============ TOOLBAR: sumber + cari + filter ============ --}}
    <section class="relative w-full max-w-[1800px] mx-auto px-6 {{ ($showHome && count($hero)) ? 'pt-6' : 'pt-24 md:pt-28' }} pb-2 z-10">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <a href="{{ AnimeSources::getUrl(panel: 'streaming') }}"
               class="inline-flex items-center gap-1.5 text-[11px] font-bold text-zinc-200 bg-black/60 border border-white/15 px-3 py-1 rounded-full hover:text-white hover:border-white/30 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Ganti Sumber
            </a>
            <span class="bg-red-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider">SERVER 1</span>
            <span class="bg-white/10 text-zinc-200 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-white/10">DIRECT MP4 HD</span>
            <span class="bg-red-600/20 text-red-400 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-red-500/30">ONLINE</span>
            @if($showHome && !empty($weeklySchedule))
                <a href="#jadwal-tayang"
                   class="inline-flex items-center gap-1.5 text-[11px] font-bold text-purple-300 bg-purple-950/70 border border-purple-500/30 px-3 py-1 rounded-full hover:bg-purple-900/60 transition">
                    <svg class="w-3.5 h-3.5 text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" /></svg>
                    Jadwal Tayang
                </a>
            @endif
        </div>

        @unless($showHome && count($hero))
            <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight text-white uppercase mb-2">Explore Anime V1</h1>
        @endunless

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 max-w-3xl">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari anime..." aria-label="Cari anime"
                       class="w-full pl-10 pr-4 py-2.5 bg-white/5 border border-white/10 rounded-lg text-sm text-white placeholder:text-zinc-500 focus:outline-none focus:ring-1 focus:ring-red-500/50 focus:border-red-500/50 transition">
            </div>
            <select wire:model.live="genre" aria-label="Genre"
                    class="px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-sm text-white focus:outline-none focus:ring-1 focus:ring-red-500/50 transition cursor-pointer">
                <option value="" class="bg-zinc-900">Semua Genre</option>
                @foreach($genres as $g)
                    <option value="{{ $g['slug'] }}" class="bg-zinc-900">{{ $g['name'] }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" aria-label="Status"
                    class="px-3 py-2.5 bg-white/5 border border-white/10 rounded-lg text-sm text-white focus:outline-none focus:ring-1 focus:ring-red-500/50 transition cursor-pointer">
                <option value="" class="bg-zinc-900">Semua Status</option>
                <option value="ongoing" class="bg-zinc-900">Ongoing</option>
            </select>
            @if(filled($search) || filled($genre) || filled($status))
                <button wire:click="clearSearch"
                        class="px-4 py-2.5 bg-red-600/20 border border-red-500/30 text-red-400 hover:bg-red-600/30 text-sm font-bold rounded-lg transition whitespace-nowrap">
                    ✕ Reset
                </button>
            @endif
        </div>
    </section>

    @if($showHome)
        {{-- ============ EPISODE TERBARU ============ --}}
        @if(count($latest))
            <section class="w-full max-w-[1800px] mx-auto px-6 pt-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-1 mb-5">
                    <div class="flex items-center gap-2.5">
                        <span class="relative flex w-2.5 h-2.5">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75 animate-ping"></span>
                            <span class="relative inline-flex w-2.5 h-2.5 rounded-full bg-red-500"></span>
                        </span>
                        <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Episode Terbaru</h2>
                    </div>
                    <span class="text-xs text-zinc-500">Update otomatis tiap 10 menit</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($latest as $ep)
                        <a href="{{ $watchUrl($ep['slug'], $ep['episode']) }}"
                           class="group block bg-zinc-900/60 rounded-xl overflow-hidden border border-zinc-800 hover:border-red-500/40 transition active:scale-[0.98]">
                            <div class="relative aspect-video bg-zinc-950 overflow-hidden">
                                <img src="{{ $ep['thumbnail'] }}" alt="{{ $ep['title'] }}" loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                     onerror="this.onerror=null;this.src='{{ $ep['poster'] }}'">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                                <span class="absolute top-2 left-2 bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-wider">Baru</span>
                                <span class="absolute bottom-2 left-2 bg-black/80 text-white text-[11px] font-bold px-2 py-0.5 rounded">EP {{ $ep['episode'] }}</span>
                                <span class="absolute inset-0 m-auto w-12 h-12 rounded-full bg-red-600/90 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                    <svg class="w-6 h-6 fill-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                </span>
                            </div>
                            <div class="p-3 flex items-start gap-3">
                                @if(!empty($ep['poster']))
                                    <img src="{{ $ep['poster'] }}" alt="" class="hidden sm:block shrink-0 w-9 h-9 rounded-full object-cover bg-zinc-800" loading="lazy">
                                @endif
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-white group-hover:text-red-400 transition line-clamp-1">{{ $ep['series'] ?: $ep['title'] }}</h3>
                                    <div class="flex flex-wrap items-center gap-x-2 mt-1 text-[11px] text-zinc-500">
                                        <span class="text-zinc-300 font-semibold">Episode {{ $ep['episode'] }}</span>
                                        @if($ep['type'])<span>·</span><span>{{ $ep['type'] }}</span>@endif
                                        @if($ep['rating'] && (float) $ep['rating'] > 0)<span>·</span><span class="text-yellow-400">★ {{ $ep['rating'] }}</span>@endif
                                        @if($ep['ago'])<span>·</span><span>{{ $ep['ago'] }}</span>@endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ============ JADWAL TAYANG ANIME (MINGGUAN) ============ --}}
        @if(!empty($weeklySchedule))
            <section id="jadwal-tayang" class="w-full max-w-[1800px] mx-auto px-6 pt-12">
                <div class="p-6 md:p-8 rounded-2xl bg-zinc-900/60 border border-zinc-800/80 shadow-2xl backdrop-blur relative overflow-hidden">
                    <!-- Glowing ambient background -->
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-red-600/10 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Section Header & Day Selector Pills -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 border-b border-zinc-800 pb-5 mb-6">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="p-2 rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" /></svg>
                                </span>
                                <h2 class="text-xl md:text-2xl font-black tracking-tight text-white">Jadwal Tayang Anime</h2>
                            </div>
                            <p class="text-xs text-zinc-400 mt-1">Jadwal rilis episode baru mingguan • Klik anime untuk langsung tonton & detail</p>
                        </div>

                        <!-- Day Selector Pills (Senin s/d Minggu & Random) -->
                        <div class="flex items-center gap-2 overflow-x-auto kz-noscroll pb-1">
                            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu', 'Random'] as $d)
                                @php
                                    $isSelected = $d === $selectedScheduleDay;
                                    $countDay = count($weeklySchedule[$d] ?? []);
                                @endphp
                                <button type="button" wire:click="selectScheduleDay('{{ $d }}')"
                                        class="shrink-0 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5
                                        {{ $isSelected
                                            ? 'bg-red-600 text-white shadow-lg shadow-red-600/30 ring-2 ring-red-500/40'
                                            : 'bg-zinc-800/80 text-zinc-300 hover:text-white hover:bg-zinc-700/80 border border-zinc-700/60' }}">
                                    <span>{{ $d }}</span>
                                    <span class="text-[10px] opacity-75 font-mono">({{ $countDay }})</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Anime Grid for Selected Day -->
                    @php
                        $activeDayAnime = $weeklySchedule[$selectedScheduleDay] ?? [];
                    @endphp

                    @if(!empty($activeDayAnime))
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3.5">
                            @foreach($activeDayAnime as $item)
                                <a href="{{ $item['detail_url'] }}"
                                   class="group flex flex-col rounded-xl overflow-hidden bg-zinc-950/70 border border-zinc-800/90 hover:border-red-500/50 hover:shadow-xl hover:shadow-red-950/20 transition active:scale-[0.98]">
                                    <!-- Poster Container -->
                                    <div class="relative aspect-[2/3] w-full overflow-hidden bg-zinc-900">
                                        <img src="{{ $item['poster'] }}" alt="{{ $item['title'] }}" loading="lazy"
                                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-black/30"></div>

                                        <!-- Bottom Badge: Latest Episode -->
                                        <div class="absolute bottom-2 left-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-purple-600/90 text-white shadow-md shadow-purple-900/50">
                                                {{ $item['episode'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Title Info -->
                                    <div class="p-2.5 flex-1 flex flex-col justify-between">
                                        <h3 class="text-xs font-bold text-zinc-200 group-hover:text-red-400 transition line-clamp-2 leading-tight">
                                            {{ $item['title'] }}
                                        </h3>
                                        <div class="mt-2 flex items-center justify-between text-[10px] text-zinc-500">
                                            <span class="text-red-400 font-semibold flex items-center gap-1">
                                                <span>Lihat Detail</span>
                                                <svg class="w-3 h-3 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="py-12 text-center text-zinc-500">
                            <p class="text-sm font-medium">Tidak ada anime yang dijadwalkan tayang pada hari {{ $selectedScheduleDay }}.</p>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- ============ BARU DIUPDATE + TOP ANIME ============ --}}
        @if(count($recent) || count($top))
            <section class="w-full max-w-[1800px] mx-auto px-6 pt-12 grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                @if(count($recent))
                    <div class="lg:col-span-2 bg-zinc-900/40 border border-zinc-800 rounded-2xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg md:text-xl font-extrabold tracking-tight">Baru Saja Diupdate</h2>
                            <span class="text-[11px] font-bold text-red-400 bg-red-950/60 border border-red-500/30 px-2.5 py-1 rounded-full">{{ count($recent) }} judul</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
                            @foreach($recent as $r)
                                <a href="{{ $watchUrl($r['slug'], $r['episode']) }}"
                                   class="group flex items-center justify-between gap-3 py-2.5 border-b border-zinc-800/70 hover:bg-white/[0.02] transition">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-zinc-200 group-hover:text-red-400 transition line-clamp-1">{{ $r['series'] }}</p>
                                        <p class="text-[11px] text-zinc-500">{{ $r['ago'] }}</p>
                                    </div>
                                    <span class="shrink-0 text-[11px] font-black text-white bg-red-600/90 group-hover:bg-red-500 px-2 py-1 rounded">EP {{ $r['episode'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(count($top))
                    <div class="bg-zinc-900/40 border border-zinc-800 rounded-2xl p-5">
                        <h2 class="text-lg md:text-xl font-extrabold tracking-tight mb-4">Top Anime</h2>
                        <div class="space-y-1.5">
                            @foreach($top as $t)
                                <a href="{{ $detailUrl($t['slug']) }}" class="group flex items-center gap-3 p-1.5 rounded-lg hover:bg-white/[0.04] transition">
                                    <span class="w-6 text-center text-sm font-black {{ (int) $t['rank'] <= 3 ? 'text-yellow-400' : 'text-zinc-500' }}">{{ $t['rank'] }}</span>
                                    <img src="{{ $t['poster'] }}" alt="" class="w-10 h-12 rounded-md object-cover bg-zinc-800 shrink-0" loading="lazy">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-zinc-200 group-hover:text-red-400 transition line-clamp-1">{{ $t['title'] }}</p>
                                        @if($t['rating'] && (float) $t['rating'] > 0)<p class="text-[11px] text-yellow-400">★ {{ $t['rating'] }}</p>@endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif

        {{-- ============ SEDANG TREN ============ --}}
        @if(count($trending))
            <section class="w-full max-w-[1800px] mx-auto px-6 pt-12">
                <h2 class="text-xl md:text-2xl font-extrabold tracking-tight mb-5">Sedang Tren</h2>
                <div class="flex gap-4 overflow-x-auto pb-3 snap-x snap-mandatory scroll-px-6">
                    @foreach($trending as $t)
                        <a href="{{ $detailUrl($t['slug']) }}" class="group snap-start shrink-0 w-[140px] sm:w-[160px] md:w-[180px]">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 border border-zinc-800 group-hover:border-red-500/50 transition">
                                <img src="{{ $t['poster'] }}" alt="{{ $t['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                                <span class="absolute bottom-1 left-2 text-4xl md:text-5xl font-black text-white/90 drop-shadow-lg leading-none">{{ ltrim($t['rank'], '#') }}</span>
                            </div>
                            <p class="mt-2 text-xs md:text-sm font-semibold text-zinc-200 group-hover:text-red-400 transition line-clamp-2">{{ $t['title'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ============ MUSIM INI ============ --}}
        @if(count($season))
            <section class="w-full max-w-[1800px] mx-auto px-6 pt-12">
                <div class="flex items-center gap-2.5 mb-5">
                    <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Tayang Musim Ini</h2>
                    @if($seasonTitle)
                        <span class="text-[11px] font-bold text-red-400 bg-red-950/60 border border-red-500/30 px-2.5 py-1 rounded-full">{{ $seasonTitle }}</span>
                    @endif
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($season as $s)
                        <a href="{{ $detailUrl($s['slug']) }}" class="group block">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 border border-zinc-800 group-hover:border-red-500/50 transition">
                                <img src="{{ $s['poster'] }}" alt="{{ $s['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                                @if($s['episodes'])<span class="absolute top-2 right-2 bg-red-600/90 text-white text-[10px] font-bold px-2 py-0.5 rounded">{{ $s['episodes'] }}</span>@endif
                                @if($s['type'])<span class="absolute bottom-2 left-2 bg-black/70 text-zinc-200 text-[10px] font-bold px-2 py-0.5 rounded border border-white/10">{{ $s['type'] }}</span>@endif
                            </div>
                            <p class="mt-2 text-xs md:text-sm font-semibold text-zinc-200 group-hover:text-red-400 transition line-clamp-2">{{ $s['title'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    {{-- ============ KATALOG ============ --}}
    <main class="w-full max-w-[1800px] mx-auto px-6 pt-12">
        <div class="flex items-center justify-between mb-6 border-b border-zinc-800/80 pb-4">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                <h2 class="text-xl md:text-2xl font-extrabold tracking-tight text-white">
                    @if(filled($search))
                        Hasil Pencarian: "{{ $search }}"
                    @elseif(filled($genre))
                        Genre: {{ ucfirst($genre) }}
                    @elseif(filled($status))
                        Status: {{ ucfirst($status) }}
                    @else
                        Katalog Anime
                    @endif
                </h2>
            </div>
            <div class="flex items-center gap-3">
                @if(blank($search))
                    <div class="text-xs text-zinc-400 bg-zinc-900 px-2.5 py-1 rounded border border-zinc-800">
                        Halaman <span class="text-red-400 font-bold">{{ $pageNumber }}</span>
                    </div>
                @endif
                <div class="hidden sm:block text-xs text-zinc-400 bg-zinc-900 px-2.5 py-1 rounded border border-zinc-800">
                    <span class="text-red-400 font-bold">{{ count($animes) }}</span> Anime
                </div>
            </div>
        </div>

        <div wire:loading wire:target="loadAnimes, nextPage, previousPage, search, genre, status" class="w-full">
            <div class="flex items-center justify-center py-16">
                <div class="flex items-center gap-3 text-zinc-400 bg-zinc-900/50 px-5 py-3 rounded-full border border-zinc-800">
                    <svg class="animate-spin h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span class="text-sm font-semibold">Mengambil data anime...</span>
                </div>
            </div>
        </div>

        <div wire:loading.remove wire:target="loadAnimes, nextPage, previousPage, search, genre, status">
            @if(empty($animes))
                <div class="flex flex-col items-center justify-center py-16 text-zinc-500 bg-zinc-900/50 rounded-xl border border-dashed border-zinc-800">
                    <p class="text-sm font-medium">Tidak ada anime yang ditemukan.</p>
                    <p class="text-xs text-zinc-600 mt-1">Coba kata kunci lain atau ubah filter.</p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($animes as $anime)
                        @php $u = $detailUrl($anime['slug'] ?? ''); @endphp
                        <a href="{{ $u }}" class="group block">
                            <div class="relative aspect-[2/3] overflow-hidden bg-zinc-950 rounded-xl border border-zinc-800/80 group-hover:border-red-500/50 transition">
                                @if(!empty($anime['thumbnail']))
                                    <img src="{{ $anime['thumbnail'] }}" alt="{{ $anime['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-zinc-900 text-zinc-600 text-xs font-semibold">No Image</div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                                <span class="absolute top-2 right-2 px-1.5 py-0.5 text-[9px] font-extrabold uppercase rounded border bg-red-950/80 text-red-400 border-red-500/30">MP4 HD</span>
                            </div>
                            <h3 class="mt-2 text-xs md:text-sm font-semibold text-zinc-200 group-hover:text-red-400 transition line-clamp-2 leading-snug">{{ $anime['title'] }}</h3>
                        </a>
                    @endforeach
                </div>

                @if(blank($search))
                    <div class="mt-12 flex items-center justify-center gap-3">
                        <button wire:click="previousPage" @if($pageNumber <= 1) disabled @endif
                                class="px-4 py-2 bg-zinc-900 border border-zinc-800 text-sm font-semibold rounded-lg text-white hover:bg-zinc-800 disabled:opacity-40 disabled:cursor-not-allowed transition">
                            ← Sebelumnya
                        </button>
                        <span class="px-4 py-2 bg-red-600/20 border border-red-500/30 rounded-lg text-sm font-bold text-red-400">{{ $pageNumber }}</span>
                        <button wire:click="nextPage"
                                class="px-4 py-2 bg-zinc-900 border border-zinc-800 text-sm font-semibold rounded-lg text-white hover:bg-zinc-800 transition">
                            Selanjutnya →
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </main>
</div>
