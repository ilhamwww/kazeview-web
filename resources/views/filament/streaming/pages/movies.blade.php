<div>
    <!-- Hero Banner Slider -->
    @if(!empty($trending))
    <section x-data="{
        active: 0,
        itemsCount: {{ count($trending) }},
        interval: null,
        init() {
            this.startAutoplay();
        },
        startAutoplay() {
            this.stopAutoplay();
            this.interval = setInterval(() => {
                this.active = (this.active + 1) % this.itemsCount;
            }, 6000);
        },
        stopAutoplay() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
        },
        goTo(index) {
            this.active = index;
            this.startAutoplay();
        }
    }" @mouseenter="stopAutoplay()" @mouseleave="startAutoplay()" class="relative w-full min-h-[600px] md:h-[90vh] overflow-hidden bg-black mb-6">
        
        @foreach($trending as $index => $heroMovie)
        <div x-show="active === {{ $index }}"
             x-transition:enter="transition ease-out duration-1000"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-1000"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 w-full h-full"
             style="display: none;">
            
            <!-- Banner Image -->
            <img src="{{ $heroMovie['banner'] ?? ($heroMovie['thumbnail'] ?? '') }}" alt="{{ $heroMovie['title'] ?? '' }}" class="absolute inset-0 w-full h-full object-cover">

            <!-- Banner Overlays for Depth -->
            <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/60 to-transparent w-full md:w-[65%]"></div>
            <div class="absolute top-0 inset-x-0 h-48 bg-gradient-to-b from-[#050505]/80 via-[#050505]/30 to-transparent"></div>
            <div class="absolute bottom-0 inset-x-0 h-96 bg-gradient-to-t from-[#050505] via-[#050505]/90 to-transparent"></div>

            <!-- Banner Content -->
            <div class="relative w-full max-w-[1800px] mx-auto px-6 pt-32 h-full flex flex-col justify-end pb-16 md:pb-24 z-10">
                <!-- Badges -->
                <div class="flex items-center gap-2"
                     x-show="active === {{ $index }}"
                     x-transition:enter="transition ease-out duration-700 delay-300 transform"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <span class="bg-yellow-500 text-black text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider font-semibold">TRENDING</span>
                    <span class="bg-red-600 text-white text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider font-semibold">{{ isset($heroMovie['type']) ? str_replace('_', ' ', $heroMovie['type']) : 'MOVIE' }}</span>
                </div>

                <!-- Stylized Title -->
                <div class="mt-4 leading-none"
                     x-show="active === {{ $index }}"
                     x-transition:enter="transition ease-out duration-700 delay-500 transform"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <span class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white block uppercase">{{ $heroMovie['title'] ?? '' }}</span>
                </div>

                <!-- Metadata Row -->
                <div class="flex items-center gap-3 mt-6 text-xs md:text-sm text-gray-300 font-semibold"
                     x-show="active === {{ $index }}"
                     x-transition:enter="transition ease-out duration-700 delay-700 transform"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    @if(isset($heroMovie['rating']))
                    <span class="text-yellow-400 font-bold flex items-center gap-1">
                        <svg class="w-4 h-4 text-yellow-500 fill-current" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        {{ $heroMovie['rating'] }}
                    </span>
                    <span>•</span>
                    @endif
                    @if(isset($heroMovie['year']))
                    <span>{{ $heroMovie['year'] }}</span>
                    <span>•</span>
                    @endif
                    @if(isset($heroMovie['duration']))
                    <span>{{ $heroMovie['duration'] }}</span>
                    <span>•</span>
                    @endif
                    @if(isset($heroMovie['genre']))
                    <span class="text-gray-400 font-normal">{{ $heroMovie['genre'] }}</span>
                    @endif
                </div>

                <!-- Description -->
                <p class="max-w-xl mt-4 text-sm md:text-base text-gray-400 leading-relaxed font-medium line-clamp-3"
                   x-show="active === {{ $index }}"
                   x-transition:enter="transition ease-out duration-700 delay-1000 transform"
                   x-transition:enter-start="opacity-0 translate-y-4"
                   x-transition:enter-end="opacity-100 translate-y-0">
                    {{ $heroMovie['description'] ?? '' }}
                </p>

                <!-- CTA & Indicators -->
                <div class="flex items-center justify-between mt-8"
                     x-show="active === {{ $index }}"
                     x-transition:enter="transition ease-out duration-700 delay-[1200ms] transform"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <!-- Watch Button -->
                @php
                    $watchUrl = '#';
                    if (!empty($heroMovie['slug'])) {
                        $isSeries = isset($heroMovie['type']) && ($heroMovie['type'] === 'tv_series' || $heroMovie['type'] === 'tv' || $heroMovie['type'] === 'series');
                        $watchUrl = $isSeries
                            ? \App\Filament\Streaming\Pages\ShowSeries::getUrl(parameters: ['slug' => $heroMovie['slug']], panel: 'streaming')
                            : \App\Filament\Streaming\Pages\WatchMovie::getUrl(parameters: ['slug' => $heroMovie['slug']], panel: 'streaming');
                    }
                @endphp
                    <a href="{{ $watchUrl }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg font-bold text-sm tracking-wide shadow-lg shadow-red-950/40 transition duration-300 max-w-full">
                        <svg class="w-5 h-5 fill-current flex-shrink-0" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        <span class="truncate">Watch Now</span>
                    </a>
                </div>
            </div>
        </div>
        @endforeach

        <!-- Navigation Dots -->
        <div class="absolute bottom-6 right-6 md:right-12 z-20 flex gap-2.5">
            @foreach($trending as $index => $heroMovie)
            <button @click="goTo({{ $index }})"
                    :class="active === {{ $index }} ? 'bg-red-600 w-8' : 'bg-white/40 hover:bg-white/80 w-2.5'"
                    class="h-2.5 rounded-full transition-all duration-300 focus:outline-none"></button>
            @endforeach
        </div>

        <!-- Next / Prev Buttons -->
        <button @click="active = active === 0 ? itemsCount - 1 : active - 1; startAutoplay()" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 flex items-center justify-center rounded-full bg-black/30 hover:bg-black/60 text-white border border-white/10 backdrop-blur opacity-0 hover:opacity-100 transition duration-300 group-hover:opacity-100 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="active = (active + 1) % itemsCount; startAutoplay()" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 flex items-center justify-center rounded-full bg-black/30 hover:bg-black/60 text-white border border-white/10 backdrop-blur opacity-0 hover:opacity-100 transition duration-300 group-hover:opacity-100 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>

    </section>
    @endif

    <div class="space-y-6 px-6 pb-8">
        <!-- Header/Title -->
    <div class="flex items-center justify-between border-b border-zinc-800 pb-4">
        <h2 class="text-xl font-bold text-gray-100 flex items-center gap-2">
            <span class="w-1 h-5 bg-red-600 rounded-full"></span>
            Semua Film (Movies)
        </h2>
        <div class="text-xs text-zinc-400 bg-zinc-900 px-2.5 py-1 rounded border border-zinc-800">
            Total Loaded: <span class="text-red-500 font-bold">{{ count($movies) }}</span> Film
        </div>
    </div>

    <!-- Movie Grid -->
    @if(empty($movies))
        <div class="flex flex-col items-center justify-center py-16 text-zinc-500 bg-zinc-900/50 rounded-xl border border-dashed border-zinc-800">
            <svg class="w-12 h-12 mb-3 stroke-current opacity-60 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0h.5m-.5 0h-10.5m0 0h-.5" />
            </svg>
            <p class="text-sm font-medium">Tidak ada film yang ditemukan.</p>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($movies as $item)
                @php
                    $detailUrl = !empty($item['slug']) ? \App\Filament\Streaming\Pages\WatchMovie::getUrl(['slug' => $item['slug']]) : '#';
                @endphp
                <div class="group relative flex flex-col rounded overflow-hidden">
                    <a href="{{ $detailUrl }}" class="block relative w-full aspect-[2/3] overflow-hidden bg-zinc-950 rounded border border-zinc-800/80 group-hover:border-zinc-700/80 transition duration-300">
                        @if(!empty($item['thumbnail']))
                            <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] ?? '' }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-350 ease-out" loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-zinc-600 text-xs">No Image</div>
                        @endif
                        
                        <!-- Badges (Top Left) -->
                        @if(!empty($item['type']))
                        <span class="absolute top-2 left-2 px-1.5 py-0.5 text-[9px] font-extrabold text-gray-200 bg-black/60 backdrop-blur-md rounded border border-white/5 uppercase tracking-wider">
                            {{ str_replace('_', ' ', $item['type']) }}
                        </span>
                        @endif
                        
                        <!-- Badges (Top Right) -->
                        @if(!empty($item['quality']))
                        @php
                            $qualityColor = ($item['quality'] === 'BLU-RAY' || $item['quality'] === 'HD') ? 'bg-blue-950/60 text-blue-400 border-blue-500/20' : 'bg-emerald-950/60 text-emerald-400 border-emerald-500/20';
                        @endphp
                        <span class="absolute top-2 right-2 px-1.5 py-0.5 text-[9px] font-extrabold border rounded uppercase tracking-wider backdrop-blur-md {{ $qualityColor }}">
                            {{ $item['quality'] }}
                        </span>
                        @endif

                        <!-- Bottom Badges Info (Left & Right) -->
                        <div class="absolute bottom-2 inset-x-2 flex items-center justify-between">
                            <!-- Star Rating Badge -->
                            @if(!empty($item['rating']))
                            <span class="flex items-center gap-0.5 text-yellow-400 font-extrabold text-[10px] bg-black/60 backdrop-blur-md px-1.5 py-0.5 rounded border border-white/5">
                                <svg class="w-3 h-3 text-yellow-500 fill-current" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                {{ $item['rating'] }}
                            </span>
                            @else
                            <span></span>
                            @endif
                            
                            <!-- Duration Badge -->
                            @if(!empty($item['duration']))
                            <span class="flex items-center gap-0.5 text-gray-300 font-bold text-[10px] bg-black/60 backdrop-blur-md px-1.5 py-0.5 rounded border border-white/5">
                                {{ $item['duration'] }}
                            </span>
                            @endif
                        </div>
                    </a>
                    <!-- Title & Year -->
                    <h3 class="mt-2 text-xs md:text-[13px] font-bold text-gray-250 group-hover:text-red-500 transition duration-200 truncate leading-snug" title="{{ $item['title'] ?? '' }} {{ isset($item['year']) ? '('.$item['year'].')' : '' }}">
                        <a href="{{ $detailUrl }}">
                            {{ $item['title'] ?? '' }}
                        </a>
                        @if(!empty($item['year']))
                        <span class="text-zinc-500 font-semibold text-[11px]">({{ $item['year'] }})</span>
                        @endif
                    </h3>
                </div>
            @endforeach
        </div>

        <!-- Load More / Infinite Scroll Trigger -->
        @if($hasMorePages)
            <div x-intersect="$wire.loadMore()" class="flex items-center justify-center pt-8 mt-6">
                <div class="flex items-center gap-3 text-zinc-400 bg-zinc-900/50 px-5 py-2.5 rounded-full border border-zinc-800 shadow-sm">
                    <svg class="animate-spin h-5 w-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-semibold tracking-wide">Loading more movies...</span>
                </div>
            </div>
        @endif
    @endif
    </div>
</div>
