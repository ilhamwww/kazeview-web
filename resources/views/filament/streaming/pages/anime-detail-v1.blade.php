<div class="min-h-screen bg-[#050505] text-white pb-32">
    <!-- Hero Banner -->
    <section class="relative w-full min-h-[500px] md:min-h-[60vh] overflow-hidden bg-black flex flex-col justify-end">
        <!-- Background Image -->
        @if(!empty($detail['thumbnail']))
            <img src="{{ $detail['thumbnail'] }}" alt="{{ $detail['title'] ?? '' }}" class="absolute inset-0 w-full h-full object-cover scale-110 blur-sm opacity-25">
        @endif

        <!-- Overlays -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/75 to-transparent w-full md:w-[70%]"></div>
        <div class="absolute top-0 inset-x-0 h-48 bg-gradient-to-b from-[#050505]/80 via-[#050505]/30 to-transparent"></div>
        <div class="absolute bottom-0 inset-x-0 h-96 bg-gradient-to-t from-[#050505] via-[#050505]/90 to-transparent"></div>

        <!-- Content -->
        <div class="relative w-full max-w-[1800px] mx-auto px-6 pt-32 pb-10 md:pb-16 z-10">
            <!-- Breadcrumb & Back -->
            <div class="flex items-center gap-2 text-xs text-zinc-400 mb-6">
                <a href="{{ \App\Filament\Streaming\Pages\ExploreAnime::getUrl(panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 font-bold text-zinc-300 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Katalog Anime V1
                </a>
                <span>/</span>
                <span class="text-zinc-500 truncate max-w-xs">{{ $detail['title'] ?? 'Detail' }}</span>
            </div>

            <div class="flex flex-col md:flex-row gap-6 md:gap-10 items-start md:items-end w-full">
                <!-- Poster -->
                @if(!empty($detail['thumbnail']))
                <div class="flex-shrink-0 hidden md:block">
                    <img src="{{ $detail['thumbnail'] }}" alt="{{ $detail['title'] ?? '' }}"
                         class="w-48 h-72 object-cover rounded-xl border border-white/10 shadow-2xl shadow-black/70">
                </div>
                @endif

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <!-- Badges -->
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="bg-emerald-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider">SERVER 1</span>
                        <span class="bg-white/10 text-zinc-200 border border-white/10 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">DIRECT MP4 HD</span>
                        <span class="bg-emerald-950/80 text-emerald-400 border border-emerald-500/30 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider">STREAMING</span>
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
                            <span class="px-2.5 py-1 text-xs font-bold text-emerald-300 bg-emerald-600/15 border border-emerald-500/20 rounded-full">
                                {{ $genre }}
                            </span>
                        @endforeach
                    </div>
                    @endif

                    <!-- Synopsis -->
                    @if(!empty($detail['description']))
                    <p class="max-w-2xl mt-4 text-sm md:text-base text-gray-400 leading-relaxed font-medium line-clamp-3">
                        {{ $detail['description'] }}
                    </p>
                    @endif

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center gap-3 mt-6">
                        @if(!empty($detail['episodes']))
                            @php
                                $firstEp = $detail['episodes'][0] ?? null;
                                $firstEpNum = $firstEp['episode'] ?? '1';
                                $watchUrl = \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $firstEpNum], panel: 'streaming');
                            @endphp
                            <a href="{{ $watchUrl }}"
                               class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-600/30 transition">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <span>Tonton Episode 1</span>
                            </a>
                        @endif

                        <button wire:click="toggleFavorite"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-zinc-900/80 hover:bg-zinc-800 text-white font-bold text-sm rounded-xl border border-zinc-700/80 transition">
                            <svg class="w-5 h-5 {{ $isFavorited ? 'text-red-500 fill-current' : 'text-zinc-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                            <span>{{ $isFavorited ? 'Tersimpan di Favorit' : 'Tambah ke Favorit' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Episode List Section -->
    <main class="w-full max-w-[1800px] mx-auto px-6 py-10">
        <div class="mb-6 flex items-center justify-between border-b border-zinc-800 pb-4">
            <div>
                <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Daftar Episode</h2>
                <p class="text-xs text-zinc-400 mt-1">Pilih episode yang ingin kamu tonton</p>
            </div>
            @if(!empty($detail['episodes']))
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-950/60 text-emerald-400 border border-emerald-500/30">
                    Total {{ count($detail['episodes']) }} Episode
                </span>
            @endif
        </div>

        @if(!empty($detail['episodes']))
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3">
                @foreach($detail['episodes'] as $ep)
                    @php
                        $epNum = $ep['episode'] ?? '1';
                        $epWatchUrl = \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $epNum], panel: 'streaming');
                    @endphp
                    <a href="{{ $epWatchUrl }}"
                       class="group flex flex-col p-3 rounded-xl bg-zinc-900/60 hover:bg-zinc-800/90 border border-zinc-800 hover:border-emerald-500/40 transition active:scale-95">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10px] font-black uppercase text-emerald-400 tracking-wider">
                                EPS {{ $ep['episode'] }}
                            </span>
                            <svg class="w-3.5 h-3.5 text-zinc-500 group-hover:text-emerald-400 group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-zinc-300 group-hover:text-white line-clamp-2 leading-tight">
                            {{ $ep['title'] }}
                        </p>
                    </a>
                @endforeach
            </div>
        @else
            <div class="py-12 text-center text-zinc-500 bg-zinc-900/40 rounded-xl border border-dashed border-zinc-800">
                <p class="text-sm font-medium">Belum ada episode yang tersedia untuk anime ini.</p>
            </div>
        @endif
    </main>
</div>
