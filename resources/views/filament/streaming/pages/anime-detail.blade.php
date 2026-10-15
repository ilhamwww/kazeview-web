<div>
    <!-- Hero Banner -->
    <section class="relative w-full min-h-[500px] md:min-h-[60vh] overflow-hidden bg-black flex flex-col justify-end">
        <!-- Background Image -->
        @if(!empty($detail['thumbnail']))
            <img src="{{ $detail['thumbnail'] }}" alt="{{ $detail['title'] ?? '' }}" class="absolute inset-0 w-full h-full object-cover scale-110 blur-sm opacity-30">
        @endif

        <!-- Overlays -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/70 to-transparent w-full md:w-[70%]"></div>
        <div class="absolute top-0 inset-x-0 h-48 bg-gradient-to-b from-[#050505]/80 via-[#050505]/30 to-transparent"></div>
        <div class="absolute bottom-0 inset-x-0 h-96 bg-gradient-to-t from-[#050505] via-[#050505]/90 to-transparent"></div>

        <!-- Content -->
        <div class="relative w-full max-w-[1800px] mx-auto px-6 pt-32 pb-10 md:pb-16 z-10">
            <div class="flex flex-col md:flex-row gap-6 md:gap-10 items-start md:items-end w-full">
                <!-- Poster -->
                @if(!empty($detail['thumbnail']))
                <div class="flex-shrink-0 hidden md:block">
                    <img src="{{ $detail['thumbnail'] }}" alt="{{ $detail['title'] ?? '' }}"
                         class="w-48 h-72 object-cover rounded-xl border border-white/10 shadow-2xl shadow-black/60">
                </div>
                @endif

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <!-- Badges -->
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="bg-red-600 text-white text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">ANIME</span>
                        <span class="bg-emerald-950/80 text-emerald-400 border border-emerald-500/20 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">720P HD</span>
                        @if(!empty($detail['episodes']))
                            <span class="bg-zinc-800 text-gray-300 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-zinc-700">
                                {{ count($detail['episodes']) }} Episode
                            </span>
                        @endif
                        @if(!empty($detail['score']))
                            <span class="bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">
                                ★ {{ $detail['score'] }}
                            </span>
                        @endif
                    </div>

                    <!-- Title -->
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight">
                        {{ $detail['title'] ?? 'Anime Detail' }}
                    </h1>

                    @if(!empty($detail['japanese_title']))
                        <p class="text-sm text-zinc-400 mt-1 italic">{{ $detail['japanese_title'] }}</p>
                    @endif

                    <!-- Genres -->
                    @if(!empty($detail['genres']))
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach($detail['genres'] as $genre)
                            <span class="px-2.5 py-1 text-xs font-bold text-red-300 bg-red-600/15 border border-red-500/20 rounded-full">
                                {{ $genre }}
                            </span>
                        @endforeach
                    </div>
                    @endif

                    <!-- Description / Synopsis -->
                    @if(!empty($detail['synopsis']))
                    <p class="max-w-2xl mt-4 text-sm md:text-base text-gray-400 leading-relaxed font-medium line-clamp-3">
                        {{ $detail['synopsis'] }}
                    </p>
                    @endif

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center gap-3 mt-6">
                        @if(!empty($detail['episodes']))
                            @php
                                $firstEp = $detail['episodes'][0] ?? null;
                                $firstEpSlug = $firstEp ? basename(rtrim($firstEp['url'], '/')) : '';
                                $firstWatchUrl = $firstEpSlug
                                    ? \App\Filament\Streaming\Pages\WatchAnime::getUrl([
                                        'anime_slug' => $animeSlug,
                                        'episode_slug' => $firstEpSlug,
                                    ], panel: 'streaming')
                                    : '#';
                            @endphp
                            <a href="{{ $firstWatchUrl }}"
                               class="inline-flex items-center gap-2 px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-extrabold text-sm tracking-wide shadow-xl shadow-red-600/30 active:scale-95 transition duration-200">
                                <svg class="w-4 h-4 fill-current translate-x-0.5" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <span>Mulai Menonton</span>
                            </a>
                        @endif

                        <a href="{{ \App\Filament\Streaming\Pages\ExploreAnime::getUrl(panel: 'streaming') }}"
                           class="inline-flex items-center gap-2 px-5 py-3 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-gray-300 rounded-xl font-bold text-sm tracking-wide transition duration-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                            Kembali
                        </a>

                        <button wire:click="toggleFavorite"
                           class="inline-flex items-center gap-2 px-5 py-3 {{ $isFavorited ? 'bg-red-600 hover:bg-red-700 border-red-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700 border-zinc-700 text-gray-300' }} border rounded-xl font-bold text-sm tracking-wide transition duration-300">
                            @if($isFavorited)
                                <svg class="w-4 h-4 fill-current text-white" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                Hapus dari Favorit
                            @else
                                <svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                Tambah ke Favorit
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Episode List Section -->
    <div class="space-y-6 px-6 pb-16">

        <!-- Episode List Header -->
        <div class="flex items-center justify-between border-b border-zinc-800 pb-4 pt-6">
            <div>
                <h2 class="text-xl md:text-2xl font-black text-gray-100 flex items-center gap-2">
                    <span class="w-1.5 h-5 bg-red-600 rounded-full"></span>
                    Daftar Episode
                </h2>
                <p class="text-xs text-zinc-400 mt-1">Klik episode untuk membuka halaman pemutar video, mirror server, dan download.</p>
            </div>
            @if(!empty($detail['episodes']))
            <div class="text-xs text-zinc-400 bg-zinc-900 px-3 py-1.5 rounded-xl border border-zinc-800">
                Total: <span class="text-red-400 font-bold">{{ count($detail['episodes']) }}</span> Episode
            </div>
            @endif
        </div>

        @if(!empty($detail['episodes']))
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
                @foreach($detail['episodes'] as $ep)
                    @php
                        $epSlug = basename(rtrim($ep['url'], '/'));
                        $watchUrl = \App\Filament\Streaming\Pages\WatchAnime::getUrl([
                            'anime_slug' => $animeSlug,
                            'episode_slug' => $epSlug,
                        ], panel: 'streaming');
                    @endphp
                    <a href="{{ $watchUrl }}"
                       class="group flex items-center gap-4 p-4 rounded-2xl border bg-zinc-900/40 border-zinc-800 hover:border-red-600/50 hover:bg-zinc-900/80 transition-all duration-300 text-left w-full cursor-pointer hover:scale-[1.02] shadow-lg">
                        
                        <!-- Episode Number Badge -->
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-zinc-800/90 text-zinc-300 group-hover:bg-red-600 group-hover:text-white flex items-center justify-center font-black text-sm transition-all duration-300 shadow-md">
                            {{ $ep['episode'] }}
                        </div>

                        <!-- Episode Title & Date -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold truncate text-gray-200 group-hover:text-white transition-colors duration-200">
                                {{ $ep['title'] ?: 'Episode ' . $ep['episode'] }}
                            </p>
                            @if(!empty($ep['date']))
                                <p class="text-xs text-zinc-500 mt-0.5">{{ $ep['date'] }}</p>
                            @endif
                        </div>

                        <!-- Play Icon Action -->
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-zinc-950 flex items-center justify-center text-zinc-500 group-hover:text-white group-hover:bg-red-600 transition-all duration-200">
                            <svg class="w-4 h-4 fill-current translate-x-0.5" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-zinc-500 bg-zinc-900/50 rounded-xl border border-dashed border-zinc-800">
                <svg class="w-12 h-12 mb-3 stroke-current opacity-60 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h-14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <p class="text-sm font-medium">Daftar episode tidak ditemukan.</p>
                <p class="text-xs text-zinc-600 mt-1">Coba kunjungi link sumber secara langsung.</p>
            </div>
        @endif
    </div>
</div>
