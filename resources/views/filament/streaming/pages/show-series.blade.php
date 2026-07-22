<div class="min-h-screen bg-[#050505] text-white pb-16">
    <!-- Hero Banner Section -->
    <section class="relative w-full min-h-[500px] md:min-h-[80vh] overflow-hidden bg-black flex flex-col justify-end">
        <!-- Backdrop Image -->
        @if(!empty($series['backdropPath']))
            <img src="{{ $series['backdropPath'] }}" alt="{{ $series['title'] ?? '' }}" class="absolute inset-0 w-full h-full object-cover">
        @elseif(!empty($series['posterPath']))
            <img src="{{ $series['posterPath'] }}" alt="{{ $series['title'] ?? '' }}" class="absolute inset-0 w-full h-full object-cover opacity-40 blur-sm">
        @endif

        <!-- Overlays for visual depth -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/40 to-transparent w-full md:w-[65%] z-[2]"></div>
        <div class="absolute top-0 inset-x-0 h-48 bg-gradient-to-b from-[#050505]/80 via-[#050505]/30 to-transparent z-[2]"></div>
        <div class="absolute bottom-0 inset-x-0 h-96 bg-gradient-to-t from-[#050505] via-[#050505]/80 to-transparent z-[2]"></div>

        <!-- Details Overlay -->
        <div class="relative w-full max-w-[1800px] mx-auto px-6 pt-28 pb-12 z-10">
            <!-- Series Name/Logo -->
            <span class="text-xs md:text-sm font-semibold tracking-wider text-red-500 uppercase mb-2 block">
                TV SERIES
            </span>

            <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold tracking-tight text-white mb-3 uppercase drop-shadow-lg" style="font-family: 'Arial Black', Impact, sans-serif; font-style: italic;">
                {{ $series['title'] ?? '' }}
            </h1>

            <div class="flex items-center gap-3 mt-4 text-xs md:text-sm text-gray-300 font-semibold mb-4">
                @if(!empty($series['voteAverage']))
                    <span class="text-yellow-400 font-bold flex items-center gap-1 bg-black/40 px-2 py-0.5 rounded backdrop-blur">
                        <svg class="w-4 h-4 fill-current text-yellow-500" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        {{ number_format((float)$series['voteAverage'], 1) }}
                    </span>
                    <span>•</span>
                @endif
                @if(!empty($series['country']))
                    <span>{{ $series['country'] }}</span>
                    <span>•</span>
                @endif
                @if(!empty($series['firstAirDate']))
                    <span>First Aired: {{ \Carbon\Carbon::parse($series['firstAirDate'])->format('Y') }}</span>
                    <span>•</span>
                @endif
                @if(!empty($series['genres']))
                    <span class="text-gray-400 font-normal">
                        {{ implode(', ', array_column($series['genres'], 'name')) }}
                    </span>
                @endif
            </div>

            <!-- Series Overview -->
            <p class="max-w-2xl text-sm md:text-base text-gray-400 leading-relaxed line-clamp-4 mb-8">
                {{ $series['overview'] ?? 'No overview available for this series.' }}
            </p>

            <!-- CTA: Play First Episode -->
            @php
                $firstEpisode = !empty($currentSeasonData['episodes']) ? $currentSeasonData['episodes'][0] : null;
                $watchUrl = '#';
                if ($firstEpisode) {
                    $watchUrl = \App\Filament\Streaming\Pages\WatchSeries::getUrl([
                        'slug' => $seriesSlug,
                        'seasonNumber' => $selectedSeasonNumber ?? 1,
                        'episodeNumber' => $firstEpisode['episodeNumber'] ?? 1
                    ]);
                }
            @endphp
            
            <div class="flex items-center gap-3">
                @if($firstEpisode)
                    <a href="{{ $watchUrl }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold text-sm tracking-wide shadow-lg shadow-red-950/40 transition duration-300 max-w-full">
                        <svg class="w-5 h-5 fill-current flex-shrink-0" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        <span class="truncate">Watch Season {{ $selectedSeasonNumber }} Episode {{ $firstEpisode['episodeNumber'] ?? 1 }}</span>
                    </a>
                @endif
                <button wire:click="toggleFavorite"
                   class="inline-flex items-center gap-1.5 px-4 py-2.5 {{ $isFavorited ? 'bg-zinc-800 text-red-500 hover:bg-zinc-700' : 'bg-zinc-800 text-white hover:bg-zinc-700' }} rounded-lg text-sm font-bold transition shadow border border-zinc-700/50">
                    @if($isFavorited)
                        <svg class="w-5 h-5 fill-current text-red-500" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        Favorit
                    @else
                        <svg class="w-5 h-5 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        Tambah Favorit
                    @endif
                </button>
            </div>
        </div>
    </section>


    <!-- Episodes Grid Section -->
    <main class="w-full max-w-[1800px] mx-auto px-6 mt-12 relative min-h-[400px]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <h2 class="text-2xl font-extrabold text-white tracking-wide">Episodes</h2>
            
            @if(!empty($series['seasons']) && count($series['seasons']) > 1)
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-gray-400">Select Season:</span>
                    <select wire:change="changeSeason($event.target.value)" class="bg-zinc-900 border border-zinc-800 text-white rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 font-semibold cursor-pointer min-w-[120px]">
                        @foreach($series['seasons'] as $s)
                            <option value="{{ $s['seasonNumber'] }}" {{ $selectedSeasonNumber == $s['seasonNumber'] ? 'selected' : '' }}>
                                Season {{ $s['seasonNumber'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div wire:loading.delay wire:target="changeSeason" class="absolute inset-x-0 top-24 bottom-0 flex flex-col items-center justify-start pt-16 z-20">
            <svg class="animate-spin -ml-1 mr-3 h-10 w-10 text-red-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-400 font-semibold animate-pulse">Loading Episodes...</p>
        </div>

        <div wire:loading.remove wire:target="changeSeason" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-8 gap-4 sm:gap-5">
            @foreach($currentSeasonData['episodes'] ?? [] as $episodeItem)
            @php
                $seasonNum = $selectedSeasonNumber ?? 1;
                $epUrl = \App\Filament\Streaming\Pages\WatchSeries::getUrl([
                    'slug' => $seriesSlug,
                    'seasonNumber' => $seasonNum,
                    'episodeNumber' => $episodeItem['episodeNumber']
                ]);
            @endphp
            <a href="{{ $epUrl }}" class="block text-left group w-full focus:outline-none" draggable="false">
                <div class="content-card relative aspect-video overflow-hidden rounded-xl bg-zinc-900 w-full shadow-lg border border-white/5">
                    @if(!empty($episodeItem['stillPath']))
                        <img src="{{ $episodeItem['stillPath'] }}" alt="{{ $episodeItem['name'] ?? 'Episode' }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy" decoding="async" draggable="false">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-zinc-600 bg-zinc-900">No Image</div>
                    @endif
                    
                    <!-- Episode Badge -->
                    <span class="absolute top-1.5 left-1.5 sm:top-2 sm:left-2 bg-black/70 backdrop-blur-sm text-white text-[11px] sm:text-xs font-bold px-1.5 py-[3px] rounded z-10 border border-white/10">
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
                    <h3 class="text-xs sm:text-sm font-medium truncate text-gray-200 group-hover:text-red-500 transition duration-200">
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
