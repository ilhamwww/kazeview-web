<div class="min-h-screen bg-[#050505] text-white pb-32">
    <!-- Hero Banner with Official Anime Metadata -->
    <section class="relative w-full min-h-[520px] md:min-h-[65vh] overflow-hidden bg-black flex flex-col justify-end">
        <!-- Background Banner -->
        @php
            $bannerImg = $metadata['banner'] ?? ($detail['thumbnail'] ?? '');
            $posterImg = $metadata['poster'] ?? ($detail['thumbnail'] ?? '');
        @endphp

        @if(!empty($bannerImg))
            <img src="{{ $bannerImg }}" alt="{{ $detail['title'] ?? '' }}" class="absolute inset-0 w-full h-full object-cover scale-105 blur-sm opacity-25">
        @endif

        <!-- Overlays -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/85 to-transparent w-full md:w-[75%]"></div>
        <div class="absolute top-0 inset-x-0 h-48 bg-gradient-to-b from-[#050505]/90 via-[#050505]/40 to-transparent"></div>
        <div class="absolute bottom-0 inset-x-0 h-96 bg-gradient-to-t from-[#050505] via-[#050505]/90 to-transparent"></div>

        <!-- Content -->
        <div class="relative w-full max-w-[1800px] mx-auto px-6 pt-32 pb-10 md:pb-16 z-10">
            <!-- Breadcrumb & Back -->
            <div class="flex items-center gap-2 text-xs text-zinc-400 mb-6">
                <a href="{{ \App\Filament\Streaming\Pages\ExploreAnime::getUrl(panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 font-bold text-zinc-300 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Katalog Anime
                </a>
                <span>/</span>
                <span class="text-zinc-500 truncate max-w-xs">{{ $metadata['title'] ?? ($detail['title'] ?? 'Detail') }}</span>
            </div>

            <div class="flex flex-col md:flex-row gap-6 md:gap-10 items-start md:items-end w-full">
                <!-- High-Res Poster -->
                @if(!empty($posterImg))
                <div class="flex-shrink-0 hidden md:block">
                    <img src="{{ $posterImg }}" alt="{{ $detail['title'] ?? '' }}"
                         class="w-52 h-80 object-cover rounded-2xl border border-white/10 shadow-2xl shadow-black/80 ring-1 ring-white/10">
                </div>
                @endif

                <!-- Info Column -->
                <div class="flex-1 min-w-0">
                    <!-- Badges Row -->
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="bg-emerald-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider shadow-md shadow-emerald-600/30">
                            SERVER 1 (V1 DIRECT HD)
                        </span>

                        @if(!empty($metadata['score']))
                            <span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 fill-amber-400" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                ★ {{ $metadata['score'] }} (MAL)
                            </span>
                        @endif

                        <span class="bg-emerald-950/80 text-emerald-400 border border-emerald-500/30 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">
                            {{ $metadata['status'] ?? 'ONGOING' }}
                        </span>

                        @if(!empty($metadata['broadcast_day']))
                            <span class="bg-purple-950/80 text-purple-300 border border-purple-500/30 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-purple-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $metadata['broadcast_day'] }}
                            </span>
                        @endif

                        @if(!empty($metadata['release_date']))
                            <span class="bg-zinc-800 text-zinc-300 border border-zinc-700 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">
                                Rilis: {{ $metadata['release_date'] }}
                            </span>
                        @endif

                        @if(!empty($metadata['studio']) && $metadata['studio'] !== 'N/A')
                            <span class="bg-zinc-800 text-zinc-300 border border-zinc-700 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">
                                Studio: {{ $metadata['studio'] }}
                            </span>
                        @endif

                        @if(!empty($detail['episodes']))
                            <span class="bg-zinc-800 text-gray-300 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-zinc-700">
                                {{ count($detail['episodes']) }} Episode
                            </span>
                        @endif
                    </div>

                    <!-- Title -->
                    <h1 class="text-3xl md:text-5xl font-black tracking-tight text-white leading-tight">
                        {{ $metadata['title'] ?? ($detail['title'] ?? 'Anime Detail') }}
                    </h1>
                    @if(!empty($metadata['english_title']) && $metadata['english_title'] !== ($metadata['title'] ?? ''))
                        <p class="text-sm font-semibold text-zinc-400 mt-1">{{ $metadata['english_title'] }}</p>
                    @endif

                    <!-- Next Airing Schedule Notification -->
                    @if(!empty($metadata['next_episode_schedule']))
                        <div class="mt-3.5 inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-500/15 border border-amber-500/30 text-xs font-bold text-amber-300">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                            <span>Jadwal Tayang: {{ $metadata['next_episode_schedule'] }}</span>
                        </div>
                    @endif

                    <!-- Genres -->
                    @php
                        $genresList = !empty($metadata['genres']) ? $metadata['genres'] : ($detail['genres'] ?? []);
                    @endphp
                    @if(!empty($genresList))
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach($genresList as $genre)
                            <span class="px-3 py-1 text-xs font-bold text-emerald-300 bg-emerald-600/15 border border-emerald-500/20 rounded-full">
                                {{ $genre }}
                            </span>
                        @endforeach
                    </div>
                    @endif

                    <!-- Synopsis -->
                    @php
                        $synopsisText = !empty($metadata['synopsis']) ? $metadata['synopsis'] : ($detail['description'] ?? '');
                    @endphp
                    @if(!empty($synopsisText))
                    <p class="max-w-3xl mt-4 text-sm md:text-base text-zinc-300 leading-relaxed font-medium line-clamp-3">
                        {{ $synopsisText }}
                    </p>
                    @endif

                    <!-- CTA Actions (Tonton & Download) -->
                    <div class="flex flex-wrap items-center gap-3 mt-6">
                        @if(!empty($detail['episodes']))
                            @php
                                $firstEp = $detail['episodes'][0] ?? null;
                                $firstEpNum = $firstEp['episode'] ?? '1';
                                $watchUrl = \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $firstEpNum], panel: 'streaming');
                            @endphp
                            <a href="{{ $watchUrl }}"
                               class="inline-flex items-center gap-2 px-7 py-3.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-extrabold text-sm rounded-xl shadow-lg shadow-emerald-600/40 transition">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <span>Tonton Episode 1</span>
                            </a>
                        @endif

                        <a href="#download-section"
                           class="inline-flex items-center gap-2 px-5 py-3.5 bg-zinc-900/90 hover:bg-zinc-800 text-zinc-200 hover:text-white font-bold text-sm rounded-xl border border-zinc-700/80 transition">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            <span>Unduh Resolusi</span>
                        </a>

                        <button wire:click="toggleFavorite"
                            class="inline-flex items-center gap-2 px-5 py-3.5 bg-zinc-900/90 hover:bg-zinc-800 text-white font-bold text-sm rounded-xl border border-zinc-700/80 transition">
                            <svg class="w-5 h-5 {{ $isFavorited ? 'text-red-500 fill-current' : 'text-zinc-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                            <span>{{ $isFavorited ? 'Tersimpan' : 'Favorit' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Download Section (Otakudesu Multi-Resolution) -->
    <section id="download-section" class="w-full max-w-[1800px] mx-auto px-6 pt-12">
        <div class="p-6 md:p-8 rounded-2xl bg-zinc-900/70 border border-zinc-800/80 shadow-2xl backdrop-blur">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-800 pb-5 mb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-lg bg-emerald-500/20 text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        </span>
                        <h2 class="text-xl md:text-2xl font-black tracking-tight">Unduh Berbagai Resolusi</h2>
                    </div>
                    <p class="text-xs text-zinc-400 mt-1">Pilihan resolusi 360p, 480p, 720p, 1080p dan batch lengkap via Otakudesu</p>
                </div>

                <!-- Episode Picker for Downloads -->
                @if(!empty($detail['episodes']))
                    <div class="flex items-center gap-2 overflow-x-auto max-w-full pb-1">
                        <span class="text-xs font-bold text-zinc-400 shrink-0">Episode:</span>
                        @foreach(array_slice($detail['episodes'], 0, 12) as $ep)
                            @php $epN = (string)($ep['episode'] ?? '1'); @endphp
                            <button type="button" wire:click="selectDownloadEpisode('{{ $epN }}')"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition shrink-0 {{ $selectedDownloadEp === $epN ? 'bg-emerald-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:text-white' }}">
                                Eps {{ $epN }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Download Content -->
            @if(!empty($downloads['episode']) || !empty($downloads['batch']))
                <div class="space-y-6">
                    <!-- Per-Episode Downloads -->
                    @if(!empty($downloads['episode']))
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-emerald-400 mb-3 flex items-center gap-2">
                                <span>Episode {{ $selectedDownloadEp }} Options</span>
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($downloads['episode'] as $dl)
                                    <div class="p-4 rounded-xl bg-zinc-950/60 border border-zinc-800 hover:border-zinc-700 transition">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="px-2.5 py-1 rounded-md text-xs font-black bg-emerald-950/70 text-emerald-400 border border-emerald-500/30">
                                                {{ $dl['format'] ?? 'MP4' }}
                                            </span>
                                            @if(!empty($dl['size']))
                                                <span class="text-xs font-mono font-bold text-zinc-400">{{ $dl['size'] }}</span>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($dl['links'] ?? [] as $lk)
                                                <a href="{{ $lk['url'] }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-900 hover:bg-emerald-600 text-zinc-200 hover:text-white text-xs font-bold border border-zinc-800 hover:border-emerald-500 transition">
                                                    <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                                    <span>{{ $lk['host'] }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Batch Downloads if available -->
                    @if(!empty($downloads['batch']))
                        <div class="pt-4 border-t border-zinc-800">
                            <h3 class="text-xs font-black uppercase tracking-wider text-purple-400 mb-3 flex items-center gap-2">
                                <span>Batch Download (Full Season)</span>
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($downloads['batch'] as $b)
                                    <div class="p-4 rounded-xl bg-purple-950/20 border border-purple-500/30">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="px-2.5 py-1 rounded-md text-xs font-black bg-purple-900/60 text-purple-300">
                                                {{ $b['format'] }}
                                            </span>
                                            @if(!empty($b['size']))
                                                <span class="text-xs font-mono font-bold text-zinc-400">{{ $b['size'] }}</span>
                                            @endif
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($b['links'] as $blk)
                                                <a href="{{ $blk['url'] }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-900 hover:bg-purple-600 text-zinc-200 hover:text-white text-xs font-bold border border-zinc-800 hover:border-purple-500 transition">
                                                    <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                                    <span>{{ $blk['host'] }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="py-8 text-center text-zinc-500">
                    <p class="text-xs font-medium">Link download Otakudesu sedang diproses atau belum tersedia untuk episode ini.</p>
                </div>
            @endif
        </div>
    </section>

    <!-- Episode List Section -->
    <main class="w-full max-w-[1800px] mx-auto px-6 py-12">
        <div class="mb-6 flex items-center justify-between border-b border-zinc-800 pb-4">
            <div>
                <h2 class="text-xl md:text-2xl font-black tracking-tight">Daftar Episode</h2>
                <p class="text-xs text-zinc-400 mt-1">Pilih episode untuk langsung memutar di Server 1 (Direct HD)</p>
            </div>
            @if(!empty($detail['episodes']))
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-950/60 text-emerald-400 border border-emerald-500/30">
                    Total {{ count($detail['episodes']) }} Episode
                </span>
            @endif
        </div>

        @if(!empty($detail['episodes']))
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3">
                @foreach($detail['episodes'] as $ep)
                    @php
                        $epNum = $ep['episode'] ?? '1';
                        $epWatchUrl = \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $epNum], panel: 'streaming');
                    @endphp
                    <a href="{{ $epWatchUrl }}"
                       class="group flex flex-col p-3.5 rounded-xl bg-zinc-900/60 hover:bg-zinc-800/90 border border-zinc-800 hover:border-emerald-500/40 transition active:scale-95 shadow-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-black uppercase text-emerald-400 tracking-wider">
                                EPS {{ $ep['episode'] }}
                            </span>
                            <svg class="w-3.5 h-3.5 text-zinc-500 group-hover:text-emerald-400 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-zinc-300 group-hover:text-white line-clamp-2 leading-tight">
                            {{ $ep['title'] }}
                        </p>
                    </a>
                @endforeach
            </div>
        @else
            <div class="py-12 text-center text-zinc-500 bg-zinc-900/40 rounded-xl border border-dashed border-zinc-800">
                <p class="text-sm font-medium">Belum ada episode yang tersedia untuk anime ini.</p>
            </div>
        @endif
    </main>
</div>
