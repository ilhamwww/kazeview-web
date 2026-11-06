@php
    $mTitle = $media['title'] ?? 'Film Indonesia';
    $mPoster = $media['poster'] ?? ($media['posterPath'] ?? null);
    $mBanner = $media['backdrop'] ?? ($media['banner'] ?? ($media['thumbnail'] ?? $mPoster));
    $mYear = $media['year'] ?? '';
    $mOverview = $media['overview'] ?? ($media['synopsis'] ?? '');
    $mRating = $media['voteAverage'] ?? ($media['rating'] ?? 8.0);
    $mRuntime = $media['duration'] ?? null;
    $genres = $media['genres'] ?? ['Indonesia'];
@endphp

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/artplayer/dist/artplayer.css">
    <style>
        .kz-noscroll::-webkit-scrollbar { display: none; }
        .kz-noscroll { -ms-overflow-style: none; scrollbar-width: none; }
        .art-video-player.art-fullscreen-web {
            z-index: 99999 !important;
            isolation: auto !important;
        }
        body:has(.art-fullscreen-web) header,
        html.kz-fullscreen-web-active header {
            display: none !important;
        }
    </style>
@endpush

<div class="min-h-screen bg-[#050505] text-white pb-20 select-none">

    {{-- Top bar Breadcrumb --}}
    <div class="relative w-full max-w-[1400px] mx-auto px-4 lg:px-8 pt-20 md:pt-24 pb-2 flex items-center justify-between">
        <a href="/indonesia/{{ $server }}"
           class="inline-flex items-center gap-1.5 text-xs md:text-sm font-semibold text-zinc-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-3 py-1.5 rounded-full transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke {{ $serverLabel }}
        </a>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 text-[11px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full bg-red-600/20 text-red-400 border border-red-500/30">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                {{ $serverLabel }}
            </span>
            <span class="hidden sm:inline-flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.2em] text-zinc-400">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Resource Terisolasi
            </span>
        </div>
    </div>

    <div class="relative w-full max-w-[1400px] mx-auto px-0 lg:px-8 space-y-8" x-data="watchPlayerInit()">

        {{-- TWO-COLUMN GRID --}}
        <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-8">

            {{-- LEFT COLUMN: PLAYER + SERVER SWITCHER + MOVIE INFO --}}
            <div class="min-w-0 space-y-4">
                {{-- Video Player Container --}}
                <div class="w-full bg-black lg:rounded-2xl overflow-hidden aspect-video relative isolate z-10 ring-1 ring-white/10 shadow-[0_30px_80px_-20px_rgba(0,0,0,0.9)]" x-ref="playerShell">
                    <div x-show="isPlaying && !isEmbed" class="w-full h-full relative" style="display: none;">
                        <div x-ref="artContainer" id="movieArtPlayer" class="w-full h-full"></div>
                    </div>

                    <div x-show="isPlaying && isEmbed" class="w-full h-full relative bg-black" style="display: none;">
                        <iframe :src="currentEmbedUrl" class="w-full h-full border-0" allowfullscreen webkitallowfullscreen mozallowfullscreen allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="no-referrer"></iframe>
                    </div>

                    <div x-show="isLoadingStream" x-cloak class="absolute inset-0 z-30 bg-black/90 backdrop-blur-md flex flex-col items-center justify-center p-6 text-center">
                        @include('filament.streaming.partials.server-loading-indicator', [
                            'title' => 'Memuat stream ' . $serverLabel,
                            'subtitle' => 'Mengambil jalur streaming tercepat & bebas gangguan',
                        ])
                    </div>

                    <div x-show="!isPlaying && !isLoadingStream" class="absolute inset-0 z-20">
                        @if($mBanner)
                            <img src="{{ $mBanner }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                        @endif
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black via-black/40 to-black/10"></div>
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-black/80 via-transparent to-transparent"></div>

                        <button type="button" @click="playVideo()" aria-label="Putar {{ $mTitle }}"
                                class="absolute inset-0 m-auto w-20 h-20 md:w-24 md:h-24 rounded-full bg-white/15 hover:bg-red-600 backdrop-blur-md ring-1 ring-white/40 flex items-center justify-center transition duration-300 hover:scale-110 group cursor-pointer z-20">
                            <span class="pointer-events-none absolute inset-0 rounded-full ring-2 ring-white/20 animate-ping"></span>
                            <svg class="pointer-events-none w-9 h-9 md:w-11 md:h-11 fill-white ml-1.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                        </button>

                        <div class="absolute left-4 right-4 bottom-4 md:left-8 md:bottom-7 flex items-end justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-[10px] md:text-xs font-black uppercase tracking-[0.2em] text-red-500">{{ $isSeries ? 'Series' : 'Film' }} • {{ $serverLabel }}</p>
                                <p class="text-lg md:text-3xl font-black tracking-tight line-clamp-1 drop-shadow-lg">{{ $mTitle }}</p>
                            </div>
                            @if($mRuntime)
                                <span class="hidden sm:inline shrink-0 text-xs font-bold bg-black/60 backdrop-blur px-2.5 py-1 rounded-full border border-white/10">{{ $mRuntime }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Server Switcher Khusus Server Ini (TIDAK DIGABUNG) --}}
                @if(!empty($servers))
                    <div class="px-4 lg:px-0 flex items-center gap-2 overflow-x-auto kz-noscroll pt-1 pb-1">
                        <span class="text-xs font-bold text-zinc-400 mr-1 flex items-center gap-1.5 shrink-0">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.7 7.2a3 3 0 012.4-1.2h7.8a3 3 0 012.4 1.2l2.55 4.35A4.5 4.5 0 0121 14.25"/></svg>
                            Pilihan Server ({{ $serverLabel }}):
                        </span>
                        @foreach($servers as $s)
                            <button type="button"
                                    @click="switchServer('{{ $s['id'] }}', '{{ $s['embed_url'] ?? '' }}', {{ ($s['type'] ?? '') === 'embed' ? 'true' : 'false' }})"
                                    :class="activeServer === '{{ $s['id'] }}' ? 'bg-red-600 text-white ring-red-500 shadow-lg shadow-red-600/30' : 'bg-zinc-900 text-zinc-300 ring-white/10 hover:text-white'"
                                    class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold ring-1 transition flex items-center gap-1.5 cursor-pointer">
                                <span class="w-1.5 h-1.5 rounded-full" :class="activeServer === '{{ $s['id'] }}' ? 'bg-white' : 'bg-zinc-500'"></span>
                                {{ $s['name'] }}
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Tips Bebas Iklan untuk Embed --}}
                <div x-show="isEmbed" class="px-4 lg:px-0 flex items-center gap-2 text-[11px] text-zinc-400 bg-amber-500/10 border border-amber-500/20 rounded-xl px-3.5 py-2 -mt-1">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <span><strong>Tips Bebas Iklan</strong>: Server mirror menggunakan iframe luar. Disarankan gunakan browser <strong>Brave</strong> atau pasang <strong>uBlock Origin / AdGuard</strong> agar 100% bebas iklan dan pop-up.</span>
                </div>

                {{-- Jika Series: Daftar Episode --}}
                @if($isSeries && !empty($episodes))
                    <div class="px-4 lg:px-0 pt-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold tracking-tight text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                                Daftar Episode
                            </h3>
                            <span class="text-xs text-zinc-400">{{ count($episodes) }} Episode Tersedia</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                            @foreach($episodes as $ep)
                                @php
                                    $epNumber = $ep['episode_number'] ?? $loop->iteration;
                                @endphp
                                <button type="button"
                                        @click="changeEpisode({{ $epNumber }})"
                                        :class="currentEp === {{ $epNumber }} ? 'bg-red-600/20 border-red-500 text-white shadow-sm ring-1 ring-red-500' : 'bg-zinc-900/60 hover:bg-zinc-800 border-zinc-800 text-zinc-300'"
                                        class="p-2.5 rounded-xl border text-left transition flex flex-col justify-between cursor-pointer">
                                    <div class="flex items-center justify-between gap-1 mb-1">
                                        <span class="text-xs font-black" :class="currentEp === {{ $epNumber }} ? 'text-red-400' : 'text-zinc-400'">EPS {{ $epNumber }}</span>
                                        <span x-show="currentEp === {{ $epNumber }}" class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                                    </div>
                                    <span class="text-xs font-medium line-clamp-1 text-zinc-200">{{ $ep['title'] ?? ('Episode ' . $epNumber) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Movie Details Block --}}
                <div class="px-4 lg:px-0 pt-2 space-y-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-bold text-zinc-400 mb-1">
                            <span class="px-2 py-0.5 rounded bg-red-600 text-white font-black text-[10px] uppercase tracking-wider">{{ $isSeries ? 'Series' : 'Movie' }}</span>
                            @if($mYear) <span>{{ $mYear }}</span> • @endif
                            @if($mRating) <span class="text-amber-400 font-bold">★ {{ number_format((float)$mRating, 1) }}</span> • @endif
                            <span class="text-zinc-400">{{ $serverLabel }}</span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">{{ $mTitle }}</h1>
                    </div>

                    @if(!empty($genres))
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            @foreach($genres as $g)
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300">{{ is_array($g) ? ($g['name'] ?? '') : $g }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if($mOverview)
                        <div class="space-y-1.5 pt-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Sinopsis</h4>
                            <p class="text-sm leading-relaxed text-zinc-300 text-justify">{{ $mOverview }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT COLUMN: SIDEBAR POSTER & CONTROLS --}}
            <div class="px-4 lg:px-0 space-y-6 mt-6 lg:mt-0">
                <div class="bg-zinc-900/40 rounded-2xl p-4 border border-zinc-800/80 space-y-4">
                    @if($mPoster)
                        <div class="aspect-[2/3] w-full rounded-xl overflow-hidden ring-1 ring-white/10 shadow-lg relative group">
                            <img src="{{ $mPoster }}" alt="{{ $mTitle }}" class="w-full h-full object-cover">
                            <div class="absolute top-2 left-2 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-red-600 text-white">
                                {{ $serverLabel }}
                            </div>
                        </div>
                    @endif

                    <div class="space-y-2 pt-2">
                        <div class="flex items-center justify-between text-xs text-zinc-400 py-1.5 border-b border-zinc-800/60">
                            <span>Sumber Server</span>
                            <span class="font-bold text-white">{{ $serverLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-zinc-400 py-1.5 border-b border-zinc-800/60">
                            <span>Status Isolasi</span>
                            <span class="font-bold text-emerald-400">Terpisah (Murni)</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-zinc-400 py-1.5 border-b border-zinc-800/60">
                            <span>Tahun</span>
                            <span class="font-bold text-white">{{ $mYear ?: '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-zinc-400 py-1.5">
                            <span>Rating</span>
                            <span class="font-bold text-amber-400">★ {{ number_format((float)$mRating, 1) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/artplayer/dist/artplayer.js"></script>
    <script>
        (function () {
            function initWatchIndonesiaComponent() {
                const componentDefinition = () => ({
                    art: null,
                    hls: null,
                    isPlaying: false,
                    isLoadingStream: false,
                    isEmbed: {{ !empty($servers[0]['type']) && $servers[0]['type'] === 'embed' ? 'true' : 'false' }},
                    serversList: @js($servers),
                    activeServer: '{{ $servers[0]["id"] ?? "server1" }}',
                    currentEmbedUrl: {!! json_encode($servers[0]['embed_url'] ?? '') !!},
                    videoSrc: {!! json_encode($media['video_url'] ?? '') !!},
                    subtitles: @js($media['subtitles'] ?? []),
                    episodesList: @js($episodes),
                    currentEp: {{ (int) ($currentEpisode ?? 1) }},

                    changeEpisode(epNum) {
                        if (this.art && this.art.video) {
                            this.art.video.dispatchEvent(new Event('pause'));
                        }
                        this.currentEp = epNum;
                        const ep = Array.isArray(this.episodesList) ? this.episodesList.find(e => (e.episode_number == epNum || e.episodeNumber == epNum)) : null;
                        if (ep) {
                            if (Array.isArray(ep.servers) && ep.servers.length > 0) {
                                this.serversList = ep.servers;
                                this.activeServer = ep.servers[0].id;
                                this.isEmbed = true;
                                this.currentEmbedUrl = ep.servers[0].embed_url || '';
                                if (this.art) {
                                    try { this.art.destroy(false); } catch(e) {}
                                    this.art = null;
                                }
                                this.isPlaying = true;
                            } else if (ep.stream_url) {
                                this.isEmbed = false;
                                this.videoSrc = ep.stream_url;
                                this.subtitles = ep.subtitles || [];
                                if (this.art) {
                                    this.art.switchUrl(this.videoSrc);
                                    this.art.play();
                                } else {
                                    this.isPlaying = true;
                                    this.$nextTick(() => {
                                        this.initArtPlayer();
                                    });
                                }
                            }
                        }
                    },

                    init() {
                        // Player siap
                    },

                    switchServer(id, embedUrl, isEmbedServer) {
                        const target = Array.isArray(this.serversList) ? this.serversList.find(s => s.id === id) : null;
                        if (target) {
                            this.activeServer = target.id;
                            this.isEmbed = target.type === 'embed';
                            this.currentEmbedUrl = target.embed_url || '';
                            if (!this.isEmbed && (target.type === 'mp4' || target.type === 'hls' || target.video_url)) {
                                this.videoSrc = target.video_url || this.videoSrc;
                            }
                        } else {
                            this.activeServer = id;
                            this.isEmbed = isEmbedServer;
                            this.currentEmbedUrl = embedUrl;
                        }

                        if (this.isEmbed) {
                            if (this.art) {
                                try { this.art.destroy(false); } catch(e) {}
                                this.art = null;
                            }
                            this.isPlaying = true;
                        } else {
                            this.isPlaying = true;
                            this.$nextTick(() => {
                                this.initArtPlayer();
                            });
                        }
                    },

                    playVideo() {
                        if (this.isEmbed) {
                            this.isPlaying = true;
                            return;
                        }

                        this.isPlaying = true;
                        this.$nextTick(() => {
                            this.initArtPlayer();
                        });
                    },

                    initArtPlayer() {
                        const container = this.$refs.artContainer || document.getElementById('movieArtPlayer');
                        if (!container || !this.videoSrc) return;

                        if (this.art) {
                            try { this.art.destroy(false); } catch(e) {}
                            this.art = null;
                        }

                        const self = this;
                        const progressMeta = {
                            item_id: '{{ $slugParam }}',
                            item_type: {{ $isSeries ? '"indonesia_series"' : '"indonesia_movie"' }},
                            episode: String(this.currentEp || 1),
                            title: {!! json_encode($mTitle ?? '') !!},
                            thumbnail: {!! json_encode($mPoster ?? '') !!},
                            url: window.location.pathname,
                        };
                        let lastSavedTime = 0;
                        let lastRecordedEpisode = String(this.currentEp || 1);
                        const csrfToken = '{{ csrf_token() }}';
                        const saveProgress = (force = false) => {
                            const video = self.art?.video;
                            if (!video) return;
                            const currentTime = self.art.currentTime || video.currentTime || 0;
                            const duration = self.art.duration || video.duration || 0;
                            if (duration < 10 || currentTime < 5) return;
                            if (!force && Math.abs(currentTime - lastSavedTime) < 8) return;
                            lastSavedTime = currentTime;
                            const payload = {
                                ...progressMeta,
                                episode: String(self.currentEp || 1),
                                progress: currentTime,
                                duration,
                            };
                            const body = JSON.stringify(payload);
                            if (force && navigator.sendBeacon) {
                                const beaconBody = new FormData();
                                Object.entries(payload).forEach(([key, value]) => beaconBody.append(key, String(value)));
                                beaconBody.append('_token', csrfToken);
                                navigator.sendBeacon('/api/watch-progress', beaconBody);
                                return;
                            }
                            fetch('/api/watch-progress', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                                body,
                                keepalive: force,
                            }).catch(() => {});
                        };
                        const isHlsSource = this.videoSrc && (this.videoSrc.includes('.m3u8') || this.videoSrc.includes('/master') || this.videoSrc.includes('moviebox'));
                        const artConfig = {
                            container: container,
                            url: this.videoSrc,
                            type: isHlsSource ? 'm3u8' : 'mp4',
                            autoplay: true,
                            pip: true,
                            fullscreen: true,
                            fullscreenWeb: true,
                            theme: '#E50914',
                            icons: {
                                loading: '<div class="w-10 h-10 border-4 border-red-600 border-t-transparent rounded-full animate-spin"></div>'
                            }
                        };
                        if (isHlsSource) {
                            artConfig.customType = {
                                m3u8: function (video, url, artInstance) {
                                    if (window.Hls && window.Hls.isSupported()) {
                                        if (self.hls) {
                                            try { self.hls.destroy(); } catch(e) {}
                                        }
                                        const hls = new window.Hls({
                                            enableWorker: true,
                                            lowLatencyMode: false,
                                            maxBufferLength: 60,
                                            backBufferLength: 30,
                                            defaultAudioCodec: 'mp4a.40.2',
                                        });
                                        self.hls = hls;
                                        artInstance.hls = hls;

                                        hls.on(window.Hls.Events.MANIFEST_LOADED, function (event, data) {
                                            if (data.levels && data.levels.length > 0) {
                                                data.levels.forEach(function (l) {
                                                    l.videoCodec = l.videoCodec || 'avc1.640028';
                                                    l.audioCodec = l.audioCodec || 'mp4a.40.2';
                                                    if (l.attrs) {
                                                        l.attrs.CODECS = l.attrs.CODECS || 'avc1.640028,mp4a.40.2';
                                                    }
                                                });
                                            }
                                        });

                                        hls.on(window.Hls.Events.BUFFER_CODECS, function (event, data) {
                                            if (data.tracks) {
                                                if (data.tracks.video && !data.tracks.video.codec) {
                                                    data.tracks.video.codec = 'avc1.640028';
                                                }
                                                if (data.tracks.audio && !data.tracks.audio.codec) {
                                                    data.tracks.audio.codec = 'mp4a.40.2';
                                                }
                                            }
                                        });

                                        hls.loadSource(url);
                                        hls.attachMedia(video);

                                        hls.on(window.Hls.Events.MANIFEST_PARSED, function () {
                                            video.play().catch(() => {});
                                            if (artInstance && artInstance.loading) {
                                                artInstance.loading.show = false;
                                            }
                                        });

                                        hls.on(window.Hls.Events.ERROR, function (event, data) {
                                            console.error('[HLS_ERROR_DETAIL]', data.details, 'mimeType:', data.mimeType, 'err:', data.error ? data.error.message : null);
                                            if (data.details === 'bufferAddCodecError' || (data.error && /unsupported/i.test(data.error.message || ''))) {
                                                if (artInstance && artInstance.notice) {
                                                    artInstance.notice.show = 'Format HEVC Ultra HD: Disarankan putar di HP (Chrome/Safari) dengan akselerasi hardware.';
                                                }
                                                if (artInstance && artInstance.loading) {
                                                    artInstance.loading.show = false;
                                                }
                                            }
                                        });

                                        video.addEventListener('timeupdate', function () {
                                            if (artInstance && artInstance.loading) {
                                                artInstance.loading.show = false;
                                            }
                                        });

                                        video.addEventListener('playing', function () {
                                            if (artInstance && artInstance.loading) {
                                                artInstance.loading.show = false;
                                            }
                                        });
                                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                        video.src = url;
                                        video.play().catch(() => {});
                                    }
                                }
                            };
                        }

                        this.art = new Artplayer(artConfig);

                        this.art.on('ready', () => {
                            const v = self.art && self.art.video;
                            const pr = v && v.play ? v.play() : null;
                            if (pr && pr.catch) {
                                pr.catch((err) => {
                                    self.art.muted = true;
                                    Promise.resolve(self.art.play()).catch(() => {});
                                    self.art.notice.show = 'Ketuk ikon suara untuk menyalakan audio';
                                });
                            }
                        });

                        this.art.on('video:playing', () => {
                            if (self.art && self.art.loading) {
                                self.art.loading.show = false;
                            }
                        });

                        this.art.on('video:timeupdate', () => {
                            const currentEpisode = String(self.currentEp || 1);
                            if (currentEpisode !== lastRecordedEpisode) {
                                lastRecordedEpisode = currentEpisode;
                                lastSavedTime = 0;
                            }
                            if (self.art && !self.art.video?.paused) saveProgress(false);
                        });
                        this.art.on('video:pause', () => saveProgress(true));
                        this.art.on('video:ended', () => saveProgress(true));
                        this.art.on('destroy', () => saveProgress(true));
                    }
                });

                if (window.Alpine) {
                    Alpine.data('watchPlayerInit', componentDefinition);
                } else {
                    document.addEventListener('alpine:init', () => {
                        Alpine.data('watchPlayerInit', componentDefinition);
                    });
                }
            }

            initWatchIndonesiaComponent();
        })();
    </script>
@endpush
