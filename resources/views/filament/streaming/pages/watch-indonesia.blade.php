@php
    $mTitle = $media['title'] ?? 'Film Indonesia';
    $mPoster = $media['poster'] ?? ($media['posterPath'] ?? null);
    $mBanner = $media['backdrop'] ?? ($media['banner'] ?? ($media['thumbnail'] ?? $mPoster));
    $mYear = $media['year'] ?? '';
    $mOverview = $media['overview'] ?? ($media['synopsis'] ?? '');
    $mRating = $media['voteAverage'] ?? ($media['rating'] ?? 8.0);
    $mRuntime = $media['duration'] ?? null;
    $genres = $media['genres'] ?? ['Indonesia'];
    $isSeries = $isSeries ?? false;
    $serverLabel = $serverLabel ?? 'Rebahin';
    $providerParam = $providerParam ?? 'rebahin';
    $servers = $servers ?? [];
    $episodes = $episodes ?? [];
    $currentEpisode = $currentEpisode ?? 1;
    $slugParam = $slugParam ?? '';
@endphp

@push('styles')
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

        /* 2. Saat kontrol aktif/hover, berikan gradien halus full-width di art-bottom */
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

        /* 4. Kotak subtitle transparan ala IDLIX / Netflix yang super rapi dan kontras tinggi */
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

        /* 5. Menu settings popup Artplayer 100% Persis IDLIX (Pixel-Perfect) */
        .art-video-player .art-settings {
            height: auto !important;
            max-height: 380px !important;
            overflow: visible !important;
        }

        .art-video-player .art-setting-panel {
            min-width: 255px !important;
            width: 255px !important;
            max-width: 92vw !important;
            height: auto !important;
            max-height: 380px !important;
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

        .kz-noscroll::-webkit-scrollbar { display: none; }
        .kz-noscroll { -ms-overflow-style: none; scrollbar-width: none; }
        .art-video-player.art-fullscreen-web {
            z-index: 99999 !important;
            isolation: auto !important;
        }
        body:has(.art-fullscreen-web) header,
        html.kz-fullscreen-web-active header {
            display: none !important;
        }
    </style>
@endpush

@php
    // Label server yang bersih untuk penonton: "Server 1 (Direct 720p)" -> ["Server 1", "720p"]
    $cleanServer = function (array $s, int $i) {
        $name = $s['name'] ?? ('Server ' . ($i + 1));
        $quality = '';
        if (preg_match('/\(([^)]+)\)/', $name, $m)) {
            $quality = trim(preg_replace('/\b(direct|native)\b/i', '', $m[1]));
        }
        return ['label' => 'Server ' . ($i + 1), 'quality' => $quality ?: 'HD'];
    };
    $epCount = count($episodes);
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-44 lg:pb-24 select-none">

    {{-- Spacer agar konten tidak tertutup header melayang --}}
    <div class="h-[76px] md:h-[92px]"></div>

    <div class="relative w-full max-w-[1400px] mx-auto lg:px-8" x-data="watchPlayerInit()">

        {{-- Breadcrumb ringkas --}}
        <nav aria-label="Breadcrumb" class="px-4 lg:px-0 pb-3 flex items-center gap-2 text-xs text-zinc-400">
            <a href="/indonesia" class="hover:text-white transition">Indonesia</a>
            <svg class="w-3 h-3 text-zinc-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <a href="/indonesia/{{ $providerParam }}" class="hover:text-white transition">{{ $serverLabel }}</a>
            <svg class="w-3 h-3 text-zinc-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="text-zinc-200 font-semibold truncate" aria-current="page">{{ $mTitle }}</span>
        </nav>

        <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_340px] lg:gap-10">

            {{-- ================= KOLOM UTAMA ================= --}}
            <div class="min-w-0">

                {{-- Player --}}
                <div class="w-full bg-black lg:rounded-xl overflow-hidden aspect-video relative isolate z-10 shadow-[0_40px_100px_-30px_rgba(0,0,0,1)]" x-ref="playerShell" data-kz-fs @kz-fs-failed="art && (art.fullscreenWeb = (!window.KzFs || KzFs.allowWebFs()))">
                    <div x-show="isPlaying && !isEmbed" class="w-full h-full absolute inset-0">
                        <div x-ref="artContainer" id="movieArtPlayer" class="w-full h-full absolute inset-0"></div>
                    </div>

                    <div x-show="isPlaying && isEmbed" class="w-full h-full absolute inset-0 bg-black">
                        <iframe :src="currentEmbedUrl" title="Pemutar {{ $mTitle }}" class="w-full h-full border-0 absolute inset-0" allowfullscreen webkitallowfullscreen mozallowfullscreen allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="no-referrer"></iframe>
                    </div>

                    <div x-show="isLoadingStream" x-cloak class="absolute inset-0 z-30 bg-black/90 backdrop-blur-md flex flex-col items-center justify-center p-6 text-center">
                        @include('filament.streaming.partials.server-loading-indicator', [
                            'title' => 'Memuat stream',
                            'subtitle' => 'Mengambil jalur streaming tercepat',
                        ])
                    </div>

                    {{-- Cover sebelum diputar --}}
                    <div x-show="!isPlaying && !isLoadingStream" x-cloak class="absolute inset-0 z-20">
                        @if($mBanner)
                            <img src="{{ $mBanner }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                        @endif
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent"></div>
                        <button type="button" @click="playVideo()" aria-label="Putar {{ $mTitle }}"
                                class="absolute inset-0 m-auto w-16 h-16 md:w-20 md:h-20 rounded-full bg-white text-black flex items-center justify-center shadow-2xl transition hover:scale-105 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/50 cursor-pointer">
                            <svg class="w-8 h-8 md:w-10 md:h-10 fill-current ml-1" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Judul & metadata (Netflix-style) --}}
                <section class="px-4 lg:px-0 pt-5 md:pt-6">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[13px] text-zinc-300">
                        <span class="inline-flex items-center gap-1.5 font-bold tracking-[0.18em] text-[11px] text-red-500 uppercase">
                            <span class="w-1 h-3.5 bg-red-600 rounded-sm"></span>{{ $isSeries ? 'Series' : 'Film' }}
                        </span>
                        @if($mRating)
                            <span class="text-emerald-400 font-semibold">★ {{ number_format((float) $mRating, 1) }}</span>
                        @endif
                        @if($mYear)<span>{{ $mYear }}</span>@endif
                        @if($isSeries && $epCount)<span>{{ $epCount }} episode</span>@endif
                        @if($mRuntime)<span>{{ $mRuntime }}</span>@endif
                        <span class="px-1.5 py-px text-[10px] font-bold border border-zinc-500 text-zinc-300 rounded-sm">HD</span>
                    </div>

                    <h1 class="mt-2 text-[26px] leading-tight md:text-4xl font-extrabold tracking-tight">{{ $mTitle }}</h1>

                    @if($isSeries)
                        <p class="mt-1 text-sm text-zinc-400">
                            Sedang diputar: <span class="text-white font-semibold" x-text="'Episode ' + currentEp">Episode {{ $currentEpisode }}</span>
                        </p>
                    @endif

                    {{-- Pilihan server: segmented control --}}
                    @if(count($servers) > 0)
                        <div class="mt-5">
                            <p id="server-label" class="text-[11px] font-semibold uppercase tracking-[0.15em] text-zinc-500 mb-2">Kualitas &amp; Server</p>
                            <div role="radiogroup" aria-labelledby="server-label" class="inline-flex flex-wrap gap-1 p-1 rounded-lg bg-zinc-900 ring-1 ring-white/[0.06]">
                                @foreach($servers as $i => $s)
                                    @php $sv = $cleanServer($s, $i); @endphp
                                    <button type="button" role="radio"
                                            :aria-checked="activeServer === '{{ $s['id'] }}' ? 'true' : 'false'"
                                            @click="switchServer('{{ $s['id'] }}', '{{ $s['embed_url'] ?? '' }}', {{ ($s['type'] ?? '') === 'embed' ? 'true' : 'false' }})"
                                            :class="activeServer === '{{ $s['id'] }}' ? 'bg-white text-black shadow' : 'text-zinc-300 hover:text-white hover:bg-white/5'"
                                            class="min-h-[40px] px-4 rounded-md text-sm font-semibold transition flex items-center gap-2 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                                        <span>{{ $sv['label'] }}</span>
                                        <span class="text-[11px] font-bold opacity-60">{{ $sv['quality'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div x-show="isEmbed" x-cloak class="mt-3 text-xs text-zinc-400 flex items-start gap-2">
                        <svg class="w-4 h-4 text-amber-400 shrink-0 mt-px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        <span>Server ini memakai pemutar luar. Gunakan Brave atau uBlock Origin agar bebas iklan.</span>
                    </div>
                </section>

                {{-- Sinopsis --}}
                @if($mOverview)
                    <section class="px-4 lg:px-0 mt-6" x-data="{ open: false }">
                        <p class="text-[15px] leading-relaxed text-zinc-300 max-w-3xl" :class="open ? '' : 'line-clamp-3'">{{ $mOverview }}</p>
                        @if(mb_strlen($mOverview) > 180)
                            <button type="button" @click="open = !open" class="mt-1.5 text-sm font-semibold text-white hover:underline cursor-pointer" x-text="open ? 'Sembunyikan' : 'Selengkapnya'">Selengkapnya</button>
                        @endif
                    </section>
                @endif

                {{-- Daftar episode ala Netflix --}}
                @if($isSeries && $epCount)
                    <section class="px-4 lg:px-0 mt-10" aria-labelledby="ep-heading">
                        <div class="flex items-baseline justify-between border-b border-white/10 pb-3 mb-2">
                            <h2 id="ep-heading" class="text-xl md:text-2xl font-bold tracking-tight">Episode</h2>
                            <span class="text-sm text-zinc-400">{{ $epCount }} episode</span>
                        </div>

                        <ol class="divide-y divide-white/[0.06]">
                            @foreach($episodes as $ep)
                                @php
                                    $epNumber = $ep['episode_number'] ?? $loop->iteration;
                                    $epTitle = $ep['title'] ?? ('Episode ' . $epNumber);
                                    $epThumb = $ep['stillPath'] ?? $mBanner;
                                    $hasCustomTitle = !preg_match('/^episode\s*\d+$/i', trim($epTitle));
                                @endphp
                                <li>
                                    <button type="button" @click="changeEpisode({{ $epNumber }}); window.scrollTo({ top: 0, behavior: 'smooth' })"
                                            :class="currentEp === {{ $epNumber }} ? 'bg-white/[0.06]' : 'hover:bg-white/[0.03]'"
                                            :aria-current="currentEp === {{ $epNumber }} ? 'true' : 'false'"
                                            class="w-full flex items-center gap-4 py-3 md:py-4 px-2 md:px-3 rounded-lg text-left transition cursor-pointer group focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                                        <span class="hidden sm:block w-6 shrink-0 text-center text-xl font-bold tabular-nums" :class="currentEp === {{ $epNumber }} ? 'text-white' : 'text-zinc-500'">{{ $epNumber }}</span>

                                        <span class="relative shrink-0 w-32 md:w-40 aspect-video rounded-md overflow-hidden bg-zinc-800">
                                            @if($epThumb)
                                                <img src="{{ $epThumb }}" alt="" loading="lazy" class="w-full h-full object-cover object-top">
                                            @endif
                                            <span class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition" :class="currentEp === {{ $epNumber }} && '!opacity-100'">
                                                <span class="w-9 h-9 rounded-full border-2 border-white flex items-center justify-center">
                                                    <svg class="w-4 h-4 fill-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                                </span>
                                            </span>
                                            <span x-show="currentEp === {{ $epNumber }}" class="absolute left-0 right-0 bottom-0 h-1 bg-red-600"></span>
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center gap-2">
                                                <span class="text-[15px] font-semibold text-white truncate">
                                                    <span class="sm:hidden">{{ $epNumber }}. </span>{{ $hasCustomTitle ? $epTitle : 'Episode ' . $epNumber }}
                                                </span>
                                                <span x-show="currentEp === {{ $epNumber }}" x-cloak class="shrink-0 text-[10px] font-bold uppercase tracking-wider text-red-500">Diputar</span>
                                            </span>
                                            
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </div>

            {{-- ================= SIDEBAR ================= --}}
            <aside class="px-4 lg:px-0 mt-10 lg:mt-0">
                <div class="lg:sticky lg:top-24 space-y-6">
                    @if($mPoster)
                        <div class="hidden lg:block aspect-[2/3] w-full rounded-xl overflow-hidden ring-1 ring-white/10 shadow-2xl bg-zinc-900">
                            <img src="{{ $mPoster }}" alt="Poster {{ $mTitle }}" class="w-full h-full object-cover">
                        </div>
                    @endif

                    <dl class="text-sm space-y-3">
                        @if(!empty($genres))
                            <div>
                                <dt class="text-zinc-400">Kategori</dt>
                                <dd class="mt-0.5 text-zinc-200">{{ collect($genres)->map(fn ($g) => is_array($g) ? ($g['name'] ?? '') : $g)->filter()->implode(', ') }}</dd>
                            </div>
                        @endif
                        @if($mYear)
                            <div>
                                <dt class="text-zinc-400">Tahun rilis</dt>
                                <dd class="mt-0.5 text-zinc-200">{{ $mYear }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-zinc-400">Tersedia di</dt>
                            <dd class="mt-0.5"><a href="/indonesia/{{ $providerParam }}" class="text-zinc-200 hover:text-white underline-offset-4 hover:underline">{{ $serverLabel }}</a></dd>
                        </div>
                    </dl>

                    <a href="/indonesia/{{ $providerParam }}"
                       class="flex items-center justify-center gap-2 w-full min-h-[44px] rounded-md bg-white/10 hover:bg-white/15 text-sm font-semibold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Jelajahi {{ $serverLabel }}
                    </a>
                </div>
            </aside>

        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/artplayer/5.1.7/artplayer.js"></script>
    <script src="/js/kz-fullscreen.js?v=3"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.18/dist/hls.min.js"></script>
    <script>
        (function () {
            function initWatchIndonesiaComponent() {
                const componentDefinition = () => ({
                    art: null,
                    hls: null,
                    isPlaying: true,
                    isLoadingStream: false,
                    isEmbed: {{ !empty($servers[0]['type']) && $servers[0]['type'] === 'embed' ? 'true' : 'false' }},
                    serversList: @js($servers),
                    activeServer: '{{ $servers[0]["id"] ?? "server1" }}',
                    currentEmbedUrl: {!! json_encode($servers[0]['embed_url'] ?? '') !!},
                    videoSrc: {!! json_encode($media['video_url'] ?? '') !!},
                    subtitles: @js($media['subtitles'] ?? []),
                    episodesList: @js($episodes),
                    currentEp: {{ (int) ($currentEpisode ?? 1) }},
                    savedProgressSeconds: {{ (float) ($savedProgressSeconds ?? 0) }},
                    activeSubtitleLang: 'id',

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

                    changeEpisode(epNum) {
                        if (this.art && this.art.video) {
                            this.art.video.dispatchEvent(new Event('pause'));
                        }
                        this.currentEp = epNum;
                        const ep = Array.isArray(this.episodesList) ? this.episodesList.find(e => (e.episode_number == epNum || e.episodeNumber == epNum)) : null;
                        if (ep) {
                            if (Array.isArray(ep.servers) && ep.servers.length > 0) {
                                this.serversList = ep.servers;
                                this.activeServer = ep.servers[0].id;
                                this.isEmbed = true;
                                this.currentEmbedUrl = ep.servers[0].embed_url || '';
                                if (this.art) {
                                    try { this.art.destroy(false); } catch(e) {}
                                    this.art = null;
                                }
                                this.isPlaying = true;
                            } else if (ep.stream_url) {
                                this.isEmbed = false;
                                this.videoSrc = ep.stream_url;
                                this.subtitles = ep.subtitles || [];
                                if (this.art) {
                                    this.art.switchUrl(this.videoSrc);
                                    this.art.play();
                                } else {
                                    this.isPlaying = true;
                                    this.$nextTick(() => {
                                        this.initArtPlayer();
                                        this.afterInitFs();
                                    });
                                }
                            }
                        }
                    },

                    init() {
                        if (!this.isEmbed && this.videoSrc) {
                            this.$nextTick(() => {
                                this.initArtPlayer();
                            });
                        }
                    },

                    switchServer(id, embedUrl, isEmbedServer) {
                        const target = Array.isArray(this.serversList) ? this.serversList.find(s => s.id === id) : null;
                        if (target) {
                            this.activeServer = target.id;
                            this.isEmbed = target.type === 'embed';
                            this.currentEmbedUrl = target.embed_url || '';
                            if (!this.isEmbed && (target.type === 'mp4' || target.type === 'hls' || target.video_url)) {
                                this.videoSrc = target.video_url || this.videoSrc;
                            }
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
                            if (this.art) {
                                try { this.art.destroy(false); } catch(e) {}
                                this.art = null;
                            }
                            this.$nextTick(() => {
                                this.initArtPlayer();
                                this.afterInitFs();
                            });
                        }
                    },

                    playVideo() {
                        if (this.useNativeFs()) this.enterNativeFs();
                        if (this.isEmbed) {
                            this.isPlaying = true;
                            return;
                        }

                        this.isPlaying = true;
                        this.$nextTick(() => {
                            this.initArtPlayer();
                            this.afterInitFs();
                        });
                    },

                    initArtPlayer() {
                        const container = this.$refs.artContainer || document.getElementById('movieArtPlayer');
                        if (!container || !this.videoSrc) return;

                        if (this.art) {
                            try { this.art.destroy(false); } catch(e) {}
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

                        const progressMeta = {
                            item_id: {!! json_encode($slugParam) !!},
                            item_type: {!! json_encode($isSeries ? 'indonesia_series' : 'indonesia_movie') !!},
                            episode: String(this.currentEp || 1),
                            title: {!! json_encode($mTitle ?? '') !!},
                            thumbnail: {!! json_encode($mPoster ?? '') !!},
                            url: window.location.pathname,
                        };
                        let lastSavedTime = 0;
                        let lastRecordedEpisode = String(this.currentEp || 1);
                        const csrfToken = '{{ csrf_token() }}';
                        const saveProgress = (force = false) => {
                            const video = self.art?.video;
                            if (!video) return;
                            const currentTime = self.art.currentTime || video.currentTime || 0;
                            const duration = self.art.duration || video.duration || 0;
                            if (duration < 10 || currentTime < 5) return;
                            if (!force && Math.abs(currentTime - lastSavedTime) < 8) return;
                            lastSavedTime = currentTime;
                            const payload = {
                                ...progressMeta,
                                episode: String(self.currentEp || 1),
                                progress: currentTime,
                                duration,
                            };
                            const body = JSON.stringify(payload);
                            if (force && navigator.sendBeacon) {
                                const beaconBody = new FormData();
                                Object.entries(payload).forEach(([key, value]) => beaconBody.append(key, String(value)));
                                beaconBody.append('_token', csrfToken);
                                navigator.sendBeacon('/api/watch-progress', beaconBody);
                                return;
                            }
                            fetch('/api/watch-progress', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                                body,
                                keepalive: force,
                            }).catch(() => {});
                        };

                        const isHlsSource = this.videoSrc && (this.videoSrc.includes('.m3u8') || this.videoSrc.includes('/master') || this.videoSrc.includes('moviebox')) && !this.videoSrc.includes('.mp4');

                        const artConfig = {
                            container: container,
                            url: this.videoSrc,
                            type: isHlsSource ? 'm3u8' : 'mp4',
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
                            subtitle: {
                                url: defaultSubUrl,
                                type: 'vtt',
                                escape: false,
                                style: {
                                    color: '#ffffff',
                                    fontSize: '22px',
                                    fontWeight: '600',
                                },
                                encoding: 'utf-8',
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
                                        if (self.art) self.art.seek = Math.max(0, self.art.currentTime - 10);
                                    },
                                },
                                {
                                    position: 'left',
                                    index: 11,
                                    html: '<svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/></svg>',
                                    tooltip: 'Maju 10 Detik',
                                    click: function () {
                                        if (self.art) self.art.seek = Math.min(self.art.duration || 999999, self.art.currentTime + 10);
                                    },
                                },
                            ],
                            settings: [
                                {
                                    html: 'Captions',
                                    name: 'captions',
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M10 9H8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2"/><path d="M18 9h-2a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2"/></svg>',
                                    tooltip: defaultSub ? (defaultSub.label || 'Indonesian') : 'Off',
                                    selector: captionOptions,
                                    onSelect: function (item) {
                                        if (item.url) {
                                            self.art.subtitle.show = true;
                                            self.art.subtitle.switch(item.url, { name: item.html });
                                        } else {
                                            self.art.subtitle.show = false;
                                        }
                                        return item.html;
                                    },
                                },
                                {
                                    html: 'Subtitle size',
                                    name: 'subtitle-size',
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/><path d="M9 8h6"/><path d="M12 8v6"/></svg>',
                                    tooltip: '100%',
                                    selector: [
                                        { html: '75%', size: '16px' },
                                        { default: true, html: '100%', size: '22px' },
                                        { html: '125%', size: '26px' },
                                        { html: '150%', size: '30px' },
                                    ],
                                    onSelect: function (item) {
                                        self.art.subtitle.style('fontSize', item.size);
                                        return item.html;
                                    },
                                },
                                {
                                    html: 'Subtitle position',
                                    name: 'subtitle-position',
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/><line x1="6" y1="13" x2="18" y2="13" stroke-width="2.5"/></svg>',
                                    tooltip: 'Default',
                                    selector: [
                                        { default: true, html: 'Default', bottom: '35px' },
                                        { html: 'Top', bottom: 'auto', top: '40px' },
                                        { html: 'Bottom', bottom: '15px' },
                                    ],
                                    onSelect: function (item) {
                                        if (item.top) {
                                            self.art.subtitle.style('bottom', 'auto');
                                            self.art.subtitle.style('top', item.top);
                                        } else {
                                            self.art.subtitle.style('top', 'auto');
                                            self.art.subtitle.style('bottom', item.bottom);
                                        }
                                        return item.html;
                                    },
                                },
                                {
                                    html: 'Resolution',
                                    name: 'resolution',
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>',
                                    tooltip: 'Auto',
                                    selector: [
                                        { default: true, html: 'Auto', levelIndex: -1 }
                                    ],
                                    onSelect: function (item) {
                                        if (self.hls) self.hls.currentLevel = item.levelIndex;
                                        return item.html;
                                    },
                                },
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
                                        self.art.playbackRate = item.speed;
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
                                        self.art.pip = !self.art.pip;
                                        return self.art.pip ? 'On' : 'Off';
                                    },
                                },
                                {
                                    html: 'Subtitle style',
                                    name: 'subtitle-style',
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 11-7-7-8 8 7 7 8-8z"/><path d="m19 11 2 2-2 2-2-2z"/><line x1="2" y1="21" x2="22" y2="21"/></svg>',
                                    tooltip: savedSubStyle === 'art-subtitle-style-solid' ? 'Solid Black' : (savedSubStyle === 'custom' ? 'Custom' : 'Clean Outline'),
                                    selector: [
                                        { default: savedSubStyle === 'art-subtitle-style-clean', html: 'Clean Outline', cls: 'art-subtitle-style-clean' },
                                        { default: savedSubStyle === 'custom', html: 'Custom', cls: '' },
                                        { default: savedSubStyle === 'art-subtitle-style-solid', html: 'Solid Black', cls: 'art-subtitle-style-solid' },
                                    ],
                                    onSelect: function (item) {
                                        const subEl = self.art.template.$subtitle;
                                        if (subEl) {
                                            subEl.classList.remove('art-subtitle-style-clean', 'art-subtitle-style-solid');
                                            if (item.cls) subEl.classList.add(item.cls);
                                        }
                                        try {
                                            localStorage.setItem('kz_sub_style', item.cls || 'custom');
                                        } catch (e) {}
                                        return item.html;
                                    },
                                },
                            ],
                        };
                        if (isHlsSource) {
                            artConfig.customType = {
                                m3u8: function (video, url, artInstance) {
                                    if (window.Hls && window.Hls.isSupported()) {
                                        if (self.hls) {
                                            try { self.hls.destroy(); } catch(e) {}
                                        }
                                        const hls = new window.Hls({
                                            enableWorker: true,
                                            lowLatencyMode: false,
                                            maxBufferLength: 60,
                                            backBufferLength: 30,
                                            defaultAudioCodec: 'mp4a.40.2',
                                        });
                                        self.hls = hls;
                                        artInstance.hls = hls;

                                        hls.on(window.Hls.Events.MANIFEST_LOADED, function (event, data) {
                                            if (data.levels && data.levels.length > 0) {
                                                data.levels.forEach(function (l) {
                                                    l.videoCodec = l.videoCodec || 'avc1.640028';
                                                    l.audioCodec = l.audioCodec || 'mp4a.40.2';
                                                    if (l.attrs) {
                                                        l.attrs.CODECS = l.attrs.CODECS || 'avc1.640028,mp4a.40.2';
                                                    }
                                                });
                                            }
                                        });

                                        hls.on(window.Hls.Events.BUFFER_CODECS, function (event, data) {
                                            if (data.tracks) {
                                                if (data.tracks.video && !data.tracks.video.codec) {
                                                    data.tracks.video.codec = 'avc1.640028';
                                                }
                                                if (data.tracks.audio && !data.tracks.audio.codec) {
                                                    data.tracks.audio.codec = 'mp4a.40.2';
                                                }
                                            }
                                        });

                                        hls.loadSource(url);
                                        hls.attachMedia(video);

                                        hls.on(window.Hls.Events.MANIFEST_PARSED, function () {
                                            video.play().catch(() => {});
                                            if (artInstance && artInstance.loading) {
                                                artInstance.loading.show = false;
                                            }
                                        });

                                        // Pasang subtitle default (VTT) jika tersedia pada metadata episode
                                        const subList = Array.isArray(self.subtitles) ? self.subtitles : [];
                                        const defaultSub = subList.find(s => s && (s.lang === 'id' || s.lang === 'id-ID' || (s.label || '').toLowerCase().includes('indo'))) || subList[0];
                                        if (defaultSub && defaultSub.url && artInstance.subtitle) {
                                            artInstance.subtitle.switch('/api/streaming/subtitle?url=' + encodeURIComponent(defaultSub.url), { type: 'vtt' });
                                            artInstance.subtitle.show = true;
                                        }

                                        hls.on(window.Hls.Events.ERROR, function (event, data) {
                                            console.error('[HLS_ERROR_DETAIL]', data.details, 'mimeType:', data.mimeType, 'err:', data.error ? data.error.message : null);
                                            if (data.details === 'bufferAddCodecError' || (data.error && /unsupported/i.test(data.error.message || ''))) {
                                                if (artInstance && artInstance.notice) {
                                                    artInstance.notice.show = 'Format HEVC Ultra HD: Disarankan putar di HP (Chrome/Safari) dengan akselerasi hardware.';
                                                }
                                                if (artInstance && artInstance.loading) {
                                                    artInstance.loading.show = false;
                                                }
                                            }
                                        });

                                        video.addEventListener('timeupdate', function () {
                                            if (artInstance && artInstance.loading) {
                                                artInstance.loading.show = false;
                                            }
                                        });

                                        video.addEventListener('playing', function () {
                                            if (artInstance && artInstance.loading) {
                                                artInstance.loading.show = false;
                                            }
                                        });
                                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                        video.src = url;
                                        video.play().catch(() => {});
                                    }
                                }
                            };
                        }

                        this.art = new Artplayer(artConfig);

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

                        // Tampilkan animasi server loader pada initial load stream
                        if (window.kzServerLoader) {
                            window.kzServerLoader.applyToArtplayer(this.art, 'Memilih server terbaik', 'Mengoptimalkan stream film & resolusi HD');
                        }

                        // Subtitle box wrapper observer (IDLIX & Netflix style)
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

                        this.art.on('ready', () => {
                            const v = self.art && self.art.video;
                            const pr = v && v.play ? v.play() : null;
                            if (pr && pr.catch) {
                                pr.catch((err) => {
                                    self.art.muted = true;
                                    Promise.resolve(self.art.play()).catch(() => {});
                                    self.art.notice.show = 'Ketuk ikon suara untuk menyalakan audio';
                                });
                            }
                        });

                        this.art.on('video:playing', () => {
                            if (self.art && self.art.loading) {
                                self.art.loading.show = false;
                            }
                        });

                        this.art.on('video:timeupdate', () => {
                            const currentEpisode = String(self.currentEp || 1);
                            if (currentEpisode !== lastRecordedEpisode) {
                                lastRecordedEpisode = currentEpisode;
                                lastSavedTime = 0;
                            }
                            if (self.art && !self.art.video?.paused) saveProgress(false);
                        });
                        this.art.on('video:pause', () => saveProgress(true));
                        this.art.on('video:ended', () => saveProgress(true));
                        this.art.on('destroy', () => saveProgress(true));
                    }
                });

                if (window.Alpine) {
                    Alpine.data('watchPlayerInit', componentDefinition);
                } else {
                    document.addEventListener('alpine:init', () => {
                        Alpine.data('watchPlayerInit', componentDefinition);
                    });
                }
            }

            initWatchIndonesiaComponent();
        })();
    </script>
@endpush
