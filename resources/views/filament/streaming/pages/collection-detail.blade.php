<div class="min-h-screen bg-[#050505] text-white pb-24"
     x-data="{
        activeSection: 0,
        scrollToSection(index) {
            this.activeSection = index;
            const el = document.getElementById('section-' + index);
            if (el) {
                const yOffset = -100;
                const y = el.getBoundingClientRect().top + window.pageYOffset + yOffset;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
        }
     }">

    @php
        $bannerImg = $collection['banner'] ?? $collection['poster'] ?? 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1920&q=80';
        $posterImg = $collection['poster'] ?? $collection['banner'] ?? '';
        $collectionsListUrl = \App\Filament\Streaming\Pages\Collections::getUrl(panel: 'streaming');
    @endphp

    <!-- Hero Backdrop Banner -->
    <div class="relative w-full min-h-[480px] md:min-h-[560px] bg-black overflow-hidden flex items-end">
        <!-- Backdrop Image -->
        <img src="{{ $bannerImg }}"
             alt="{{ $collection['title'] }}"
             class="absolute inset-0 w-full h-full object-cover object-center filter brightness-90">

        <!-- Cinematic Gradient Overlays -->
        <div class="absolute inset-0 bg-gradient-to-t from-[#050505] via-[#050505]/75 to-black/30"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/60 to-transparent w-full md:w-[70%]"></div>
        <div class="absolute top-0 inset-x-0 h-32 bg-gradient-to-b from-[#050505]/90 to-transparent"></div>

        <!-- Hero Content Container -->
        <div class="relative z-10 w-full max-w-7xl mx-auto px-4 md:px-6 pt-24 md:pt-28 pb-10 md:pb-14">
            <!-- Navigation Back & Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-zinc-400 mb-6">
                <a href="{{ $collectionsListUrl }}" class="inline-flex items-center gap-1.5 text-zinc-300 hover:text-white bg-black/40 hover:bg-black/60 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/10 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Koleksi</span>
                </a>
                <span>/</span>
                <span class="text-zinc-500">Franchise</span>
                <span>/</span>
                <span class="text-white font-semibold truncate">{{ $collection['title'] }}</span>
            </div>

            <!-- Main Hero Details (Poster + Meta + Title + Desc) -->
            <div class="flex flex-col md:flex-row items-center md:items-end gap-6 md:gap-8">
                <!-- Collection Poster (Hidden on small mobile if empty, shown on tablet/desktop) -->
                @if(!empty($posterImg))
                    <div class="w-36 sm:w-44 md:w-52 flex-shrink-0 aspect-[2/3] rounded-2xl overflow-hidden shadow-2xl shadow-black/90 border border-zinc-700/60 bg-zinc-900 hidden sm:block">
                        <img src="{{ $posterImg }}" alt="{{ $collection['title'] }}" class="w-full h-full object-cover">
                    </div>
                @endif

                <!-- Meta & Text Info -->
                <div class="flex-1 text-center md:text-left space-y-3.5">
                    <!-- Badges -->
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                        <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-red-400 bg-red-950/60 border border-red-500/20 rounded-md backdrop-blur-md">
                            {{ str_replace('_', ' ', $collection['kind']) }}
                        </span>
                        
                        <span class="px-2.5 py-1 text-[10px] font-bold text-zinc-300 bg-black/50 border border-white/10 rounded-md backdrop-blur-md">
                            {{ $collection['itemCount'] }} Titles
                        </span>

                        @if(!empty($collection['yearRange']))
                            <span class="px-2.5 py-1 text-[10px] font-bold text-zinc-300 bg-black/50 border border-white/10 rounded-md backdrop-blur-md">
                                {{ $collection['yearRange'] }}
                            </span>
                        @endif

                        @if(!empty($collection['childCount']) && $collection['childCount'] > 0)
                            <span class="px-2.5 py-1 text-[10px] font-bold text-amber-300 bg-amber-950/60 border border-amber-500/20 rounded-md backdrop-blur-md">
                                {{ $collection['childCount'] }} Phases / Sections
                            </span>
                        @endif
                    </div>

                    <!-- Title -->
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight leading-tight">
                        {{ $collection['title'] }}
                    </h1>

                    <!-- Description -->
                    @if(!empty($collection['description']))
                        <p class="text-sm md:text-base text-zinc-300 max-w-3xl leading-relaxed">
                            {{ $collection['description'] }}
                        </p>
                    @endif

                    <!-- Quick Jump Action -->
                    <div class="pt-2 flex items-center justify-center md:justify-start gap-3">
                        <button @click="scrollToSection(0)"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs md:text-sm font-bold shadow-lg shadow-red-950/40 transition duration-200">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <span>Mulai Tonton Kronologi</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-Section Sticky Quick Nav (if more than 1 section, like MCU phases) -->
    @if(count($sections) > 1)
        <div class="sticky top-16 z-30 bg-[#050505]/90 backdrop-blur-xl border-y border-zinc-800/80 px-4 md:px-6 py-3 my-6">
            <div class="max-w-7xl mx-auto flex items-center gap-2 overflow-x-auto scrollbar-none">
                <span class="text-xs font-bold text-zinc-400 mr-2 flex-shrink-0 uppercase tracking-wider">Lompat Ke:</span>
                @foreach($sections as $sIdx => $sec)
                    <button @click="scrollToSection({{ $sIdx }})"
                            :class="activeSection === {{ $sIdx }} ? 'bg-red-600 text-white font-bold shadow-md shadow-red-900/30' : 'bg-zinc-900 text-zinc-400 hover:text-white hover:bg-zinc-800 border border-zinc-800'"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition duration-200">
                        {{ $sec['title'] }} ({{ $sec['itemCount'] }})
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Content Sections Body -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 space-y-12 {{ count($sections) <= 1 ? 'mt-8' : '' }}">
        @foreach($sections as $sIdx => $sec)
            <section id="section-{{ $sIdx }}" class="space-y-5 scroll-mt-28">
                <!-- Section Header -->
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 border-b border-zinc-800/80 pb-3">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="w-1.5 h-5 bg-red-600 rounded-full"></span>
                            <h2 class="text-xl md:text-2xl font-bold text-white tracking-tight">
                                {{ $sec['title'] }}
                            </h2>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-zinc-900 text-zinc-400 border border-zinc-800">
                                {{ count($sec['items']) }} Judul
                            </span>
                        </div>
                        @if(!empty($sec['description']))
                            <p class="text-xs md:text-sm text-zinc-400 mt-1 pl-4 leading-relaxed">
                                {{ $sec['description'] }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Items Grid -->
                @if(empty($sec['items']))
                    <div class="py-12 text-center text-zinc-500 bg-zinc-900/30 rounded-xl border border-dashed border-zinc-800">
                        Belum ada judul di bagian ini.
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                        @foreach($sec['items'] as $item)
                            @php
                                $detailUrl = $item['url'] ?? '#';
                                $imgSrc = $item['thumbnail'] ?? $item['banner'] ?? '';
                            @endphp
                            <div class="group relative flex flex-col rounded overflow-hidden">
                                <a href="{{ $detailUrl }}"
                                   class="block relative w-full aspect-[2/3] overflow-hidden bg-zinc-950 rounded-xl border border-zinc-800/80 group-hover:border-zinc-700 transition duration-300">
                                    
                                    @if(!empty($imgSrc))
                                        <img src="{{ $imgSrc }}"
                                             alt="{{ $item['title'] }}"
                                             loading="lazy"
                                             class="w-full h-full object-cover group-hover:scale-105 transition duration-350 ease-out">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-zinc-600 text-xs bg-zinc-900">
                                            No Image
                                        </div>
                                    @endif

                                    <!-- Top Left Badge: Content Type -->
                                    <span class="absolute top-2 left-2 px-1.5 py-0.5 text-[9px] font-extrabold text-gray-200 bg-black/60 backdrop-blur-md rounded border border-white/5 uppercase tracking-wider">
                                        {{ $item['type'] === 'tv_series' ? 'TV SERIES' : 'MOVIE' }}
                                    </span>

                                    <!-- Top Right Badge: Quality -->
                                    @if(!empty($item['quality']))
                                        @php
                                            $qualityColor = ($item['quality'] === 'BLU-RAY' || $item['quality'] === 'HD' || $item['quality'] === '4K') 
                                                ? 'bg-blue-950/70 text-blue-400 border-blue-500/20' 
                                                : 'bg-emerald-950/70 text-emerald-400 border-emerald-500/20';
                                        @endphp
                                        <span class="absolute top-2 right-2 px-1.5 py-0.5 text-[9px] font-extrabold border rounded uppercase tracking-wider backdrop-blur-md {{ $qualityColor }}">
                                            {{ $item['quality'] }}
                                        </span>
                                    @endif

                                    <!-- Bottom Badges Info (Rating & Duration) -->
                                    <div class="absolute bottom-2 inset-x-2 flex items-center justify-between">
                                        @if(!empty($item['rating']) && $item['rating'] !== '0.0')
                                            <span class="flex items-center gap-0.5 text-yellow-400 font-extrabold text-[10px] bg-black/70 backdrop-blur-md px-1.5 py-0.5 rounded border border-white/5">
                                                <svg class="w-3 h-3 text-yellow-500 fill-current" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                                {{ $item['rating'] }}
                                            </span>
                                        @else
                                            <span></span>
                                        @endif

                                        @if(!empty($item['duration']))
                                            <span class="text-gray-300 font-bold text-[10px] bg-black/70 backdrop-blur-md px-1.5 py-0.5 rounded border border-white/5">
                                                {{ $item['duration'] }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Hover Play Indicator -->
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition duration-200">
                                        <div class="w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center shadow-lg shadow-red-950 transform group-hover:scale-110 transition duration-150">
                                            <svg class="w-4 h-4 ml-0.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        </div>
                                    </div>
                                </a>

                                <!-- Card Title & Year -->
                                <div class="mt-2.5">
                                    <h3 class="text-xs md:text-[13px] font-bold text-zinc-200 group-hover:text-red-500 transition duration-200 truncate leading-snug"
                                        title="{{ $item['title'] }} {{ !empty($item['year']) ? '('.$item['year'].')' : '' }}">
                                        <a href="{{ $detailUrl }}">
                                            {{ $item['title'] }}
                                        </a>
                                    </h3>
                                    @if(!empty($item['year']))
                                        <span class="text-zinc-500 font-medium text-[11px] block mt-0.5">
                                            {{ $item['year'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
