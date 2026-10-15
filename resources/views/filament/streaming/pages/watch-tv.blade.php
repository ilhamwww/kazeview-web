<div class="min-h-screen bg-[#050505] text-white pb-36 md:pb-24 relative overflow-x-hidden"
     x-data="{
        currentServerIndex: 0,
        servers: {{ Js::from($channel['servers']) }},
        channel: {{ Js::from($channel) }},
        allChannels: {{ Js::from($allChannels) }},
        hlsInstance: null,
        isPlaying: false,
        isLoading: true,
        hasError: false,
        fullscreenMode: false,

        init() {
            this.$nextTick(() => {
                this.loadCurrentServer();
            });
        },

        get currentServer() {
            return this.servers[this.currentServerIndex] || this.servers[0];
        },

        switchServer(idx) {
            if (this.currentServerIndex === idx) return;
            this.currentServerIndex = idx;
            this.loadCurrentServer();
        },

        loadCurrentServer() {
            this.isLoading = true;
            this.hasError = false;

            if (this.currentServer.type === 'hls') {
                this.initHlsPlayer(this.currentServer.url);
            } else {
                this.destroyHls();
                this.isLoading = false;
            }
        },

        initHlsPlayer(url) {
            const video = this.$refs.videoElement;
            if (!video) return;

            this.destroyHls();

            if (window.Hls && Hls.isSupported()) {
                const hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: true,
                    backBufferLength: 60,
                });
                this.hlsInstance = hls;

                hls.loadSource(url);
                hls.attachMedia(video);

                hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    this.isLoading = false;
                    video.play().catch(() => {});
                });

                hls.on(Hls.Events.ERROR, (event, data) => {
                    console.warn('HLS Error:', data);
                    if (data.fatal) {
                        switch (data.type) {
                            case Hls.ErrorTypes.NETWORK_ERROR:
                                hls.startLoad();
                                break;
                            case Hls.ErrorTypes.MEDIA_ERROR:
                                hls.recoverMediaError();
                                break;
                            default:
                                this.hasError = true;
                                this.isLoading = false;
                                this.destroyHls();
                                break;
                        }
                    }
                });
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                // Safari iOS native HLS
                video.src = url;
                video.addEventListener('loadedmetadata', () => {
                    this.isLoading = false;
                    video.play().catch(() => {});
                });
                video.addEventListener('error', () => {
                    this.hasError = true;
                    this.isLoading = false;
                });
            } else {
                this.hasError = true;
                this.isLoading = false;
            }
        },

        destroyHls() {
            if (this.hlsInstance) {
                this.hlsInstance.destroy();
                this.hlsInstance = null;
            }
            if (this.$refs.videoElement) {
                this.$refs.videoElement.removeAttribute('src');
                this.$refs.videoElement.load();
            }
        },

        reloadStream() {
            this.loadCurrentServer();
        },

        async toggleFullscreen() {
            const container = this.$refs.playerContainer;
            if (!container) return;

            if (!document.fullscreenElement) {
                try {
                    if (container.requestFullscreen) {
                        await container.requestFullscreen();
                    } else if (container.webkitRequestFullscreen) {
                        await container.webkitRequestFullscreen();
                    }
                    this.fullscreenMode = true;

                    // Landscape lock for mobile devices
                    if (screen.orientation && screen.orientation.lock) {
                        screen.orientation.lock('landscape').catch(() => {});
                    }
                } catch (e) {
                    console.warn('Fullscreen error:', e);
                }
            } else {
                if (document.exitFullscreen) {
                    await document.exitFullscreen();
                }
                this.fullscreenMode = false;
            }
        }
     }">

    {{-- Load HLS.js CDN for reliable client-side stream playback --}}
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.8/dist/hls.min.js"></script>

    {{-- Ambient Glow --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-[500px] overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] md:w-[900px] h-[350px] md:h-[450px] bg-red-600/15 rounded-full blur-[140px]"></div>
    </div>

    {{-- Main Container --}}
    <div class="relative w-full max-w-7xl mx-auto px-4 md:px-8 pt-16 md:pt-24 space-y-6">
        {{-- Breadcrumb Navigation --}}
        <div class="flex items-center justify-between gap-3 text-xs text-zinc-400">
            <a href="/tv" class="inline-flex items-center gap-1.5 hover:text-white font-medium transition group">
                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Daftar TV
            </a>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-600/15 border border-red-500/30 text-[11px] font-black text-red-400">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    ON AIR
                </span>
                <span class="text-zinc-500 hidden sm:inline" x-text="channel.category"></span>
            </div>
        </div>

        {{-- Main Player & Remote Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {{-- Left Column: Player & Info (8 cols) --}}
            <div class="lg:col-span-8 space-y-4">
                {{-- Video Player Container (16:9) --}}
                <div x-ref="playerContainer"
                     class="relative w-full aspect-video bg-black rounded-3xl overflow-hidden border border-zinc-800 shadow-2xl flex items-center justify-center group">

                    {{-- 1. HLS Video Element --}}
                    <div x-show="currentServer.type === 'hls'" class="w-full h-full relative" style="display: none;">
                        <video x-ref="videoElement"
                               class="w-full h-full object-contain bg-black"
                               playsinline
                               controls
                               autoplay></video>
                    </div>

                    {{-- 2. YouTube Live Embed --}}
                    <template x-if="currentServer.type === 'youtube'">
                        <iframe :src="currentServer.url"
                                class="w-full h-full border-0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                    </template>

                    {{-- 3. Iframe Web Stream --}}
                    <template x-if="currentServer.type === 'iframe'">
                        <iframe :src="currentServer.url"
                                class="w-full h-full border-0 bg-black"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                    </template>

                    {{-- Live Channel Watermark Overlay (Top Left) --}}
                    <div class="pointer-events-none absolute top-4 left-4 z-10 flex items-center gap-2 bg-black/60 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/10 shadow-lg">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        <span class="text-xs font-black text-white tracking-wider uppercase" x-text="channel.name"></span>
                        <span class="text-[10px] text-zinc-400 font-mono border-l border-zinc-700 pl-2" x-text="currentServer.quality"></span>
                    </div>

                    {{-- Loading Overlay --}}
                    <div x-show="isLoading"
                         class="absolute inset-0 bg-black/80 backdrop-blur-sm flex flex-col items-center justify-center gap-3 z-20"
                         style="display: none;">
                        <div class="w-10 h-10 border-3 border-red-500/20 border-t-red-500 rounded-full animate-spin"></div>
                        <p class="text-xs font-bold text-zinc-300">Menghubungkan ke siaran live...</p>
                    </div>

                    {{-- Error Overlay with Fallback Action --}}
                    <div x-show="hasError"
                         class="absolute inset-0 bg-black/90 backdrop-blur-md flex flex-col items-center justify-center gap-4 p-6 text-center z-30"
                         style="display: none;">
                        <div class="w-12 h-12 rounded-2xl bg-red-950/80 border border-red-500/30 flex items-center justify-center text-red-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h-14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="space-y-1 max-w-sm">
                            <h4 class="text-sm font-bold text-white">Siaran Sedang Mengalami Gangguan</h4>
                            <p class="text-xs text-zinc-400">Stream ini mungkin dibatasi wilayah atau server stasiun sedang refresh.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="reloadStream()"
                                    class="px-4 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-white transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                Coba Lagi
                            </button>
                            <template x-if="servers.length > 1">
                                <button type="button"
                                        @click="switchServer(currentServerIndex === 0 ? 1 : 0)"
                                        class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-xs font-bold text-white transition shadow-lg shadow-red-900/30">
                                    Ganti ke Server Cadangan
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Player Controls & Server Switcher Bar --}}
                <div class="p-3.5 md:p-4 rounded-2xl bg-zinc-900/60 border border-zinc-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 overflow-hidden">
                    {{-- Server Switcher Pills (Wrap cleanly on mobile) --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-zinc-400 shrink-0 mr-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.75 5.1a3 3 0 012.4-1.1h7.7a3 3 0 012.4 1.1l2.6 3.45a4.5 4.5 0 01.9 2.7"/></svg>
                            Server:
                        </span>
                        <template x-for="(srv, idx) in servers" :key="idx">
                            <button type="button"
                                    @click="switchServer(idx)"
                                    :class="currentServerIndex === idx ? 'bg-red-600 text-white font-black shadow-md shadow-red-950/40 border-red-500' : 'bg-zinc-800/80 text-zinc-400 hover:text-white border-zinc-700/60'"
                                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full" :class="currentServerIndex === idx ? 'bg-white' : 'bg-zinc-500'"></span>
                                <span x-text="'Server ' + (idx + 1)"></span>
                                <span class="text-[10px] opacity-80 font-mono px-1 py-0.2 rounded bg-black/30" x-text="srv.quality"></span>
                            </button>
                        </template>
                    </div>

                    {{-- Actions: Refresh & Fullscreen --}}
                    <div class="flex items-center justify-end gap-2 border-t sm:border-t-0 border-zinc-800/50 pt-2.5 sm:pt-0">
                        <button type="button"
                                @click="reloadStream()"
                                title="Segarkan Siaran"
                                class="p-2 px-3 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white border border-zinc-700 transition flex items-center gap-1.5 text-xs font-semibold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            <span class="text-[11px]">Segarkan</span>
                        </button>
                        <button type="button"
                                @click="toggleFullscreen()"
                                title="Fullscreen Layar Penuh"
                                class="px-3.5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-red-900/30">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                            <span>Layar Penuh</span>
                        </button>
                    </div>
                </div>

                {{-- Channel Info Card --}}
                <div class="p-6 rounded-3xl bg-zinc-900/40 border border-zinc-800 flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-center p-2.5 shrink-0 shadow-lg">
                        <img :src="channel.logo" :alt="channel.name" class="max-h-full max-w-full object-contain filter drop-shadow">
                    </div>
                    <div class="space-y-1.5 flex-1">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 class="text-xl md:text-2xl font-black text-white" x-text="channel.name"></h2>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-zinc-800 border border-zinc-700 text-zinc-300 font-semibold" x-text="channel.category"></span>
                        </div>
                        <p class="text-xs md:text-sm text-zinc-400 font-medium" x-text="channel.tagline"></p>
                        <p class="text-xs text-zinc-500 leading-relaxed pt-1" x-text="channel.description"></p>
                    </div>
                </div>
            </div>

            {{-- Right Column: Interactive Remote TV Switcher (4 cols) --}}
            <div class="lg:col-span-4 space-y-3">
                <div class="flex items-center justify-between pb-1">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z"/></svg>
                        Remote TV (Ganti Channel)
                    </h3>
                    <span class="text-[11px] text-zinc-500 font-medium" x-text="allChannels.length + ' Channel'"></span>
                </div>

                {{-- Scrollable Remote List --}}
                <div class="rounded-3xl bg-zinc-950/70 border border-zinc-800 p-2.5 max-h-[640px] overflow-y-auto space-y-1.5 no-scrollbar shadow-inner">
                    <template x-for="ch in allChannels" :key="ch.id">
                        <a :href="'/tv/' + ch.slug"
                           :class="ch.slug === channel.slug ? 'bg-red-600/15 border-red-500/40 text-white shadow-lg shadow-red-950/20' : 'bg-zinc-900/40 hover:bg-zinc-800/60 border-zinc-800/80 text-zinc-300'"
                           class="flex items-center gap-3 p-2.5 rounded-2xl border transition duration-200 group">
                            {{-- Channel Logo Thumbnail --}}
                            <div class="w-10 h-10 rounded-xl bg-zinc-950 border border-zinc-800/80 flex items-center justify-center p-1.5 shrink-0">
                                <img :src="ch.logo" :alt="ch.name" class="max-h-full max-w-full object-contain" loading="lazy">
                            </div>

                            {{-- Channel Title & Tagline --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <h4 class="text-xs font-bold truncate group-hover:text-red-400 transition" x-text="ch.name"></h4>
                                    <template x-if="ch.slug === channel.slug">
                                        <span class="text-[9px] px-1.5 py-0.2 rounded bg-red-600 text-white font-black tracking-wider uppercase">TAYANG</span>
                                    </template>
                                </div>
                                <p class="text-[10px] text-zinc-500 truncate" x-text="ch.category + ' · ' + (ch.tagline || ch.name)"></p>
                            </div>

                            {{-- Active or Arrow Icon --}}
                            <div class="shrink-0 text-zinc-500 group-hover:text-white transition pr-1">
                                <template x-if="ch.slug === channel.slug">
                                    <span class="w-2 h-2 rounded-full bg-red-500 inline-block animate-ping"></span>
                                </template>
                                <template x-if="ch.slug !== channel.slug">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                </template>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
