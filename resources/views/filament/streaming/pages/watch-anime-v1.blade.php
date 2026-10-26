@push('styles')
    <style>
        .art-video-player .art-mask { z-index: 20 !important; }
        .art-video-player .art-bottom { z-index: 30 !important; }
        .art-video-player .art-progress { height: 4px !important; }
        .art-video-player .art-progress:hover { height: 6px !important; }
        .art-video-player .art-progress-played { background: #10B981 !important; }
        .art-video-player .art-progress-highlight { background: #10B981 !important; }
        .art-video-player .art-volume-slider-handle { background: #10B981 !important; }
        .art-video-player .art-settings {
            height: auto !important;
            max-height: 380px !important;
            overflow: visible !important;
        }
        .art-video-player .art-setting-panel {
            min-width: 255px !important;
            width: 255px !important;
            max-width: 92vw !important;
            height: auto !important;
            max-height: 380px !important;
            overflow: visible !important;
            border-radius: 12px !important;
            backdrop-filter: blur(18px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(18px) saturate(180%) !important;
            background: rgba(18, 18, 22, 0.88) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.75), 0 2px 6px rgba(0, 0, 0, 0.4) !important;
            padding: 6px 6px !important;
        }
        .art-video-player .art-setting-item {
            height: 38px !important;
            min-height: 38px !important;
            padding: 0 10px !important;
            border-radius: 8px !important;
            margin: 1px 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            transition: background 0.15s ease !important;
            cursor: pointer !important;
        }
        .art-video-player .art-setting-item:hover {
            background: rgba(255, 255, 255, 0.09) !important;
        }
        .art-video-player .art-setting-item-left {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }
    </style>
@endpush

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-20"
     x-data="initWatchAnimeComponent()"
     x-init="init()">
    <!-- Top Bar Navigation (Desktop & Mobile) -->
    <div class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-20 md:pt-28 pb-3 md:pb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $animeSlug], panel: 'streaming') }}"
            class="inline-flex items-center text-xs md:text-sm font-semibold text-zinc-400 hover:text-white transition gap-1.5">
            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Detail Anime
        </a>

        <!-- Episode Prev / Next Navigation -->
        <div class="flex items-center gap-2">
            @if(!empty($prevEpisodeSlug))
                <a href="{{ \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $prevEpisodeSlug], panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-xs font-bold text-zinc-300 hover:text-white border border-zinc-800 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    <span>Eps Sebelumnya</span>
                </a>
            @endif

            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-zinc-900/80 border border-zinc-800 text-xs font-mono">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-bold text-white">Eps {{ $episodeDetail['episode_number'] ?? $episodeSlug }}</span>
            </div>

            @if(!empty($nextEpisodeSlug))
                <a href="{{ \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $nextEpisodeSlug], panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white shadow-lg shadow-emerald-600/30 transition">
                    <span>Eps Selanjutnya</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            @endif
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="w-full max-w-[1800px] mx-auto px-0 md:px-6 space-y-6 md:space-y-8">

        <!-- Video Player Card Container with Artplayer -->
        <div data-kz-fs x-ref="playerShell" wire:ignore.self class="w-full bg-black md:rounded-2xl overflow-hidden shadow-2xl aspect-video border-y md:border border-zinc-800/80 relative">
            <button type="button" data-kz-rotate aria-pressed="false" aria-label="Layar penuh landscape"
                    class="kz-fs-btn absolute top-2 right-2 z-[60] items-center gap-1.5 h-9 px-3 rounded-full bg-black/65 backdrop-blur text-white text-xs font-bold ring-1 ring-white/20 active:bg-black/80">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="14" height="10" rx="2"/><path d="M20 4v5h-5"/><path d="M20 9a8 8 0 0 0-8-6"/></svg>
                <span>Layar penuh</span>
            </button>

            <!-- Embed Player -->
            <template x-if="isEmbed && currentEmbedUrl">
                <iframe :src="currentEmbedUrl" class="w-full h-full border-0" allowfullscreen allow="autoplay; fullscreen; picture-in-picture"></iframe>
            </template>

            <!-- Native Artplayer Player -->
            <template x-if="!isEmbed">
                <div class="w-full h-full bg-black relative flex items-center justify-center">
                    <div x-ref="artContainer" class="w-full h-full" x-show="videoSrc"></div>
                    <div x-show="!videoSrc" class="flex flex-col items-center justify-center text-zinc-500 p-6 text-center">
                        <svg class="w-12 h-12 mb-3 stroke-current opacity-60 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h-14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <p class="text-sm font-medium text-white">Stream video belum tersedia</p>
                        <p class="text-xs text-zinc-500 mt-1">Silakan coba ganti server atau pilih episode lain.</p>
                    </div>
                </div>
            </template>
        </div>

        <!-- ================= SERVER SWITCHER ================= -->
        <template x-if="serversList && serversList.length > 0">
            <div class="px-4 md:px-0 flex items-center gap-2 overflow-x-auto kz-noscroll -mt-2 pb-1">
                <span class="text-xs font-bold text-zinc-400 mr-1 flex items-center gap-1.5 shrink-0">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.7 7.2a3 3 0 012.4-1.2h7.8a3 3 0 012.4 1.2l2.55 4.35A4.5 4.5 0 0121 14.25"/></svg>
                    Pilihan Server:
                </span>
                <template x-for="s in serversList" :key="s.id">
                    <button type="button" @click="switchServer(s.id)"
                            class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold ring-1 transition flex items-center gap-1.5"
                            :class="activeServer === s.id ? 'bg-emerald-600 text-white ring-emerald-500 shadow-lg shadow-emerald-600/30' : 'bg-zinc-900 text-zinc-300 ring-white/10 hover:text-white'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="activeServer === s.id ? 'bg-white' : 'bg-zinc-500'"></span>
                        <span x-text="s.name"></span>
                    </button>
                </template>
            </div>
        </template>

        <!-- Episode Info & Metadata Bar -->
        <div class="px-4 md:px-0 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-800 pb-5">
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-md bg-emerald-600 text-white text-[10px] font-black uppercase tracking-wider">
                        SERVER 1 (V1 DIRECT HD)
                    </span>
                    @if(!empty($metadata['score']))
                        <span class="px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-extrabold uppercase">
                            ★ {{ $metadata['score'] }} MAL
                        </span>
                    @endif
                    <span class="px-2 py-0.5 rounded-md bg-emerald-950 text-emerald-400 border border-emerald-500/30 text-[10px] font-extrabold uppercase">
                        STREAMING AKTIF
                    </span>
                </div>

                <h1 class="text-xl md:text-2xl font-black text-white leading-tight">
                    {{ $episodeDetail['title'] ?? 'Anime Episode' }}
                </h1>
                @if(!empty($seriesDetail['title']))
                    <p class="text-xs md:text-sm text-zinc-400">
                        Dari serial: <a href="{{ \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $animeSlug], panel: 'streaming') }}" class="text-emerald-400 hover:underline font-semibold">{{ $seriesDetail['title'] }}</a>
                    </p>
                @endif
            </div>

            <!-- Actions (Favorite & Download Jump) -->
            <div class="flex items-center gap-3">
                <a href="#episode-downloads"
                   class="px-4 py-2.5 rounded-xl border border-zinc-800 bg-zinc-900 hover:bg-zinc-800 text-zinc-200 hover:text-white text-xs font-bold transition flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    <span>Download Episode Ini</span>
                </a>

                <button wire:click="toggleFavorite"
                        class="px-4 py-2.5 rounded-xl border {{ $isFavorited ? 'bg-red-600 border-red-600 text-white shadow-lg shadow-red-600/30' : 'bg-zinc-900 border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800' }} text-xs font-bold transition flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $isFavorited ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    <span>{{ $isFavorited ? 'Tersimpan di Favorit' : 'Tambah ke Favorit' }}</span>
                </button>
            </div>
        </div>

        <!-- Downloads for this Episode -->
        @if(!empty($downloads['episode']))
            <div id="episode-downloads" class="px-4 md:px-0">
                <div class="p-5 rounded-2xl bg-zinc-900/50 border border-zinc-800/80">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="p-1.5 rounded-lg bg-emerald-500/20 text-emerald-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        </span>
                        <h3 class="text-sm font-black text-white">Download Episode {{ $episodeSlug }} (Otakudesu)</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($downloads['episode'] as $dl)
                            <div class="p-3.5 rounded-xl bg-zinc-950/70 border border-zinc-800">
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-black bg-emerald-950/80 text-emerald-400 border border-emerald-500/30">
                                        {{ $dl['format'] ?? 'MP4' }}
                                    </span>
                                    @if(!empty($dl['size']))
                                        <span class="text-xs font-mono font-bold text-zinc-400">{{ $dl['size'] }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($dl['links'] ?? [] as $lk)
                                        <a href="{{ $lk['url'] }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-zinc-900 hover:bg-emerald-600 text-zinc-300 hover:text-white text-[11px] font-bold border border-zinc-800 transition">
                                            <span>{{ $lk['host'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- All Episodes Navigation Section -->
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
                        $epNum = (string)($ep['episode'] ?? '');
                        $isActive = $epNum === (string)$episodeSlug;
                        $epUrl = !empty($epNum)
                            ? \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $epNum], panel: 'streaming')
                            : '#';
                    @endphp
                    <a href="{{ $epUrl }}"
                       class="px-3 py-2.5 rounded-xl text-center text-xs font-bold transition border
                       {{ $isActive
                           ? 'bg-emerald-600 border-emerald-500 text-white shadow-md shadow-emerald-600/30 ring-2 ring-emerald-500/40'
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/artplayer/5.1.7/artplayer.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.18/dist/hls.min.js"></script>
    <script src="/js/kz-fullscreen.js?v=1"></script>
    <script>
        (function() {
            let _isAuthorizedNav = false;
            window.addEventListener('beforeunload', function(e) {
                if (!_isAuthorizedNav && document.querySelector('iframe')) {
                    e.preventDefault();
                    e.returnValue = 'Batalkan pengalihan ke situs iklan?';
                    return e.returnValue;
                }
            });
            document.addEventListener('click', function(e) {
                if (e.target.closest('a, button, [wire\\:click], input, select, textarea')) {
                    _isAuthorizedNav = true;
                    setTimeout(() => { _isAuthorizedNav = false; }, 3000);
                }
            }, true);

            window.initWatchAnimeComponent = function() {
                return {
                    art: null,
                    hls: null,
                    videoSrc: {!! json_encode($currentStreamUrl) !!},
                    serversList: @js($servers),
                    activeServer: '{{ $activeServer }}',
                    isEmbed: {{ $isEmbed ? 'true' : 'false' }},
                    currentEmbedUrl: {!! json_encode($currentEmbedUrl) !!},

                    init() {
                        this.$nextTick(() => {
                            if (!this.isEmbed && this.videoSrc) {
                                this.initPlayer();
                            }
                        });
                    },

                    switchServer(id) {
                        const target = Array.isArray(this.serversList) ? this.serversList.find(s => s.id === id) : null;
                        if (!target) return;

                        this.activeServer = target.id;
                        this.isEmbed = (target.type === 'embed');
                        this.currentEmbedUrl = target.embed_url || '';
                        if (!this.isEmbed && target.stream_url) {
                            this.videoSrc = target.stream_url;
                        }

                        if (this.isEmbed) {
                            if (this.art) {
                                try { this.art.destroy(false); } catch(e) {}
                                this.art = null;
                            }
                        } else {
                            this.$nextTick(() => {
                                this.initPlayer();
                            });
                        }
                    },

                    useNativeFs() {
                        return !!(window.KzFs && window.KzFs.isTouch() && window.KzFs.supportsElement(this.$refs.playerShell));
                    },

                    enterNativeFs() {
                        if (window.KzFs && this.$refs.playerShell) window.KzFs.enter(this.$refs.playerShell);
                    },

                    toggleNativeFs() {
                        if (!window.KzFs) return;
                        if (window.KzFs.isActive(this.$refs.playerShell)) window.KzFs.exit();
                        else this.enterNativeFs();
                    },

                    initPlayer() {
                        if (this.isEmbed || !this.videoSrc) return;
                        const artContainer = this.$refs.artContainer;
                        if (!artContainer) return;

                        if (this.art) {
                            try { this.art.destroy(false); } catch(e) {}
                            this.art = null;
                        }

                        const self = this;
                        const isHls = this.videoSrc.includes('.m3u8');

                        this.art = new Artplayer({
                            container: artContainer,
                            url: this.videoSrc,
                            type: isHls ? 'm3u8' : 'mp4',
                            theme: '#10B981',
                            volume: 0.8,
                            isLive: false,
                            muted: false,
                            autoplay: true,
                            pip: true,
                            setting: true,
                            loop: false,
                            flip: true,
                            playbackRate: true,
                            aspectRatio: true,
                            fullscreen: !self.useNativeFs(),
                            fullscreenWeb: !self.useNativeFs(),
                            miniProgressBar: true,
                            playsInline: true,
                            airplay: true,
                            hotkey: true,
                            customType: {
                                m3u8: function (video, url, art) {
                                    if (Hls.isSupported()) {
                                        if (art.hls) art.hls.destroy();
                                        const hls = new Hls({ enableWorker: true });
                                        hls.loadSource(url);
                                        hls.attachMedia(video);
                                        art.hls = hls;
                                        art.on('destroy', () => hls.destroy());
                                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                        video.src = url;
                                    }
                                }
                            },
                            controls: [
                                {
                                    name: 'kz-fs',
                                    position: 'right',
                                    index: 70,
                                    html: '<svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="14" height="10" rx="2"/><path d="M20 4v5h-5"/><path d="M20 9a8 8 0 0 0-8-6"/></svg>',
                                    tooltip: 'Layar penuh',
                                    style: self.useNativeFs() ? {} : { display: 'none' },
                                    click: () => self.toggleNativeFs(),
                                },
                                {
                                    position: 'left',
                                    index: 10,
                                    html: '<svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/></svg>',
                                    tooltip: 'Mundur 10 Detik',
                                    click: function (art) {
                                        art.seek = Math.max(0, art.currentTime - 10);
                                    },
                                },
                                {
                                    position: 'left',
                                    index: 11,
                                    html: '<svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/></svg>',
                                    tooltip: 'Maju 10 Detik',
                                    click: function (art) {
                                        art.seek = Math.min(art.duration || 999999, art.currentTime + 10);
                                    },
                                },
                            ],
                        });

                        this.art.on('play', () => {
                            if (self.useNativeFs() && window.KzFs && !KzFs.current()) {
                                self.enterNativeFs();
                            }
                        });
                    }
                };
            };
        })();
    </script>
@endpush
