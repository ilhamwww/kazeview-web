@php
    use App\Filament\Streaming\Pages\Movies;
    use App\Filament\Streaming\Pages\ShowSeries;
    use App\Filament\Streaming\Pages\WatchMovie;

    $mBanner = str_replace('/t/p/original/', '/t/p/w1280/', $media['banner'] ?? '') ?: ($media['thumbnail'] ?? '');
    $mRating = is_numeric($media['rating'] ?? null) ? (float) $media['rating'] : 0;
    $rawGenres = !empty($media['genres']) && is_array($media['genres'])
        ? (isset($media['genres'][0]['name']) ? array_column($media['genres'], 'name') : $media['genres'])
        : array_filter(array_map('trim', explode(',', (string) ($media['genre'] ?? ''))));
    $mGenres = array_map(fn($g) => html_entity_decode((string)$g, ENT_QUOTES | ENT_HTML5, 'UTF-8'), array_values($rawGenres));
    $mRuntime = null;
    if (preg_match('/^(\d+)m$/', (string) ($media['duration'] ?? ''), $mm)) {
        $mRuntime = (int) $mm[1] >= 60 ? intdiv((int) $mm[1], 60) . 'j ' . ((int) $mm[1] % 60) . 'm' : $mm[1] . 'm';
    } elseif (!empty($media['duration'])) {
        $mRuntime = $media['duration'];
    }
    $recUrl = function (array $it) {
        if (empty($it['slug'])) return '#';
        $isSeries = in_array($it['type'] ?? '', ['tv_series', 'tv', 'series'], true);
        return $isSeries
            ? ShowSeries::getUrl(['slug' => $it['slug']], panel: 'streaming')
            : WatchMovie::getUrl(['slug' => $it['slug']], panel: 'streaming');
    };
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16 relative">

    {{-- Ambient backdrop --}}
    @if($mBanner)
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[900px] overflow-hidden" aria-hidden="true">
            <img src="{{ $mBanner }}" alt="" class="w-full h-full object-cover opacity-30 blur-3xl scale-110">
            <div class="absolute inset-0 bg-gradient-to-b from-[#050505]/40 via-[#050505]/80 to-[#050505]"></div>
        </div>
    @endif

    {{-- Top bar (disamakan dengan halaman TV Series) --}}
    <div class="relative w-full max-w-[1400px] mx-auto px-4 lg:px-8 pt-20 md:pt-24 pb-2">
        <div class="flex items-center gap-3 h-14 lg:h-12">
            <a href="{{ Movies::getUrl(panel: 'streaming') }}" aria-label="Kembali ke daftar film"
               class="w-10 h-10 shrink-0 inline-flex items-center justify-center rounded-full bg-white/5 ring-1 ring-white/10 active:bg-white/10 hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold leading-tight truncate">{{ $media['title'] ?? 'Film' }}</p>
                <p class="text-[11px] text-zinc-400 leading-tight truncate">
                    @php
                        $mSub = collect([
                            !empty($media['year']) ? (string) $media['year'] : null,
                            $mRuntime,
                        ])->filter()->implode(' · ');
                    @endphp
                    {{ $mSub !== '' ? $mSub : 'Film' }}
                </p>
            </div>
            <span class="shrink-0 text-[10px] font-black tracking-wider text-red-300 bg-red-600/15 ring-1 ring-red-500/30 px-2 py-1 rounded-md tabular-nums">{{ !empty($media['quality']) ? strtoupper((string) $media['quality']) : 'HD' }}</span>
        </div>
    </div>

    <div class="relative w-full max-w-[1400px] mx-auto px-0 lg:px-8 space-y-8" x-data="watchPlayerInit()">

        {{-- ============ TWO-COLUMN GRID: LEFT (PLAYER & DETAILS) + RIGHT (SIDEBAR) ============ --}}
        <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-8">

            {{-- ============ LEFT COLUMN: PLAYER + SERVER SWITCHER + MOVIE INFO ============ --}}
            <div class="min-w-0 space-y-4">
                {{-- Video Player Container --}}
                <div class="w-full bg-black lg:rounded-2xl overflow-hidden aspect-video relative isolate z-10 ring-1 ring-white/10 shadow-[0_30px_80px_-20px_rgba(0,0,0,0.9)]" x-ref="playerShell" data-kz-fs @kz-fs-failed="art && (art.fullscreenWeb = (!window.KzFs || KzFs.allowWebFs()))">
                    <div x-show="isPlaying && !isEmbed" class="w-full h-full relative" style="display: none;">
                        <div x-ref="artContainer" id="movieArtPlayer" class="w-full h-full"></div>
                    </div>

                    <div x-show="isPlaying && isEmbed" class="w-full h-full relative bg-black" style="display: none;">
                        <iframe :src="currentEmbedUrl" class="w-full h-full border-0" allowfullscreen webkitallowfullscreen mozallowfullscreen allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="no-referrer"></iframe>
                    </div>

                    <div x-show="isLoadingStream" x-cloak class="absolute inset-0 z-30 bg-black/90 backdrop-blur-md flex flex-col items-center justify-center p-6 text-center">
                        @include('filament.streaming.partials.server-loading-indicator', [
                            'title' => 'Memilih server terbaik',
                            'subtitle' => 'Mengambil jalur stream film tercepat & subtitle Indonesia',
                        ])
                    </div>

                    <div x-show="!isPlaying && !isLoadingStream" class="absolute inset-0 z-20">
                        @if($mBanner)
                            <img src="{{ $mBanner }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                        @endif
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black via-black/30 to-black/10"></div>
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-black/70 via-transparent to-transparent"></div>

                        <button type="button" @click="playVideo()" aria-label="Putar {{ $media['title'] }}"
                                class="absolute inset-0 m-auto w-20 h-20 md:w-24 md:h-24 rounded-full bg-white/15 hover:bg-red-600 backdrop-blur-md ring-1 ring-white/40 flex items-center justify-center transition duration-300 hover:scale-110 group cursor-pointer z-20">
                            <span class="pointer-events-none absolute inset-0 rounded-full ring-2 ring-white/20 animate-ping"></span>
                            <svg class="pointer-events-none w-9 h-9 md:w-11 md:h-11 fill-white ml-1.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                        </button>

                        <div class="absolute left-4 right-4 bottom-4 md:left-8 md:bottom-7 flex items-end justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-[10px] md:text-xs font-black uppercase tracking-[0.2em] text-red-500">Movie</p>
                                <p class="text-lg md:text-3xl font-black tracking-tight line-clamp-1 drop-shadow-lg">{{ $media['title'] }}</p>
                            </div>
                            @if($mRuntime)
                                <span class="hidden sm:inline shrink-0 text-xs font-bold bg-black/60 backdrop-blur px-2.5 py-1 rounded-full border border-white/10">{{ $mRuntime }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Movie Details Block --}}
                <div class="px-4 lg:px-0 pt-2 space-y-4">
                    <div>
                        <p class="text-[10px] md:text-xs font-black uppercase tracking-[0.2em] text-red-500 mb-1">Movie</p>
                        <h1 class="text-2xl md:text-3xl lg:text-4xl font-black tracking-tight leading-tight">{{ $media['title'] }}</h1>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mt-2 text-sm font-semibold text-zinc-300">
                            @if($mRating > 0)
                                <span class="inline-flex items-center gap-1 text-yellow-400">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                    {{ number_format($mRating, 1) }}
                                </span>
                            @endif
                            @if(!empty($media['year']))<span class="text-zinc-600">•</span><span>{{ $media['year'] }}</span>@endif
                            @if($mRuntime)<span class="text-zinc-600">•</span><span>{{ $mRuntime }}</span>@endif
                            <span class="px-1.5 py-0.5 text-[10px] font-black border border-emerald-500/50 text-emerald-400 rounded">{{ $media['quality'] ?? 'HD' }}</span>
                        </div>
                        @if(count($mGenres))
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                @foreach($mGenres as $g)
                                    <span class="px-2.5 py-0.5 text-xs font-semibold text-zinc-200 bg-white/5 border border-white/10 rounded-full">{{ $g }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if(!empty($media['description']))
                        <div class="mt-4 pt-4 border-t border-zinc-800/80">
                            <h2 class="text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-2">Sinopsis</h2>
                            <p x-data="{ more: false }" @click="more = !more" :class="more ? '' : 'line-clamp-4'"
                               class="text-sm md:text-base text-zinc-300 leading-relaxed cursor-pointer">{{ $media['description'] }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============ RIGHT COLUMN: SIDEBAR (POSTER + ACTIONS + SUBTITLE) ============ --}}
            <aside class="px-4 lg:px-0 pt-6 lg:pt-0 min-w-0" aria-label="Informasi & Kontrol">
                <div class="lg:sticky lg:top-24 bg-zinc-900/50 backdrop-blur rounded-2xl ring-1 ring-white/5 p-5 space-y-5">
                    @if(!empty($media['thumbnail']))
                        <div class="w-full aspect-[2/3] max-h-[280px] overflow-hidden rounded-xl ring-1 ring-white/10 shadow-xl hidden lg:block bg-zinc-950">
                            <img src="{{ $media['thumbnail'] }}" alt="{{ $media['title'] }}" class="w-full h-full object-cover">
                        </div>
                    @endif

                    <button type="button" @click="playVideo()" x-show="!isPlaying"
                            class="w-full inline-flex items-center justify-center gap-2 py-3 bg-white text-black hover:bg-zinc-200 active:scale-[0.98] rounded-xl font-bold text-sm transition shadow-lg shadow-white/10">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                        Putar Film
                    </button>

                    <div class="grid grid-cols-2 gap-2">
                        <button wire:click="toggleFavorite" type="button"
                                class="inline-flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold border transition {{ $isFavorited ? 'bg-red-600/15 border-red-600/50 text-red-400' : 'bg-zinc-950 border-zinc-800 text-zinc-200 hover:border-zinc-600' }}">
                            <svg class="w-4 h-4 {{ $isFavorited ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            {{ $isFavorited ? 'Tersimpan' : 'Favorit' }}
                        </button>
                        <button type="button" x-data="{ ok: false }"
                                @click="navigator.clipboard.writeText(window.location.href); ok = true; setTimeout(() => ok = false, 1800)"
                                class="inline-flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold bg-zinc-950 border border-zinc-800 text-zinc-200 hover:border-zinc-600 transition">
                            <span x-text="ok ? 'Link disalin' : 'Bagikan'">Bagikan</span>
                        </button>
                    </div>
                </div>
            </aside>
        </div>

        {{-- ================= RECOMMENDATIONS ================= --}}
        @if(count($recommendations))
            <section class="px-4 md:px-0 pt-4">
                <h2 class="text-xl md:text-2xl font-black tracking-tight mb-5">Mungkin Kamu Suka</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-x-4 gap-y-6">
                    @foreach(array_slice($recommendations, 0, 12) as $item)
                        @php $r = is_numeric($item['rating'] ?? null) ? (float) $item['rating'] : 0; @endphp
                        <a href="{{ $recUrl($item) }}" class="group block">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 ring-1 ring-white/5 group-hover:ring-red-600/60 transition">
                                <img src="{{ $item['thumbnail'] ?? '' }}" alt="{{ $item['title'] ?? '' }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-70 group-hover:opacity-100 transition"></div>
                                @if($r > 0)
                                    <span class="absolute top-2 right-2 px-1.5 py-0.5 text-[10px] font-black rounded bg-black/60 backdrop-blur text-yellow-400 border border-white/10">★ {{ number_format($r, 1) }}</span>
                                @endif
                                <span class="absolute top-2 left-2 px-1.5 py-0.5 text-[9px] font-black uppercase rounded bg-black/60 backdrop-blur border border-white/10">{{ in_array($item['type'] ?? '', ['tv_series', 'tv', 'series'], true) ? 'Series' : 'Movie' }}</span>
                            </div>
                            <h3 class="mt-2.5 text-sm font-bold text-zinc-100 group-hover:text-red-500 transition line-clamp-1">{{ $item['title'] ?? '' }}</h3>
                            @if(!empty($item['year']))<p class="text-[11px] text-zinc-500 mt-0.5">{{ $item['year'] }}</p>@endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="/css/kz-player-v3.css?v=3">
    <style>
        .artplayer-app {
            width: 100% !important;
            height: 100% !important;
            background: #000 !important;
        }

        /* 1. Hapus total gradien hitam tebal dari art-bottom agar video jernih dan subtitle tidak tertutup */
        /* Bar waktu gaya YouTube/Netflix + area sentuh 22px agar mudah di-drag di HP (garis visual tetap 4px) */
        .art-video-player .art-progress { height: 22px !important; }
        .art-video-player .art-control-progress { height: 22px !important; touch-action: none !important; }
        .art-video-player .art-control-progress-inner {
            height: 4px !important; background: rgba(255, 255, 255, 0.25) !important;
            border-radius: 2px !important; transition: height 0.15s ease !important;
        }
        .art-video-player .art-control-progress:hover .art-control-progress-inner,
        .art-video-player .art-control-progress:active .art-control-progress-inner { height: 6px !important; }
        .art-video-player .art-progress-loaded { background: rgba(255, 255, 255, 0.45) !important; }
        .art-video-player .art-progress-played { background: #E50914 !important; }
        .art-video-player .art-progress-highlight { background: transparent !important; }
        .art-video-player .art-progress-indicator { background-color: #E50914 !important; }

        /* 1. Reset default art-bottom background saat kontrol sembunyi agar video jernih dan subtitle bebas */
        .art-video-player .art-bottom {
            background: none !important;
            background-image: none !important;
            transition: background-image 0.2s ease, opacity 0.2s ease !important;
        }

        /* Anti-spoiler: Sembunyikan mini progress bar di bagian bawah saat kontrol idle/tersembunyi */
        .art-video-player .art-mini-progress-bar,
        .art-video-player:not(.art-control-show):not(.art-hover) .art-bottom {
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* 2. Saat kontrol aktif/hover, berikan gradien halus full-width di art-bottom (bukan di art-controls agar samping tidak terpotong) */
        .art-video-player.art-control-show .art-bottom,
        .art-video-player.art-hover .art-bottom {
            opacity: 1 !important;
            pointer-events: auto !important;
            background-image: linear-gradient(to top, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.45) 50px, transparent 85px) !important;
            background-size: 100% 85px !important;
            background-repeat: no-repeat !important;
            background-position: bottom !important;
        }

        .art-video-player .art-controls {
            background: none !important;
            background-image: none !important;
        }

        /* 3. Subtitle styling: Z-index tinggi agar selalu di atas layer kontrol & tidak pernah tertutup */
        .art-video-player .art-subtitle {
            z-index: 75 !important;
            pointer-events: none !important;
            text-align: center !important;
            bottom: 35px !important;
            padding: 0 4% !important;
            white-space: pre-line !important;
            transition: bottom 0.2s ease !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: flex-end !important;
            text-shadow: none !important;
        }

        /* Saat bar kontrol bawah muncul, naikkan sedikit subtitle agar tidak bertabrakan dengan progress bar */
        .art-video-player.art-control-show .art-subtitle {
            bottom: 72px !important;
        }

        /* 4. Kotak subtitle transparan ala Netflix yang super rapi dan kontras tinggi */
        .art-subtitle-inner-box {
            display: inline-block !important;
            background: rgba(0, 0, 0, 0.75) !important;
            padding: 4px 14px !important;
            border-radius: 8px !important;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 22px !important;
            font-weight: 600 !important;
            color: #ffffff !important;
            line-height: 1.42 !important;
            text-align: center !important;
            box-decoration-break: clone !important;
            -webkit-box-decoration-break: clone !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.9) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5) !important;
        }

        /* Style subtitle saat dalam mode teks bersih (outline tanpa kotak) */
        .art-subtitle-style-clean .art-subtitle-inner-box {
            background: transparent !important;
            box-shadow: none !important;
            text-shadow: 
                0 2px 4px rgba(0, 0, 0, 0.95),
                0 0 4px rgba(0, 0, 0, 0.9),
                1px 1px 2px rgba(0, 0, 0, 0.95),
                -1px -1px 2px rgba(0, 0, 0, 0.95),
                1px -1px 2px rgba(0, 0, 0, 0.95),
                -1px 1px 2px rgba(0, 0, 0, 0.95),
                0 0 8px rgba(0, 0, 0, 0.9) !important;
        }

        /* Style subtitle saat kotak hitam pekat */
        .art-subtitle-style-solid .art-subtitle-inner-box {
            background: #000000 !important;
        }

        /* 5. Menu settings popup Artplayer Pixel-Perfect */
        .art-video-player .art-settings {
            height: auto !important;
            /* Batasi tinggi panel agar tetap di dalam player, lalu scroll di dalamnya. */
            max-height: min(420px, calc(100% - 56px)) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            overscroll-behavior: contain !important;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.28) transparent;
        }

        .art-video-player .art-settings::-webkit-scrollbar {
            width: 6px;
        }

        .art-video-player .art-settings::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.26);
            border-radius: 999px;
        }

        .art-video-player .art-setting-panel {
            min-width: 255px !important;
            width: 255px !important;
            max-width: 92vw !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            border-radius: 12px !important;
            backdrop-filter: blur(18px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(18px) saturate(180%) !important;
            background: rgba(18, 18, 22, 0.88) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.75), 0 2px 6px rgba(0, 0, 0, 0.4) !important;
            padding: 6px 6px !important;
        }

        .art-video-player .art-setting-item {
            height: 38px !important;
            min-height: 38px !important;
            padding: 0 10px !important;
            border-radius: 8px !important;
            margin: 1px 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            transition: background 0.15s ease !important;
            cursor: pointer !important;
        }

        .art-video-player .art-setting-item:hover {
            background: rgba(255, 255, 255, 0.09) !important;
        }

        .art-video-player .art-setting-item-left {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }

        .art-video-player .art-setting-item-left-icon {
            flex-shrink: 0 !important;
            width: 18px !important;
            height: 18px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #ffffff !important;
        }

        .art-video-player .art-setting-item-left-icon svg {
            width: 18px !important;
            height: 18px !important;
            display: block !important;
            fill: none !important;
            stroke: #ffffff !important;
            stroke-width: 2 !important;
        }

        .art-video-player .art-setting-item-left-icon svg rect,
        .art-video-player .art-setting-item-left-icon svg circle,
        .art-video-player .art-setting-item-left-icon svg path,
        .art-video-player .art-setting-item-left-icon svg line,
        .art-video-player .art-setting-item-left-icon svg polyline {
            fill: none !important;
            stroke: #ffffff !important;
        }

        .art-video-player .art-setting-item-left-text {
            font-size: 13.5px !important;
            font-weight: 500 !important;
            color: #ffffff !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }

        .art-video-player .art-setting-item-right {
            flex-shrink: 0 !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
        }

        .art-video-player .art-setting-item-right-tooltip {
            font-size: 12.5px !important;
            font-weight: 400 !important;
            color: rgba(255, 255, 255, 0.78) !important;
            white-space: nowrap !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }

        .art-video-player .art-setting-item-right-icon {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Pure CSS Crisp 90deg White Chevron Arrow */
        .art-video-player .art-setting-item-right-icon svg {
            display: none !important;
        }
        .art-video-player .art-setting-item-right-icon::after {
            content: '';
            display: block;
            width: 5.5px;
            height: 5.5px;
            border-top: 1.8px solid rgba(255, 255, 255, 0.75);
            border-right: 1.8px solid rgba(255, 255, 255, 0.75);
            transform: rotate(45deg);
            margin-right: 2px;
        }

        /* Hide chevron arrow on Picture-in-Picture row */
        .art-video-player .art-setting-item[data-name="pip"] .art-setting-item-right-icon::after,
        .art-video-player .art-setting-item:nth-child(6) .art-setting-item-right-icon::after {
            display: none !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/artplayer/5.1.7/artplayer.js"></script>
    <script src="/js/kz-fullscreen.js?v=3"></script>
<script src="/js/kz-player-core.js?v=2"></script>
    <script src="/js/kz-player-v3.js?v=1"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.18/dist/hls.min.js"></script>
    <script>
        (function() {
            // Anti-Redirect Hijack Guard: Cegah skrip iklan iframe membajak window.top ke Shopee / PLEASE WAIT
            let _isAuthorizedNav = false;
            window.addEventListener('beforeunload', function(e) {
                if (!_isAuthorizedNav && document.querySelector('iframe')) {
                    e.preventDefault();
                    e.returnValue = 'Batalkan pengalihan ke situs iklan?';
                    return e.returnValue;
                }
            });
            document.addEventListener('click', function(e) {
                if (e.target.closest('a, button, [wire\\:click], input, select, textarea')) {
                    _isAuthorizedNav = true;
                    setTimeout(() => { _isAuthorizedNav = false; }, 3000);
                }
            }, true);

            function initWatchMovieComponent() {
                const componentDefinition = () => ({
                    isPlaying: false,
                    isLoadingStream: false,
                    art: null,
                    hls: null,
                    videoSrc: {!! json_encode($media['video_url'] ?? '') !!},
                    subtitles: @json($media['subtitles'] ?? []),
                    sources: @json($media['sources'] ?? []),
                    activeQuality: 0,
                    movieId: '{{ $media['id'] ?? '' }}',
                    movieTitle: {!! json_encode($media['title'] ?? '') !!},
                    movieYear: {!! json_encode($media['year'] ?? '') !!},
                    activeSubtitleLang: 'id',
                    serversList: @js($servers),
                    activeServer: '{{ $servers[0]["id"] ?? "server1" }}',
                    isEmbed: {{ !empty($servers[0]["type"]) && $servers[0]["type"] === "embed" ? "true" : "false" }},
                    currentEmbedUrl: {!! json_encode($servers[0]["embed_url"] ?? "") !!},
                    savedProgressSeconds: {{ (float) ($savedProgressSeconds ?? 0) }},
                    useProxy: false,
                    streamError: '',
                    _prefetch: null,
                    _stallTimer: null,
                    _proxyRestarted: false,

                    init() {
                        // Ambil stream sejak halaman dibuka supaya saat tombol play ditekan video langsung jalan (khusus HLS)
                        if (!this.isEmbed && !this.videoSrc && this.movieId) this._prefetch = this.fetchStreamAsync(false);
                    },

                    switchServer(id, embedUrl, isEmbedServer) {
                        const target = Array.isArray(this.serversList) ? this.serversList.find(s => s.id === id) : null;
                        if (target) {
                            this.activeServer = target.id;
                            this.isEmbed = target.type === 'embed';
                            this.currentEmbedUrl = target.embed_url || '';
                        } else {
                            this.activeServer = id;
                            this.isEmbed = isEmbedServer;
                            this.currentEmbedUrl = embedUrl;
                        }
                        if (this.isEmbed) {
                            if (this.art) {
                                try { this.art.destroy(false); } catch(e) {}
                                this.art = null;
                            }
                            this.isPlaying = true;
                        } else {
                            this.isPlaying = true;
                            this.$nextTick(() => {
                                this.initPlayer();
                            });
                        }
                    },

                    // HP/tablet: fullscreen native + kunci landscape. Desktop: tetap web fullscreen.
                    useNativeFs() {
                        return !!(window.KzFs && window.KzFs.isTouch() && window.KzFs.supportsElement(this.$refs.playerShell));
                    },

                    enterNativeFs() {
                        if (window.KzFs && this.$refs.playerShell) window.KzFs.enter(this.$refs.playerShell);
                    },

                    toggleNativeFs() {
                        if (!window.KzFs) return;
                        if (window.KzFs.isActive(this.$refs.playerShell)) window.KzFs.exit();
                        else this.enterNativeFs();
                    },

                    afterInitFs() {
                        if (!this.art) return;
                        if (this.useNativeFs()) {
                            if (!window.KzFs.isActive(this.$refs.playerShell)) this.art.fullscreenWeb = (!window.KzFs || KzFs.allowWebFs());
                        } else {
                            this.art.fullscreenWeb = (!window.KzFs || KzFs.allowWebFs());
                        }
                    },

                    restartWithProxy() {
                        // Bangun ulang player (video + audio) lewat proxy, lanjut dari posisi terakhir
                        if (this._proxyRestarted) return;
                        this._proxyRestarted = true;
                        this.useProxy = true;
                        const at = (this.art && this.art.currentTime) || 0;
                        this.report('restart-proxy', 'at=' + Math.round(at));
                        this.initPlayer();
                        if (this.art) {
                            this.art.once('video:loadedmetadata', () => {
                                if (at > 1) this.art.currentTime = at;
                                Promise.resolve(this.art.play()).catch(() => {});
                            });
                        }
                    },

                    setSubtitle(lang) {
                        this.activeSubtitleLang = lang;
                        if (!this.art) return;
                        if (lang === 'off') {
                            this.art.subtitle.show = false;
                            return;
                        }
                        const list = Array.isArray(this.subtitles) ? this.subtitles : Object.values(this.subtitles || {});
                        const sub = list.find(s => s && (s.lang === lang || (lang === 'id' && (s.label || '').toLowerCase().includes('indo'))
                            || (lang === 'en' && (s.label || '').toLowerCase().includes('eng')))) || list[0];
                        if (!sub) return;
                        const subPath = sub.path || sub.url || '';
                        if (!subPath) return;
                        this.art.subtitle.switch('/api/streaming/subtitle?url=' + encodeURIComponent(subPath), { type: 'vtt' });
                        this.art.subtitle.show = true;
                    },

                    report(kind, detail = '') {
                        try {
                            const q = new URLSearchParams({ kind, detail: String(detail).slice(0, 300), type: 'movie', id: this.movieId, proxy: this.useProxy ? '1' : '0' });
                            fetch('/api/player-log?' + q.toString(), { keepalive: true }).catch(() => {});
                        } catch (e) {}
                    },

                    get qualityOptions() {
                        return window.KzPlayerV3 ? window.KzPlayerV3.normalize(this.sources) : [];
                    },

                    subtitleLabel(lang) {
                        const list = Array.isArray(this.subtitles) ? this.subtitles : Object.values(this.subtitles || {});
                        const found = list.find(s => s && s.lang === lang);
                        return found ? (found.label || lang) : '';
                    },

                    setQuality(quality) {
                        const opt = this.qualityOptions.find(o => o.quality === Number(quality));
                        if (!opt) return;
                        this.activeQuality = opt.quality;
                        window.KzPlayerV3 && window.KzPlayerV3.rememberQuality(opt.quality);
                        this.videoSrc = opt.url;
                        window.KzPlayerV3 && window.KzPlayerV3.applyQuality(this, opt.url);
                        this.syncQualitySetting();
                    },

                    // Isi menu "Resolution" di dalam player dengan daftar kualitas provider.
                    syncQualitySetting() {
                        if (!this.art || !this.art.setting) return;
                        const opts = this.qualityOptions;
                        if (!opts.length) return;
                        const selector = [
                            { html: 'Auto', quality: 0, levelIndex: -1, default: !this.activeQuality },
                            ...opts.map(o => ({ html: o.label, quality: o.quality, default: o.quality === this.activeQuality })),
                        ];
                        try {
                            this.art.setting.update({
                                name: 'resolution',
                                selector: selector,
                                tooltip: this.activeQuality ? this.activeQuality + 'p' : 'Auto',
                            });
                        } catch (e) {}
                    },

                    destroy() {
                        if (this.hls) {
                            this.hls.destroy();
                            this.hls = null;
                        }
                        if (this.art) {
                            this.art.destroy(false);
                            this.art = null;
                        }
                    },

                    async fetchStreamAsync(autoPlayAfter = true, force = false) {
                        if (!this.movieId) return;
                        if (autoPlayAfter) this.isLoadingStream = true;
                        try {
                            const ctrl = new AbortController();
                            const timer = setTimeout(() => ctrl.abort(), 60000);
                            const url = '/api/streaming/stream?type=movie&id=' + encodeURIComponent(this.movieId) + 
                                (force ? '&force=1' : '') + 
                                (this.movieTitle ? '&title=' + encodeURIComponent(this.movieTitle) : '') + 
                                (this.movieYear ? '&year=' + encodeURIComponent(this.movieYear) : '');
                            const res = await fetch(url, { signal: ctrl.signal });
                            clearTimeout(timer);
                            if (res.ok) {
                                const data = await res.json();
                                if (data && data.video_url) {
                                    this.sources = data.sources || [];
                                    const opts = window.KzPlayerV3 ? window.KzPlayerV3.normalize(this.sources) : [];
                                    const pref = (window.KzPlayerV3 && opts.length) ? window.KzPlayerV3.preferredQuality(opts) : 0;
                                    const picked = opts.find(o => o.quality === pref) || null;
                                    this.activeQuality = picked ? picked.quality : 0;
                                    this.videoSrc = picked ? picked.url : data.video_url;
                                    this.subtitles = data.subtitles || [];
                                    if (this.art) this.syncQualitySetting();
                                }
                            } else {
                                this.report('stream-api', res.status);
                            }
                        } catch (e) {
                            this.report('stream-api-exc', e && e.name);
                        } finally {
                            if (autoPlayAfter) this.isLoadingStream = false;
                        }
                        if (autoPlayAfter && this.videoSrc) {
                            this.isPlaying = true;
                            await this.$nextTick();
                            this.initPlayer();
                            this.afterInitFs();
                            this.syncQualitySetting();
                        }
                    },

                    async playVideo() {
                        console.log('playVideo called! videoSrc:', this.videoSrc, 'isPlaying:', this.isPlaying);
                        this.streamError = '';
                        // Harus dipanggil sebelum await apa pun, selagi masih di dalam gesture tap
                        if (this.useNativeFs()) this.enterNativeFs();
                        
                        // Mode embed
                        if (this.isEmbed) {
                            this.isPlaying = true;
                            return;
                        }

                        if (!this.videoSrc) {
                            this.isLoadingStream = true;
                            if (this._prefetch) await this._prefetch;
                            if (!this.videoSrc) await this.fetchStreamAsync(false);
                            this.isLoadingStream = false;
                            if (!this.videoSrc) {
                                const fallbackEmbed = Array.isArray(this.serversList) ? this.serversList.find(s => s.type === 'embed' && s.embed_url) : null;
                                if (fallbackEmbed) {
                                    this.switchServer(fallbackEmbed.id, fallbackEmbed.embed_url, true);
                                    return;
                                }
                                this.isPlaying = false;
                                this.streamError = 'Stream belum tersedia. Coba tekan putar lagi sebentar lagi.';
                                this.report('no-stream');
                                return;
                            }
                        }
                        this.isPlaying = true;
                        await this.$nextTick();
                        this.initPlayer();
                        this.afterInitFs();
                        this.syncQualitySetting();
                    },

                    initPlayer() {
                        const artContainer = this.$refs.artContainer || document.getElementById('movieArtPlayer');
                        if (!artContainer || !this.videoSrc) return;

                        if (this.art) {
                            this.art.destroy(false);
                            this.art = null;
                        }

                        const self = this;
                        let savedSubStyle = 'art-subtitle-style-clean';
                        try {
                            savedSubStyle = localStorage.getItem('kz_sub_style') || 'art-subtitle-style-clean';
                        } catch (e) {}

                        const subList = Array.isArray(this.subtitles) 
                            ? this.subtitles 
                            : Object.values(this.subtitles || {});

                        // Prefer Indonesian as default subtitle
                        const defaultSub = subList.find(s => s && (s.lang === 'id' || s.label?.toLowerCase().includes('indo'))) || subList[0];
                        const defaultSubPath = defaultSub ? (defaultSub.path || defaultSub.url || '') : '';
                        const defaultSubUrl = defaultSubPath ? ('/api/streaming/subtitle?url=' + encodeURIComponent(defaultSubPath)) : '';

                        // Build subtitles options for setting menu
                        const captionOptions = [
                            ...subList.map(s => {
                                const label = s.label || (s.lang === 'id' ? 'Indonesia' : (s.lang === 'en' ? 'English' : s.lang));
                                const sPath = s.path || s.url || '';
                                const isDef = defaultSub && (s.lang === defaultSub.lang || sPath === defaultSubPath);
                                return {
                                    default: isDef,
                                    html: label,
                                    url: sPath ? ('/api/streaming/subtitle?url=' + encodeURIComponent(sPath)) : '',
                                };
                            }),
                            {
                                default: !defaultSub,
                                html: 'Matikan Subtitle (Off)',
                                url: '',
                            }
                        ];

                        const kzCfg = KzPlayerCore.config({
                            container: artContainer,
                            url: this.videoSrc,
                            poster: {{ \Illuminate\Support\Js::from($mBanner ?? '') }},
                            subtitleUrl: defaultSubUrl,
                            nativeFs: self.useNativeFs(),
                            getArt: () => self.art,
                            shell: this.$refs.playerShell,
                            extraSettings: KzPlayerCore.subtitleSettings(captionOptions, defaultSub ? (defaultSub.label || 'Indonesian') : 'Off'),
                        });
                        // HLS khusus film (fallback proxy + pemulihan codec) tetap dipakai.
                        kzCfg.customType = {
                                m3u8: function (video, url, artInstance) {
                                    if (window.Hls && window.Hls.isSupported()) {
                                        if (artInstance.hls) artInstance.hls.destroy();
                                        const masterHost = (() => { try { return new URL(url, location.href).host; } catch (e) { return ''; } })();
                                        const hls = new window.Hls({
                                            // Fallback: kalau potongan video gagal diambil langsung (mis. diblok jaringan),
                                            // ambil lewat server sendiri. Playlist tetap langsung agar URL relatif benar.
                                            xhrSetup: function (xhr, u) {
                                                try {
                                                    const h = new URL(u, location.href).host;
                                                    if (self.useProxy && h !== masterHost && h !== location.host) {
                                                        xhr.open('GET', '/api/proxy-stream?url=' + encodeURIComponent(u), true);
                                                    }
                                                } catch (e) {}
                                            },
                                            enableWorker: true,
                                            lowLatencyMode: false,
                                            maxBufferLength: 60,
                                            backBufferLength: 30,
                                        });
                                        artInstance.hls = hls;
                                        self.hls = hls;

                                        hls.loadSource(url);
                                        hls.attachMedia(video);

                                        hls.on(window.Hls.Events.MANIFEST_PARSED, function () {
                                            const levels = hls.levels;
                                            if (levels && levels.length > 0) {
                                                const qualityOptions = [
                                                    {
                                                        default: true,
                                                        html: 'Auto',
                                                        levelIndex: -1,
                                                    },
                                                    ...levels.map((level, idx) => ({
                                                        html: (level.height ? level.height + 'p' : 'Level ' + (idx + 1)),
                                                        levelIndex: idx,
                                                    }))
                                                ];

                                                artInstance.setting.update({
                                                    name: 'resolution',
                                                    selector: qualityOptions,
                                                    onSelect: function (item) {
                                                        hls.currentLevel = item.levelIndex;
                                                        return item.html;
                                                    },
                                                });
                                            }
                                        });

                                        hls.on(window.Hls.Events.ERROR, async function (event, data) {
                                            // Deteksi browser tidak mendukung codec tertentu (misal HEVC di browser non-hardware)
                                            if (/buffer.*Codec/i.test(data.details || '')) {
                                                console.warn('[KZSTRM] Codec tidak didukung browser ini (' + data.details + '). Berpindah ke server cadangan...');
                                                const altServer = Array.isArray(self.serversList) ? self.serversList.find(s => s.id !== self.activeServer) : null;
                                                if (altServer) {
                                                    self.switchServer(altServer.id);
                                                    return;
                                                }
                                            }

                                            // Potongan video/audio gagal diambil langsung (biasanya domain CDN diblok operator):
                                            // pindah ke proxy server. Retry berikutnya otomatis lewat proxy.
                                            const segErr = data.type === window.Hls.ErrorTypes.NETWORK_ERROR && /frag|key|init/i.test(data.details || '');
                                            if (segErr && !self.useProxy) {
                                                self.useProxy = true;
                                                self.report('switch-proxy', data.details + (data.fatal ? ':fatal' : ''));
                                                if (data.fatal) { self.restartWithProxy(); return; }
                                            }
                                            if (data.fatal) {
                                                self.report('hls-fatal', data.type + ':' + data.details + (data.response ? ':' + data.response.code : '') + (self.useProxy ? ':proxy' : ''));
                                                if (segErr && !self._proxyRestarted) { self.restartWithProxy(); return; }
                                                const isAuthError = data.response && (data.response.code === 403 || data.response.code === 401);
                                                if (isAuthError || data.details === 'manifestLoadError') {
                                                    console.log('Stream token expired/rejected, refreshing fresh stream...');
                                                    await self.fetchStreamAsync(true, true);
                                                    return;
                                                }

                                                switch (data.type) {
                                                    case window.Hls.ErrorTypes.NETWORK_ERROR:
                                                        hls.startLoad();
                                                        break;
                                                    case window.Hls.ErrorTypes.MEDIA_ERROR:
                                                        hls.recoverMediaError();
                                                        break;
                                                    default:
                                                        hls.destroy();
                                                        break;
                                                }
                                            }
                                        });

                                        artInstance.on('destroy', () => hls.destroy());
                                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                        video.src = url;
                                    }
                                }
                            };
                        this.art = new Artplayer(kzCfg);
                        window.__kzArt = this.art;
                        window.art = this.art;
                        // Stamp data-name on setting items for custom styling
                        const stampSettingNames = () => {
                            const items = document.querySelectorAll('.art-setting-item');
                            const names = ['captions', 'subtitle-size', 'subtitle-position', 'resolution', 'playback-speed', 'pip', 'subtitle-style'];
                            items.forEach((item, idx) => {
                                if (names[idx]) item.setAttribute('data-name', names[idx]);
                            });
                        };
                        window.art = this.art;
                        this.art.on('setting', stampSettingNames);
                        this.art.on('fullscreenWeb', (state) => {
                            document.body.classList.toggle('kz-fullscreen-web-active', state);
                            document.documentElement.classList.toggle('kz-fullscreen-web-active', state);
                            window.dispatchEvent(new CustomEvent('artplayer:fullscreenWeb', { detail: state }));
                        });

                        // Auto-resume ke posisi menit terakhir ditonton
                        if (this.savedProgressSeconds > 5) {
                            this.art.once('video:loadedmetadata', () => {
                                if (self.art && self.savedProgressSeconds > 0) {
                                    self.art.currentTime = self.savedProgressSeconds;
                                    const m = Math.floor(self.savedProgressSeconds / 60);
                                    const s = Math.floor(self.savedProgressSeconds % 60);
                                    const formatted = `${m}:${s < 10 ? '0' : ''}${s}`;
                                    self.art.notice.show = `Melanjutkan dari ${formatted}`;
                                }
                            });
                        }

                        // Tampilkan animasi 3-server pada initial load stream (auto-switch ke circular spinner saat seeking)
                        if (window.kzServerLoader) {
                            window.kzServerLoader.applyToArtplayer(this.art, 'Memilih server terbaik', 'Mengoptimalkan stream film & resolusi HD');
                        }

                        // Kalau browser menolak autoplay bersuara, putar tanpa suara dulu; laporkan kalau macet
                        this.art.on('ready', () => {
                            const v = self.art && self.art.video;
                            const pr = v && v.play ? v.play() : null;
                            if (pr && pr.catch) pr.catch((err) => {
                                self.report('play-rejected', err && err.name);
                                self.art.muted = true;
                                Promise.resolve(self.art.play()).catch(() => {});
                                self.art.notice.show = 'Ketuk ikon suara untuk menyalakan audio';
                            });
                            clearTimeout(self._stallTimer);
                            self._stallTimer = setTimeout(() => {
                                const vv = self.art && self.art.video;
                                if (vv && vv.currentTime < 0.5 && !vv.paused) {
                                    self.report('stall', 'rs=' + vv.readyState + ' net=' + vv.networkState);
                                    if (!self._proxyRestarted) { self.useProxy = true; self.restartWithProxy(); }
                                }
                            }, 20000);
                        });

                        // Subtitle box wrapper observer (Netflix style)
                        const subEl = this.art.template.$subtitle;
                        if (subEl) {
                            subEl.classList.remove('art-subtitle-style-clean', 'art-subtitle-style-solid');
                            if (savedSubStyle !== 'custom') {
                                subEl.classList.add(savedSubStyle || 'art-subtitle-style-clean');
                            }
                            let updating = false;
                            const subObserver = new MutationObserver(() => {
                                if (updating) return;
                                if (subEl.childNodes.length > 0 && !subEl.querySelector('.art-subtitle-inner-box')) {
                                    const raw = subEl.innerHTML.trim();
                                    if (raw) {
                                        updating = true;
                                        subEl.innerHTML = '<span class="art-subtitle-inner-box">' + raw + '</span>';
                                        setTimeout(() => { updating = false; }, 20);
                                    }
                                }
                            });
                            subObserver.observe(subEl, { childList: true });
                            this.art.on('destroy', () => subObserver.disconnect());
                        }

                        // Otomatis aktifkan Web Fullscreen (in-page theater mode) saat mulai play
                        let hasTriggeredAutoWebFs = false;
                        const enterWebFs = () => {
                            if (!hasTriggeredAutoWebFs) {
                                hasTriggeredAutoWebFs = true;
                                if (self.useNativeFs() && window.KzFs.isActive(self.$refs.playerShell)) return;
                                self.art.fullscreenWeb = (!window.KzFs || KzFs.allowWebFs());
                            }
                        };
                        this.art.on('video:play', enterWebFs);
                        if (this.art.video) {
                            this.art.video.addEventListener('play', enterWebFs);
                        }

                        // ============ SAVE PLAYBACK PROGRESS ============
                        // Simpan posisi menonton ke server tiap 10 detik & saat pause/beforeunload
                        const progressMeta = {
                            item_id: this.movieId || '{{ $movieSlug ?? "" }}',
                            item_type: 'movie',
                            title: {!! json_encode($media['title'] ?? '') !!},
                            thumbnail: {!! json_encode($media['poster'] ?? $media['thumbnail'] ?? '') !!},
                            url: window.location.pathname,
                        };
                        let _lastSavedTime = 0;
                        const _csrfToken = '{{ csrf_token() }}';

                        const saveProgress = (force = false) => {
                            if (!self.art || !self.art.video) return;
                            const ct = self.art.currentTime || 0;
                            const dur = self.art.duration || 0;
                            if (dur < 10 || ct < 5) return; // skip kalau durasi terlalu pendek atau baru mulai
                            if (!force && Math.abs(ct - _lastSavedTime) < 8) return; // debounce
                            _lastSavedTime = ct;
                            const body = JSON.stringify({
                                ...progressMeta,
                                progress: ct,
                                duration: dur,
                            });
                            if (navigator.sendBeacon && force) {
                                const beaconBody = new FormData();
                                Object.entries(JSON.parse(body)).forEach(([key, value]) => beaconBody.append(key, String(value)));
                                beaconBody.append('_token', _csrfToken);
                                navigator.sendBeacon('/api/watch-progress', beaconBody);
                            } else {
                                fetch('/api/watch-progress', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrfToken },
                                    body,
                                    keepalive: true,
                                }).catch(() => {});
                            }
                        };

                        // Tiap 10 detik saat sedang diputar
                        const _progressInterval = setInterval(() => {
                            if (self.art && !self.art.video?.paused) saveProgress();
                        }, 10000);

                        // Saat pause / video berakhir
                        this.art.on('video:pause', () => saveProgress(true));
                        this.art.on('video:ended', () => saveProgress(true));

                        // Saat tab ditutup / navigasi pergi
                        const _beforeUnload = () => saveProgress(true);
                        window.addEventListener('beforeunload', _beforeUnload);
                        this.art.on('destroy', () => {
                            clearInterval(_progressInterval);
                            window.removeEventListener('beforeunload', _beforeUnload);
                            saveProgress(true);
                        });
                    }
                });

                window.watchPlayerInit = componentDefinition;

                if (window.Alpine) {
                    Alpine.data('watchPlayerInit', componentDefinition);
                } else {
                    document.addEventListener('alpine:init', () => {
                        Alpine.data('watchPlayerInit', componentDefinition);
                    });
                }
            }
            initWatchMovieComponent();
        })();
    </script>
@endpush
