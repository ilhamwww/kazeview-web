<div class="min-h-screen bg-[#050505] text-white pb-24 md:pb-16">
    <!-- Top Bar Navigation (Desktop & Mobile) -->
    <div class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-20 md:pt-28 pb-3 md:pb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ \App\Filament\Streaming\Pages\AnimeDetailV5::getUrl(['slug' => $animeSlug], panel: 'streaming') }}"
            class="inline-flex items-center text-xs md:text-sm font-semibold text-zinc-400 hover:text-white transition gap-1.5">
            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Detail Anime
        </a>

        <!-- Episode Prev / Next Navigation -->
        <div class="flex items-center gap-2">
            @if(!empty($episodeDetail['prev_episode_slug']))
                <a href="{{ \App\Filament\Streaming\Pages\WatchAnimeV5::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $episodeDetail['prev_episode_slug']], panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-xs font-bold text-zinc-300 hover:text-white border border-zinc-800 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    <span>Eps Sebelumnya</span>
                </a>
            @endif

            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-zinc-900/80 border border-zinc-800 text-xs font-mono">
                <span class="inline-block w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                <span class="font-bold text-white">Eps {{ $episodeDetail['episode_number'] ?? '1' }}</span>
            </div>

            @if(!empty($episodeDetail['next_episode_slug']))
                <a href="{{ \App\Filament\Streaming\Pages\WatchAnimeV5::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $episodeDetail['next_episode_slug']], panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-xs font-bold text-white shadow-lg shadow-rose-600/30 transition">
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
            <div wire:loading.flex wire:target="switchMirror, toggleRelay" class="absolute inset-0 z-30 bg-black/90 flex flex-col items-center justify-center gap-3">
                <div class="relative w-14 h-14">
                    <div class="w-14 h-14 rounded-full border-4 border-rose-500/30 border-t-rose-500 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-rose-400 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                </div>
                <div class="text-center px-4">
                    <p class="text-sm font-bold text-white tracking-wide">Menghubungkan ke Stream Relay Server...</p>
                    <p class="text-xs text-zinc-400 mt-1">Mengambil video resolusi tinggi tanpa iklan...</p>
                </div>
            </div>

            <!-- Video Player (Native HTML5 Video / HLS.js / Iframe) -->
            <div class="w-full h-full bg-black relative flex items-center justify-center">
                @if(!empty($currentStreamUrl))
                    @if($currentType === 'iframe')
                        <iframe key="{{ $currentStreamUrl }}"
                                src="{{ $currentStreamUrl }}"
                                class="w-full h-full"
                                frameborder="0"
                                allowfullscreen="true"
                                webkitallowfullscreen="true"
                                mozallowfullscreen="true"
                                allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                referrerpolicy="no-referrer">
                        </iframe>
                    @else
                        <video id="v5-video-player"
                               key="{{ $currentStreamUrl }}"
                               src="{{ $currentStreamUrl }}"
                               controls
                               autoplay
                               playsinline
                               webkit-playsinline
                               class="w-full h-full object-contain bg-black">
                            Browser Anda tidak mendukung tag video HTML5.
                        </video>
                    @endif
                @else
                    <div class="text-center p-6 text-zinc-400">
                        <p class="text-base font-bold text-white">Stream video belum siap atau tidak tersedia.</p>
                        <p class="text-xs text-zinc-500 mt-1">Silakan coba pilih resolusi server lain di bawah.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Episode Info & Metadata Bar -->
        <div class="px-4 md:px-0 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-800 pb-5">
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-md bg-rose-600 text-white text-[10px] font-black uppercase tracking-wider">
                        SERVER 5
                    </span>
                    <span class="px-2.5 py-0.5 rounded-md bg-zinc-800 text-zinc-300 text-[10px] font-bold border border-zinc-700">
                        RESOLUSI: {{ strtoupper($currentQuality) }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md {{ $useRelay ? 'bg-emerald-950 text-emerald-400 border-emerald-500/30' : 'bg-zinc-900 text-zinc-400 border-zinc-800' }} border text-[10px] font-extrabold uppercase flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full {{ $useRelay ? 'bg-emerald-400 animate-pulse' : 'bg-zinc-500' }}"></span>
                        {{ $useRelay ? 'VPS RELAY PASSTHROUGH' : 'DIRECT CDN' }}
                    </span>
                </div>

                <h1 class="text-xl md:text-2xl font-black text-white leading-tight">
                    {{ $episodeDetail['title'] ?? 'Anime Episode' }}
                </h1>
                @if(!empty($seriesDetail['title']))
                    <p class="text-xs md:text-sm text-zinc-400">
                        Dari serial: <a href="{{ \App\Filament\Streaming\Pages\AnimeDetailV5::getUrl(['slug' => $animeSlug], panel: 'streaming') }}" class="text-rose-400 hover:underline font-semibold">{{ $seriesDetail['title'] }}</a>
                    </p>
                @endif
            </div>

            <!-- Actions Bar: Toggle Relay & Favorite -->
            <div class="flex items-center gap-3 flex-wrap">
                <button wire:click="toggleRelay"
                        class="px-3.5 py-2.5 rounded-xl border border-zinc-800 bg-zinc-900 hover:bg-zinc-800 text-zinc-300 hover:text-white text-xs font-bold transition flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $useRelay ? 'text-emerald-400' : 'text-zinc-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>{{ $useRelay ? 'Mode Relay Aktif' : 'Ganti ke Relay' }}</span>
                </button>

                <button wire:click="toggleFavorite"
                        class="px-4 py-2.5 rounded-xl border {{ $isFavorited ? 'bg-red-600 border-red-600 text-white shadow-lg shadow-red-600/30' : 'bg-zinc-900 border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800' }} text-xs font-bold transition flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $isFavorited ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    <span>{{ $isFavorited ? 'Tersimpan di Favorit' : 'Tambah ke Favorit' }}</span>
                </button>
            </div>
        </div>

        <!-- 1. PILIH RESOLUSI STREAMING -->
        @if(!empty($mirrors))
            <div class="px-4 md:px-0">
                <div class="bg-zinc-900/40 border border-zinc-800/80 rounded-2xl p-5 md:p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-zinc-800/60 pb-3 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Pilih Resolusi Video (Server 5)</h2>
                        </div>
                        <span class="text-xs text-zinc-500 font-mono">Bebas Iklan & Tanpa Buffering</span>
                    </div>

                    <div class="flex flex-wrap gap-2.5">
                        @foreach($mirrors as $index => $mirror)
                            <button wire:click="switchMirror({{ $index }})"
                                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition border flex items-center gap-2
                                    {{ $activeMirrorIndex === $index
                                        ? 'bg-rose-600 border-rose-500 text-white shadow-lg shadow-rose-600/30 ring-1 ring-rose-400'
                                        : 'bg-zinc-900 border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800' }}">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <span>{{ $mirror['label'] ?? ($mirror['name'] ?? 'Resolusi ' . ($index + 1)) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- 2. DAFTAR EPISODE LAINNYA DI SERIAL INI -->
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
                            $epSlug = !empty($ep['slug']) ? $ep['slug'] : basename(rtrim($ep['url'] ?? '', '/'));
                            $isActive = $epSlug === $episodeSlug;
                            $epUrl = !empty($epSlug)
                                ? \App\Filament\Streaming\Pages\WatchAnimeV5::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $epSlug], panel: 'streaming')
                                : '#';
                        @endphp
                        <a href="{{ $epUrl }}"
                           class="px-3 py-2.5 rounded-xl text-center text-xs font-bold transition border
                           {{ $isActive
                               ? 'bg-rose-600 border-rose-500 text-white shadow-md shadow-rose-600/30 ring-2 ring-rose-500/40'
                               : 'bg-zinc-900/90 border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800' }}">
                            EPS {{ $ep['number'] ?? $ep['episode'] ?? '•' }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.18/dist/hls.min.js"></script>
    <script src="/js/kz-fullscreen.js?v=1"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            initV5HlsPlayer();
        });

        document.addEventListener('livewire:navigated', function () {
            initV5HlsPlayer();
        });

        function initV5HlsPlayer() {
            const video = document.getElementById('v5-video-player');
            if (!video) return;

            const streamUrl = video.getAttribute('src');
            if (!streamUrl) return;

            // Jika URL adalah playlist HLS m3u8 atau tar chunklist
            if (streamUrl.includes('.m3u8') || streamUrl.includes('chunklist')) {
                if (window.Hls && Hls.isSupported()) {
                    const hls = new Hls({
                        debug: false,
                        enableWorker: true,
                        lowLatencyMode: true,
                        backBufferLength: 90
                    });
                    hls.loadSource(streamUrl);
                    hls.attachMedia(video);
                    hls.on(Hls.Events.MANIFEST_PARSED, function () {
                        video.play().catch(() => {});
                    });
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    video.src = streamUrl;
                    video.addEventListener('loadedmetadata', function () {
                        video.play().catch(() => {});
                    });
                }
            }
        }
    </script>
@endpush
