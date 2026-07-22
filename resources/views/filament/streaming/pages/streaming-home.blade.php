<div class="min-h-screen bg-[#050505] text-white pb-16">
    <!-- Full Screen Hero Banner Slider -->
    @if(!empty($heroes))
    <section x-data="{
        active: 0,
        itemsCount: {{ count($heroes) }},
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
    }" @mouseenter="stopAutoplay()" @mouseleave="startAutoplay()" class="relative w-full h-[90vh] overflow-hidden bg-black">
        
        @foreach($heroes as $index => $heroMovie)
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
            <div class="relative w-full max-w-[1800px] mx-auto px-6 h-full flex flex-col justify-end pb-16 md:pb-24 z-10">
                <!-- Badges -->
                <div class="flex items-center gap-2"
                     x-show="active === {{ $index }}"
                     x-transition:enter="transition ease-out duration-700 delay-300 transform"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <span class="bg-yellow-500 text-black text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">FEATURED</span>
                    <span class="bg-red-600 text-white text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">{{ isset($heroMovie['type']) ? str_replace('_', ' ', $heroMovie['type']) : 'MOVIE' }}</span>
                </div>

                <!-- Stylized Title -->
                <div class="mt-4 leading-none"
                     x-show="active === {{ $index }}"
                     x-transition:enter="transition ease-out duration-700 delay-500 transform"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <span class="text-4xl md:text-6xl lg:text-7xl font-extrabold tracking-tight text-white block uppercase">{{ $heroMovie['title'] ?? '' }}</span>
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
            @foreach($heroes as $index => $heroMovie)
            <button @click="goTo({{ $index }})"
                    :class="active === {{ $index }} ? 'bg-red-600 w-8' : 'bg-white/40 hover:bg-white/80 w-2.5'"
                    class="h-2.5 rounded-full transition-all duration-300 focus:outline-none"></button>
            @endforeach
        </div>

        <!-- Next / Prev Buttons (Optional overlay) -->
        <button @click="active = active === 0 ? itemsCount - 1 : active - 1; startAutoplay()" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 flex items-center justify-center rounded-full bg-black/30 hover:bg-black/60 text-white border border-white/10 backdrop-blur opacity-0 hover:opacity-100 transition duration-300 group-hover:opacity-100 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button @click="active = (active + 1) % itemsCount; startAutoplay()" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 flex items-center justify-center rounded-full bg-black/30 hover:bg-black/60 text-white border border-white/10 backdrop-blur opacity-0 hover:opacity-100 transition duration-300 group-hover:opacity-100 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>

    </section>
    @endif

    <!-- Sections Loop -->
    @foreach($sections as $section)
    <main class="w-full max-w-[1800px] mx-auto px-6 mt-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-extrabold text-white tracking-wide">{{ $section['title'] ?? 'Media' }}</h2>
            <button class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-gray-400 bg-zinc-900/60 border border-zinc-800 rounded-lg hover:text-white hover:bg-zinc-800 transition duration-200 shadow">
                View All
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>

        @if(($section['slug'] ?? '') === 'tmdb-trending')
            <!-- Scrollable Horizontal Cards -->
            <div class="flex overflow-x-auto gap-6 pb-4 snap-x snap-mandatory [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                @foreach($section['items'] ?? [] as $item)
                    @php
                        $itemUrl = '#';
                        if (!empty($item['slug'])) {
                            $itemIsSeries = isset($item['type']) && ($item['type'] === 'tv_series' || $item['type'] === 'tv' || $item['type'] === 'series');
                            $itemUrl = $itemIsSeries
                                ? \App\Filament\Streaming\Pages\ShowSeries::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming')
                                : \App\Filament\Streaming\Pages\WatchMovie::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming');
                        }
                    @endphp
                    <div class="relative w-[90vw] sm:w-[400px] md:w-[450px] lg:w-[500px] xl:w-[550px] flex-shrink-0 snap-start overflow-hidden rounded-2xl border border-zinc-900 bg-zinc-950/40 p-4 sm:p-5 flex gap-4 sm:gap-5 group transition duration-300 hover:scale-[1.01] hover:border-zinc-800/80 shadow-lg bg-cover bg-center"
                         style="background-image: linear-gradient(to right, #050505 0%, rgba(5,5,5,0.9) 45%, rgba(5,5,5,0.2) 100%), url('{{ $item['banner'] ?? $item['thumbnail'] }}')">
                        
                        <!-- Poster Image (Left Side) -->
                        <div class="relative w-24 sm:w-32 aspect-[2/3] flex-shrink-0 overflow-hidden rounded-xl bg-zinc-900 border border-white/5 shadow-md">
                            @if(!empty($item['thumbnail']))
                                <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] ?? '' }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-zinc-600 text-xs">No Image</div>
                            @endif
                        </div>

                        <!-- Details (Right Side) -->
                        <div class="flex flex-col justify-between flex-1 min-w-0 py-0.5">
                            <div>
                                <!-- Title -->
                                <h3 class="text-base sm:text-xl font-extrabold text-white tracking-tight uppercase group-hover:text-red-500 transition duration-200 line-clamp-1" title="{{ $item['title'] ?? '' }}">
                                    {{ $item['title'] ?? '' }}
                                </h3>

                                <!-- Metadata Row -->
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <!-- Rating -->
                                    @if(!empty($item['rating']))
                                    <span class="flex items-center gap-1 text-yellow-400 font-extrabold text-xs bg-zinc-900/90 border border-zinc-800/50 px-2 py-0.5 rounded-lg">
                                        <svg class="w-3.5 h-3.5 text-yellow-500 fill-current" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                        {{ $item['rating'] }}
                                    </span>
                                    @endif

                                    <!-- Duration / Season -->
                                    <span class="text-gray-300 font-bold text-xs bg-zinc-900/90 border border-zinc-800/50 px-2 py-0.5 rounded-lg">
                                        @if($itemIsSeries)
                                            {{ $item['seasons'] ?? '1 Seasons' }}
                                        @else
                                            {{ $item['duration'] ?: '120m' }}
                                        @endif
                                    </span>

                                    <!-- Trending Badge -->
                                    <span class="bg-yellow-500/10 text-yellow-500 border border-yellow-500/25 px-2 py-0.5 rounded-lg text-xs font-bold uppercase tracking-wider">
                                        Trending
                                    </span>
                                </div>

                                <!-- Description -->
                                <p class="text-gray-400 text-xs sm:text-sm font-medium leading-relaxed mt-3 line-clamp-2 sm:line-clamp-3">
                                    {{ $item['description'] ?? 'No overview available.' }}
                                </p>
                            </div>

                            <!-- Action Button -->
                            <a href="{{ $itemUrl }}" class="flex items-center gap-2 px-3 py-1.5 bg-red-600 hover:bg-red-700 active:scale-95 transition-all text-white rounded-lg font-bold text-[11px] sm:text-xs tracking-wide shadow-md shadow-red-950/20 w-fit mt-3">
                                <svg class="w-3 h-3 fill-current flex-shrink-0" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <span>Watch Now</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Scrollable Regular Cards -->
            <div class="flex overflow-x-auto gap-4 pb-4 snap-x snap-mandatory [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                @foreach($section['items'] ?? [] as $item)
                        @php
                            $itemUrl = '#';
                            if (!empty($item['slug'])) {
                                $itemIsSeries = isset($item['type']) && ($item['type'] === 'tv_series' || $item['type'] === 'tv' || $item['type'] === 'series');
                                $itemUrl = $itemIsSeries
                                    ? \App\Filament\Streaming\Pages\ShowSeries::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming')
                                    : \App\Filament\Streaming\Pages\WatchMovie::getUrl(parameters: ['slug' => $item['slug']], panel: 'streaming');
                            }
                        @endphp
                    <div class="flex flex-col group w-[140px] sm:w-[160px] md:w-[180px] xl:w-[200px] flex-shrink-0 snap-start">
                        <a href="{{ $itemUrl }}" class="block relative overflow-hidden rounded-lg transition duration-300 hover:scale-[1.02]">
                            <!-- Image -->
                            <div class="relative aspect-[2/3] w-full overflow-hidden bg-zinc-900">
                                @if(!empty($item['thumbnail']))
                                    <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] ?? '' }}" class="w-full h-full object-cover group-hover:opacity-85 transition duration-300" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-zinc-600">No Image</div>
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
                                    
                                    <!-- Episodes / Comments Badge -->
                                    @if(!empty($item['comments']))
                                    <span class="flex items-center gap-0.5 text-gray-300 font-bold text-[10px] bg-black/60 backdrop-blur-md px-1.5 py-0.5 rounded border border-white/5">
                                        <svg class="w-3 h-3 fill-current opacity-80" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                                        {{ $item['comments'] }}
                                    </span>
                                    @elseif(!empty($item['episodes']))
                                    <span class="flex items-center gap-0.5 text-gray-300 font-bold text-[10px] bg-black/60 backdrop-blur-md px-1.5 py-0.5 rounded border border-white/5">
                                        EP {{ $item['episodes'] }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                        <!-- Title & Year -->
                        <h3 class="mt-2 text-xs md:text-[13px] font-bold text-gray-200 group-hover:text-red-500 transition duration-200 truncate leading-snug" title="{{ $item['title'] ?? '' }} {{ isset($item['year']) ? '('.$item['year'].')' : '' }}">
                            {{ $item['title'] ?? '' }} 
                            @if(!empty($item['year']))
                            <span class="text-zinc-550 font-semibold text-[11.5px]">({{ $item['year'] }})</span>
                            @endif
                        </h3>
                    </div>
                @endforeach
            </div>
        @endif
    </main>
    @endforeach
</div>