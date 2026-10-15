<div class="min-h-screen bg-[#050505] text-white pb-24 md:pb-16">
    <!-- Top Bar Navigation (Desktop & Mobile) -->
    <div class="w-full max-w-[1800px] mx-auto px-4 md:px-6 pt-20 md:pt-28 pb-3 md:pb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $animeSlug], panel: 'streaming') }}"
            class="inline-flex items-center text-xs md:text-sm font-semibold text-zinc-400 hover:text-white transition gap-1.5">
            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Detail Anime
        </a>

        <!-- Episode Prev / Next Navigation -->
        <div class="flex items-center gap-2">
            @if(!empty($episodeDetail['prev_episode_slug']))
                <a href="{{ \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $episodeDetail['prev_episode_slug']], panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-xs font-bold text-zinc-300 hover:text-white border border-zinc-800 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                    <span>Eps Sebelumnya</span>
                </a>
            @endif

            <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-zinc-900/80 border border-zinc-800 text-xs font-mono">
                <span class="inline-block w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
                <span class="font-bold text-white">Eps {{ $episodeDetail['episode_number'] ?? '1' }}</span>
            </div>

            @if(!empty($episodeDetail['next_episode_slug']))
                <a href="{{ \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $episodeDetail['next_episode_slug']], panel: 'streaming') }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-xs font-bold text-white shadow-lg shadow-red-600/30 transition">
                    <span>Eps Selanjutnya</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            @endif
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="w-full max-w-[1800px] mx-auto px-0 md:px-6 space-y-6 md:space-y-8">

        <!-- Video Player Card Container -->
        <div class="w-full bg-black md:rounded-2xl overflow-hidden shadow-2xl aspect-video border-y md:border border-zinc-800/80 relative">

            <!-- Loading Spinner when switching mirror -->
            <div wire:loading.flex wire:target="switchMirror" class="absolute inset-0 z-30 bg-black/90 flex flex-col items-center justify-center gap-3">
                <div class="relative w-14 h-14">
                    <div class="w-14 h-14 rounded-full border-4 border-red-600/30 border-t-red-600 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-500 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                </div>
                <div class="text-center px-4">
                    <p class="text-sm font-bold text-white tracking-wide">Menghubungkan ke Server Mirror...</p>
                    <p class="text-xs text-zinc-400 mt-1">Mengambil stream video kualitas tinggi...</p>
                </div>
            </div>

            <!-- Video Player (Direct MP4 vs Iframe Embed) -->
            <div class="w-full h-full bg-black relative flex items-center justify-center">
                @if($isDirectMp4)
                    <video key="{{ $currentStreamUrl }}"
                           src="{{ $currentStreamUrl }}"
                           controls
                           autoplay
                           playsinline
                           class="w-full h-full object-contain bg-black">
                    </video>
                @else
                    <iframe key="{{ $currentStreamUrl }}"
                            src="{{ $currentStreamUrl }}"
                            class="w-full h-full"
                            frameborder="0"
                            allowfullscreen="true"
                            webkitallowfullscreen="true"
                            mozallowfullscreen="true"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            referrerpolicy="no-referrer">
                    </iframe>
                @endif
            </div>
        </div>

        <!-- Episode Info & Metadata Bar -->
        <div class="px-4 md:px-0 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-800 pb-5">
            <div class="space-y-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2.5 py-0.5 rounded-md bg-purple-600 text-white text-[10px] font-black uppercase tracking-wider">
                        SERVER 1 720P HD
                    </span>
                    <span class="px-2.5 py-0.5 rounded-md bg-zinc-800 text-zinc-300 text-[10px] font-bold border border-zinc-700">
                        SERVER: {{ strtoupper($currentServer) }} ({{ $currentQuality }})
                    </span>
                    @if($isDirectMp4)
                        <span class="px-2 py-0.5 rounded-md bg-emerald-950 text-emerald-400 border border-emerald-500/30 text-[10px] font-extrabold uppercase">
                            DIRECT MP4 HD
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-md bg-blue-950 text-blue-400 border border-blue-500/30 text-[10px] font-extrabold uppercase">
                            EMBED PLAYER
                        </span>
                    @endif
                </div>

                <h1 class="text-xl md:text-2xl font-black text-white leading-tight">
                    {{ $episodeDetail['title'] ?? 'Anime Episode' }}
                </h1>
                @if(!empty($seriesDetail['title']))
                    <p class="text-xs md:text-sm text-zinc-400">
                        Dari serial: <a href="{{ \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $animeSlug], panel: 'streaming') }}" class="text-red-400 hover:underline font-semibold">{{ $seriesDetail['title'] }}</a>
                    </p>
                @endif
            </div>

            <!-- Favorite & Share Button -->
            <div class="flex items-center gap-3">
                <button wire:click="toggleFavorite"
                        class="px-4 py-2.5 rounded-xl border {{ $isFavorited ? 'bg-red-600 border-red-600 text-white shadow-lg shadow-red-600/30' : 'bg-zinc-900 border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800' }} text-xs font-bold transition flex items-center gap-2">
                    <svg class="w-4 h-4 {{ $isFavorited ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                    <span>{{ $isFavorited ? 'Tersimpan di Favorit' : 'Tambah ke Favorit' }}</span>
                </button>
            </div>
        </div>

        <!-- 1. PILIH SERVER MIRROR STREAMING (TOMBOL GANTI-GANTI MIRROR) -->
        <div class="px-4 md:px-0">
            <div class="bg-gradient-to-br from-zinc-900/90 to-zinc-950/90 border border-zinc-800/90 rounded-2xl md:rounded-3xl p-5 md:p-6 space-y-4 shadow-xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-800/80 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-red-600/10 border border-red-500/20 text-red-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-white">Ganti Mirror Server Streaming</h2>
                            <p class="text-xs text-zinc-400">Pilih mirror alternatif jika server saat ini lambat, bermasalah, atau buffering.</p>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-zinc-400 bg-zinc-900 px-3 py-1 rounded-full border border-zinc-800 self-start sm:self-auto">
                        {{ count($mirrors) }} Server Tersedia
                    </span>
                </div>

                @if(!empty($mirrors))
                    <!-- Mirror Buttons Grid / Flex -->
                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                        @foreach($mirrors as $idx => $m)
                            @php
                                $isActive = ($activeMirrorIndex === $idx);
                                $qBadgeColor = match($m['quality']) {
                                    '720p', '1080p' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                                    '480p' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                                    default => 'bg-zinc-700/40 text-zinc-400 border-zinc-600/30',
                                };
                            @endphp
                            <button type="button"
                                    wire:click="switchMirror({{ $idx }})"
                                    class="group px-3.5 py-2.5 rounded-xl border text-xs font-bold transition-all duration-200 flex items-center gap-2 active:scale-95 {{ $isActive ? 'bg-red-600 border-red-500 text-white shadow-lg shadow-red-600/30 scale-[1.02]' : 'bg-zinc-950/70 border-zinc-800 hover:border-red-600/50 hover:bg-zinc-900 text-zinc-300 hover:text-white' }}">
                                
                                @if($isActive)
                                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                @else
                                    <svg class="w-3.5 h-3.5 text-zinc-500 group-hover:text-red-400" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                @endif

                                <span>{{ ucfirst($m['server']) }}</span>

                                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono border {{ $isActive ? 'bg-white/20 text-white border-white/30' : $qBadgeColor }}">
                                    {{ $m['quality'] }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-zinc-500 italic">Hanya ada 1 server default untuk episode ini.</p>
                @endif

                <div class="pt-1 flex items-center gap-2 text-[11px] text-zinc-500">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h-14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    <span>Server <strong>ODStream</strong> & <strong>OndesuHD</strong> menyediakan direct stream MP4 resolusi 720p HD. Jika buffering, coba mirror <strong>Vidhide</strong> atau <strong>Mega</strong>.</span>
                </div>
            </div>
        </div>

        <!-- 2. DOWNLOAD EPISODE INI (SEMUA TOMBOL DOWNLOAD DARI OTAKUDESU) -->
        <div class="px-4 md:px-0">
            <div class="bg-gradient-to-br from-zinc-900/90 to-zinc-950/90 border border-zinc-800/90 rounded-2xl md:rounded-3xl p-5 md:p-6 space-y-5 shadow-xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-800/80 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-black text-white">Download Episode Ini</h2>
                            <p class="text-xs text-zinc-400">Unduh video untuk ditonton offline dari seluruh server resmi.</p>
                        </div>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-400 bg-emerald-950/60 border border-emerald-500/30 px-3 py-1 rounded-full self-start sm:self-auto">
                        Tersedia {{ count($downloads) }} Kualitas
                    </span>
                </div>

                @if(!empty($downloads))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($downloads as $dl)
                            <div class="bg-zinc-950/80 border border-zinc-800/80 hover:border-zinc-700 rounded-2xl p-4 flex flex-col justify-between gap-3 transition">
                                <!-- Format & Size Header -->
                                <div class="flex items-center justify-between gap-2 border-b border-zinc-800/60 pb-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-red-600"></span>
                                        <h3 class="text-sm font-bold text-white tracking-wide">
                                            {{ $dl['format'] }}
                                        </h3>
                                    </div>
                                    @if(!empty($dl['size']))
                                        <span class="text-[11px] font-mono font-bold text-zinc-400 bg-zinc-900 px-2 py-0.5 rounded border border-zinc-800">
                                            {{ $dl['size'] }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Host Download Buttons -->
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach($dl['links'] as $link)
                                        <a href="{{ $link['url'] }}"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-900 hover:bg-red-600 active:scale-95 text-zinc-300 hover:text-white border border-zinc-800 hover:border-red-500 text-xs font-bold transition duration-150 shadow-sm">
                                            <svg class="w-3.5 h-3.5 text-zinc-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            <span>{{ $link['host'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center text-zinc-500 text-xs bg-zinc-950/60 rounded-2xl border border-dashed border-zinc-800">
                        Link download untuk episode ini belum tersedia.
                    </div>
                @endif
            </div>
        </div>

        <!-- 3. DAFTAR EPISODE LAINNYA DI SERIAL INI -->
        @if(!empty($seriesDetail['episodes']))
            <div class="px-4 md:px-0 space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
                    <h2 class="text-lg md:text-xl font-black text-white flex items-center gap-2">
                        <span class="w-1.5 h-5 bg-red-600 rounded-full"></span>
                        Semua Episode {{ $seriesDetail['title'] ?? '' }}
                    </h2>
                    <span class="text-xs text-zinc-400 bg-zinc-900 px-2.5 py-1 rounded border border-zinc-800">
                        Total: <strong class="text-red-400">{{ count($seriesDetail['episodes']) }}</strong> Episode
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach($seriesDetail['episodes'] as $ep)
                        @php
                            $targetSlug = basename(rtrim($ep['url'], '/'));
                            $isCurrent = ($targetSlug === $episodeSlug);
                            $watchUrl = \App\Filament\Streaming\Pages\WatchAnime::getUrl([
                                'anime_slug' => $animeSlug,
                                'episode_slug' => $targetSlug,
                            ], panel: 'streaming');
                        @endphp
                        <a href="{{ $watchUrl }}"
                           class="p-3 rounded-xl border flex flex-col justify-between gap-2 transition duration-200 text-left {{ $isCurrent ? 'bg-red-600 text-white border-red-500 shadow-lg shadow-red-600/30' : 'bg-zinc-900/60 hover:bg-zinc-800 border-zinc-800 text-zinc-300 hover:text-white' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black font-mono {{ $isCurrent ? 'text-white' : 'text-red-400' }}">
                                    Eps {{ $ep['episode'] }}
                                </span>
                                @if($isCurrent)
                                    <span class="text-[9px] uppercase px-1.5 py-0.2 rounded bg-black/40 font-bold">Diputar</span>
                                @endif
                            </div>
                            <p class="text-[11px] font-semibold truncate leading-tight">
                                {{ $ep['title'] }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
