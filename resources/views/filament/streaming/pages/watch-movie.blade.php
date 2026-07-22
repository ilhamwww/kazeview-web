<div class="min-h-screen bg-[#050505] text-white pb-16">
    <!-- Main Content Container -->
    <div class="w-full max-w-[1800px] mx-auto px-6 pt-24 space-y-8">
        <!-- Back Link & Header -->
        <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
            <a href="{{ \App\Filament\Streaming\Pages\StreamingHome::getUrl(panel: 'streaming') }}"
                class="inline-flex items-center text-sm font-semibold text-gray-400 hover:text-white transition gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Jelajah
            </a>
            <h1 class="text-xl font-bold text-white tracking-wide">Sedang Menonton</h1>
        </div>

        <!-- Video Player Wrapper -->
        <div class="w-full bg-black rounded-xl overflow-hidden shadow-2xl aspect-video border border-zinc-800 relative"
            x-data="watchPlayerInit()">
            <!-- Shaka Player Container (Only initialized / visible when playing) -->
            <div x-show="isPlaying" class="w-full h-full relative" x-ref="playerContainer" style="display: none;">
                <div class="w-full h-full bg-black relative">
                    <video x-ref="video" class="w-full h-full object-contain" poster="{{ $media['banner'] }}"></video>
                    <!-- IDLIX Titlebar style -->
                    <div class="absolute top-0 left-0 w-full p-4 flex items-center gap-3 z-10 pointer-events-none bg-gradient-to-b from-black/80 to-transparent opacity-0 transition-opacity duration-300"
                        id="shaka-titlebar">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs uppercase tracking-[0.18em] text-white/50 font-semibold drop-shadow-md">
                                Now Playing</p>
                            <p class="truncate text-base font-semibold text-white/95 drop-shadow-md">
                                {{ $media['title'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Poster / Details Overlay (Initially visible, hidden when playing) -->
            <div x-show="!isPlaying" class="absolute inset-0 z-20 flex flex-col justify-end">
                <!-- Backdrop/Banner Image -->
                @if(!empty($media['banner']))
                    <img src="{{ $media['banner'] }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-75">
                @endif

                <!-- Gradients -->
                <div class="absolute inset-0 bg-gradient-to-t from-[#050505] via-[#050505]/40 to-transparent"></div>

                <!-- Center Play Button -->
                <div class="absolute inset-0 flex items-center justify-center">
                    <button @click="playVideo()"
                        class="w-20 h-20 rounded-full border border-white/40 bg-white/10 hover:bg-white/20 backdrop-blur-md flex items-center justify-center text-white transition duration-300 transform hover:scale-110 shadow-2xl focus:outline-none">
                        <svg class="w-8 h-8 fill-current ml-1" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Video Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center space-x-3 text-xs md:text-sm">
                    <span
                        class="inline-block text-[11px] bg-white/10 text-gray-300 font-bold px-2.5 py-0.5 rounded border border-white/10">{{ strtoupper($media['type']) }}</span>
                    <span class="text-emerald-500 font-bold flex items-center">
                        <svg class="w-4 h-4 mr-1 text-yellow-500 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                        </svg>
                        {{ $media['rating'] }} Rating
                    </span>
                    <span class="text-gray-400 font-medium">{{ $media['year'] }}</span>
                    <span class="text-gray-400 font-medium">{{ $media['duration'] }}</span>
                </div>
                <h2 class="text-2xl md:text-4xl font-extrabold text-white">{{ $media['title'] }}</h2>
                <p class="text-gray-400 text-sm md:text-base leading-relaxed font-medium">
                    {{ $media['description'] }}
                </p>
                <div class="text-xs md:text-sm text-gray-500 space-y-1 pt-2">
                    <p><strong class="text-gray-300">Genre:</strong> {{ $media['genre'] }}</p>
                </div>
            </div>

            <!-- Sidebar/Quick Actions -->
            <div class="bg-zinc-950 p-6 rounded-xl border border-zinc-800 h-max space-y-4 shadow-sm">
                <h3 class="font-bold text-lg text-white">Watch Information</h3>
                <div class="space-y-2 text-sm text-gray-400">
                    <div class="flex justify-between border-b border-zinc-800 pb-2">
                        <span>Quality:</span>
                        <span class="text-emerald-500 font-bold">{{ $media['quality'] ?? '1080p Full HD' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-zinc-800 pb-2">
                        <span>Audio:</span>
                        <span>Stereo 2.0 (English)</span>
                    </div>
                    <div class="flex justify-between pb-2">
                        <span>Subtitles:</span>
                        <span>English, Indonesia</span>
                    </div>
                </div>
                <button wire:click="toggleFavorite"
                    class="w-full {{ $isFavorited ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-white hover:bg-gray-200 text-black' }} text-sm py-2.5 rounded-lg font-bold transition flex items-center justify-center space-x-2 shadow">
                    @if($isFavorited)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Hapus dari Favorit</span>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambahkan ke Favorit</span>
                    @endif
                </button>

            </div>
        </div>

        <!-- Recommendations Row -->
        <section class="space-y-4 pt-8 border-t border-zinc-800">
            <h2 class="text-xl font-bold tracking-wide text-white border-l-4 border-red-600 pl-3">Rekomendasi Lainnya
            </h2>
            <div
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 2xl:grid-cols-10 gap-x-4 gap-y-6">
                @foreach(array_slice($recommendations, 0, 8) as $item)
                    @php
                        $itemUrl = '#';
                        if (!empty($item['slug'])) {
                            $itemIsSeries = isset($item['type']) && ($item['type'] === 'tv_series' || $item['type'] === 'tv' || $item['type'] === 'series');
                            $itemUrl = $itemIsSeries
                                ? \App\Filament\Streaming\Pages\ShowSeries::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming')
                                : \App\Filament\Streaming\Pages\WatchMovie::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming');
                        }
                    @endphp
                    <div class="flex flex-col group">
                        <a href="{{ $itemUrl }}"
                            class="block relative overflow-hidden rounded-lg transition duration-300 hover:scale-[1.02]">
                            <!-- Image -->
                            <div class="relative aspect-[2/3] w-full overflow-hidden">
                                <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}"
                                    class="w-full h-full object-cover group-hover:opacity-85 transition duration-300">

                                <!-- Badges (Top Left) -->
                                <span
                                    class="absolute top-2 left-2 px-1.5 py-0.5 text-[9px] font-extrabold text-gray-200 bg-black/60 backdrop-blur-md rounded border border-white/5 uppercase tracking-wider">
                                    {{ strtoupper($item['type']) }}
                                </span>

                                <!-- Badges (Top Right) -->
                                @php
                                    $qualityColor = $item['quality'] === 'BLU-RAY' ? 'bg-blue-950/60 text-blue-400 border-blue-500/20' : 'bg-emerald-950/60 text-emerald-400 border-emerald-500/20';
                                @endphp
                                <span
                                    class="absolute top-2 right-2 px-1.5 py-0.5 text-[9px] font-extrabold border rounded uppercase tracking-wider backdrop-blur-md {{ $qualityColor }}">
                                    {{ $item['quality'] }}
                                </span>

                                <!-- Bottom Badges Info (Left & Right) -->
                                <div class="absolute bottom-2 inset-x-2 flex items-center justify-between">
                                    <!-- Star Rating Badge -->
                                    <span
                                        class="flex items-center gap-0.5 text-yellow-400 font-extrabold text-[10px] bg-black/60 backdrop-blur-md px-1.5 py-0.5 rounded border border-white/5">
                                        <svg class="w-3 h-3 text-yellow-500 fill-current" viewBox="0 0 24 24">
                                            <path
                                                d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
                                        </svg>
                                        {{ $item['rating'] }}
                                    </span>

                                    <!-- Comments Badge -->
                                    <span
                                        class="flex items-center gap-0.5 text-gray-300 font-bold text-[10px] bg-black/60 backdrop-blur-md px-1.5 py-0.5 rounded border border-white/5">
                                        <svg class="w-3 h-3 fill-current opacity-80" viewBox="0 0 24 24">
                                            <path
                                                d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z" />
                                        </svg>
                                        {{ $item['comments'] }}
                                    </span>
                                </div>
                            </div>
                        </a>
                        <!-- Title & Year -->
                        <h3 class="mt-2 text-xs md:text-[13px] font-bold text-gray-200 group-hover:text-red-500 transition duration-200 truncate leading-snug"
                            title="{{ $item['title'] }} ({{ $item['year'] }})">
                            {{ $item['title'] }} <span
                                class="text-zinc-550 font-semibold text-[11.5px]">({{ $item['year'] }})</span>
                        </h3>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/shaka-player/4.7.9/controls.min.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <style>
        /* Hide native HTML5 video controls */
        video::-webkit-media-controls {
            display: none !important;
        }

        video::-webkit-media-controls-enclosure {
            display: none !important;
        }

        /* Shaka Customizations for IDLIX style */
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

        .shaka-text-container span {
            background-color: rgba(0, 0, 0, 0.6) !important;
            color: #fff !important;
            font-size: 1.1em !important;
            text-shadow: 1px 1px 2px #000 !important;
            padding: 2px 8px !important;
            border-radius: 4px !important;
        }

        .shaka-video-container:hover #shaka-titlebar {
            opacity: 1 !important;
        }
    </style>
@endpush

@push('scripts')
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/shaka-player/4.7.9/shaka-player.ui.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('watchPlayerInit', () => ({
                isPlaying: false,
                player: null,
                ui: null,
                destroy() {
                    if (this.ui) this.ui.destroy();
                    if (this.player) this.player.destroy();
                },
                async playVideo() {
                    this.isPlaying = true;
                    await this.$nextTick();
                    await this.initPlayer();
                },
                async initPlayer() {
                    const videoSrc = '{{ $media['video_url'] ?? '' }}';
                    const subtitles = @json($media['subtitles'] ?? []);

                    if (!videoSrc) return;

                    if (!window.shaka) {
                        setTimeout(() => this.initPlayer(), 100);
                        return;
                    }

                    shaka.polyfill.installAll();
                    if (!shaka.Player.isBrowserSupported()) {
                        console.error('Browser not supported!');
                        return;
                    }

                    const video = this.$refs.video;
                    const container = video.parentElement;

                    this.player = new shaka.Player(video);
                    this.ui = new shaka.ui.Overlay(this.player, container, video);

                    this.ui.configure({
                        controlPanelElements: [
                            'play_pause',
                            'rewind',
                            'fast_forward',
                            'mute',
                            'volume',
                            'time_and_duration',
                            'spacer',
                            'captions',
                            'quality',
                            'playback_rate',
                            'picture_in_picture',
                            'fullscreen'
                        ],
                        addSeekBar: true,
                        overflowMenuButtons: ['captions', 'quality', 'language', 'playback_rate', 'picture_in_picture']
                    });

                    this.player.addEventListener('error', (e) => console.error('Error code', e.detail.code, 'object', e.detail));

                    try {
                        await this.player.load(videoSrc);

                        video.play();

                        // Add text tracks manually
                        if (subtitles && subtitles.length > 0) {
                            for (const sub of subtitles) {
                                try {
                                    if (sub.path && sub.lang) {
                                        await this.player.addTextTrackAsync(sub.path, sub.lang, 'subtitles', 'text/vtt', '', sub.label || sub.lang);
                                    }
                                } catch (e) {
                                    console.error('Error adding text track:', e);
                                }
                            }

                            // Enable ID text track by default if exists
                            const tracks = this.player.getTextTracks();
                            const defaultTrack = tracks.find(t => t.language === 'id' || t.language === 'ind') || tracks[0];
                            if (defaultTrack) {
                                this.player.selectTextTrack(defaultTrack);
                                this.player.setTextTrackVisibility(true);
                            }
                        }
                    } catch (e) {
                        console.error('Error code', e.code, 'object', e);
                    }
                }
            }));
        });
    </script>
@endpush