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
            transition: background-image 0.25s ease, opacity 0.25s ease !important;
        }

        /* Anti-spoiler: Sembunyikan mini progress bar di bagian bawah saat kontrol idle/tersembunyi */
        .art-video-player .art-mini-progress-bar,
        .art-video-player:not(.art-control-show):not(.art-hover) .art-bottom {
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* 2. Saat kontrol aktif/hover, berikan gradien halus full-width di art-bottom dan aktifkan interaksi (seeking, scrubbing) */
        .art-video-player.art-control-show .art-bottom,
        .art-video-player.art-hover .art-bottom {
            opacity: 1 !important;
            pointer-events: auto !important;
            background-image: linear-gradient(to top, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.45) 50px, transparent 85px) !important;
            background-size: 100% 85px !important;
            background-repeat: no-repeat !important;
            background-position: bottom !important;
        }

        /* Elemen kontrol interaktif dan progress bar menangkap pointer events */
        .art-video-player .art-bottom .art-controls,
        .art-video-player .art-bottom .art-progress {
            pointer-events: auto !important;
        }

        /* Sinkronisasi tombol Layar penuh (top-right overlay) dengan visibilitas kontrol */
        .kz-fs-btn {
            transition: opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        [data-kz-fs]:not(.kz-controls-visible) .kz-fs-btn {
            opacity: 0 !important;
            pointer-events: none !important;
            transform: translateY(-8px) !important;
        }
        [data-kz-fs].kz-controls-visible .kz-fs-btn {
            opacity: 1 !important;
            pointer-events: auto !important;
            transform: translateY(0) !important;
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
    $m = $media;
    $servers = $stream['servers'] ?? [];
@endphp

<div class="min-h-screen bg-[#0b0b0f] pb-24 text-zinc-100">
    @include('filament.streaming.partials.animek-subnav', ['active' => ''])

    <div class="relative">
        <div class="absolute inset-x-0 top-0 h-[380px] overflow-hidden" aria-hidden="true">
            <img src="{{ $m['poster'] }}" alt="" class="h-full w-full scale-110 object-cover opacity-20 blur-2xl">
            <div class="absolute inset-0 bg-gradient-to-b from-[#0b0b0f]/40 via-[#0b0b0f]/80 to-[#0b0b0f]"></div>
        </div>

        <div class="relative mx-auto max-w-[1500px] px-4 md:px-8">
            <a href="{{ $m['url'] }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-[12.5px] font-semibold text-zinc-200 ring-1 ring-white/10 transition hover:bg-white/20">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg> Detail anime
            </a>

            <div class="mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="min-w-0">
                    <div class="overflow-hidden rounded-xl border border-white/10 bg-black shadow-2xl shadow-black/60">
                        <div wire:ignore id="kz-animek-player" data-kz-fs class="kz-controls-visible relative isolate z-10 w-full aspect-video bg-black lg:rounded-2xl overflow-hidden lg:ring-1 lg:ring-white/10 shadow-2xl"></div>
                        <div wire:ignore id="kz-animek-embed" class="hidden w-full aspect-video overflow-hidden bg-black lg:rounded-2xl"></div>
                    </div>

                    @if(empty($stream))
                        <div class="mt-3 rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                            Sumber video episode ini sedang tidak tersedia. Coba episode lain.
                        </div>
                    @endif

                    {{-- Prev / Next + server --}}
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex gap-2">
                            @if($prev)
                                <a href="{{ $prev['url'] }}" class="rounded-lg bg-white/[0.07] px-3.5 py-2 text-[13px] font-semibold text-zinc-200 transition hover:bg-white/15">‹ Ep {{ $prev['number'] }}</a>
                            @endif
                            @if($next)
                                <a href="{{ $next['url'] }}" class="rounded-lg bg-red-600 px-3.5 py-2 text-[13px] font-bold text-white transition hover:bg-red-500">Ep {{ $next['number'] }} ›</a>
                            @endif
                        </div>
                        @if(count($servers) > 1)
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Server</span>
                                @foreach($servers as $i => $srv)
                                    <button type="button" data-src="{{ $srv['url'] }}" data-kind="{{ $srv['kind'] ?? 'video' }}"
                                            class="kz-server rounded-md border px-2.5 py-1 text-[11.5px] font-semibold transition {{ $i === 0 ? 'border-red-500/50 bg-red-600/15 text-white' : 'border-white/10 bg-white/5 text-zinc-300 hover:bg-white/10' }}">{{ $srv['name'] }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mt-6">
                        <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                            <a href="{{ $m['url'] }}" class="hover:text-red-300">{{ $m['title'] }}</a>
                        </h1>
                        @if($current)
                            <p class="mt-1 text-sm font-semibold text-red-400">Episode {{ $current['number'] }}@if($current['date']) · <span class="font-normal text-zinc-400">{{ \Illuminate\Support\Carbon::parse($current['date'])->translatedFormat('j F Y') }}</span>@endif</p>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-zinc-400">
                            @if($m['rating'])<span class="font-semibold text-yellow-400">★ {{ number_format($m['rating'], 1) }}</span>@endif
                            @if($m['year'])<span>{{ $m['year'] }}</span>@endif
                            @if($m['kind'])<span>{{ $m['kind'] }}</span>@endif
                            @if($m['status_label'])<span>{{ $m['status_label'] }}</span>@endif
                            @foreach($m['genre_links'] as $g)
                                <a href="/animek/genre?g={{ $g['id'] }}" class="rounded-full bg-white/[0.06] px-2 py-0.5 font-semibold text-zinc-300 hover:bg-white/15 hover:text-white">{{ $g['name'] }}</a>
                            @endforeach
                        </div>

                        @if($m['synopsis'])
                            <div class="mt-5 max-w-3xl" x-data="{ more: false }">
                                <h2 class="text-[11px] font-black uppercase tracking-[0.18em] text-zinc-500">Sinopsis</h2>
                                <p :class="more ? '' : 'line-clamp-3'" class="mt-2 text-sm leading-relaxed text-zinc-300">{{ $m['synopsis'] }}</p>
                                @if(mb_strlen($m['synopsis']) > 240)
                                    <button type="button" @click="more = !more" class="mt-1 text-xs font-bold text-red-400" x-text="more ? 'Tutup' : 'Baca selengkapnya'"></button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <aside>
                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4" x-data="{ q: '' }">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-zinc-300">Episode <span class="text-zinc-500">({{ count($episodes) }})</span></h2>
                            @if(count($episodes) > 12)
                                <input x-model="q" type="search" inputmode="numeric" placeholder="No. ep" aria-label="Cari nomor episode"
                                       class="w-24 rounded-full border border-white/10 bg-black/30 px-3 py-1 text-xs text-white outline-none focus:border-red-500/60">
                            @endif
                        </div>
                        <div id="kz-ep-list" class="grid max-h-[520px] grid-cols-4 gap-1.5 overflow-y-auto pr-1">
                            @foreach($episodes as $ep)
                                <a href="{{ $ep['url'] }}" x-show="q === '' || '{{ $ep['number'] }}'.includes(q.trim())"
                                   title="{{ $ep['title'] }}" @if($ep['id'] === $currentId) data-current="1" aria-current="true" @endif
                                   class="rounded-lg py-2 text-center text-sm font-bold transition {{ $ep['id'] === $currentId ? 'bg-red-600 text-white' : 'bg-white/[0.05] text-zinc-300 hover:bg-white/15 hover:text-white' }}">{{ $ep['number'] }}</a>
                            @endforeach
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/artplayer/5.1.7/artplayer.js"></script>
<script src="/js/kz-fullscreen.js?v=3"></script>
<script>
function initKzAnimek() {
    const videoUrl = @json($stream['video_url'] ?? '');
    const embedUrl = @json($stream['embed_url'] ?? '');
    const poster = @json($m['poster']);
    const nextUrl = @json($next['url'] ?? '');
    const shell = document.getElementById('kz-animek-player');

    const cur = document.querySelector('#kz-ep-list [data-current]');
    if (cur) cur.scrollIntoView({ block: 'nearest' });

    const embedBox = document.getElementById('kz-animek-embed');

    if (!shell || shell.dataset.ready === '1') return;
    shell.dataset.ready = '1';

    // Sebagian judul (film) hanya punya server embed: tampilkan iframe pemutar.
    const mountEmbed = (url) => {
        if (!embedBox) return;
        shell.classList.add('hidden');
        embedBox.classList.remove('hidden');
        embedBox.innerHTML = '<iframe src="' + url + '" class="h-full w-full" frameborder="0" scrolling="no" allow="autoplay; fullscreen; encrypted-media; picture-in-picture" allowfullscreen sandbox="allow-scripts allow-same-origin allow-presentation"></iframe>';
    };

    const showPlayer = () => {
        if (embedBox) {
            embedBox.classList.add('hidden');
            embedBox.innerHTML = '';
        }
        shell.classList.remove('hidden');
    };

    if (!videoUrl) {
        if (embedUrl) mountEmbed(embedUrl);
        return;
    }

    const useNativeFs = () => !!(window.KzFs && window.KzFs.isTouch());
    let art = null;

    art = new Artplayer({
        container: shell,
        url: videoUrl,
        type: videoUrl.includes('.m3u8') ? 'm3u8' : 'mp4',
        theme: '#E50914',
        volume: 0.8,
        muted: false,
        autoplay: true,
        pip: true,
        setting: true,
        loop: false,
        flip: false,
        playbackRate: false,
        aspectRatio: false,
        fullscreen: !useNativeFs(),
        fullscreenWeb: !useNativeFs(),
        subtitleOffset: false,
        miniProgressBar: false,
        playsInline: true,
        airplay: true,
        hotkey: true,
        controls: [
            {
                name: 'kz-fs',
                position: 'right',
                index: 70,
                html: '<svg style="width:22px;height:22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="14" height="10" rx="2"/><path d="M20 4v5h-5"/><path d="M20 9a8 8 0 0 0-8-6"/></svg>',
                tooltip: 'Layar penuh',
                style: useNativeFs() ? {} : { display: 'none' },
                click: function () {
                    if (!window.KzFs) return;
                    if (window.KzFs.isActive(shell)) window.KzFs.exit();
                    else window.KzFs.enter(shell, { video: art.video });
                },
            },
            {
                position: 'left',
                index: 10,
                html: '<svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"/></svg>',
                tooltip: 'Mundur 10 Detik',
                click: function () { if (art) art.seek = Math.max(0, art.currentTime - 10); },
            },
            {
                position: 'left',
                index: 11,
                html: '<svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z"/></svg>',
                tooltip: 'Maju 10 Detik',
                click: function () { if (art) art.seek = Math.min(art.duration || 999999, art.currentTime + 10); },
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
                onSelect: function (item) { art.playbackRate = item.speed; return item.html; },
            },
            {
                html: 'Picture-in-Picture',
                name: 'pip',
                icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><rect x="11" y="9" width="9" height="6" rx="1" stroke="currentColor" fill="none"/></svg>',
                tooltip: 'Off',
                selector: [{ html: 'On' }, { default: true, html: 'Off' }],
                onSelect: function (item) { art.pip = !art.pip; return art.pip ? 'On' : 'Off'; },
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
                onSelect: function (item) { art.flip = item.flip; return item.html; },
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
                onSelect: function (item) { art.aspectRatio = item.ratio; return item.html; },
            },
        ],
    });

    // ---- Tampilkan / sembunyikan kontrol (gaya player anime lama) ----
    let hideTimer = null;
    const showControls = () => {
        shell.classList.add('kz-controls-visible');
        if (art && art.controls) art.controls.show = true;
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => {
            if (art && art.playing && !(art.setting && art.setting.show)) hideControls();
        }, 3500);
    };
    const hideControls = () => {
        shell.classList.remove('kz-controls-visible');
        if (art && art.controls) art.controls.show = false;
        clearTimeout(hideTimer);
    };

    art.on('video:play', showControls);
    art.on('video:pause', showControls);
    shell.addEventListener('mousemove', showControls, { passive: true });
    shell.addEventListener('touchstart', showControls, { passive: true });

    // ---- Fullscreen otomatis (mengikuti player anime lama) ----
    // HP: native fullscreen + kunci landscape saat video mulai diputar.
    // Desktop: web fullscreen (theater) saat video mulai diputar.
    const enterAutoFs = () => {
        if (shell.dataset.autofsDone === '1') return;

        if (useNativeFs()) {
            if (!window.KzFs || window.KzFs.isActive(shell)) return;
            shell.dataset.autofsDone = '1';
            window.KzFs.enter(shell, { video: art.video });
            setTimeout(() => {
                if (window.KzFs && !window.KzFs.isActive(shell)) shell.dataset.autofsDone = '';
            }, 900);
            return;
        }

        shell.dataset.autofsDone = '1';
        try { if (!art.fullscreenWeb) art.fullscreenWeb = true; } catch (e) {}
    };

    art.on('play', enterAutoFs);
    art.on('video:play', enterAutoFs);
    if (art.video) art.video.addEventListener('play', enterAutoFs);

    // Catatan: jangan pasang fullscreen pada klik/tap sembarang di pemutar.
    // Itu membuat tombol pause/seek ikut memicu layar penuh.

    // Fullscreen native ditolak: hanya desktop yang jatuh ke web fullscreen.
    shell.addEventListener('kz-fs-failed', () => {
        if (useNativeFs()) return;
        try { art.fullscreenWeb = true; } catch (e) {}
    });

    art.on('fullscreenWeb', (state) => {
        document.body.classList.toggle('kz-fullscreen-web-active', state);
        document.documentElement.classList.toggle('kz-fullscreen-web-active', state);
    });

    // Lanjut otomatis ke episode berikutnya saat selesai.
    if (nextUrl) art.on('video:ended', () => { window.location.href = nextUrl; });

    document.querySelectorAll('.kz-server').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.kz-server').forEach((b) => {
                b.classList.remove('border-red-500/50', 'bg-red-600/15', 'text-white');
                b.classList.add('border-white/10', 'bg-white/5', 'text-zinc-300');
            });
            btn.classList.remove('border-white/10', 'bg-white/5', 'text-zinc-300');
            btn.classList.add('border-red-500/50', 'bg-red-600/15', 'text-white');
            if (btn.dataset.kind === 'embed') {
                mountEmbed(btn.dataset.src);
                return;
            }

            showPlayer();
            try { art.switchUrl(btn.dataset.src); } catch (e) { art.url = btn.dataset.src; }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setTimeout(initKzAnimek, 150));
} else {
    setTimeout(initKzAnimek, 150);
}
</script>
