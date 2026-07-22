<div class="min-h-screen bg-[#050505] text-white pb-16">
    <!-- Hero / Video Player Section -->
    <section class="relative w-full min-h-[500px] md:h-[85vh] bg-black overflow-hidden" x-data="watchPlayerInit()" x-init="preload()">
        <!-- Shaka Player Container (Only initialized / visible when playing) -->
        <div x-show="isPlaying" class="w-full h-full relative" x-ref="playerContainer" style="display: none;">
            <div class="w-full h-full bg-black relative">
                <video x-ref="video" class="w-full h-full object-contain" poster="{{ $activeEpisode['stillPath'] ?? ($series['backdropPath'] ?? '') }}"></video>
                <!-- IDLIX Titlebar style -->
                <div class="absolute top-0 left-0 w-full p-4 flex items-center gap-3 z-10 pointer-events-none bg-gradient-to-b from-black/80 to-transparent opacity-0 transition-opacity duration-300" id="shaka-titlebar">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs uppercase tracking-[0.18em] text-white/50 font-semibold drop-shadow-md">Now Playing</p>
                        <p class="truncate text-base font-semibold text-white/95 drop-shadow-md">{{ $activeEpisode['name'] ?? 'Episode' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Poster / Details Overlay (Initially visible, hidden when playing) -->
        <div x-show="!isPlaying" class="absolute inset-0 z-20 flex flex-col justify-end">
            <!-- Backdrop Image -->
            @if(!empty($series['backdropPath']))
                <img src="{{ $series['backdropPath'] }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-60">
            @elseif(!empty($series['posterPath']))
                <img src="{{ $series['posterPath'] }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30 blur-sm">
            @endif

            <!-- Gradients -->
            <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/40 to-transparent w-full md:w-[65%]"></div>
            <div class="absolute top-0 inset-x-0 h-48 bg-gradient-to-b from-[#050505]/80 via-[#050505]/30 to-transparent"></div>
            <div class="absolute bottom-0 inset-x-0 h-96 bg-gradient-to-t from-[#050505] via-[#050505]/80 to-transparent"></div>

            <!-- Center Play Button -->
            <div class="absolute inset-0 flex items-center justify-center">
                <button @click="playVideo()" class="w-20 h-20 rounded-full border border-white/40 bg-white/10 hover:bg-white/20 backdrop-blur-md flex items-center justify-center text-white transition duration-300 transform hover:scale-110 shadow-2xl focus:outline-none">
                    <svg class="w-8 h-8 fill-current ml-1" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </button>
            </div>

            <!-- Left Details Info Overlay -->
            <div class="relative w-full max-w-[1800px] mx-auto px-6 pt-24 pb-12 md:pb-16 flex flex-col items-start pointer-events-none">
                <span class="text-xs md:text-sm font-semibold tracking-wider text-red-500 uppercase mb-2">
                    {{ $series['title'] ?? 'Series' }}
                </span>
                <h1 class="text-3xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white mb-2 uppercase drop-shadow-lg" style="font-family: 'Arial Black', Impact, sans-serif; font-style: italic;">
                    Season {{ $seasonNumber }} Episode {{ $activeEpisode['episodeNumber'] }}
                </h1>
                <h2 class="text-base md:text-lg text-gray-300 font-medium mb-4">
                    {{ $activeEpisode['name'] ?? '' }}
                </h2>
                
                <div class="flex items-center gap-3 mt-1 text-xs md:text-sm text-gray-400 font-semibold mb-4 bg-black/25 px-3 py-1 rounded backdrop-blur">
                    @if(!empty($activeEpisode['runtime']))
                        <span>{{ $activeEpisode['runtime'] }}min</span>
                        <span>•</span>
                    @endif
                    @if(!empty($activeEpisode['voteAverage']))
                        <span class="text-yellow-400 font-bold flex items-center gap-0.5">
                            <svg class="w-3.5 h-3.5 fill-current text-yellow-500" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            {{ number_format((float)$activeEpisode['voteAverage'], 1) }}
                        </span>
                        <span>•</span>
                    @endif
                    @if(!empty($series['country']))
                        <span>{{ $series['country'] }}</span>
                        <span>•</span>
                    @endif
                    @if(!empty($activeEpisode['airDate']))
                        <span>Released: {{ \Carbon\Carbon::parse($activeEpisode['airDate'])->format('M Y') }}</span>
                    @endif
                </div>

                <!-- Episode Overview -->
                <p class="max-w-xl text-sm md:text-base text-gray-300/90 leading-relaxed line-clamp-3 mb-4 drop-shadow">
                    {{ $activeEpisode['overview'] ?? 'No overview available for this episode.' }}
                </p>
            </div>

            <!-- Next Episode Button in bottom right -->
            @php
                $nextEpisode = null;
                if (!empty($episodes)) {
                    $foundActive = false;
                    foreach ($episodes as $e) {
                        if ($foundActive) {
                            $nextEpisode = $e;
                            break;
                        }
                        if ((int)$e['episodeNumber'] === (int)$episodeNumber) {
                            $foundActive = true;
                        }
                    }
                }
            @endphp
            @if($nextEpisode)
                <div class="absolute bottom-12 right-12 z-30 pointer-events-auto">
                    @php
                        $nextEpUrl = \App\Filament\Streaming\Pages\WatchSeries::getUrl(parameters: [
                            'slug' => $seriesSlug,
                            'seasonNumber' => $seasonNumber,
                            'episodeNumber' => $nextEpisode['episodeNumber']
                        ], panel: 'streaming');
                    @endphp
                    <a href="{{ $nextEpUrl }}" class="group flex items-center gap-3 bg-black/60 hover:bg-black/80 hover:border-red-600 border border-white/10 backdrop-blur-md px-5 py-3 rounded-lg transition duration-300 shadow-xl">
                        <div class="text-right">
                            <span class="block text-[10px] font-bold text-red-500 uppercase tracking-widest">Next</span>
                            <span class="block text-xs font-semibold text-white/90">Ep {{ $nextEpisode['episodeNumber'] }}: {{ $nextEpisode['name'] }}</span>
                        </div>
                        <svg class="w-5 h-5 text-white transform group-hover:translate-x-1 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            @endif
        </div>
    </section>

    <!-- Episodes Grid Section -->
    <main class="w-full max-w-[1800px] mx-auto px-6 mt-12">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <h2 class="text-2xl font-extrabold text-white tracking-wide">More Episodes in Season {{ $seasonNumber }}</h2>
            
            <a href="{{ \App\Filament\Streaming\Pages\ShowSeries::getUrl(parameters: ['slug' => $seriesSlug], panel: 'streaming') }}" class="text-sm font-semibold text-red-500 hover:text-red-400 transition flex items-center gap-1">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/></svg>
                Change Season
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-4 sm:gap-5">
            @foreach($episodes as $episodeItem)
            @php
                $isActive = (int)$episodeItem['episodeNumber'] === (int)$episodeNumber;
                $seasonNum = $seasonNumber;
                $epUrl = \App\Filament\Streaming\Pages\WatchSeries::getUrl(parameters: [
                    'slug' => $seriesSlug,
                    'seasonNumber' => $seasonNum,
                    'episodeNumber' => $episodeItem['episodeNumber']
                ], panel: 'streaming');
            @endphp
            <a href="{{ $epUrl }}" class="block text-left group w-full focus:outline-none" draggable="false">
                <div class="content-card relative aspect-video overflow-hidden rounded-xl bg-zinc-900 w-full shadow-lg border {{ $isActive ? 'border-red-500' : 'border-white/5' }}">
                    @if(!empty($episodeItem['stillPath']))
                        <img src="{{ $episodeItem['stillPath'] }}" alt="{{ $episodeItem['name'] ?? 'Episode' }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy" decoding="async" draggable="false">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-zinc-600 bg-zinc-900">No Image</div>
                    @endif
                    
                    <!-- Episode Badge -->
                    <span class="absolute top-1.5 left-1.5 sm:top-2 sm:left-2 {{ $isActive ? 'bg-red-600' : 'bg-black/70' }} backdrop-blur-sm text-white text-[11px] sm:text-xs font-bold px-1.5 py-[3px] rounded z-10 border border-white/10">
                        S{{ str_pad($seasonNum, 2, '0', STR_PAD_LEFT) }}E{{ str_pad($episodeItem['episodeNumber'], 2, '0', STR_PAD_LEFT) }}
                    </span>
                    
                    <!-- Date Badge -->
                    @if(!empty($episodeItem['airDate']))
                    <span class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 bg-black/70 backdrop-blur-sm text-[11px] sm:text-xs font-medium px-1.5 py-[3px] rounded z-10 text-white/90 border border-white/10">
                        {{ \Carbon\Carbon::parse($episodeItem['airDate'])->format('j M y') }}
                    </span>
                    @endif

                    <div class="absolute bottom-0 left-0 right-0 h-12 bg-gradient-to-t from-black/80 to-transparent z-[5]"></div>
                    
                    <!-- Bottom Info Row -->
                    <div class="absolute bottom-1.5 left-1.5 right-1.5 sm:left-2 sm:right-2 flex items-end justify-between z-10">
                        <!-- Rating -->
                        <span class="inline-flex items-center gap-0.5 bg-black/75 backdrop-blur-sm border border-white/5 rounded px-1.5 py-[3px]">
                            <svg class="w-3 h-3 text-yellow-500 fill-current" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            <span class="text-[11px] sm:text-xs text-yellow-400 font-semibold">{{ $episodeItem['voteAverage'] ?? '0.0' }}</span>
                        </span>
                        
                        <!-- Duration -->
                        @if(!empty($episodeItem['runtime']))
                        <span class="bg-black/60 backdrop-blur-sm border border-white/5 text-white/80 text-[11px] sm:text-xs font-medium px-1.5 py-[3px] rounded">
                            {{ $episodeItem['runtime'] }}m
                        </span>
                        @endif
                    </div>
                </div>
                
                <!-- Title & Overview -->
                <div class="mt-1.5 sm:mt-2 px-0.5">
                    <h3 class="text-xs sm:text-sm font-medium truncate {{ $isActive ? 'text-red-500 font-bold' : 'text-gray-200' }} group-hover:text-red-500 transition duration-200">
                        {{ $episodeItem['name'] ?? 'Episode ' . $loop->iteration }}
                    </h3>
                    <p class="text-[11px] sm:text-xs text-gray-500 line-clamp-2 leading-relaxed mt-0.5 group-hover:text-gray-400 transition duration-200">
                        {{ $episodeItem['overview'] ?? '' }}
                    </p>
                </div>
            </a>
            @endforeach
        </div>
    </main>
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
.shaka-play-button, .shaka-fullscreen-button, .shaka-volume-bar, .shaka-range-element {
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
            preload() {
                // Do preload if any
            },
            async playVideo() {
                this.isPlaying = true;
                await this.$nextTick();
                await this.initPlayer();
            },
            async initPlayer() {
                const videoSrc = '{{ $streamData['video_url'] ?? '' }}';
                const subtitles = @json($streamData['subtitles'] ?? []);
                
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