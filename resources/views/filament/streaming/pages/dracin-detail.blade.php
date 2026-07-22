<div class="min-h-screen bg-[#050505] text-white pt-28 pb-16 px-4 md:px-8 max-w-[1600px] mx-auto">
    <div x-data="dracinPlayerInit()" wire:init="autoPlayMelolo">

        <!-- Two Column Layout: Left (Player & Meta), Right (Episodes Grid) -->
        <div class="flex flex-col lg:flex-row gap-6 items-start">

            <!-- Left Column: Player & Detail Info -->
            <div class="flex-1 w-full min-w-0">

                <!-- Player Container -->
                <div x-show="isPlaying"
                    class="mb-6 w-full bg-black rounded-3xl overflow-hidden border border-zinc-800 shadow-2xl relative"
                    style="display: none;" x-ref="playerContainer">
                    <div :class="isPortrait ? 'aspect-[9/16] max-w-[450px] mx-auto w-full border-x border-zinc-800/50' : 'aspect-video w-full'"
                        class="bg-black relative transition-all duration-500">
                        <video x-ref="video" class="w-full h-full object-contain"></video>

                        <!-- Unmute Overlay Banner -->
                        <div x-show="isMutedAutoplay" @click.stop="unmuteVideo()" class="absolute inset-0 flex items-center justify-center bg-black/40 z-20 cursor-pointer transition">
                            <div class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs md:text-sm px-5 py-2.5 rounded-full flex items-center gap-2 shadow-lg tracking-wide animate-pulse">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM19 12c0 2.94-1.65 5.5-4.08 6.78l1.41 1.41C19.78 18.52 22 15.52 22 12s-2.22-6.52-5.67-8.19l-1.41 1.41C17.35 6.5 19 9.06 19 12z" />
                                </svg>
                                Ketuk Untuk Bersuara (Tap to Unmute)
                            </div>
                        </div>

                        <!-- Custom Title Overlay -->
                        <div
                            class="absolute top-0 left-0 w-full p-4 flex items-center gap-3 z-10 pointer-events-none bg-gradient-to-b from-black/80 to-transparent">
                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-xs uppercase tracking-[0.18em] text-white/50 font-semibold drop-shadow-md">
                                    Now Playing</p>
                                <p class="truncate text-base font-semibold text-white/95 drop-shadow-md">
                                    {{ $drama['title'] ?? '' }} - Episode <span x-text="currentEpisode"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Quality Controls below the video inside the card -->
                    <div
                        class="flex flex-wrap items-center justify-between gap-4 bg-zinc-900/60 p-4 border-t border-zinc-800">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-semibold text-zinc-400">Pilih Kualitas:</span>
                            <template x-for="q in qualityList" :key="q.label">
                                <button @click="changeQuality(q.url, q.label)"
                                    :class="currentQualityLabel === q.label ? 'bg-red-600 text-white border-red-600' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-300 border-zinc-700'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold border transition">
                                    <span x-text="q.label"></span>
                                </button>
                            </template>
                        </div>

                        <!-- Auto Next Episode Switch/Trigger -->
                        @if(isset($activeEpisode) && !empty($drama['videos']))
                            @php
                                $nextEp = null;
                                $foundActive = false;
                                foreach ($drama['videos'] as $v) {
                                    if ($foundActive) {
                                        $nextEp = $v['episode'];
                                        break;
                                    }
                                    if ($v['episode'] === ($activeEpisode['episode'] ?? null)) {
                                        $foundActive = true;
                                    }
                                }
                            @endphp
                            @if($nextEp)
                                <button wire:click="playEpisode({{ $nextEp }})"
                                    class="px-4 py-2 bg-red-600/10 hover:bg-red-600 text-red-500 hover:text-white rounded-lg text-xs font-bold border border-red-500/30 transition">
                                    Next Episode (Ep {{ $nextEp }}) →
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- Drama Info Header -->
                <div class="bg-zinc-900/40 border border-zinc-800 rounded-3xl p-6 md:p-8 backdrop-blur-md">
                    <div class="flex flex-col md:flex-row gap-8">
                        <!-- Cover image -->
                        <div
                            class="w-full md:w-48 aspect-[3/4] rounded-2xl bg-black border border-zinc-800 flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xl">
                            @if(!empty($drama['cover']))
                                <img src="{{ url('/api/proxy-image?url=' . urlencode($drama['cover'])) }}"
                                    alt="{{ $drama['title'] ?? '' }}" class="w-full h-full object-cover"
                                    referrerpolicy="no-referrer">
                            @else
                                <div class="text-zinc-600 flex flex-col items-center gap-2">
                                    <svg class="w-16 h-16 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z" />
                                    </svg>
                                    <span class="text-xs uppercase tracking-widest font-semibold">No Cover</span>
                                </div>
                            @endif
                        </div>

                        <!-- Details -->
                        <div class="flex-1 space-y-4 text-left">
                            <div
                                class="inline-flex items-center gap-2 px-3 py-1 bg-red-600/10 border border-red-500/25 rounded-full text-red-500 text-xs font-semibold uppercase tracking-wider">
                                {{ $network['name'] }}
                            </div>
                            <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">
                                {{ $drama['title'] ?? $drama['name'] ?? 'Detail Drama' }}</h1>

                            <div class="flex flex-wrap items-center gap-4 text-zinc-400 text-sm">
                                @if(!empty($drama['episodes']))
                                    <div class="flex items-center gap-2">
                                        <span class="text-zinc-500 font-semibold uppercase">Total Episode:</span>
                                        <span class="text-white font-bold">{{ $drama['episodes'] }}</span>
                                    </div>
                                @endif

                                <button wire:click="toggleFavorite"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 {{ $isFavorited ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-300' }} rounded text-xs font-bold transition">
                                    @if($isFavorited)
                                        <svg class="w-3.5 h-3.5 fill-current text-white" viewBox="0 0 24 24">
                                            <path
                                                d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                        </svg>
                                        Favorit
                                    @else
                                        <svg class="w-3.5 h-3.5 fill-none stroke-current" viewBox="0 0 24 24"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                        </svg>
                                        Tambah Favorit
                                    @endif
                                </button>
                            </div>

                            @if(!empty($drama['intro']))

                                <div class="space-y-1.5">
                                    <h3 class="text-xs uppercase tracking-widest text-zinc-500 font-bold">Sinopsis / Intro
                                    </h3>
                                    <p class="text-zinc-300 text-sm leading-relaxed max-w-4xl">
                                        {{ $drama['intro'] }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Episode Selection List/Grid Sidebar -->
            <div class="w-full lg:w-[400px] lg:shrink-0 lg:sticky lg:top-28">
                <div
                    class="bg-[#0e0e0f] border border-zinc-800/80 rounded-3xl p-5 backdrop-blur-md max-h-[calc(100vh-10rem)] overflow-y-auto">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-zinc-800 pb-3 mb-2">
                            <h2 class="text-sm font-bold tracking-wider text-zinc-300 uppercase">Pilih Episode</h2>
                            <span
                                class="text-[10px] text-zinc-500 font-mono bg-zinc-900 px-2 py-0.5 rounded border border-zinc-800/50">
                                @if(!empty($drama['videos']))
                                    {{ count($drama['videos']) }} Ep
                                @elseif(!empty($drama['episodes']))
                                    {{ $drama['episodes'] }} Ep
                                @endif
                            </span>
                        </div>

                        @if($lastWatchedEpisode)
                            <div class="p-3 bg-zinc-900/80 border border-zinc-800 rounded-2xl flex items-center justify-between gap-3 mb-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Terakhir Ditonton</p>
                                    <p class="text-xs font-bold text-red-400 mt-0.5 truncate">Episode {{ $lastWatchedEpisode }}</p>
                                </div>
                                <button wire:click="playEpisode({{ $lastWatchedEpisode }})"
                                    class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-[10px] font-bold transition flex-shrink-0">
                                    Putar Kembali
                                </button>
                            </div>
                        @endif

                        @if(!empty($drama['videos']))
                            <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-4 gap-2">
                                @foreach($drama['videos'] as $video)
                                                    @php
                                                        $isActive = ($activeEpisode['episode'] ?? null) === $video['episode'];
                                                        $isLastWatched = $lastWatchedEpisode === (string) $video['episode'];
                                                        $isWatched = in_array((string) $video['episode'], $watchedEpisodes, true);
                                                    @endphp
                                                    <button wire:click="playEpisode({{ $video['episode'] }})"
                                                        wire:loading.class="opacity-60 cursor-wait"
                                                        class="p-2.5 rounded-xl border font-bold text-center text-xs transition-all duration-200 flex flex-col justify-center items-center h-14 relative overflow-hidden group
                                                                    {{ $isActive
                                    ? 'bg-red-600 text-white border-red-600 shadow-md shadow-red-600/20'
                                    : ($isWatched ? 'bg-zinc-900/40 hover:bg-zinc-900/80 text-zinc-300 border-zinc-800' : 'bg-zinc-900/60 hover:bg-zinc-900 text-zinc-300 border-zinc-800/60 hover:border-zinc-700') }}">
                                                        Ep {{ $video['episode'] }}
                                                        @if(!empty($video['duration']))
                                                            <span
                                                                class="block text-[8px] font-normal text-zinc-500 mt-0.5">{{ round($video['duration'] / 60) }}m</span>
                                                        @endif
                                                        @if($isWatched)
                                                            <svg class="w-2.5 h-2.5 text-emerald-500 absolute top-1 left-1" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        @endif
                                                        @if($isLastWatched && !$isActive)
                                                            <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                                        @endif
                                                    </button>
                                @endforeach
                            </div>
                        @else
                            @if(!empty($drama['episodes']))
                                <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-4 gap-2">
                                    @for($ep = 1; $ep <= $drama['episodes']; $ep++)
                                                    @php
                                                        $isActive = ($activeEpisode['episode'] ?? null) === $ep;
                                                        $isLastWatched = $lastWatchedEpisode === (string) $ep;
                                                        $isWatched = in_array((string) $ep, $watchedEpisodes, true);
                                                    @endphp
                                                    <button wire:click="playEpisode({{ $ep }})" wire:loading.class="opacity-60 cursor-wait"
                                                        class="p-2.5 rounded-xl border font-bold text-center text-xs transition-all duration-200 flex flex-col justify-center items-center h-14 relative overflow-hidden group
                                                                        {{ $isActive
                                        ? 'bg-red-600 text-white border-red-600 shadow-md shadow-red-600/20'
                                        : ($isWatched ? 'bg-zinc-900/40 hover:bg-zinc-900/80 text-zinc-300 border-zinc-800' : 'bg-zinc-900/60 hover:bg-zinc-900 text-zinc-300 border-zinc-800/60 hover:border-zinc-700') }}">
                                                        Ep {{ $ep }}
                                                        @if($isWatched)
                                                            <svg class="w-2.5 h-2.5 text-emerald-500 absolute top-1 left-1" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        @endif
                                                        @if($isLastWatched && !$isActive)
                                                            <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                                        @endif
                                                    </button>
                                    @endfor
                                </div>
                            @else
                                <div
                                    class="py-8 text-center text-zinc-600 border border-dashed border-zinc-800/60 rounded-xl text-xs">
                                    Tidak ada episode.
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/shaka-player/4.7.9/controls.min.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <style>
        .shaka-seek-bar-container {
            background: rgba(255, 255, 255, 0.25) !important;
        }

        .shaka-seek-bar {
            background: linear-gradient(to right, rgb(229, 9, 20) 0%, rgb(229, 9, 20) var(--seek-bar-percentage, 0%), rgba(255, 255, 255, 0) var(--seek-bar-percentage, 0%)) !important;
        }

        .shaka-play-button,
        .shaka-fullscreen-button,
        .shaka-volume-bar,
        .shaka-range-element {
            accent-color: #E50914 !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="/aes-js.min.js"></script>
    <script src="/melolo-decrypt.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/shaka-player/4.7.9/shaka-player.ui.js"></script>
    <script>
        let meloloSwReady = false;
        async function initMeloloSW() {
            if (!('serviceWorker' in navigator)) return false;
            try {
                await navigator.serviceWorker.register('/melolo-sw.js');
                if (!navigator.serviceWorker.controller) {
                    await new Promise(r => navigator.serviceWorker.addEventListener('controllerchange', r, { once: true }));
                }
                meloloSwReady = true;
                return true;
            } catch (e) { return false; }
        }
        initMeloloSW();

        function registerDracinPlayer() {
            Alpine.data('dracinPlayerInit', () => ({
                isPlaying: false,
                player: null,
                ui: null,
                videoUrl: '',
                currentEpisode: 1,
                qualityList: [],
                currentQualityLabel: '',
                isPortrait: false,
                isMutedAutoplay: false,
                unmuteHandler: null,
                nextEpisode: null,
                subtitles: [],
                attemptPlay(video) {
                    const playPromise = video.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(error => {
                            console.log("Autoplay with sound blocked. Retrying muted...", error);
                            video.muted = true;

                            // Tampilkan banner "Tap to Unmute" dan pasang handler SEBELUM
                            // menunggu promise play muted. Play muted bisa reject dengan
                            // AbortError ("interrupted by a new load request"), yang membuat
                            // .then() tidak pernah jalan sehingga banner tak muncul dan user
                            // tidak bisa menyalakan suara. Set state secara sinkron di sini.
                            this.isMutedAutoplay = true;

                            if (this.unmuteHandler) {
                                document.removeEventListener('click', this.unmuteHandler);
                                document.removeEventListener('touchstart', this.unmuteHandler);
                            }

                            this.unmuteHandler = () => {
                                if (video) video.muted = false;
                                this.isMutedAutoplay = false;
                                document.removeEventListener('click', this.unmuteHandler);
                                document.removeEventListener('touchstart', this.unmuteHandler);
                            };

                            document.addEventListener('click', this.unmuteHandler, { once: true });
                            document.addEventListener('touchstart', this.unmuteHandler, { once: true });

                            video.play().catch(err => {
                                console.error("Muted autoplay also blocked:", err);
                            });
                        });
                    }
                },
                unmuteVideo() {
                    const video = this.$refs.video;
                    if (video) {
                        video.muted = false;
                    }
                    this.isMutedAutoplay = false;
                    if (this.unmuteHandler) {
                        document.removeEventListener('click', this.unmuteHandler);
                        document.removeEventListener('touchstart', this.unmuteHandler);
                    }
                },
                init() {
                    window.addEventListener('init-player', (e) => {
                        this.isPlaying = true;

                        let videoUrl = e.detail.videoUrl;
                        let qualityList = e.detail.qualityList;

                        // Fallback to array syntax if needed
                        if (!videoUrl && e.detail && e.detail[0]) {
                            videoUrl = e.detail[0].videoUrl;
                            qualityList = e.detail[0].qualityList;
                        }

                        this.videoUrl = videoUrl;
                        this.qualityList = qualityList || [];
                        this.currentQualityLabel = this.qualityList.length > 0 ? this.qualityList[0].label : 'Default';

                        // Extract subtitles (used by netshort and other networks that provide external tracks)
                        let subs = e.detail.subtitles;
                        if (!subs && e.detail && e.detail[0]) {
                            subs = e.detail[0].subtitles;
                        }
                        this.subtitles = Array.isArray(subs) ? subs : [];

                        // Extract episode number from Livewire event parameters
                        let episodeNum = e.detail.episode;
                        if (!episodeNum && e.detail && e.detail[0]) {
                            episodeNum = e.detail[0].episode;
                        }
                        this.currentEpisode = episodeNum || 1;

                        let nextEp = e.detail.nextEpisode;
                        if (!nextEp && e.detail && e.detail[0]) {
                            nextEp = e.detail[0].nextEpisode;
                        }
                        this.nextEpisode = nextEp || null;

                        this.$nextTick(() => {
                            this.initPlayer();
                            // Scroll to the video player container smoothly
                            this.$refs.playerContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        });
                    });

                    // Set up metadata listener to detect portrait videos
                    this.$nextTick(() => {
                        const video = this.$refs.video;
                        if (video) {
                            video.addEventListener('loadedmetadata', () => {
                                this.isPortrait = video.videoHeight > video.videoWidth;
                            });
                            
                            video.addEventListener('ended', () => {
                                if (this.nextEpisode) {
                                    this.$wire.playEpisode(this.nextEpisode);
                                }
                            });
                        }
                    });
                },
                async initPlayer() {
                    if (!this.videoUrl) return;
                    
                    this.isMutedAutoplay = false;

                    const currentQ = this.qualityList.find(q => q.label === this.currentQualityLabel);
                    const isEncrypted = currentQ && currentQ.kid;

                    const video = this.$refs.video;

                    // Reset portrait flag by default until metadata loads
                    this.isPortrait = false;

                    // If it's an encrypted Melolo video, we don't use Shaka Player
                    if (isEncrypted) {
                        // Destroy shaka if exists
                        if (this.player) { await this.player.destroy(); this.player = null; }
                        if (this.ui) { await this.ui.destroy(); this.ui = null; }

                        video.controls = true; // Use native controls
                        video.style.display = 'block';

                        try {
                            const kid = currentQ.kid;
                            const keyHexResp = await fetch(`https://melolo-api.dramabos.fun/api/melolo/key?vid=${kid}`);
                            const keyHexData = await keyHexResp.json();
                            const keyHex = keyHexData.key;

                            if (!keyHex) throw new Error("Key not found");

                            if (meloloSwReady) {
                                const swURL = '/sw-play?url=' + encodeURIComponent(this.videoUrl) + '&key=' + keyHex;
                                video.src = swURL;
                                video.load();
                                await new Promise((resolve, reject) => {
                                    video.oncanplay = resolve;
                                    video.onerror = () => reject(new Error('SW error'));
                                    setTimeout(() => reject(new Error('Timeout')), 15000);
                                });
                                this.attemptPlay(video);
                            } else {
                                // Fallback to JS decryption Blob
                                const blob = await MeloloDecrypt.decrypt(this.videoUrl, keyHex, (stage, msg) => {
                                    console.log("Decrypting...", msg);
                                });
                                if (video.src && video.src.startsWith('blob:')) URL.revokeObjectURL(video.src);
                                video.src = URL.createObjectURL(blob);
                                video.load();
                                this.attemptPlay(video);
                            }
                        } catch (e) {
                            console.error('Decryption failed:', e);
                            alert('Gagal mendekripsi video.');
                        }
                        return;
                    }

                    // Normal Shaka Player behavior
                    video.controls = false;
                    if (!window.shaka) {
                        setTimeout(() => this.initPlayer(), 100);
                        return;
                    }

                    if (this.player) {
                        await this.player.destroy();
                    }
                    if (this.ui) {
                        await this.ui.destroy();
                    }

                    shaka.polyfill.installAll();
                    if (!shaka.Player.isBrowserSupported()) {
                        console.error('Browser not supported!');
                        return;
                    }

                    const container = video.parentElement;

                    this.player = new shaka.Player(video);
                    this.ui = new shaka.ui.Overlay(this.player, container, video);

                    // Buffer agresif: mulai playback cepat (2s), preload 60s ke depan.
                    // Ini memberi bantalan besar terhadap latensi proxy HLS.
                    this.player.configure({
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

                    this.ui.configure({
                        controlPanelElements: [
                            'play_pause',
                            'rewind',
                            'fast_forward',
                            'mute',
                            'volume',
                            'time_and_duration',
                            'spacer',
                            'playback_rate',
                            'fullscreen'
                        ],
                        addSeekBar: true
                    });

                    try {
                        await this.player.load(this.videoUrl);
                        await this.loadSubtitleTracks();
                        this.attemptPlay(video);
                    } catch (e) {
                        console.error('Error code', e.code, 'object', e);
                    }
                },
                async loadSubtitleTracks() {
                    if (!this.player || !this.subtitles || this.subtitles.length === 0) return;

                    for (const sub of this.subtitles) {
                        try {
                            // Normalize language code (e.g. "id_ID" -> "id")
                            const rawLang = (sub.lang || 'id').toString();
                            const langCode = rawLang.split(/[-_]/)[0].toLowerCase();
                            await this.player.addTextTrackAsync(
                                sub.url,
                                langCode,
                                'subtitles',
                                'text/vtt',
                                null,
                                sub.lang || langCode
                            );
                        } catch (err) {
                            console.warn('Failed to add subtitle track:', sub, err);
                        }
                    }

                    try {
                        const tracks = this.player.getTextTracks();
                        if (tracks && tracks.length > 0) {
                            this.player.selectTextTrack(tracks[0]);
                            this.player.setTextTrackVisibility(true);
                        }
                    } catch (err) {
                        console.warn('Failed to enable subtitle track:', err);
                    }
                },
                async changeQuality(url, label) {
                    const video = this.$refs.video;
                    const currentTime = video.currentTime;
                    const wasPlaying = !video.paused;

                    this.currentQualityLabel = label;
                    this.videoUrl = url;

                    const currentQ = this.qualityList.find(q => q.label === label);
                    const isEncrypted = currentQ && currentQ.kid;

                    if (isEncrypted) {
                        await this.initPlayer(); // Re-initialize to handle decryption flow
                        video.currentTime = currentTime;
                        if (wasPlaying) video.play();
                        return;
                    }

                    if (!this.player) {
                        await this.initPlayer();
                        return;
                    }

                    try {
                        await this.player.load(url);
                        await this.loadSubtitleTracks();
                        video.currentTime = currentTime;
                        if (wasPlaying) {
                            video.play();
                        }
                    } catch (e) {
                        console.error('Error changing quality', e);
                    }
                }
            }));
        }

        if (window.Alpine) {
            registerDracinPlayer();
        } else {
            document.addEventListener('alpine:init', registerDracinPlayer);
        }
    </script>
@endpush