<div class="min-h-screen bg-[#050505] text-white pt-28 pb-16 px-4 md:px-8 max-w-[1600px] mx-auto">
    <!-- Inject No-Referrer Meta dynamically for Dash.js CDN requests -->
    <script>
        document.querySelectorAll('meta[name="referrer"]').forEach(el => el.remove());
        const meta = document.createElement('meta');
        meta.name = 'referrer';
        meta.content = 'no-referrer';
        document.head.appendChild(meta);
    </script>

    <div x-data="bstationPlayerInit()">

        <!-- Two Column Layout: Left (Player & Meta), Right (Episodes Grid) -->
        <div class="flex flex-col lg:flex-row gap-6 items-start">
            
            <!-- Left Column: Player & Detail Info -->
            <div class="flex-1 w-full min-w-0">
                
                <!-- Player Container -->
                <div x-show="isPlaying" class="mb-6 w-full bg-black rounded-3xl overflow-hidden border border-zinc-800 shadow-2xl relative" style="display: none;" x-ref="playerContainer">
                    <div :class="isPortrait ? 'aspect-[9/16] max-w-[450px] mx-auto w-full border-x border-zinc-800/50' : 'aspect-video w-full'" class="bg-black relative transition-all duration-500">
                        <iframe x-ref="playerIframe"
                                src="/bstation-embed.html"
                                sandbox="allow-scripts"
                                allow="autoplay; encrypted-media"
                                class="w-full h-full border-0 absolute inset-0"
                                allowfullscreen>
                        </iframe>
                        
                        <!-- Loading Overlay -->
                        <div x-show="isLoading" class="absolute inset-0 flex items-center justify-center bg-black/40 pointer-events-none z-20">
                            <svg class="animate-spin h-12 w-12 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        
                        <!-- Custom Title Overlay (Top Left) -->
                        <div class="absolute top-0 left-0 w-full p-4 flex items-center gap-3 z-10 pointer-events-none bg-gradient-to-b from-black/85 via-black/40 to-transparent">
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] uppercase tracking-[0.2em] text-red-500 font-extrabold drop-shadow-md">Kazeview Player</p>
                                <p class="truncate text-sm sm:text-base font-bold text-white/95 drop-shadow-md">
                                    {{ $detail['title'] ?? '' }} - Episode <span x-text="currentEpisodeTitle"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Controls below video -->
                    <div class="flex flex-col gap-4 bg-zinc-900/60 p-4 border-t border-zinc-800">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <!-- Quality Selection -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold text-zinc-400">Kualitas:</span>
                                <template x-for="(q, index) in availableQualities" :key="index">
                                    <button @click="loadQuality(index)" 
                                            :class="currentQualityIndex === index ? 'bg-red-600 text-white border-red-600' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-300 border-zinc-700'" 
                                            class="px-3 py-1.5 rounded-lg text-[11px] font-bold border transition whitespace-nowrap">
                                        <span x-text="q.label || q.quality"></span>
                                    </button>
                                </template>
                            </div>

                            <!-- Next Episode Trigger -->
                            @if(!empty($episodesData['sections']))
                                <div class="flex items-center gap-2">
                                    <template x-if="nextEpisodeId">
                                        <button @click="playNext()" 
                                                class="px-4 py-2 bg-red-600/10 hover:bg-red-600 text-red-500 hover:text-white rounded-lg text-xs font-bold border border-red-500/30 transition">
                                            Episode Berikutnya &rarr;
                                        </button>
                                    </template>
                                </div>
                            @endif
                        </div>

                        <!-- Subtitle Selector -->
                        <div class="flex flex-wrap items-center gap-2 border-t border-zinc-800/60 pt-3">
                            <span class="text-xs font-semibold text-zinc-400">Subtitle:</span>
                            <button @click="setSubtitle(null)" 
                                    :class="!activeSubtitleLang ? 'bg-red-600 text-white border-red-600' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-300 border-zinc-700'" 
                                    class="px-2.5 py-1 rounded-md text-[11px] font-bold border transition">
                                Off
                            </button>
                            <template x-for="(sub, index) in subtitles" :key="index">
                                <button @click="setSubtitle(sub)" 
                                        :class="activeSubtitleLang === sub.lang ? 'bg-red-600 text-white border-red-600' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-300 border-zinc-700'" 
                                        class="px-2.5 py-1 rounded-md text-[11px] font-bold border transition">
                                    <span x-text="sub.label || sub.lang"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Bstation Detail Meta Card -->
                <div class="bg-zinc-900/40 border border-zinc-800 rounded-3xl p-6 md:p-8 backdrop-blur-md">
                    <div class="flex flex-col md:flex-row gap-8">
                        <!-- Cover Image -->
                        <div class="w-full md:w-48 aspect-[3/4] rounded-2xl bg-black border border-zinc-800 flex items-center justify-center overflow-hidden flex-shrink-0 shadow-2xl relative">
                            @if(!empty($detail['cover']))
                                <img src="{{ $detail['cover'] }}" alt="{{ $detail['title'] ?? '' }}" class="w-full h-full object-cover" referrerpolicy="no-referrer">
                            @else
                                <div class="text-zinc-600 flex flex-col items-center gap-2">
                                    <svg class="w-16 h-16 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z"/>
                                    </svg>
                                    <span class="text-xs uppercase tracking-widest font-semibold">No Cover</span>
                                </div>
                            @endif

                            @if(!empty($detail['badge']))
                                <span class="absolute top-3 left-3 px-2 py-0.5 bg-yellow-500 text-[10px] font-extrabold text-black uppercase tracking-wider rounded">
                                    {{ $detail['badge'] }}
                                </span>
                            @endif
                        </div>

                        <!-- Info Details -->
                        <div class="flex-1 space-y-4 text-left">
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-600/10 border border-red-500/25 rounded-full text-red-500 text-xs font-semibold uppercase tracking-wider">
                                Bstation
                            </div>
                            
                            <h1 class="text-2xl md:text-4xl font-extrabold tracking-tight">
                                {{ $detail['title'] ?? 'Bstation Video' }}
                            </h1>

                            <!-- Subtitle / Meta -->
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-zinc-400 text-xs sm:text-sm font-medium">
                                @if(!empty($detail['subtitle']))
                                    <span class="bg-zinc-800 px-2.5 py-1 rounded text-zinc-300">{{ $detail['subtitle'] }}</span>
                                @endif
                                @if(!empty($detail['styles']))
                                    <span class="text-zinc-500">Kategori: <strong class="text-zinc-300">{{ $detail['styles'] }}</strong></span>
                                @endif

                                <button wire:click="toggleFavorite"
                                   class="inline-flex items-center gap-1.5 px-3 py-1 {{ $isFavorited ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-300' }} rounded text-xs font-bold transition">
                                    @if($isFavorited)
                                        <svg class="w-3.5 h-3.5 fill-current text-white" viewBox="0 0 24 24">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                        Favorit
                                    @else
                                        <svg class="w-3.5 h-3.5 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                        Tambah Favorit
                                    @endif
                                </button>
                            </div>

                            <!-- Intro / Synopsis -->
                            @if(!empty($detail['intro']))
                                <div class="space-y-1.5 pt-2">
                                    <h3 class="text-xs uppercase tracking-widest text-zinc-500 font-bold">Sinopsis / Deskripsi</h3>
                                    <p class="text-zinc-300 text-sm leading-relaxed max-w-4xl font-normal">
                                        {{ $detail['intro'] }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Episode Lists Sidebar -->
            <div class="w-full lg:w-[400px] lg:shrink-0 lg:sticky lg:top-28">
                <div class="bg-[#0e0e0f] border border-zinc-800/80 rounded-3xl p-5 backdrop-blur-md max-h-[calc(100vh-10rem)] overflow-y-auto">
                    @if(!empty($episodesData['sections']))
                        @foreach($episodesData['sections'] as $section)
                            <div class="space-y-4 @if(!$loop->first) mt-6 @endif">
                                <div class="flex items-center justify-between border-b border-zinc-800 pb-3 mb-2">
                                    <h2 class="text-sm font-bold tracking-wider text-zinc-300 uppercase">{{ $section['title'] ?? 'Daftar Episode' }}</h2>
                                    <span class="text-[10px] text-zinc-500 font-mono bg-zinc-900 px-2 py-0.5 rounded border border-zinc-800/50">
                                        {{ count($section['episodes'] ?? []) }} Ep
                                    </span>
                                </div>

                                @if(!empty($section['episodes']))
                                    <!-- Sidebar Grid Layout -->
                                    <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-4 gap-2">
                                        @foreach($section['episodes'] as $ep)
                                            @php
                                                $isLocked = !empty($ep['badge']) && (strtoupper($ep['badge']) === 'VIP');
                                                $epId = $ep['ep_id'];
                                            @endphp
                                            <button wire:click="playEpisode({{ $epId }})" 
                                                    wire:loading.class="opacity-60 cursor-wait"
                                                    :class="activeEpId == '{{ $epId }}' 
                                                        ? 'bg-red-600 text-white border-red-600 shadow-md shadow-red-600/20' 
                                                        : 'bg-zinc-900/60 hover:bg-zinc-900 text-zinc-300 border-zinc-800/60 hover:border-zinc-700'"
                                                    class="p-2.5 rounded-xl border font-bold text-center transition-all duration-200 flex flex-col justify-center items-center h-14 relative overflow-hidden group">
                                                
                                                <span class="text-xs font-extrabold tracking-wide">{{ $ep['title'] }}</span>
                                                
                                                @if($isLocked)
                                                    <span class="absolute top-1 right-1 px-1 py-0.2 bg-yellow-500 text-[6px] font-black text-black uppercase tracking-wider rounded">
                                                        VIP
                                                    </span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="py-8 text-center text-zinc-600 border border-dashed border-zinc-800/60 rounded-xl text-xs">
                                        Tidak ada episode.
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="py-12 text-center text-zinc-500 border border-dashed border-zinc-800 rounded-2xl text-xs">
                            Belum ada daftar episode yang tersedia dari Bstation.
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>
</div>

@push('scripts')
<script>
    function bstationPlayerInit() {
        return {
            isPlaying: false,
            isLoading: false,
            iframeReady: false,
            playData: null,
            activeEpId: null,
            currentEpisodeTitle: '',
            nextEpisodeId: null,
            isPortrait: false,
            
            availableQualities: [],
            currentQualityIndex: 0,
            
            subtitles: [],
            activeSubtitleLang: null,

            init() {
                // Ensure no-referrer meta is set and any existing ones are cleared
                document.querySelectorAll('meta[name="referrer"]').forEach(el => el.remove());
                const meta = document.createElement('meta');
                meta.name = 'referrer';
                meta.content = 'no-referrer';
                document.head.appendChild(meta);

                // Listen for messages from iframe
                window.addEventListener('message', (event) => {
                    const msg = event.data;
                    if (!msg) return;

                    if (msg.type === 'player-ready') {
                        this.iframeReady = true;
                        this.sendInitToIframe();
                    } else if (msg.type === 'player-loading') {
                        this.isLoading = msg.value;
                    } else if (msg.type === 'player-ended') {
                        this.playNext();
                    } else if (msg.type === 'video-dimensions') {
                        this.isPortrait = msg.isPortrait;
                    }
                });

                window.addEventListener('init-bstation-player', (e) => {
                    let data = e.detail.playData;
                    let epId = e.detail.epId;

                    if (!data && e.detail && e.detail[0]) {
                        data = e.detail[0].playData;
                        epId = e.detail[0].epId;
                    }
                    if (!data) return;

                    this.playData = data;
                    this.activeEpId = epId;
                    this.isPlaying = true;
                    this.currentQualityIndex = 0;
                    this.activeSubtitleLang = null;

                    // Parse meta
                    this.updateEpisodeMeta(epId);

                    // Setup qualities and subtitles
                    this.availableQualities = data.qualities || [];
                    this.subtitles = data.subtitles || [];
                    
                    this.$nextTick(() => {
                        this.$refs.playerContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        if (this.iframeReady) {
                            this.sendInitToIframe();
                        }
                    });
                });
            },

            sendInitToIframe() {
                const iframe = this.$refs.playerIframe;
                if (!iframe || !this.playData) return;

                // Send play data and initial configuration
                // Parse and stringify to remove Alpine.js Proxy wrapper before postMessage
                const rawPlayData = JSON.parse(JSON.stringify(this.playData));

                iframe.contentWindow.postMessage({
                    type: 'init',
                    playData: rawPlayData,
                    qualityIndex: this.currentQualityIndex
                }, '*');

                // Load default subtitle
                setTimeout(() => {
                    const idSub = this.subtitles.find(s => s.lang === 'id' || s.lang === 'ind');
                    if (idSub) this.setSubtitle(idSub);
                    else if (this.subtitles.length > 0) this.setSubtitle(this.subtitles[0]);
                }, 500);
            },

            updateEpisodeMeta(epId) {
                const episodes = @json(collect($episodesData['sections'] ?? [])->flatMap->episodes->toArray());
                const currentEp = episodes.find(e => e.ep_id == epId);
                
                if (currentEp) {
                    this.currentEpisodeTitle = currentEp.title;
                    if (currentEp.long_title) this.currentEpisodeTitle += ' - ' + currentEp.long_title;

                    const currentIndex = episodes.findIndex(e => e.ep_id == epId);
                    if (currentIndex !== -1 && currentIndex < episodes.length - 1) {
                        this.nextEpisodeId = episodes[currentIndex + 1].ep_id;
                    } else {
                        this.nextEpisodeId = null;
                    }
                }
            },

            playNext() {
                if (this.nextEpisodeId) {
                    this.$wire.playEpisode(this.nextEpisodeId);
                }
            },

            loadQuality(qIndex) {
                this.currentQualityIndex = qIndex;
                const iframe = this.$refs.playerIframe;
                if (iframe) {
                    iframe.contentWindow.postMessage({
                        type: 'change-quality',
                        qualityIndex: qIndex
                    }, '*');
                }
            },

            setSubtitle(sub) {
                this.activeSubtitleLang = sub ? sub.lang : null;
                const iframe = this.$refs.playerIframe;
                if (iframe) {
                    const rawSub = sub ? JSON.parse(JSON.stringify(sub)) : null;
                    iframe.contentWindow.postMessage({
                        type: 'change-subtitle',
                        subtitle: rawSub
                    }, '*');
                }
            }
        }
    }
</script>
@endpush