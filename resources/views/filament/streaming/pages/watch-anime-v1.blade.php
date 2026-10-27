@push('styles')
    <style>
        .kz-noscroll { scrollbar-width: none; } .kz-noscroll::-webkit-scrollbar { display: none; }
        .kz-thin { scrollbar-width: thin; scrollbar-color: #3f3f46 transparent; }
        .kz-fade-x { -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 16px, #000 calc(100% - 24px), transparent 100%); mask-image: linear-gradient(90deg, transparent 0, #000 16px, #000 calc(100% - 24px), transparent 100%); }
        @media (min-width: 1024px) { .lg\:kz-fade-y { -webkit-mask-image: linear-gradient(180deg, #000 calc(100% - 48px), transparent 100%); mask-image: linear-gradient(180deg, #000 calc(100% - 48px), transparent 100%); padding-bottom: 2rem; } }
        @keyframes kzbar { 0%,100% { transform: scaleY(.4) } 50% { transform: scaleY(1) } }
        [class*='kzbar'] { transform-origin: bottom; }

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
        .art-video-player .art-volume-slider-handle { background: #E50914 !important; }

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

        /* 3. Subtitle styling */
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

        .art-video-player.art-control-show .art-subtitle {
            bottom: 72px !important;
        }

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

        .art-subtitle-style-solid .art-subtitle-inner-box {
            background: #000000 !important;
        }

        /* 5. Menu settings popup Artplayer 100% Persis Movie (Pixel-Perfect) */
        .art-video-player .art-settings {
            height: auto !important;
            max-height: 380px !important;
            overflow: visible !important;
            padding: 0 !important;
            background: transparent !important;
        }

        .art-video-player .art-setting-panel {
            background: rgba(18, 18, 20, 0.85) !important;
            backdrop-filter: blur(18px) !important;
            -webkit-backdrop-filter: blur(18px) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 16px !important;
            box-shadow: 0 12px 36px 0 rgba(0, 0, 0, 0.8) !important;
            padding: 6px !important;
            color: #f4f4f5 !important;
            font-family: inherit !important;
            min-width: 200px !important;
        }

        .art-video-player .art-setting-item {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            height: 38px !important;
            line-height: 38px !important;
            padding: 0 10px !important;
            margin: 2px 0 !important;
            border-radius: 10px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            color: #d4d4d8 !important;
            cursor: pointer !important;
            transition: all 0.15s ease !important;
        }

        .art-video-player .art-setting-item:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
        }

        .art-video-player .art-setting-item-left {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        .art-video-player .art-setting-item-left-icon {
            width: 18px !important;
            height: 18px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            color: #a1a1aa !important;
            flex-shrink: 0 !important;
        }

        .art-video-player .art-setting-item-left-icon svg {
            width: 15px !important;
            height: 15px !important;
            stroke-width: 2 !important;
            stroke: currentColor !important;
            fill: none !important;
        }

        .art-video-player .art-setting-item-left-icon svg rect,
        .art-video-player .art-setting-item-left-icon svg circle,
        .art-video-player .art-setting-item-left-icon svg path,
        .art-video-player .art-setting-item-left-icon svg line,
        .art-video-player .art-setting-item-left-icon svg polyline {
            stroke-width: 2 !important;
        }

        .art-video-player .art-setting-item-left-text {
            font-size: 13px !important;
            font-weight: 500 !important;
            letter-spacing: -0.01em !important;
            color: inherit !important;
        }

        .art-video-player .art-setting-item-right {
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
            color: #a1a1aa !important;
            font-size: 12px !important;
        }

        .art-video-player .art-setting-item-right-tooltip {
            color: #a1a1aa !important;
            font-size: 12px !important;
            font-weight: 500 !important;
        }

        .art-video-player .art-setting-item-right-icon {
            width: 14px !important;
            height: 14px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

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

        .art-video-player .art-setting-item[data-name="pip"] .art-setting-item-right-icon::after,
        .art-video-player .art-setting-item:nth-child(6) .art-setting-item-right-icon::after {
            display: none !important;
        }
    </style>
@endpush

@php
    $backdropImg = $metadata['banner'] ?? ($metadata['poster'] ?? ($seriesDetail['thumbnail'] ?? ''));
    $epNo = $episodeDetail['episode_number'] ?? $episodeSlug;
    $episodes = $seriesDetail['episodes'] ?? [];
    $totalEps = count($episodes);
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 lg:pb-16 relative"
     x-data="initWatchAnimeComponent()">

    {{-- Ambient backdrop ala TV Series --}}
    @if($backdropImg)
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[70vh] overflow-hidden" aria-hidden="true">
            <img src="{{ $backdropImg }}" alt="" class="w-full h-full object-cover opacity-20 blur-3xl scale-125">
            <div class="absolute inset-0 bg-gradient-to-b from-[#050505]/30 via-[#050505]/80 to-[#050505]"></div>
        </div>
    @endif

    <div class="relative w-full max-w-[1400px] mx-auto lg:px-8 lg:pt-24">

        {{-- Top bar --}}
        <div class="flex items-center gap-3 px-4 lg:px-0 h-14 pt-[env(safe-area-inset-top,0px)] box-content lg:h-12 lg:pt-0 lg:mb-3">
            <a href="{{ \App\Filament\Streaming\Pages\AnimeDetail::getUrl(['slug' => $animeSlug], panel: 'streaming') }}" aria-label="Kembali ke detail anime"
               class="w-10 h-10 shrink-0 inline-flex items-center justify-center rounded-full bg-white/5 ring-1 ring-white/10 active:bg-white/10 hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold leading-tight truncate">{{ $seriesDetail['title'] ?? 'Anime' }}</p>
                <p class="text-[11px] text-zinc-400 leading-tight truncate">Episode {{ $epNo }}</p>
            </div>
            <span class="shrink-0 text-[10px] font-black tracking-wider text-red-300 bg-red-600/15 ring-1 ring-red-500/30 px-2 py-1 rounded-md tabular-nums">EP {{ $epNo }}</span>
        </div>

        {{-- ============ TWO-COLUMN GRID: LEFT (PLAYER & INFO) + RIGHT (EPISODE PLAYLIST) ============ --}}
        <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-8">

            {{-- ============ LEFT: PLAYER + SERVER + INFO + DOWNLOADS ============ --}}
            <div class="min-w-0">
                {{-- Video Player Card Container with Artplayer --}}
                <div data-kz-fs x-ref="playerShell" wire:ignore.self class="relative w-full aspect-video bg-black lg:rounded-2xl overflow-hidden lg:ring-1 lg:ring-white/10 shadow-2xl border-y lg:border-0 border-zinc-800/80">
                    <button type="button" data-kz-rotate aria-pressed="false" aria-label="Layar penuh landscape"
                            class="kz-fs-btn absolute top-2 right-2 z-[60] items-center gap-1.5 h-9 px-3 rounded-full bg-black/65 backdrop-blur text-white text-xs font-bold ring-1 ring-white/20 active:bg-black/80">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="14" height="10" rx="2"/><path d="M20 4v5h-5"/><path d="M20 9a8 8 0 0 0-8-6"/></svg>
                        <span>Layar penuh</span>
                    </button>

                    <!-- Embed Player -->
                    <template x-if="isEmbed && currentEmbedUrl">
                        <iframe :src="currentEmbedUrl" class="w-full h-full border-0" allowfullscreen allow="autoplay; fullscreen; picture-in-picture"></iframe>
                    </template>

                    <!-- Native Artplayer Player -->
                    <template x-if="!isEmbed">
                        <div class="w-full h-full bg-black relative flex items-center justify-center">
                            <div x-ref="artContainer" class="w-full h-full" x-show="videoSrc"></div>
                            <div x-show="!videoSrc" class="flex flex-col items-center justify-center text-zinc-500 p-6 text-center">
                                <svg class="w-12 h-12 mb-3 stroke-current opacity-60 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h-14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                <p class="text-sm font-medium text-white">Stream video belum tersedia</p>
                                <p class="text-xs text-zinc-500 mt-1">Silakan coba ganti server atau pilih episode lain.</p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Server Switcher --}}
                <template x-if="serversList && serversList.length > 0">
                    <div class="px-4 lg:px-0 flex items-center gap-2 overflow-x-auto kz-noscroll pt-3 pb-1">
                        <span class="text-xs font-bold text-zinc-400 mr-1 flex items-center gap-1.5 shrink-0">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.7 7.2a3 3 0 012.4-1.2h7.8a3 3 0 012.4 1.2l2.55 4.35A4.5 4.5 0 0121 14.25"/></svg>
                            Pilihan Server:
                        </span>
                        <template x-for="s in serversList" :key="s.id">
                            <button type="button" @click="switchServer(s.id)"
                                    class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold ring-1 transition flex items-center gap-1.5"
                                    :class="activeServer === s.id ? 'bg-red-600 text-white ring-red-500 shadow-lg shadow-red-600/30' : 'bg-zinc-900 text-zinc-300 ring-white/10 hover:text-white'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="activeServer === s.id ? 'bg-white' : 'bg-zinc-500'"></span>
                                <span x-text="s.name"></span>
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Info Block --}}
                <div class="px-4 lg:px-0 pt-4 space-y-4">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-red-400 mb-1">{{ $seriesDetail['title'] ?? 'Anime' }}</p>
                        <h1 class="text-xl md:text-2xl lg:text-3xl font-black tracking-tight leading-tight">
                            {{ $episodeDetail['title'] ?? ($seriesDetail['title'] . ' Episode ' . $epNo) }}
                        </h1>
                        <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-400">
                            <span class="px-2 py-0.5 rounded-md bg-red-600/20 text-red-400 font-bold border border-red-500/30" x-text="serversList.find(s => s.id === activeServer)?.name ? (activeServer === 'server1' ? 'SERVER 1 FHD' : 'SERVER 2 ALT') : 'SERVER 1 FHD'">SERVER 1 FHD</span>
                            @if(!empty($metadata['score']))
                                <span class="inline-flex items-center gap-1 font-bold text-amber-300">
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                    {{ $metadata['score'] }} MAL
                                </span>
                            @endif
                            @if(!empty($metadata['duration']))
                                <span class="text-zinc-600">·</span>
                                <span>{{ $metadata['duration'] }}</span>
                            @endif
                            <span class="text-zinc-600">·</span>
                            <span class="font-bold text-emerald-400">HD 720p</span>
                            @if(!empty($seriesDetail['genres']))
                                @foreach(array_slice($seriesDetail['genres'], 0, 3) as $g)
                                    <span class="text-zinc-600">·</span>
                                    <span>{{ $g }}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Action & Navigation Bar (Prev / Next / Favorite / Share) --}}
                    <div class="grid grid-cols-[1fr_1fr_auto_auto] gap-2">
                        @if(!empty($prevEpisodeSlug))
                            <a href="{{ \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $prevEpisodeSlug], panel: 'streaming') }}"
                               class="inline-flex items-center justify-center gap-1.5 h-11 rounded-xl bg-zinc-900 ring-1 ring-white/10 text-sm font-bold active:bg-zinc-800 hover:bg-zinc-800 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                                Ep {{ $prevEpisodeSlug }}
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center h-11 rounded-xl bg-zinc-950 ring-1 ring-white/5 text-sm font-bold text-zinc-500">Episode pertama</span>
                        @endif

                        @if(!empty($nextEpisodeSlug))
                            <a href="{{ \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $nextEpisodeSlug], panel: 'streaming') }}"
                               class="inline-flex items-center justify-center gap-1.5 h-11 rounded-xl bg-red-600 text-sm font-bold shadow-lg shadow-red-600/25 active:bg-red-700 hover:bg-red-700 transition">
                                Ep {{ $nextEpisodeSlug }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center h-11 rounded-xl bg-zinc-950 ring-1 ring-white/5 text-sm font-bold text-zinc-500">Episode terakhir</span>
                        @endif

                        <button wire:click="toggleFavorite" type="button" aria-label="Simpan favorit"
                                class="w-11 h-11 inline-flex items-center justify-center rounded-xl {{ $isFavorited ? 'bg-red-600 text-white ring-1 ring-red-500 shadow-md shadow-red-600/30' : 'bg-zinc-900 ring-1 ring-white/10 text-zinc-300 hover:text-white' }} transition">
                            <svg class="w-5 h-5 {{ $isFavorited ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </button>

                        <button type="button" x-data="{ copied: false }" aria-label="Bagikan episode"
                                @click="if (navigator.share) { navigator.share({ title: document.title, url: location.href }).catch(() => {}) } else { navigator.clipboard.writeText(location.href); copied = true; setTimeout(() => copied = false, 1800) }"
                                class="relative w-11 h-11 inline-flex items-center justify-center rounded-xl bg-zinc-900 ring-1 ring-white/10 active:bg-zinc-800 hover:bg-zinc-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/></svg>
                            <span x-show="copied" x-cloak class="absolute -top-8 right-0 whitespace-nowrap text-[10px] font-bold bg-white text-black px-2 py-1 rounded-md">Link disalin</span>
                        </button>
                    </div>

                    {{-- Downloads for this Episode --}}
                    @if(!empty($downloads['episode']))
                        <div id="episode-downloads" class="pt-4">
                            <div class="p-4 md:p-5 rounded-2xl bg-zinc-900/50 border border-zinc-800/80">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="p-1.5 rounded-lg bg-red-500/20 text-red-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                    </span>
                                    <h3 class="text-sm font-black text-white">Download Episode {{ $epNo }}</h3>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                                    @foreach($downloads['episode'] as $dl)
                                        <div class="p-3 rounded-xl bg-zinc-950/70 border border-zinc-800">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-red-950/80 text-red-400 border border-red-500/30">
                                                    {{ $dl['format'] ?? 'MP4' }}
                                                </span>
                                                @if(!empty($dl['size']))
                                                    <span class="text-[11px] font-mono font-bold text-zinc-400">{{ $dl['size'] }}</span>
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($dl['links'] ?? [] as $lk)
                                                    <a href="{{ $lk['url'] }}" target="_blank" rel="noopener noreferrer"
                                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-zinc-900 hover:bg-red-600 text-zinc-300 hover:text-white text-[10px] font-bold border border-zinc-800 transition">
                                                        <span>{{ $lk['host'] }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============ RIGHT: EPISODE PLAYLIST (SIDEBAR) ============ --}}
            <aside class="px-4 lg:px-0 pt-6 lg:pt-0 min-w-0" aria-label="Daftar episode">
                <div class="lg:sticky lg:top-24 lg:rounded-2xl lg:bg-zinc-900/50 lg:ring-1 lg:ring-white/[0.06] lg:p-4">
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 class="text-base font-black tracking-tight">Episode</h2>
                        <span class="text-[11px] text-zinc-500">{{ $totalEps }} episode</span>
                    </div>

                    <ol class="mt-3 space-y-2 lg:max-h-[calc(100vh-14rem)] lg:overflow-y-auto lg:pr-1 kz-thin lg:kz-fade-y"
                        x-init="$nextTick(() => { const a = $el.querySelector('[data-active]'); if (a && window.innerWidth >= 1024) a.scrollIntoView({ block: 'center' }) })">
                        @foreach($episodes as $ep)
                            @php
                                $epItemNum = (string)($ep['episode'] ?? '');
                                $isActive = $epItemNum === (string)$episodeSlug;
                                $epUrl = !empty($epItemNum)
                                    ? \App\Filament\Streaming\Pages\WatchAnime::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $epItemNum], panel: 'streaming')
                                    : '#';
                                $thumb = $seriesDetail['thumbnail'] ?? ($metadata['poster'] ?? '');
                            @endphp
                            <li>
                                <a href="{{ $epUrl }}" @if($isActive) data-active aria-current="true" @endif
                                   class="group flex gap-3 p-2 -mx-2 lg:mx-0 rounded-xl transition {{ $isActive ? 'bg-red-600/10 ring-1 ring-red-500/40' : 'hover:bg-white/[0.04] active:bg-white/[0.06]' }}">
                                    <div class="relative w-28 sm:w-32 aspect-video shrink-0 self-start rounded-lg overflow-hidden bg-zinc-800">
                                        @if($thumb)
                                            <img src="{{ $thumb }}" alt="" class="w-full h-full object-cover {{ $isActive ? '' : 'opacity-85 group-hover:opacity-100' }}" loading="lazy">
                                        @endif
                                        @if($isActive)
                                            <span class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                                <span class="flex items-end gap-0.5 h-4" aria-hidden="true">
                                                    <span class="w-1 bg-red-500 rounded-sm animate-[kzbar_0.9s_ease-in-out_infinite]" style="height:60%"></span>
                                                    <span class="w-1 bg-red-500 rounded-sm animate-[kzbar_0.9s_ease-in-out_0.2s_infinite]" style="height:100%"></span>
                                                    <span class="w-1 bg-red-500 rounded-sm animate-[kzbar_0.9s_ease-in-out_0.4s_infinite]" style="height:40%"></span>
                                                </span>
                                            </span>
                                        @endif
                                        <span class="absolute bottom-1 right-1 text-[9px] font-bold bg-black/75 text-white px-1 py-0.5 rounded">EP {{ $epItemNum }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1 py-0.5">
                                        <p class="text-[10px] font-black uppercase tracking-wider {{ $isActive ? 'text-red-400' : 'text-zinc-500' }}">
                                            {{ $isActive ? 'Sedang diputar' : 'Episode ' . $epItemNum }}
                                        </p>
                                        <p class="mt-0.5 text-sm font-bold leading-snug line-clamp-2 {{ $isActive ? 'text-white' : 'text-zinc-200' }}">
                                            {{ $ep['title'] ?? ('Episode ' . $epItemNum) }}
                                        </p>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </aside>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/artplayer/5.1.7/artplayer.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.18/dist/hls.min.js"></script>
    <script src="/js/kz-fullscreen.js?v=2"></script>
    <script>
        (function() {
            let _isAuthorizedNav = false;
            let _art = null; // instance Artplayer non-reaktif
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

            window.initWatchAnimeComponent = function() {
                return {
                    hls: null,
                    videoSrc: {!! json_encode($currentStreamUrl) !!},
                    serversList: @js($servers),
                    activeServer: '{{ $activeServer }}',
                    isEmbed: {{ $isEmbed ? 'true' : 'false' }},
                    currentEmbedUrl: {!! json_encode($currentEmbedUrl) !!},

                    init() {
                        // HP diputar balik ke potret saat fullscreen → keluar (gaya YouTube)
                        window.addEventListener('kz-fs-portrait', () => {
                            if (_art && _art.fullscreenWeb) _art.fullscreenWeb = false;
                        });
                        this.$nextTick(() => {
                            if (!this.isEmbed && this.videoSrc) {
                                this.initPlayer();
                            }
                        });
                    },

                    switchServer(id) {
                        const target = Array.isArray(this.serversList) ? this.serversList.find(s => s.id === id) : null;
                        if (!target) return;

                        this.activeServer = target.id;
                        this.isEmbed = (target.type === 'embed');
                        this.currentEmbedUrl = target.embed_url || '';
                        if (!this.isEmbed && target.stream_url) {
                            this.videoSrc = target.stream_url;
                        }

                        if (this.isEmbed) {
                            if (_art) {
                                try { _art.destroy(false); } catch(e) {}
                                _art = null;
                            }
                        } else {
                            this.$nextTick(() => {
                                this.initPlayer();
                            });
                        }
                    },

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

                    initPlayer() {
                        if (this.isEmbed || !this.videoSrc) return;
                        const artContainer = this.$refs.artContainer;
                        if (!artContainer) return;

                        if (_art) {
                            try { _art.destroy(false); } catch(e) {}
                            _art = null;
                        }

                        const self = this;
                        const isHls = this.videoSrc.includes('.m3u8');

                        _art = new Artplayer({
                            container: artContainer,
                            url: this.videoSrc,
                            type: isHls ? 'm3u8' : 'mp4',
                            theme: '#E50914',
                            volume: 0.8,
                            isLive: false,
                            muted: false,
                            autoplay: true,
                            pip: true,
                            setting: true,
                            loop: false,
                            flip: false,
                            playbackRate: false,
                            aspectRatio: false,
                            fullscreen: !self.useNativeFs(),
                            fullscreenWeb: !self.useNativeFs(),
                            subtitleOffset: false,
                            miniProgressBar: false,
                            playsInline: true,
                            airplay: true,
                            hotkey: true,
                            customType: {
                                m3u8: function (video, url, art) {
                                    if (Hls.isSupported()) {
                                        if (art.hls) art.hls.destroy();
                                        const hls = new Hls({ enableWorker: true });
                                        hls.loadSource(url);
                                        hls.attachMedia(video);
                                        art.hls = hls;
                                        art.on('destroy', () => hls.destroy());
                                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                        video.src = url;
                                    }
                                }
                            },
                            controls: [
                                {
                                    name: 'kz-fs',
                                    position: 'right',
                                    index: 70,
                                    html: '<svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="14" height="10" rx="2"/><path d="M20 4v5h-5"/><path d="M20 9a8 8 0 0 0-8-6"/></svg>',
                                    tooltip: 'Layar penuh',
                                    style: self.useNativeFs() ? {} : { display: 'none' },
                                    click: () => self.toggleNativeFs(),
                                },
                                {
                                    position: 'left',
                                    index: 10,
                                    html: '<svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/></svg>',
                                    tooltip: 'Mundur 10 Detik',
                                    click: function () {
                                        if (_art) _art.seek = Math.max(0, _art.currentTime - 10);
                                    },
                                },
                                {
                                    position: 'left',
                                    index: 11,
                                    html: '<svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/></svg>',
                                    tooltip: 'Maju 10 Detik',
                                    click: function () {
                                        if (_art) _art.seek = Math.min(_art.duration || 999999, _art.currentTime + 10);
                                    },
                                },
                            ],
                            settings: [
                                {
                                    html: 'Playback speed',
                                    name: 'playback-speed',
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>',
                                    tooltip: '1x',
                                    selector: [
                                        { html: '0.5x', speed: 0.5 },
                                        { html: '0.75x', speed: 0.75 },
                                        { default: true, html: '1x', speed: 1.0 },
                                        { html: '1.25x', speed: 1.25 },
                                        { html: '1.5x', speed: 1.5 },
                                        { html: '2x', speed: 2.0 },
                                    ],
                                    onSelect: function (item) {
                                        _art.playbackRate = item.speed;
                                        return item.html;
                                    },
                                },
                                {
                                    html: 'Picture-in-Picture',
                                    name: 'pip',
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><rect x="11" y="9" width="9" height="6" rx="1" stroke="currentColor" fill="none"/></svg>',
                                    tooltip: 'Off',
                                    selector: [
                                        { html: 'On' },
                                        { default: true, html: 'Off' }
                                    ],
                                    onSelect: function (item) {
                                        _art.pip = !_art.pip;
                                        return _art.pip ? 'On' : 'Off';
                                    },
                                },
                                {
                                    html: 'Flip Video',
                                    name: 'flip',
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
                                    tooltip: 'Normal',
                                    selector: [
                                        { default: true, html: 'Normal', flip: 'normal' },
                                        { html: 'Horizontal', flip: 'horizontal' },
                                        { html: 'Vertical', flip: 'vertical' },
                                    ],
                                    onSelect: function (item) {
                                        _art.flip = item.flip;
                                        return item.html;
                                    },
                                },
                                {
                                    html: 'Aspect Ratio',
                                    name: 'aspect-ratio',
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="2" y1="9" x2="22" y2="9"/></svg>',
                                    tooltip: 'Default',
                                    selector: [
                                        { default: true, html: 'Default', ratio: 'default' },
                                        { html: '16:9', ratio: '16:9' },
                                        { html: '4:3', ratio: '4:3' },
                                    ],
                                    onSelect: function (item) {
                                        _art.aspectRatio = item.ratio;
                                        return item.html;
                                    },
                                }
                            ]
                        });

                        _art.on('play', () => {
                            if (self.useNativeFs() && window.KzFs && !KzFs.current()) {
                                self.enterNativeFs();
                            }
                        });
                    }
                };
            };
        })();
    </script>
@endpush
