<div class="min-h-screen bg-[#050505] text-white pb-24 md:pb-16">
    <!-- Top Bar Navigation (Desktop & Mobile) -->
    <div class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-20 md:pt-28 pb-3 md:pb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ \App\Filament\Streaming\Pages\AnimeDetailV2::getUrl(['slug' => $animeSlug], panel: 'streaming') }}"
            class="inline-flex items-center text-xs md:text-sm font-semibold text-zinc-400 hover:text-white transition gap-1.5">
            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Detail Anime
        </a>

        <!-- Episode Prev / Next Navigation -->
        <div class="flex items-center gap-2">
            @if(!empty($episodeDetail['prev_episode_slug']))
                <a href="{{ \App\Filament\Streaming\Pages\WatchAnimeV2::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $episodeDetail['prev_episode_slug']], panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-xs font-bold text-zinc-300 hover:text-white border border-zinc-800 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    <span>Eps Sebelumnya</span>
                </a>
            @endif

            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-zinc-900/80 border border-zinc-800 text-xs font-mono">
                <span class="inline-block w-2 h-2 rounded-full bg-purple-600 animate-pulse"></span>
                <span class="font-bold text-white">Eps {{ $episodeDetail['episode_number'] ?? '1' }}</span>
            </div>

            @if(!empty($episodeDetail['next_episode_slug']))
                <a href="{{ \App\Filament\Streaming\Pages\WatchAnimeV2::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $episodeDetail['next_episode_slug']], panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-xs font-bold text-white shadow-lg shadow-purple-600/30 transition">
                    <span>Eps Selanjutnya</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            @endif
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="w-full max-w-[1800px] mx-auto px-0 md:px-6 space-y-6 md:space-y-8">

        <!-- Video Player Card Container -->
        <div data-kz-fs wire:ignore.self class="w-full bg-black md:rounded-2xl overflow-hidden shadow-2xl aspect-video border-y md:border border-zinc-800/80 relative">
            <button type="button" data-kz-rotate aria-pressed="false" aria-label="Layar penuh landscape"
                    class="kz-fs-btn absolute top-2 right-2 z-[60] items-center gap-1.5 h-9 px-3 rounded-full bg-black/65 backdrop-blur text-white text-xs font-bold ring-1 ring-white/20 active:bg-black/80">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="14" height="10" rx="2"/><path d="M20 4v5h-5"/><path d="M20 9a8 8 0 0 0-8-6"/></svg>
                <span>Layar penuh</span>
            </button>


            <!-- Loading Spinner when switching mirror -->
            <div wire:loading.flex wire:target="switchMirror" class="absolute inset-0 z-30 bg-black/90 flex flex-col items-center justify-center gap-3">
                <div class="relative w-14 h-14">
                    <div class="w-14 h-14 rounded-full border-4 border-purple-600/30 border-t-purple-600 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-500 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                </div>
                <div class="text-center px-4">
                    <p class="text-sm font-bold text-white tracking-wide">Menghubungkan ke Server Mirror...</p>
                    <p class="text-xs text-zinc-400 mt-1">Mengambil stream video kualitas tinggi...</p>
                </div>
            </div>

            <!-- Video Player (Direct MP4 vs Iframe Embed) -->
            <div class="w-full h-full bg-black relative flex items-center justify-center">
                @if($isDirectMp4)
                    <video key="{{ $currentStreamUrl }}"
                           src="{{ $currentStreamUrl }}"
                           controls
                           autoplay
                           playsinline
                           class="w-full h-full object-contain bg-black">
                    </video>
                @else
                    <iframe key="{{ $currentStreamUrl }}"
                            src="{{ $currentStreamUrl }}"
                            class="w-full h-full"
                            frameborder="0"
                            allowfullscreen="true"
                            webkitallowfullscreen="true"
                            mozallowfullscreen="true"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            referrerpolicy="no-referrer">
                    </iframe>
                @endif
            </div>
        </div>

        <!-- Episode Info & Metadata Bar -->
        <div class="px-4 md:px-0 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-800 pb-5">
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-md bg-purple-600 text-white text-[10px] font-black uppercase tracking-wider">
                        SERVER 2 720P HD
                    </span>
                    <span class="px-2.5 py-0.5 rounded-md bg-zinc-800 text-zinc-300 text-[10px] font-bold border border-zinc-700">
                        SERVER: {{ strtoupper($currentServer) }} ({{ $currentQuality }})
                    </span>
                    @if($isDirectMp4)
                        <span class="px-2 py-0.5 rounded-md bg-emerald-950 text-emerald-400 border border-emerald-500/30 text-[10px] font-extrabold uppercase">
                            DIRECT MP4 HD
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-md bg-blue-950 text-blue-400 border border-blue-500/30 text-[10px] font-extrabold uppercase">
                            EMBED PLAYER
                        </span>
                    @endif
                </div>

                <h1 class="text-xl md:text-2xl font-black text-white leading-tight">
                    {{ $episodeDetail['title'] ?? 'Anime Episode' }}
                </h1>
                @if(!empty($seriesDetail['title']))
                    <p class="text-xs md:text-sm text-zinc-400">
                        Dari serial: <a href="{{ \App\Filament\Streaming\Pages\AnimeDetailV2::getUrl(['slug' => $animeSlug], panel: 'streaming') }}" class="text-purple-400 hover:underline font-semibold">{{ $seriesDetail['title'] }}</a>
                    </p>
                @endif
            </div>

            <!-- Favorite Button -->
            <div class="flex items-center gap-3">
                <button wire:click="toggleFavorite"
                        class="px-4 py-2.5 rounded-xl border {{ $isFavorited ? 'bg-red-600 border-red-600 text-white shadow-lg shadow-red-600/30' : 'bg-zinc-900 border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800' }} text-xs font-bold transition flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $isFavorited ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    <span>{{ $isFavorited ? 'Tersimpan di Favorit' : 'Tambah ke Favorit' }}</span>
                </button>
            </div>
        </div>

        <!-- 1. PILIH RESOLUSI & SERVER STREAMING -->
        @if(!empty($mirrors))
            <div class="px-4 md:px-0">
                <div class="bg-zinc-900/40 border border-zinc-800/80 rounded-2xl p-5 md:p-6 space-y-4">
                    <div class="flex items-center gap-2 border-b border-zinc-800/60 pb-3">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Pilih Server / Resolusi Streaming</h2>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach($mirrors as $index => $mirror)
                            <button wire:click="switchMirror({{ $index }})"
                                    class="px-3 py-2 rounded-xl text-xs font-bold transition border flex items-center gap-2
                                    {{ $activeMirrorIndex === $index
                                        ? 'bg-purple-600 border-purple-500 text-white shadow-lg shadow-purple-600/30'
                                        : 'bg-zinc-900 border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800' }}">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <span>{{ $mirror['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- 2. DOWNLOAD EPISODE INI -->
        <div class="px-4 md:px-0">
            <div class="bg-zinc-900/40 border border-zinc-800/80 rounded-2xl overflow-hidden">
                <div class="p-5 md:p-6 border-b border-zinc-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-600/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-white">Download Episode Ini</h2>
                            <p class="text-xs text-zinc-400">Unduh video untuk ditonton offline dari seluruh server resmi.</p>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-400 bg-emerald-950/60 border border-emerald-500/30 px-3 py-1 rounded-full self-start sm:self-auto">
                        Tersedia {{ count($downloads) }} Kualitas
                    </span>
                </div>

                @if(!empty($downloads))
                    <div class="p-5 md:p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($downloads as $dl)
                            <div class="bg-zinc-950/80 border border-zinc-800/80 hover:border-zinc-700 rounded-2xl p-4 flex flex-col justify-between gap-3 transition">
                                <div class="flex items-center justify-between gap-2 border-b border-zinc-800/60 pb-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                                        <h3 class="text-sm font-bold text-white tracking-wide">
                                            {{ $dl['format'] }}
                                        </h3>
                                    </div>
                                    @if(!empty($dl['size']))
                                        <span class="text-[11px] font-mono font-bold text-zinc-400 bg-zinc-900 px-2 py-0.5 rounded border border-zinc-800">
                                            {{ $dl['size'] }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach($dl['links'] as $link)
                                        <a href="{{ $link['url'] }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-900 hover:bg-purple-600 active:scale-95 text-zinc-300 hover:text-white border border-zinc-800 hover:border-purple-500 text-xs font-bold transition duration-150 shadow-sm">
                                            <svg class="w-3.5 h-3.5 text-zinc-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span>{{ $link['host'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center text-zinc-500 text-xs bg-zinc-950/60 rounded-2xl border border-dashed border-zinc-800 m-5">
                        Link download untuk episode ini belum tersedia.
                    </div>
                @endif
            </div>
        </div>

        <!-- 3. DAFTAR EPISODE LAINNYA DI SERIAL INI -->
        @if(!empty($seriesDetail['episodes']))
            <div class="px-4 md:px-0 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg md:text-xl font-extrabold text-white">Semua Episode ({{ count($seriesDetail['episodes']) }})</h2>
                        <p class="text-xs text-zinc-400">Pindah ke episode lain</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2.5 max-h-72 overflow-y-auto pr-1">
                    @foreach($seriesDetail['episodes'] as $ep)
                        @php
                            $epSlug = basename(rtrim($ep['url'] ?? '', '/'));
                            $isActive = $epSlug === $episodeSlug;
                            $epUrl = !empty($epSlug)
                                ? \App\Filament\Streaming\Pages\WatchAnimeV2::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $epSlug], panel: 'streaming')
                                : '#';
                        @endphp
                        <a href="{{ $epUrl }}"
                           class="px-3 py-2.5 rounded-xl text-center text-xs font-bold transition border
                           {{ $isActive
                               ? 'bg-purple-600 border-purple-500 text-white shadow-md shadow-purple-600/30 ring-2 ring-purple-500/40'
                               : 'bg-zinc-900/90 border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800' }}">
                            EPS {{ $ep['episode'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script src="/js/kz-fullscreen.js?v=1"></script>
    <script>
        // Video langsung (MP4): saat user menekan play pertama kali di HP, masuk fullscreen landscape
        document.addEventListener('play', (e) => {
            const v = e.target;
            if (!(v instanceof HTMLVideoElement) || v.dataset.kzFsAuto || !window.KzFs || !KzFs.isTouch()) return;
            const shell = v.closest('[data-kz-fs]');
            if (!shell || KzFs.current()) return;
            if (navigator.userActivation && !navigator.userActivation.isActive) return; // autoplay tanpa tap: jangan paksa
            v.dataset.kzFsAuto = '1';
            KzFs.enter(shell, { video: v });
        }, true);
    </script>
@endpush
