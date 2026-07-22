<div class="fixed inset-0 z-[100] overflow-hidden bg-black text-white" x-data="bstationV2Player()" x-init="init()"
    wire:init="autoPlayBstation" wire:ignore.self @pointermove.throttle.150ms="showControls()"
    @pointerdown="showControls()" @focusin="showControls()" @keydown.window="showControls()">
    <script type="application/json" id="bstation-v2-data">
        @php
            $bstationV2Data = [
                'title' => $detail['title'] ?? 'Bstation Video',
                'intro' => $detail['intro'] ?? '',
                'episodeList' => array_values($episodeList ?? []),
                'watchedEpisodes' => $watchedEpisodes ?? [],
                'lastWatchedEpisode' => $lastWatchedEpisode ?? null,
                'isFavorited' => $isFavorited ?? false,
            ];
        @endphp
        {!! json_encode(
    $bstationV2Data,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
) !!}
    </script>

    <div class="relative flex h-full w-full items-center justify-center">
        <div class="relative max-h-screen max-w-full overflow-hidden bg-black transition-all duration-500" :class="isPortrait
                ? 'h-full aspect-[9/16]'
                : 'w-full aspect-video'">
            <iframe x-ref="playerIframe" src="/bstation-embed.html" title="Bstation Player"
                class="absolute inset-0 z-0 h-full w-full border-0" sandbox="allow-scripts"
                allow="autoplay; encrypted-media; fullscreen" referrerpolicy="no-referrer"
                @load="onIframeLoad()"></iframe>

            <button type="button" class="absolute inset-0 z-10 cursor-pointer bg-transparent"
                aria-label="Putar atau jeda" @click="togglePlayPause()"></button>

            <div x-show="showCenterIndicator" x-transition.opacity.duration.300ms
                class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center"
                style="display: none;">
                <div class="rounded-full bg-black/55 p-6 backdrop-blur-sm">
                    <svg x-show="isPaused" class="h-12 w-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                    </svg>
                    <svg x-show="!isPaused" class="h-12 w-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 4h4v16H6zm8 0h4v16h-4z" />
                    </svg>
                </div>
            </div>

            <div x-show="isLoading" class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center"
                style="display: none;">
                <div class="h-12 w-12 animate-spin rounded-full border-4 border-white/30 border-t-white"></div>
            </div>

            <div x-show="isMutedAutoplay" class="pointer-events-none absolute inset-0 z-40" style="display: none;">
                <button type="button"
                    class="pointer-events-auto absolute flex -translate-x-1/2 -translate-y-1/2 animate-pulse items-center gap-2 whitespace-nowrap rounded-full bg-red-600 px-4 py-2 text-xs font-bold text-white shadow-lg transition hover:bg-red-700 md:text-sm"
                    style="left: 50%; top: 50%;" @click.stop="unmute()">
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                        <path
                            d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02z" />
                    </svg>
                    Ketuk Untuk Bersuara
                </button>
            </div>

            <div x-show="errorMessage" x-transition.opacity
                class="absolute left-1/2 top-1/2 z-40 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-xl border border-red-500/30 bg-black/80 p-4 text-center text-xs text-red-200 shadow-2xl backdrop-blur"
                style="display: none;">
                <p x-text="errorMessage"></p>
                <button type="button"
                    class="mt-3 rounded-lg bg-red-600 px-3 py-1.5 font-bold text-white hover:bg-red-700"
                    @click="errorMessage = ''; showControls()">
                    Tutup
                </button>
            </div>

            <header x-show="controlsVisible" x-transition.opacity.duration.200ms
                class="pointer-events-none absolute inset-x-0 top-0 z-30 flex items-start gap-3 bg-gradient-to-b from-black/80 via-black/35 to-transparent p-4">
                <a href="{{ \App\Filament\Streaming\Pages\Bstation::getUrl(panel: 'streaming') }}"
                    class="pointer-events-auto flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition hover:bg-white/10"
                    title="Kembali ke Bstation">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <div class="mt-1 min-w-0 flex-1">
                    <p class="truncate text-sm font-bold drop-shadow-md md:text-base">
                        {{ $detail['title'] ?? 'Bstation Video' }}
                    </p>
                </div>

                <div class="pointer-events-auto flex shrink-0 flex-col items-end gap-1.5">
                    <span class="text-[11px] font-semibold text-white/80 drop-shadow-md md:text-xs">
                        Eps <span x-text="currentEpisodeNumber"></span>/<span x-text="meta.episodeList.length"></span>
                    </span>
                    <button type="button"
                        class="flex items-center gap-1.5 rounded-full border border-white/20 bg-black/40 px-3 py-1 text-xs font-bold backdrop-blur transition hover:bg-black/60"
                        @click="showEpisodes = true">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span>
                            <span x-text="currentEpisodeNumber"></span>/<span x-text="meta.episodeList.length"></span>
                        </span>
                    </button>
                </div>
            </header>

            <aside x-show="controlsVisible" x-transition.opacity.duration.200ms
                class="pointer-events-none absolute right-3 top-1/2 z-30 flex -translate-y-1/2 flex-col items-center gap-5">
                <div class="pointer-events-auto flex flex-col items-center gap-1">
                    <button type="button"
                        class="flex h-12 w-12 items-center justify-center rounded-full border border-white/20 bg-black/40 backdrop-blur transition hover:bg-black/60"
                        title="Episode" @click="showEpisodes = true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span class="text-[11px] font-semibold drop-shadow-md">Episode</span>
                </div>

                <div class="pointer-events-auto flex flex-col items-center gap-1">
                    <button type="button"
                        class="flex h-12 w-12 items-center justify-center rounded-full border border-white/20 bg-black/40 backdrop-blur transition hover:bg-black/60"
                        :title="isMuted ? 'Nyalakan suara' : 'Bisukan'" @click="toggleMute()">
                        <svg x-show="!isMuted" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.536 8.464a5 5 0 010 7.072M17.828 5.172a9 9 0 010 13.656M11 5L6 9H2v6h4l5 4V5z" />
                        </svg>
                        <svg x-show="isMuted" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11 5L6 9H2v6h4l5 4V5zM23 9l-6 6M17 9l6 6" />
                        </svg>
                    </button>
                    <span class="text-[11px] font-semibold drop-shadow-md" x-text="isMuted ? 'Bisu' : 'Suara'"></span>
                </div>

                <div class="pointer-events-auto flex flex-col items-center gap-1">
                    <button wire:click="toggleFavorite" class="flex h-12 w-12 items-center justify-center rounded-full border backdrop-blur transition
                            {{ $isFavorited
    ? 'border-red-500 bg-red-600 hover:bg-red-700'
    : 'border-white/20 bg-black/40 hover:bg-black/60' }}" title="Favorit">
                        @if($isFavorited)
                            <svg class="h-5 w-5 fill-current text-white" viewBox="0 0 24 24">
                                <path
                                    d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        @endif
                    </button>
                    <span class="text-[11px] font-semibold drop-shadow-md">
                        {{ $isFavorited ? 'Favorit' : 'Simpan' }}
                    </span>
                </div>
            </aside>

            <section x-show="controlsVisible" x-transition.opacity.duration.200ms
                class="absolute inset-x-0 bottom-0 z-30 space-y-3 bg-gradient-to-t from-black/95 via-black/75 to-transparent p-4 pt-16">
                <p class="pr-14 text-sm font-bold leading-tight md:text-base">
                    {{ $detail['title'] ?? 'Bstation Video' }}
                    <span x-show="currentEpisodeTitle">
                        - <span x-text="currentEpisodeTitle"></span>
                    </span>
                </p>

                @if(!empty($detail['intro']))
                    <p class="line-clamp-2 pr-12 text-[11px] leading-snug text-white/70 md:text-xs">
                        {{ $detail['intro'] }}
                    </p>
                @endif

                <div class="flex items-center gap-2 font-mono text-[11px]">
                    <span class="w-9 text-white/80" x-text="fmtTime(currentTime)"></span>
                    <button type="button" class="group relative h-3 flex-1 cursor-pointer"
                        aria-label="Geser posisi video" @click="seek($event)">
                        <span class="absolute inset-x-0 top-1/2 h-1 -translate-y-1/2 rounded-full bg-white/20"></span>
                        <span class="absolute left-0 top-1/2 h-1 -translate-y-1/2 rounded-full bg-red-600"
                            :style="`width: ${progressPercent}%`"></span>
                        <span
                            class="absolute top-1/2 h-3 w-3 -translate-y-1/2 rounded-full bg-red-600 opacity-0 transition group-hover:opacity-100"
                            :style="`left: calc(${progressPercent}% - 6px)`"></span>
                    </button>
                    <span class="w-9 text-right text-white/80" x-text="fmtTime(duration)"></span>
                </div>

                <div class="flex max-h-16 flex-wrap items-center gap-2 overflow-y-auto">
                    <template x-for="(quality, index) in qualities" :key="`quality-${index}`">
                        <button type="button" class="rounded-md border px-3 py-1 text-[11px] font-bold transition"
                            :class="currentQualityIndex === index
                                ? 'border-red-600 bg-red-600 text-white'
                                : 'border-white/10 bg-white/10 text-white/80 hover:bg-white/20'"
                            @click="changeQuality(index)">
                            <span x-text="quality.label || quality.quality || `${quality.height || ''}p`"></span>
                        </button>
                    </template>

                    <span x-show="subtitles.length > 0" class="mx-0.5 h-5 w-px bg-white/20"></span>

                    <button x-show="subtitles.length > 0" type="button"
                        class="rounded-md border px-2.5 py-1 text-[11px] font-bold transition" :class="activeSubtitleKey === null
                            ? 'border-red-600 bg-red-600 text-white'
                            : 'border-white/10 bg-white/10 text-white/80 hover:bg-white/20'"
                        @click="setSubtitle(null)">
                        Sub Off
                    </button>

                    <template x-for="(subtitle, index) in subtitles" :key="subtitleKey(subtitle, index)">
                        <button type="button" class="rounded-md border px-2.5 py-1 text-[11px] font-bold transition"
                            :class="activeSubtitleKey === subtitleKey(subtitle, index)
                                ? 'border-red-600 bg-red-600 text-white'
                                : 'border-white/10 bg-white/10 text-white/80 hover:bg-white/20'"
                            @click="setSubtitle(subtitle, index)">
                            <span x-text="subtitle.label || subtitle.lang || `Sub ${index + 1}`"></span>
                        </button>
                    </template>
                </div>

                <button type="button"
                    class="flex w-full items-center justify-between rounded-lg border border-white/10 bg-white/5 p-3 text-left transition hover:bg-white/10"
                    @click="showEpisodes = true">
                    <span class="flex min-w-0 items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-white/60" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="truncate text-xs font-semibold"
                            x-text="currentEpisodeTitle || 'Pilih Episode'"></span>
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-white/50" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </section>
        </div>
    </div>

    <div x-show="showEpisodes" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[110] bg-black/70 backdrop-blur-sm" style="display: none;"
        @click.self="showEpisodes = false" @keydown.escape.window="showEpisodes = false">
        <aside x-show="showEpisodes" x-transition:enter="transition-transform duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-200" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-white/10 bg-[#0e0e0f]">
            <header class="flex shrink-0 items-center justify-between border-b border-white/10 p-5">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider">Pilih Episode</h2>
                    <p class="mt-0.5 text-[10px] text-white/50">
                        <span x-text="meta.episodeList.length"></span> episode
                    </p>
                </div>
                <button type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-full transition hover:bg-white/10"
                    @click="showEpisodes = false">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </header>

            <div class="flex-1 overflow-y-auto p-5">
                @if(!empty($lastWatchedEpisode))
                    <div
                        class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-red-500/25 bg-red-600/10 p-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-red-400/80">
                                Terakhir Ditonton
                            </p>
                            <p class="mt-0.5 truncate text-xs font-bold text-red-300">
                                <span x-text="episodeTitleById('{{ $lastWatchedEpisode }}')"></span>
                            </p>
                        </div>
                        <button wire:click="playEpisode({{ (int) $lastWatchedEpisode }})" type="button"
                            class="shrink-0 rounded-lg bg-red-600 px-3 py-1.5 text-[10px] font-bold text-white transition hover:bg-red-700"
                            @click="showEpisodes = false">
                            Lanjutkan
                        </button>
                    </div>
                @endif

                <div class="grid grid-cols-4 gap-2 sm:grid-cols-5">
                    <template x-for="(episode, index) in meta.episodeList" :key="episode.ep_id">
                        <button type="button"
                            class="relative flex h-12 items-center justify-center overflow-hidden rounded-lg border p-2 text-center text-[11px] font-bold"
                            :class="String(episode.ep_id) === String(activeEpId)
                                ? 'border-red-600 bg-red-600 text-white shadow-md shadow-red-600/20'
                                : isWatched(episode.ep_id)
                                    ? 'border-white/10 bg-white/[0.02] text-white/60 hover:bg-white/10'
                                    : 'border-white/10 bg-white/5 text-white/80 hover:bg-white/10'"
                            @click="pickEpisode(episode.ep_id)">
                            <span class="truncate" x-text="episode.title || `Ep ${index + 1}`"></span>
                            <svg x-show="isWatched(episode.ep_id) && String(episode.ep_id) !== String(activeEpId)"
                                class="absolute left-1 top-1 h-2.5 w-2.5 text-emerald-400" fill="none"
                                stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </aside>
    </div>
</div>

@push('styles')
    <style>
        body>.fi-topbar,
        body>header,
        nav[aria-label="Global"] {
            display: none !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        function registerBstationV2Player() {
            Alpine.data('bstationV2Player', () => ({
                iframeReady: false,
                playData: null,
                qualities: [],
                subtitles: [],
                currentQualityIndex: 0,
                activeSubtitleKey: null,
                activeEpId: null,
                nextEpisodeId: null,
                currentEpisodeTitle: '',
                currentEpisodeNumber: 0,
                currentTime: 0,
                duration: 0,
                isPaused: true,
                isMuted: false,
                isMutedAutoplay: false,
                isLoading: true,
                isPortrait: false,
                showEpisodes: false,
                showCenterIndicator: false,
                centerIndicatorTimeout: null,
                controlsVisible: true,
                controlsTimer: null,
                errorMessage: '',
                subtitleTimer: null,
                messageHandler: null,
                playerEventHandler: null,

                meta: {
                    title: '',
                    intro: '',
                    episodeList: [],
                    watchedEpisodes: [],
                    lastWatchedEpisode: null,
                    isFavorited: false,
                },

                get progressPercent() {
                    if (!this.duration) {
                        return 0;
                    }

                    return Math.min(
                        100,
                        Math.max(0, (this.currentTime / this.duration) * 100),
                    );
                },

                init() {
                    try {
                        const raw = document.getElementById('bstation-v2-data')?.textContent;

                        if (raw) {
                            this.meta = {
                                ...this.meta,
                                ...JSON.parse(raw),
                            };
                        }
                    } catch (error) {
                        console.warn('Metadata Bstation V2 gagal dibaca:', error);
                    }

                    this.messageHandler = (event) => this.handlePlayerMessage(event);
                    this.playerEventHandler = (event) => this.handlePlayerInit(event);

                    window.addEventListener('message', this.messageHandler);
                    window.addEventListener('init-bstation-player', this.playerEventHandler);
                    this.$watch('showEpisodes', () => this.showControls());
                    this.showControls();
                },

                destroy() {
                    if (this.messageHandler) {
                        window.removeEventListener('message', this.messageHandler);
                    }

                    if (this.playerEventHandler) {
                        window.removeEventListener(
                            'init-bstation-player',
                            this.playerEventHandler,
                        );
                    }

                    if (this.subtitleTimer) {
                        window.clearTimeout(this.subtitleTimer);
                    }

                    if (this.centerIndicatorTimeout) {
                        window.clearTimeout(this.centerIndicatorTimeout);
                    }

                    if (this.controlsTimer) {
                        window.clearTimeout(this.controlsTimer);
                    }
                },

                onIframeLoad() {
                    this.postToPlayer({ type: 'ping' });
                },

                postToPlayer(message) {
                    const iframe = this.$refs.playerIframe;

                    if (!iframe?.contentWindow) {
                        return;
                    }

                    iframe.contentWindow.postMessage(message, '*');
                },

                handlePlayerMessage(event) {
                    const iframe = this.$refs.playerIframe;

                    if (!iframe?.contentWindow || event.source !== iframe.contentWindow) {
                        return;
                    }

                    const message = event.data;

                    if (!message || typeof message !== 'object') {
                        return;
                    }

                    switch (message.type) {
                        case 'player-ready':
                            this.iframeReady = true;
                            this.sendInitToIframe();
                            break;

                        case 'player-loading':
                            this.isLoading = Boolean(message.value);
                            this.showControls();
                            break;

                        case 'player-state': {
                            const wasPaused = this.isPaused;

                            this.currentTime = Number(message.currentTime || 0);
                            this.duration = Number(message.duration || 0);
                            this.isPaused = Boolean(message.paused);
                            this.isMuted = Boolean(message.muted);

                            if (this.isPaused || wasPaused !== this.isPaused) {
                                this.showControls();
                            }
                            break;
                        }

                        case 'player-activity':
                            this.showControls();
                            break;

                        case 'player-ended':
                            this.playNext();
                            break;

                        case 'video-dimensions':
                            this.isPortrait = Boolean(message.isPortrait);
                            break;

                        case 'player-autoplay-muted':
                            this.isMutedAutoplay = Boolean(message.value);
                            this.isMuted = Boolean(message.value) || this.isMuted;
                            this.showControls();
                            break;

                        case 'player-error':
                            this.errorMessage = message.message || 'Player Bstation mengalami kesalahan.';
                            this.isLoading = false;
                            this.showControls();
                            break;
                    }
                },

                handlePlayerInit(event) {
                    this.showControls();

                    let payload = event.detail;

                    if (payload?.[0]) {
                        payload = payload[0];
                    }

                    if (!payload?.playData) {
                        return;
                    }

                    this.playData = payload.playData;
                    this.qualities = Array.isArray(payload.playData.qualities)
                        ? payload.playData.qualities
                        : [];
                    this.subtitles = Array.isArray(payload.playData.subtitles)
                        ? payload.playData.subtitles
                        : [];
                    this.currentQualityIndex = 0;
                    this.activeSubtitleKey = null;
                    this.activeEpId = payload.epId;
                    this.nextEpisodeId = payload.nextEpisodeId || null;
                    this.isLoading = true;
                    this.isMutedAutoplay = false;
                    this.errorMessage = '';

                    const episode = payload.episode
                        || this.meta.episodeList.find(
                            (item) => String(item.ep_id) === String(payload.epId),
                        );

                    this.updateEpisodeMeta(episode);

                    const episodeId = String(payload.epId);

                    if (!this.meta.watchedEpisodes.includes(episodeId)) {
                        this.meta.watchedEpisodes = [
                            ...this.meta.watchedEpisodes,
                            episodeId,
                        ];
                    }

                    this.sendInitToIframe();
                },

                updateEpisodeMeta(episode) {
                    const index = this.meta.episodeList.findIndex(
                        (item) => String(item.ep_id) === String(episode?.ep_id),
                    );

                    this.currentEpisodeNumber = index >= 0 ? index + 1 : 0;
                    this.currentEpisodeTitle = episode?.title || '';

                    if (episode?.long_title) {
                        this.currentEpisodeTitle += ` - ${episode.long_title}`;
                    }
                },

                sendInitToIframe() {
                    if (!this.iframeReady || !this.playData) {
                        return;
                    }

                    this.postToPlayer({
                        type: 'init',
                        playData: JSON.parse(JSON.stringify(this.playData)),
                        qualityIndex: this.currentQualityIndex,
                    });

                    if (this.subtitleTimer) {
                        window.clearTimeout(this.subtitleTimer);
                    }

                    this.subtitleTimer = window.setTimeout(() => {
                        const preferredSubtitle = this.subtitles.find((subtitle) =>
                            ['id', 'ind', 'in'].includes(
                                String(subtitle.lang || '').toLowerCase(),
                            ),
                        ) || this.subtitles[0];

                        if (preferredSubtitle) {
                            const index = this.subtitles.indexOf(preferredSubtitle);
                            this.setSubtitle(preferredSubtitle, index);
                        }
                    }, 500);
                },

                togglePlayPause() {
                    this.showControls();
                    this.postToPlayer({ type: 'toggle-play' });
                    this.flashCenterIndicator();
                },

                flashCenterIndicator() {
                    this.showCenterIndicator = true;

                    if (this.centerIndicatorTimeout) {
                        window.clearTimeout(this.centerIndicatorTimeout);
                    }

                    this.centerIndicatorTimeout = window.setTimeout(() => {
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
                        || this.isLoading
                        || this.showEpisodes
                        || this.isMutedAutoplay
                        || this.errorMessage
                    ) {
                        return;
                    }

                    this.controlsTimer = window.setTimeout(() => {
                        if (
                            !this.isPaused
                            && !this.isLoading
                            && !this.showEpisodes
                            && !this.isMutedAutoplay
                            && !this.errorMessage
                        ) {
                            this.controlsVisible = false;
                        }
                    }, 3000);
                },

                toggleMute() {
                    this.isMutedAutoplay = false;
                    this.showControls();
                    this.postToPlayer({ type: 'toggle-mute' });
                },

                unmute() {
                    this.isMutedAutoplay = false;
                    this.isMuted = false;
                    this.showControls();
                    this.postToPlayer({
                        type: 'set-muted',
                        value: false,
                    });
                },

                seek(event) {
                    this.showControls();

                    if (!this.duration) {
                        return;
                    }

                    const rect = event.currentTarget.getBoundingClientRect();
                    const percent = Math.min(
                        1,
                        Math.max(0, (event.clientX - rect.left) / rect.width),
                    );

                    this.postToPlayer({
                        type: 'seek',
                        time: percent * this.duration,
                    });
                },

                changeQuality(index) {
                    this.showControls();

                    if (index === this.currentQualityIndex) {
                        return;
                    }

                    this.currentQualityIndex = index;
                    this.postToPlayer({
                        type: 'change-quality',
                        qualityIndex: index,
                    });
                },

                subtitleKey(subtitle, index) {
                    return `${subtitle.lang || 'sub'}-${subtitle.label || index}-${index}`;
                },

                setSubtitle(subtitle, index = null) {
                    this.showControls();
                    this.activeSubtitleKey = subtitle
                        ? this.subtitleKey(subtitle, index)
                        : null;

                    this.postToPlayer({
                        type: 'change-subtitle',
                        subtitle: subtitle
                            ? JSON.parse(JSON.stringify(subtitle))
                            : null,
                    });
                },

                pickEpisode(epId) {
                    this.showControls();

                    if (String(epId) === String(this.activeEpId)) {
                        this.showEpisodes = false;
                        return;
                    }

                    this.showEpisodes = false;
                    this.$wire.playEpisode(Number(epId));
                },

                playNext() {
                    if (this.nextEpisodeId) {
                        this.$wire.playEpisode(Number(this.nextEpisodeId));
                    }
                },

                isWatched(epId) {
                    return (this.meta.watchedEpisodes || []).includes(String(epId));
                },

                episodeTitleById(epId) {
                    const episode = this.meta.episodeList.find(
                        (item) => String(item.ep_id) === String(epId),
                    );

                    return episode?.title || 'Episode terakhir';
                },

                fmtTime(seconds) {
                    if (!seconds || !Number.isFinite(seconds)) {
                        return '0:00';
                    }

                    const hours = Math.floor(seconds / 3600);
                    const minutes = Math.floor((seconds % 3600) / 60);
                    const secs = Math.floor(seconds % 60)
                        .toString()
                        .padStart(2, '0');

                    if (hours > 0) {
                        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs}`;
                    }

                    return `${minutes}:${secs}`;
                },
            }));
        }

        if (window.Alpine) {
            registerBstationV2Player();
        } else {
            document.addEventListener('alpine:init', registerBstationV2Player);
        }
    </script>
@endpush