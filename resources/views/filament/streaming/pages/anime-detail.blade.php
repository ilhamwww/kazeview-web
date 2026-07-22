<div>
    <!-- Hero Banner -->
    <section class="relative w-full min-h-[500px] md:min-h-[60vh] overflow-hidden bg-black flex flex-col justify-end">
        <!-- Background Image -->
        @if(!empty($detail['thumbnail']))
            <img src="{{ $detail['thumbnail'] }}" alt="{{ $detail['title'] ?? '' }}" class="absolute inset-0 w-full h-full object-cover scale-110 blur-sm opacity-30">
        @endif

        <!-- Overlays -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/70 to-transparent w-full md:w-[70%]"></div>
        <div class="absolute top-0 inset-x-0 h-48 bg-gradient-to-b from-[#050505]/80 via-[#050505]/30 to-transparent"></div>
        <div class="absolute bottom-0 inset-x-0 h-96 bg-gradient-to-t from-[#050505] via-[#050505]/90 to-transparent"></div>

        <!-- Content -->
        <div class="relative w-full max-w-[1800px] mx-auto px-6 pt-32 pb-10 md:pb-16 z-10">
            <div class="flex flex-col md:flex-row gap-6 md:gap-10 items-start md:items-end w-full">
                <!-- Poster -->
                @if(!empty($detail['thumbnail']))
                <div class="flex-shrink-0 hidden md:block">
                    <img src="{{ $detail['thumbnail'] }}" alt="{{ $detail['title'] ?? '' }}"
                         class="w-48 h-72 object-cover rounded-xl border border-white/10 shadow-2xl shadow-black/60">
                </div>
                @endif

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <!-- Badges -->
                    <div class="flex items-center gap-2 mb-3">
                        <span class="bg-purple-600 text-white text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">ANIME</span>
                        @if(!empty($detail['episodes']))
                            <span class="bg-zinc-800 text-gray-300 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-zinc-700">
                                {{ count($detail['episodes']) }} Episode
                            </span>
                        @endif
                    </div>

                    <!-- Title -->
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold tracking-tight text-white leading-tight">
                        {{ $detail['title'] ?? 'Anime Detail' }}
                    </h1>

                    <!-- Genres -->
                    @if(!empty($detail['genres']))
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach($detail['genres'] as $genre)
                            <span class="px-2.5 py-1 text-xs font-bold text-purple-300 bg-purple-600/15 border border-purple-500/20 rounded-full">
                                {{ $genre }}
                            </span>
                        @endforeach
                    </div>
                    @endif

                    <!-- Description -->
                    @if(!empty($detail['description']))
                    <p class="max-w-2xl mt-4 text-sm md:text-base text-gray-400 leading-relaxed font-medium line-clamp-3">
                        {{ $detail['description'] }}
                    </p>
                    @endif

                    <!-- Actions -->
                    <div class="flex items-center gap-3 mt-6">
                        <a href="{{ \App\Filament\Streaming\Pages\ExploreAnime::getUrl(panel: 'streaming') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-gray-300 rounded-lg font-bold text-sm tracking-wide transition duration-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                            </svg>
                            Kembali
                        </a>

                        <button wire:click="toggleFavorite"
                           class="inline-flex items-center gap-2 px-5 py-2.5 {{ $isFavorited ? 'bg-red-600 hover:bg-red-700 border-red-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700 border-zinc-700 text-gray-300' }} border rounded-lg font-bold text-sm tracking-wide transition duration-300">
                            @if($isFavorited)
                                <svg class="w-4 h-4 fill-current text-white" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                Hapus dari Favorit
                            @else
                                <svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                Tambah ke Favorit
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Episode List Section -->
    <div class="space-y-6 px-6 pb-12" x-data="{ selectedEpisode: null, videoUrl: '', loadingVideo: false }">

        <!-- Video Player (shown when episode selected) -->
        <div x-show="videoUrl || loadingVideo" x-cloak class="mt-2">
            <!-- Loading -->
            <div x-show="loadingVideo" class="flex items-center justify-center py-16 bg-zinc-900/50 rounded-xl border border-zinc-800">
                <div class="flex items-center gap-3 text-zinc-400">
                    <svg class="animate-spin h-6 w-6 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-semibold">Memuat video...</span>
                </div>
            </div>

            <!-- Player -->
            <div x-show="videoUrl && !loadingVideo" x-cloak>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-1 h-5 bg-purple-600 rounded-full"></span>
                    <h2 class="text-lg font-bold text-gray-100">
                        Now Playing — <span class="text-purple-400" x-text="'Episode ' + selectedEpisode"></span>
                    </h2>
                </div>
                <div class="bg-black rounded-xl overflow-hidden border border-zinc-800 shadow-2xl" style="aspect-ratio: 16/9;">
                    <iframe x-bind:src="videoUrl"
                            class="w-full h-full"
                            frameborder="0"
                            allowfullscreen
                            allow="autoplay; encrypted-media; picture-in-picture"
                            sandbox="allow-scripts allow-same-origin allow-popups allow-forms">
                    </iframe>
                </div>
            </div>
        </div>

        <!-- Episode List Header -->
        <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
            <h2 class="text-xl font-bold text-gray-100 flex items-center gap-2">
                <span class="w-1 h-5 bg-purple-600 rounded-full"></span>
                Daftar Episode
            </h2>
            @if(!empty($detail['episodes']))
            <div class="text-xs text-zinc-400 bg-zinc-900 px-2.5 py-1 rounded border border-zinc-800">
                Total: <span class="text-purple-400 font-bold">{{ count($detail['episodes']) }}</span> Episode
            </div>
            @endif
        </div>

        @if(!empty($detail['episodes']))
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                @foreach($detail['episodes'] as $ep)
                    <button
                        type="button"
                        class="group flex items-center gap-4 p-4 rounded-xl border transition-all duration-200 text-left w-full cursor-pointer"
                        :class="selectedEpisode === '{{ $ep['episode'] }}'
                            ? 'bg-purple-600/15 border-purple-500/30 shadow-lg shadow-purple-950/20'
                            : 'bg-zinc-900/50 border-zinc-800 hover:bg-zinc-800/80 hover:border-zinc-700'"
                        @click="
                            loadingVideo = true;
                            selectedEpisode = '{{ $ep['episode'] }}';
                            videoUrl = '';
                            fetch('{{ route('api.anime.episode-video', ['url' => '']) }}' + encodeURIComponent('{{ $ep['url'] }}'))
                                .then(r => r.json())
                                .then(data => {
                                    videoUrl = data.video_url || '';
                                    loadingVideo = false;
                                    window.scrollTo({ top: 0, behavior: 'smooth' });
                                })
                                .catch(() => { loadingVideo = false; });
                        "
                    >
                        <!-- Episode Number -->
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center font-extrabold text-sm transition-colors duration-200"
                             :class="selectedEpisode === '{{ $ep['episode'] }}'
                                 ? 'bg-purple-600 text-white'
                                 : 'bg-zinc-800 text-zinc-400 group-hover:bg-purple-600/20 group-hover:text-purple-400'">
                            {{ $ep['episode'] }}
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold truncate transition-colors duration-200"
                               :class="selectedEpisode === '{{ $ep['episode'] }}' ? 'text-purple-300' : 'text-gray-200 group-hover:text-white'">
                                {{ $ep['title'] ?: 'Episode ' . $ep['episode'] }}
                            </p>
                            @if(!empty($ep['date']))
                            <p class="text-xs text-zinc-500 mt-0.5">{{ $ep['date'] }}</p>
                            @endif
                        </div>

                        <!-- Play Icon -->
                        <div class="flex-shrink-0 transition-colors duration-200"
                             :class="selectedEpisode === '{{ $ep['episode'] }}' ? 'text-purple-400' : 'text-zinc-600 group-hover:text-purple-500'">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </button>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-zinc-500 bg-zinc-900/50 rounded-xl border border-dashed border-zinc-800">
                <svg class="w-12 h-12 mb-3 stroke-current opacity-60 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-1.5A1.125 1.125 0 0118 18.375M20.625 4.5H3.375m17.25 0c.621 0 1.125.504 1.125 1.125M20.625 4.5h-1.5C18.504 4.5 18 5.004 18 5.625m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125M3.375 4.5c-.621 0-1.125.504-1.125 1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m1.5-3.75C5.496 8.25 6 7.746 6 7.125v-1.5M4.875 8.25C5.496 8.25 6 8.754 6 9.375v1.5m0-5.25v5.25m0-5.25C6 5.004 6.504 4.5 7.125 4.5h9.75c.621 0 1.125.504 1.125 1.125m1.125 2.625h1.5m-1.5 0A1.125 1.125 0 0118 7.125v-1.5m1.125 2.625c-.621 0-1.125.504-1.125 1.125v1.5m2.625-2.625c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125M18 5.625v5.25M7.125 12h9.75m-9.75 0A1.125 1.125 0 016 10.875M7.125 12C6.504 12 6 12.504 6 13.125m0-2.25C6 11.496 5.496 12 4.875 12M18 10.875c0 .621-.504 1.125-1.125 1.125M18 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m-12 5.25v-5.25m0 5.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125m-12 0v-1.5c0-.621-.504-1.125-1.125-1.125M18 18.375v-5.25m0 5.25v-1.5c0-.621.504-1.125 1.125-1.125M18 13.125v1.5c0 .621.504 1.125 1.125 1.125M18 13.125c0-.621.504-1.125 1.125-1.125M6 13.125v1.5c0 .621-.504 1.125-1.125 1.125M6 13.125C6 12.504 5.496 12 4.875 12m-1.5 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M19.125 12h1.5m0 0c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h1.5m14.25 0h1.5" />
                </svg>
                <p class="text-sm font-medium">Daftar episode tidak ditemukan.</p>
                <p class="text-xs text-zinc-600 mt-1">Coba kunjungi link sumber secara langsung.</p>
            </div>
        @endif
    </div>
</div>
