<div>
    <!-- Hero Section -->
    <section class="relative w-full min-h-[550px] md:min-h-[60vh] overflow-hidden bg-black flex flex-col justify-end">
        <!-- Background Image / Gradient -->
        <div class="absolute inset-0 bg-gradient-to-br from-purple-900/40 via-[#050505] to-indigo-900/30"></div>
        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1578662996442-48f60103fc96?auto=format&fit=crop&w=1600&q=60')] bg-cover bg-center opacity-20"></div>
        <div class="absolute bottom-0 inset-x-0 h-64 bg-gradient-to-t from-[#050505] via-[#050505]/90 to-transparent"></div>
        <div class="absolute top-0 inset-x-0 h-48 bg-gradient-to-b from-[#050505]/80 via-[#050505]/30 to-transparent"></div>

        <!-- Content -->
        <div class="relative w-full max-w-[1800px] mx-auto px-6 pt-32 pb-10 md:pb-16 z-10">
            <div class="flex items-center gap-2 mb-4">
                <span class="bg-purple-600 text-white text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">ANIME</span>
                <span class="bg-red-600 text-white text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">LIVE SCRAPER</span>
            </div>
            <h1 class="text-3xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-white uppercase">Explore Anime</h1>
            <p class="max-w-xl mt-4 text-sm md:text-base text-gray-400 leading-relaxed font-medium">
                Jelajahi koleksi anime dari berbagai genre. Data diambil langsung dari sumber secara real-time.
            </p>

            <!-- Search & Filter Bar -->
            <div class="mt-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 max-w-3xl">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text"
                        wire:model.live.debounce.500ms="search"
                        placeholder="Cari anime..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white/5 backdrop-blur-md border border-white/10 rounded-lg text-sm text-white placeholder:text-zinc-500 focus:outline-none focus:ring-1 focus:ring-purple-500/50 focus:border-purple-500/50 transition">
                </div>

                <!-- Genre Select -->
                <select wire:model.live="genre"
                    class="px-3 py-2.5 bg-white/5 backdrop-blur-md border border-white/10 rounded-lg text-sm text-white focus:outline-none focus:ring-1 focus:ring-purple-500/50 transition appearance-none cursor-pointer">
                    <option value="" class="bg-zinc-900 text-white">Semua Genre</option>
                    @foreach($genres as $g)
                        <option value="{{ $g['slug'] }}" class="bg-zinc-900 text-white">{{ $g['name'] }}</option>
                    @endforeach
                </select>

                <!-- Status Select -->
                <select wire:model.live="status"
                    class="px-3 py-2.5 bg-white/5 backdrop-blur-md border border-white/10 rounded-lg text-sm text-white focus:outline-none focus:ring-1 focus:ring-purple-500/50 transition appearance-none cursor-pointer">
                    <option value="" class="bg-zinc-900 text-white">Semua Status</option>
                    <option value="ongoing" class="bg-zinc-900 text-white">Ongoing</option>
                    <option value="completed" class="bg-zinc-900 text-white">Completed</option>
                </select>

                @if(filled($search) || filled($genre) || filled($status))
                    <button wire:click="clearSearch"
                        class="px-4 py-2.5 bg-red-600/20 border border-red-500/30 text-red-400 hover:bg-red-600/30 text-sm font-bold rounded-lg transition whitespace-nowrap">
                        ✕ Reset
                    </button>
                @endif
            </div>
        </div>
    </section>

    <div class="space-y-6 px-6 pb-8">
        <!-- Info Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-zinc-800 pb-4 gap-3">
            <div>
                <h2 class="text-xl font-bold text-gray-100 flex items-center gap-2">
                    <span class="w-1 h-5 bg-purple-600 rounded-full"></span>
                    @if(filled($search))
                        Hasil Pencarian: "{{ $search }}"
                    @elseif(filled($genre))
                        @php
                            $selectedGenreName = collect($genres)->firstWhere('slug', $genre)['name'] ?? $genre;
                        @endphp
                        Genre: {{ $selectedGenreName }}
                    @elseif(filled($status))
                        Status: {{ ucfirst($status) }}
                    @else
                        Semua Anime
                    @endif
                </h2>
            </div>
            <div class="flex items-center gap-3">
                @if(blank($search))
                    <div class="text-xs text-zinc-400 bg-zinc-900 px-2.5 py-1 rounded border border-zinc-800">
                        Halaman <span class="text-purple-400 font-bold">{{ $pageNumber }}</span>
                    </div>
                @endif
                <div class="text-xs text-zinc-400 bg-zinc-900 px-2.5 py-1 rounded border border-zinc-800">
                    Ditemukan: <span class="text-red-500 font-bold">{{ count($animes) }}</span> Anime
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div wire:loading wire:target="loadAnimes, nextPage, previousPage, search, genre, status"
            class="flex items-center justify-center py-16">
            <div class="flex items-center gap-3 text-zinc-400 bg-zinc-900/50 px-5 py-3 rounded-full border border-zinc-800 shadow-sm">
                <svg class="animate-spin h-5 w-5 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-semibold tracking-wide">Mengambil data anime...</span>
            </div>
        </div>

        <!-- Content Grid -->
        <div wire:loading.remove wire:target="loadAnimes, nextPage, previousPage, search, genre, status">
            @if(empty($animes))
                <div class="flex flex-col items-center justify-center py-16 text-zinc-500 bg-zinc-900/50 rounded-xl border border-dashed border-zinc-800">
                    <svg class="w-12 h-12 mb-3 stroke-current opacity-60 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h-14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <p class="text-sm font-medium">Tidak ada anime yang ditemukan.</p>
                    <p class="text-xs text-zinc-600 mt-1">Coba kata kunci lain atau ubah filter.</p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($animes as $anime)
                        @php
                            // Extract slug from URL like https://anoboy.be/anime/liar-game/
                            $animeUrlPath = parse_url($anime['url'], PHP_URL_PATH);
                            $animeSlug = '';
                            if ($animeUrlPath) {
                                $parts = array_filter(explode('/', trim($animeUrlPath, '/')));
                                $animeSlug = end($parts);
                            }
                            $detailUrl = $animeSlug
                                ? \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $animeSlug], panel: 'streaming')
                                : '#';
                        @endphp
                        <div class="group relative flex flex-col rounded overflow-hidden">
                            <a href="{{ $detailUrl }}"
                               class="block relative w-full aspect-[2/3] overflow-hidden bg-zinc-950 rounded border border-zinc-800/80 group-hover:border-purple-500/50 transition duration-300">
                                @if(!empty($anime['thumbnail']))
                                    <img src="{{ $anime['thumbnail'] }}" alt="{{ $anime['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-350 ease-out" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-zinc-600 text-xs">No Image</div>
                                @endif

                                <!-- Type Badge (Top Left) -->
                                @if(!empty($anime['type']))
                                <span class="absolute top-2 left-2 px-1.5 py-0.5 text-[9px] font-extrabold text-gray-200 bg-purple-600/80 backdrop-blur-md rounded border border-purple-400/20 uppercase tracking-wider">
                                    {{ $anime['type'] }}
                                </span>
                                @endif

                                <!-- Status Badge (Top Right) -->
                                @if(!empty($anime['status']))
                                @php
                                    $statusColor = strtolower($anime['status']) === 'ongoing'
                                        ? 'bg-emerald-950/60 text-emerald-400 border-emerald-500/20'
                                        : 'bg-blue-950/60 text-blue-400 border-blue-500/20';
                                @endphp
                                <span class="absolute top-2 right-2 px-1.5 py-0.5 text-[9px] font-extrabold border rounded uppercase tracking-wider backdrop-blur-md {{ $statusColor }}">
                                    {{ $anime['status'] }}
                                </span>
                                @endif

                                <!-- Hover Overlay -->
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition duration-300 flex items-center justify-center">
                                    <div class="w-12 h-12 rounded-full bg-purple-600/80 flex items-center justify-center backdrop-blur-sm border border-purple-400/30">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </a>

                            <!-- Title -->
                            <h3 class="mt-2 text-xs md:text-[13px] font-bold text-gray-250 group-hover:text-purple-400 transition duration-200 truncate leading-snug" title="{{ $anime['title'] }}">
                                <a href="{{ $detailUrl }}">{{ $anime['title'] }}</a>
                            </h3>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if(blank($search))
                    <div class="flex items-center justify-center gap-4 pt-8">
                        @if($pageNumber > 1)
                        <button wire:click="previousPage"
                            class="flex items-center gap-2 px-5 py-2.5 bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 hover:border-zinc-700 text-sm font-bold text-gray-300 rounded-lg transition duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            Sebelumnya
                        </button>
                        @endif

                        <span class="px-4 py-2 bg-purple-600/20 border border-purple-500/30 rounded-lg text-purple-400 font-bold text-sm">
                            {{ $pageNumber }}
                        </span>

                        <button wire:click="nextPage"
                            class="flex items-center gap-2 px-5 py-2.5 bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 hover:border-zinc-700 text-sm font-bold text-gray-300 rounded-lg transition duration-200">
                            Selanjutnya
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
