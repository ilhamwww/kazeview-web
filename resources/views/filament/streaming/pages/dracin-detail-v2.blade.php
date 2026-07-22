{{-- Fullscreen vertical player Dracin V2 untuk short drama. --}}
{{-- Menutupi topbar Filament via position: fixed + z-index tinggi. --}}
<div class="fixed inset-0 z-[100] bg-black text-white overflow-hidden"
     x-data="dracinVerticalPlayer()"
     x-init="init()"
     wire:init="autoPlayMelolo"
     wire:ignore.self
     @pointermove.throttle.150ms="showControls()"
     @pointerdown="showControls()"
     @focusin="showControls()"
     @keydown.window="showControls()">

    {{-- Preload data drama untuk Alpine (episode list drawer) --}}
    <script type="application/json" id="dracin-v2-drama-data">
        @php
            $totalEps = $drama['episodes'] ?? 0;
            $videos = $drama['videos'] ?? [];
            $episodeList = [];
            if (!empty($videos)) {
                foreach ($videos as $v) {
                    $episodeList[] = [
                        'episode' => (int) $v['episode'],
                        'is_lock' => !empty($v['is_lock']),
                        'duration' => $v['duration'] ?? null,
                    ];
                }
            } elseif ($totalEps > 0) {
                for ($i = 1; $i <= $totalEps; $i++) {
                    $episodeList[] = ['episode' => $i, 'is_lock' => false, 'duration' => null];
                }
            }

            $shortmaxDramaData = [
                'title' => $drama['title'] ?? '',
                'intro' => $drama['intro'] ?? '',
                'totalEpisodes' => $totalEps,
                'episodeList' => $episodeList,
                'watchedEpisodes' => $watchedEpisodes ?? [],
                'lastWatchedEpisode' => $lastWatchedEpisode ?? null,
                'isFavorited' => $isFavorited ?? false,
            ];
        @endphp
        {!! json_encode(
            $shortmaxDramaData,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
        ) !!}
    </script>

    {{-- Stage: layar hitam penuh dengan video di tengah (pillarbox pada desktop) --}}
    <div class="w-full h-full flex items-center justify-center relative">

        {{-- Wrapper video 9:16 --}}
        <div class="relative h-full aspect-[9/16] max-w-full max-h-screen bg-black overflow-hidden select-none">

            {{-- Elemen video --}}
            <video x-ref="video"
                   class="absolute inset-0 w-full h-full object-contain bg-black"
                   @click="togglePlayPause()"
                   playsinline></video>

            {{-- Overlay klik-tengah play/pause (untuk area video) --}}
            <div x-show="showCenterIndicator"
                 x-transition.opacity.duration.300ms
                 class="absolute inset-0 flex items-center justify-center pointer-events-none z-20"
                 style="display: none;">
                <div class="bg-black/50 rounded-full p-6">
                    <svg x-show="isPaused" class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <svg x-show="!isPaused" class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 4h4v16H6zm8 0h4v16h-4z" />
                    </svg>
                </div>
            </div>

            {{-- Loading spinner --}}
            <div x-show="isBuffering"
                 class="absolute inset-0 flex items-center justify-center pointer-events-none z-20"
                 style="display: none;">
                <div class="w-12 h-12 border-4 border-white/30 border-t-white rounded-full animate-spin"></div>
            </div>

            {{-- Unmute banner --}}
            <div x-show="isMutedAutoplay"
                 class="absolute inset-0 z-40 pointer-events-none"
                 style="display: none;">
                <button type="button"
                        @click.stop="unmute()"
                        class="absolute pointer-events-auto whitespace-nowrap bg-red-600 hover:bg-red-700 text-white font-bold text-xs md:text-sm px-4 py-2 rounded-full flex items-center gap-2 shadow-lg animate-pulse"
                        style="left: 50%; top: 50%; transform: translate(-50%, -50%);">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z" />
                    </svg>
                    Ketuk Untuk Bersuara
                </button>
            </div>

            {{-- Header: back + title + eps count --}}
            <div x-show="controlsVisible"
                 x-transition.opacity.duration.200ms
                 class="absolute top-0 left-0 right-0 z-30 p-4 flex items-start gap-3 bg-gradient-to-b from-black/70 via-black/30 to-transparent pointer-events-none">
                <a href="{{ \App\Filament\Streaming\Pages\DracinNetwork::getUrl(['slug' => $networkSlug], panel: 'streaming') }}"
                   class="pointer-events-auto w-9 h-9 flex items-center justify-center rounded-full hover:bg-white/10 transition"
                   title="Kembali">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <div class="flex-1 min-w-0 mt-1">
                    <p class="text-sm md:text-base font-bold truncate drop-shadow-md">
                        {{ $drama['title'] ?? 'Detail Drama' }}
                    </p>
                </div>

                <div class="pointer-events-auto flex flex-col items-end gap-1.5">
                    <span class="text-[11px] md:text-xs font-semibold text-white/80 drop-shadow-md">
                        Eps <span x-text="currentEpisode"></span>/<span x-text="drama.totalEpisodes"></span>
                    </span>
                    <button @click="showEpisodes = true"
                            class="bg-black/40 backdrop-blur border border-white/20 hover:bg-black/60 rounded-full px-3 py-1 text-xs font-bold flex items-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span><span x-text="currentEpisode"></span>/<span x-text="drama.totalEpisodes"></span></span>
                    </button>
                </div>
            </div>

            {{-- Floating action panel: kanan tengah --}}
            <div x-show="controlsVisible"
                 x-transition.opacity.duration.200ms
                 class="absolute right-3 top-1/2 -translate-y-1/2 z-30 flex flex-col items-center gap-5 pointer-events-none">
                <div class="flex flex-col items-center gap-1 pointer-events-auto">
                    <button @click="showEpisodes = true"
                            class="w-12 h-12 rounded-full bg-black/40 backdrop-blur border border-white/20 hover:bg-black/60 flex items-center justify-center transition"
                            title="Episode">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span class="text-[11px] font-semibold drop-shadow-md">Episode</span>
                </div>

                <div class="flex flex-col items-center gap-1 pointer-events-auto">
                    <button @click="toggleMute()"
                            class="w-12 h-12 rounded-full bg-black/40 backdrop-blur border border-white/20 hover:bg-black/60 flex items-center justify-center transition"
                            :title="muted ? 'Nyalakan suara' : 'Bisukan'">
                        <svg x-show="!muted" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072M17.828 5.172a9 9 0 010 13.656M11 5L6 9H2v6h4l5 4V5z" />
                        </svg>
                        <svg x-show="muted" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5L6 9H2v6h4l5 4V5zM23 9l-6 6M17 9l6 6" />
                        </svg>
                    </button>
                    <span class="text-[11px] font-semibold drop-shadow-md" x-text="muted ? 'Bisu' : 'Suara'"></span>
                </div>

                <div class="flex flex-col items-center gap-1 pointer-events-auto">
                    <button wire:click="toggleFavorite"
                            class="w-12 h-12 rounded-full backdrop-blur border transition flex items-center justify-center
                                {{ ($isFavorited ?? false) ? 'bg-red-600 border-red-500 hover:bg-red-700' : 'bg-black/40 border-white/20 hover:bg-black/60' }}"
                            title="Favorit">
                        @if($isFavorited ?? false)
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        @endif
                    </button>
                    <span class="text-[11px] font-semibold drop-shadow-md">{{ ($isFavorited ?? false) ? 'Favorit' : 'Simpan' }}</span>
                </div>
            </div>

            {{-- Bottom info panel: title, intro, progress, quality, episode card --}}
            <div x-show="controlsVisible"
                 x-transition.opacity.duration.200ms
                 class="absolute bottom-0 left-0 right-0 z-30 bg-gradient-to-t from-black/95 via-black/70 to-transparent p-4 pt-16 space-y-3">
                <p class="font-bold text-sm md:text-base leading-tight">
                    {{ $drama['title'] ?? '' }} - Eps <span x-text="currentEpisode"></span>
                </p>

                @if(!empty($drama['intro']))
                    <p class="text-[11px] md:text-xs text-white/70 leading-snug line-clamp-2">
                        {{ $drama['intro'] }}
                    </p>
                @endif

                {{-- Progress bar --}}
                <div class="flex items-center gap-2 text-[11px] font-mono">
                    <span x-text="fmtTime(currentTime)" class="w-9 text-white/80"></span>
                    <div class="flex-1 h-1 bg-white/20 rounded-full cursor-pointer relative group"
                         @click="seek($event)">
                        <div class="absolute inset-y-0 left-0 bg-red-600 rounded-full transition-[width] duration-100"
                             :style="`width: ${progressPercent}%`"></div>
                        <div class="absolute top-1/2 -translate-y-1/2 w-3 h-3 rounded-full bg-red-600 opacity-0 group-hover:opacity-100 transition"
                             :style="`left: calc(${progressPercent}% - 6px)`"></div>
                    </div>
                    <span x-text="fmtTime(duration)" class="w-9 text-white/80 text-right"></span>
                </div>

                {{-- Quality selector --}}
                <div class="flex flex-wrap items-center gap-2">
                    <template x-for="q in qualityList" :key="q.label">
                        <button @click="changeQuality(q)"
                                :class="currentQualityLabel === q.label
                                    ? 'bg-red-600 text-white border-red-600'
                                    : 'bg-white/10 text-white/80 border-white/10 hover:bg-white/20'"
                                class="px-3 py-1 rounded-md text-[11px] font-bold border transition">
                            <span x-text="q.label"></span>
                        </button>
                    </template>
                </div>

                {{-- Episode card (shortcut to drawer) --}}
                <button @click="showEpisodes = true"
                        class="w-full bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg p-3 flex items-center justify-between text-left transition">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="text-xs font-semibold">Episode <span x-text="currentEpisode"></span></span>
                    </div>
                    <svg class="w-4 h-4 text-white/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Drawer daftar episode --}}
    <div x-show="showEpisodes"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="showEpisodes = false"
         class="fixed inset-0 z-[110] bg-black/70 backdrop-blur-sm"
         style="display: none;">
        <div x-show="showEpisodes"
             x-transition:enter="transition transform duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition transform duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="absolute right-0 top-0 bottom-0 w-full max-w-md bg-[#0e0e0f] border-l border-white/10 flex flex-col">

            <div class="flex items-center justify-between p-5 border-b border-white/10 flex-shrink-0">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider">Pilih Episode</h2>
                    <p class="text-[10px] text-white/50 mt-0.5">
                        <span x-text="drama.totalEpisodes"></span> episode
                    </p>
                </div>
                <button @click="showEpisodes = false"
                        class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                @if(!empty($lastWatchedEpisode))
                    <div class="p-3 mb-4 bg-red-600/10 border border-red-500/25 rounded-xl flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-[9px] text-red-400/80 font-bold uppercase tracking-wider">Terakhir Ditonton</p>
                            <p class="text-xs font-bold text-red-300 mt-0.5">Episode {{ $lastWatchedEpisode }}</p>
                        </div>
                        <button wire:click="playEpisode({{ (int) $lastWatchedEpisode }})"
                                @click="showEpisodes = false"
                                class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-[10px] font-bold transition flex-shrink-0">
                            Lanjutkan
                        </button>
                    </div>
                @endif

                <div class="grid grid-cols-4 sm:grid-cols-5 gap-2">
                    <template x-for="ep in drama.episodeList" :key="ep.episode">
                        <button @click="pickEpisode(ep.episode)"
                                :class="{
                                    'bg-red-600 text-white border-red-600 shadow-md shadow-red-600/20': ep.episode === currentEpisode,
                                    'bg-white/5 hover:bg-white/10 border-white/10 text-white/80': ep.episode !== currentEpisode && !isWatched(ep.episode),
                                    'bg-white/[0.02] hover:bg-white/10 border-white/10 text-white/60': ep.episode !== currentEpisode && isWatched(ep.episode),
                                }"
                                class="p-2 rounded-lg border font-bold text-center text-[11px] h-12 flex items-center justify-center relative">
                            <span>Ep <span x-text="ep.episode"></span></span>
                            <template x-if="isWatched(ep.episode) && ep.episode !== currentEpisode">
                                <svg class="w-2.5 h-2.5 text-emerald-400 absolute top-1 left-1" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        /* Sembunyikan navbar Filament di halaman shortmax player */
        body>.fi-topbar,
        body>header,
        nav[aria-label="Global"] {
            display: none !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="/aes-js.min.js"></script>
    <script src="/melolo-decrypt.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/shaka-player/4.7.9/shaka-player.compiled.js"></script>
    {{-- hls.js dipakai khusus Raptdrama: audio stream Bunny ber-MIME octet-stream
         ditolak Shaka/MSE, tetapi berhasil ditransmux oleh hls.js. --}}
    <script defer src="https://cdn.jsdelivr.net/npm/hls.js@1.5.18/dist/hls.min.js"></script>
    <script>
        let meloloSwReady = false;

        async function initMeloloServiceWorker() {
            if (!('serviceWorker' in navigator)) {
                return false;
            }

            try {
                await navigator.serviceWorker.register('/melolo-sw.js');

                if (!navigator.serviceWorker.controller) {
                    await new Promise((resolve) => {
                        navigator.serviceWorker.addEventListener(
                            'controllerchange',
                            resolve,
                            { once: true },
                        );
                    });
                }

                meloloSwReady = true;

                return true;
            } catch (error) {
                console.warn('Service Worker Melolo tidak tersedia:', error);

                return false;
            }
        }

        initMeloloServiceWorker();
    </script>
    <script>
        function registerDracinVerticalPlayer() {
            Alpine.data('dracinVerticalPlayer', () => ({
                // State player
                player: null,
                videoUrl: '',
                qualityList: [],
                currentQualityLabel: '',
                currentEpisode: {{ (int) ($lastWatchedEpisode ?? 1) }},
                nextEpisode: null,
                subtitles: [],
                muted: false,
                isMutedAutoplay: false,
                isPaused: true,
                isBuffering: false,
                currentTime: 0,
                duration: 0,
                showEpisodes: false,
                showCenterIndicator: false,
                centerIndicatorTimeout: null,
                controlsVisible: true,
                controlsTimer: null,
                meloloBlobUrl: null,
                playerEngine: null,
                useHlsJs: {{ $networkSlug === 'raptdrama' ? 'true' : 'false' }},

                // Metadata dari server (di-hydrate dari <script> tag)
                drama: {
                    title: '',
                    intro: '',
                    totalEpisodes: 0,
                    episodeList: [],
                    watchedEpisodes: [],
                    lastWatchedEpisode: null,
                    isFavorited: false,
                },

                get progressPercent() {
                    if (!this.duration) return 0;
                    return Math.min(100, Math.max(0, (this.currentTime / this.duration) * 100));
                },

                init() {
                    try {
                        const raw = document.getElementById('dracin-v2-drama-data')?.textContent;
                        if (raw) this.drama = { ...this.drama, ...JSON.parse(raw) };
                    } catch (e) {
                        console.warn('Failed to parse drama data', e);
                    }

                    window.addEventListener('init-player', (e) => this.handleInitPlayer(e));
                    this.$watch('showEpisodes', () => this.showControls());

                    this.$nextTick(() => {
                        this.attachVideoEvents();
                        this.showControls();
                    });
                },

                attachVideoEvents() {
                    const v = this.$refs.video;
                    if (!v) return;

                    v.addEventListener('timeupdate', () => { this.currentTime = v.currentTime; });
                    v.addEventListener('durationchange', () => { this.duration = v.duration || 0; });
                    v.addEventListener('loadedmetadata', () => { this.duration = v.duration || 0; });
                    v.addEventListener('play', () => {
                        this.isPaused = false;
                        this.showControls();
                    });
                    v.addEventListener('pause', () => {
                        this.isPaused = true;
                        this.showControls();
                    });
                    v.addEventListener('waiting', () => {
                        this.isBuffering = true;
                        this.showControls();
                    });
                    v.addEventListener('canplay', () => {
                        this.isBuffering = false;
                        this.showControls();
                    });
                    v.addEventListener('playing', () => {
                        this.isBuffering = false;
                        this.showControls();
                    });
                    v.addEventListener('volumechange', () => { this.muted = v.muted; });
                    v.addEventListener('ended', () => {
                        if (this.nextEpisode) {
                            this.$wire.playEpisode(this.nextEpisode);
                        }
                    });
                },

                handleInitPlayer(e) {
                    this.showControls();

                    let payload = e.detail;
                    if (payload && payload[0]) payload = payload[0]; // fallback array style

                    this.videoUrl = payload.videoUrl || '';
                    this.qualityList = Array.isArray(payload.qualityList) ? payload.qualityList : [];
                    const defaultQ = this.qualityList.find(q => q.isDefault) || this.qualityList[0];
                    this.currentQualityLabel = defaultQ?.label || 'HD';
                    this.currentEpisode = payload.episode || this.currentEpisode || 1;
                    this.nextEpisode = payload.nextEpisode || null;
                    this.subtitles = Array.isArray(payload.subtitles) ? payload.subtitles : [];

                    // Track episode sebagai "watched" secara lokal juga
                    const epStr = String(this.currentEpisode);
                    if (!this.drama.watchedEpisodes.includes(epStr)) {
                        this.drama.watchedEpisodes = [...this.drama.watchedEpisodes, epStr];
                    }

                    this.$nextTick(() => this.initPlayer());
                },

                async initPlayer(resumeTime = 0, shouldPlay = true) {
                    if (!this.videoUrl) return;

                    const video = this.$refs.video;
                    if (!video) return;

                    // Generation guard: mencegah dua load() berjalan bersamaan (Shaka error 7002
                    // LOAD_INTERRUPTED lalu media error 3015). Setiap init baru membatalkan yang lama.
                    const generation = (this._loadGeneration || 0) + 1;
                    this._loadGeneration = generation;
                    const isStale = () => this._loadGeneration !== generation;

                    this.isMutedAutoplay = false;

                    const currentQuality = this.qualityList.find(
                        (quality) => quality.label === this.currentQualityLabel,
                    );
                    const isEncryptedMelolo = Boolean(currentQuality?.kid);

                    // Cleanup player sebelumnya sebelum berpindah stream atau episode.
                    if (this.player) {
                        const previousPlayer = this.player;
                        this.player = null;
                        this.playerEngine = null;

                        try {
                            await previousPlayer.destroy();
                        } catch (error) {
                            console.warn('Gagal membersihkan player lama:', error);
                        }
                    }

                    // Init yang lebih baru sudah mengambil alih; hentikan yang ini.
                    if (isStale()) return;

                    // Stream Raptdrama memisahkan audio AAC yang dilayani dengan MIME
                    // application/octet-stream. Shaka meneruskan MIME ini ke MSE dan
                    // gagal (3015); hls.js mentransmux stream tersebut dengan benar.
                    if (this.useHlsJs) {
                        await this.playWithHlsJs(
                            video,
                            resumeTime,
                            shouldPlay,
                            generation,
                        );

                        return;
                    }

                    if (isEncryptedMelolo) {
                        await this.playEncryptedMelolo(
                            video,
                            currentQuality,
                            resumeTime,
                            shouldPlay,
                        );

                        return;
                    }

                    if (!window.shaka) {
                        setTimeout(() => {
                            if (isStale()) return;
                            this.initPlayer(resumeTime, shouldPlay);
                        }, 100);

                        return;
                    }

                    shaka.polyfill.installAll();

                    if (!shaka.Player.isBrowserSupported()) {
                        console.error('Browser tidak mendukung Shaka Player');

                        return;
                    }

                    const player = new shaka.Player(video);
                    this.player = player;
                    this.playerEngine = 'shaka';
                    player.configure({
                        streaming: {
                            bufferingGoal: 60,
                            rebufferingGoal: 2,
                            bufferBehind: 30,
                            retryParameters: { timeout: 30000, maxAttempts: 5, baseDelay: 500, backoffFactor: 2 },
                        },
                        manifest: {
                            retryParameters: { timeout: 20000, maxAttempts: 3 },
                        },
                    });

                    this.isBuffering = true;

                    try {
                        await player.load(this.videoUrl);

                        // Jika init lain sudah mengambil alih selama load, abaikan hasil ini.
                        if (isStale()) return;

                        await this.loadSubtitleTracks();

                        if (resumeTime > 0 && Number.isFinite(resumeTime)) {
                            video.currentTime = resumeTime;
                        }

                        if (shouldPlay) {
                            this.attemptPlay(video);
                        } else {
                            this.isBuffering = false;
                        }
                    } catch (error) {
                        // LOAD_INTERRUPTED (7002) akibat init yang lebih baru bukan error nyata.
                        if (isStale() || error?.code === 7002) {
                            return;
                        }

                        console.error('Shaka load error', error);
                        this.isBuffering = false;
                    }
                },

                async playWithHlsJs(video, resumeTime, shouldPlay, generation) {
                    const isStale = () => this._loadGeneration !== generation;

                    if (!window.Hls) {
                        window.setTimeout(() => {
                            if (!isStale()) {
                                this.initPlayer(resumeTime, shouldPlay);
                            }
                        }, 100);

                        return;
                    }

                    if (!window.Hls.isSupported()) {
                        console.error('Browser tidak mendukung playback HLS Raptdrama');
                        this.isBuffering = false;

                        return;
                    }

                    this.isBuffering = true;

                    const player = new window.Hls({
                        enableWorker: true,
                        lowLatencyMode: false,
                        maxBufferLength: 60,
                        backBufferLength: 30,
                    });

                    this.player = player;
                    this.playerEngine = 'hls';

                    player.on(window.Hls.Events.ERROR, (_event, data) => {
                        if (isStale() || !data.fatal) {
                            return;
                        }

                        if (data.type === window.Hls.ErrorTypes.NETWORK_ERROR) {
                            player.startLoad();

                            return;
                        }

                        if (data.type === window.Hls.ErrorTypes.MEDIA_ERROR) {
                            player.recoverMediaError();

                            return;
                        }

                        console.error('HLS Raptdrama error', data);
                        this.isBuffering = false;
                    });

                    player.on(window.Hls.Events.MANIFEST_PARSED, () => {
                        if (isStale()) {
                            return;
                        }

                        if (resumeTime > 0 && Number.isFinite(resumeTime)) {
                            video.currentTime = resumeTime;
                        }

                        this.isBuffering = false;

                        if (shouldPlay) {
                            this.attemptPlay(video);
                        }
                    });

                    player.loadSource(this.videoUrl);
                    player.attachMedia(video);
                },

                async playEncryptedMelolo(video, quality, resumeTime = 0, shouldPlay = true) {
                    this.isBuffering = true;

                    try {
                        const keyResponse = await fetch(
                            `https://melolo-api.dramabos.fun/api/melolo/key?vid=${encodeURIComponent(quality.kid)}`,
                        );
                        const keyPayload = await keyResponse.json();
                        const keyHex = keyPayload?.key;

                        if (!keyResponse.ok || !keyHex) {
                            throw new Error('Kunci dekripsi Melolo tidak tersedia.');
                        }

                        if (this.meloloBlobUrl) {
                            URL.revokeObjectURL(this.meloloBlobUrl);
                            this.meloloBlobUrl = null;
                        }

                        if (meloloSwReady) {
                            video.src =
                                '/sw-play?url=' +
                                encodeURIComponent(this.videoUrl) +
                                '&key=' +
                                encodeURIComponent(keyHex);
                        } else {
                            const blob = await MeloloDecrypt.decrypt(this.videoUrl, keyHex);
                            this.meloloBlobUrl = URL.createObjectURL(blob);
                            video.src = this.meloloBlobUrl;
                        }

                        video.load();

                        await new Promise((resolve, reject) => {
                            const timeout = window.setTimeout(() => {
                                cleanup();
                                reject(new Error('Waktu tunggu dekripsi Melolo habis.'));
                            }, 15000);

                            const cleanup = () => {
                                video.removeEventListener('canplay', onCanPlay);
                                video.removeEventListener('error', onError);
                                window.clearTimeout(timeout);
                            };

                            const onCanPlay = () => {
                                cleanup();
                                resolve();
                            };

                            const onError = () => {
                                cleanup();
                                reject(new Error('Video Melolo hasil dekripsi gagal diputar.'));
                            };

                            video.addEventListener('canplay', onCanPlay, { once: true });
                            video.addEventListener('error', onError, { once: true });
                        });

                        if (resumeTime > 0 && Number.isFinite(resumeTime)) {
                            video.currentTime = resumeTime;
                        }

                        this.isBuffering = false;

                        if (shouldPlay) {
                            this.attemptPlay(video);
                        }
                    } catch (error) {
                        console.error('Gagal mendekripsi stream Melolo:', error);
                        this.isBuffering = false;
                        alert('Gagal mendekripsi video Melolo. Silakan coba episode lain.');
                    }
                },

                async loadSubtitleTracks() {
                    if (!this.player || this.subtitles.length === 0) return;

                    for (const subtitle of this.subtitles) {
                        if (!subtitle?.url) continue;

                        try {
                            const rawLanguage = String(subtitle.lang || 'id');
                            const language = rawLanguage.split(/[-_]/)[0].toLowerCase();

                            await this.player.addTextTrackAsync(
                                subtitle.url,
                                language,
                                'subtitles',
                                'text/vtt',
                                null,
                                rawLanguage
                            );
                        } catch (error) {
                            console.warn('Gagal menambahkan subtitle:', subtitle, error);
                        }
                    }

                    const tracks = this.player.getTextTracks();
                    if (tracks.length > 0) {
                        this.player.selectTextTrack(tracks[0]);
                        this.player.setTextTrackVisibility(true);
                    }
                },

                attemptPlay(video) {
                    // Coba play dengan suara dulu. Jika ditolak (autoplay policy),
                    // mute dan tampilkan banner "Tap to Unmute" secara sinkron.
                    video.muted = false;
                    this.muted = false;
                    const p = video.play();
                    if (p !== undefined) {
                        p.catch((err) => {
                            console.log('Autoplay bersuara ditolak, coba muted...', err);
                            video.muted = true;
                            this.muted = true;
                            this.isMutedAutoplay = true;
                            video.play().catch(e => console.warn('Muted autoplay juga gagal:', e));
                        });
                    }
                },

                unmute() {
                    const v = this.$refs.video;
                    if (!v) return;
                    v.muted = false;
                    this.muted = false;
                    this.isMutedAutoplay = false;
                    this.showControls();
                },

                toggleMute() {
                    const v = this.$refs.video;
                    if (!v) return;
                    v.muted = !v.muted;
                    this.muted = v.muted;
                    this.isMutedAutoplay = false;
                    this.showControls();
                },

                togglePlayPause() {
                    const v = this.$refs.video;
                    if (!v) return;
                    this.showControls();
                    if (v.paused) {
                        v.play().catch(() => {});
                    } else {
                        v.pause();
                    }
                    this.flashCenterIndicator();
                },

                flashCenterIndicator() {
                    this.showCenterIndicator = true;
                    if (this.centerIndicatorTimeout) clearTimeout(this.centerIndicatorTimeout);
                    this.centerIndicatorTimeout = setTimeout(() => {
                        this.showCenterIndicator = false;
                    }, 500);
                },

                showControls() {
                    this.controlsVisible = true;

                    if (this.controlsTimer) {
                        window.clearTimeout(this.controlsTimer);
                        this.controlsTimer = null;
                    }

                    if (
                        this.isPaused
                        || this.isBuffering
                        || this.showEpisodes
                        || this.isMutedAutoplay
                    ) {
                        return;
                    }

                    this.controlsTimer = window.setTimeout(() => {
                        if (
                            !this.isPaused
                            && !this.isBuffering
                            && !this.showEpisodes
                            && !this.isMutedAutoplay
                        ) {
                            this.controlsVisible = false;
                        }
                    }, 3000);
                },

                seek(evt) {
                    const el = evt.currentTarget;
                    const rect = el.getBoundingClientRect();
                    const pct = Math.min(1, Math.max(0, (evt.clientX - rect.left) / rect.width));
                    const v = this.$refs.video;
                    if (v && v.duration) {
                        v.currentTime = pct * v.duration;
                    }
                },

                async changeQuality(q) {
                    if (!q || this.currentQualityLabel === q.label) return;

                    const video = this.$refs.video;
                    if (!video) return;

                    const wasPaused = video.paused;
                    const savedTime = video.currentTime;
                    const previousQuality = this.qualityList.find(
                        (quality) => quality.label === this.currentQualityLabel,
                    );

                    this.currentQualityLabel = q.label;
                    this.videoUrl = q.url;

                    // Perpindahan menuju atau keluar dari encrypted Melolo selalu
                    // membuat ulang source/player agar native source dan Shaka tidak bercampur.
                    if (
                        q.kid
                        || previousQuality?.kid
                        || this.playerEngine === 'hls'
                        || !this.player
                    ) {
                        await this.initPlayer(savedTime, !wasPaused);

                        return;
                    }

                    this.isBuffering = true;

                    try {
                        await this.player.load(q.url);
                        await this.loadSubtitleTracks();
                        video.currentTime = savedTime;

                        if (!wasPaused) {
                            video.play().catch(() => {});
                        }
                    } catch (error) {
                        console.error('Gagal ganti kualitas', error);
                    } finally {
                        this.isBuffering = false;
                    }
                },

                pickEpisode(ep) {
                    if (ep === this.currentEpisode) {
                        this.showEpisodes = false;
                        return;
                    }
                    this.showEpisodes = false;
                    this.$wire.playEpisode(ep);
                },

                isWatched(ep) {
                    return (this.drama.watchedEpisodes || []).includes(String(ep));
                },

                fmtTime(secs) {
                    if (!secs || isNaN(secs) || !isFinite(secs)) return '0:00';
                    const m = Math.floor(secs / 60);
                    const s = Math.floor(secs % 60).toString().padStart(2, '0');
                    return `${m}:${s}`;
                },
            }));
        }

        if (window.Alpine) {
            registerDracinVerticalPlayer();
        } else {
            document.addEventListener('alpine:init', registerDracinVerticalPlayer);
        }
    </script>
@endpush