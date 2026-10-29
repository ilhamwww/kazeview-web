@php
    use App\Filament\Streaming\Pages\ShowSeries;
    use App\Filament\Streaming\Pages\WatchSeries;

    $tmdb = function ($path, $size = 'w500') {
        if (empty($path)) return null;
        return str_starts_with($path, 'http') ? $path : "https://image.tmdb.org/t/p/{$size}" . $path;
    };
    $epNo = (int) ($activeEpisode['episodeNumber'] ?? $episodeNumber);
    $epUrl = fn ($n, $season = null) => WatchSeries::getUrl(['slug' => $seriesSlug, 'seasonNumber' => $season ?? $seasonNumber, 'episodeNumber' => $n], panel: 'streaming');

    $prevEpisode = null; $nextEpisode = null;
    foreach (array_values($episodes) as $i => $e) {
        if ((int) $e['episodeNumber'] === $epNo) {
            $prevEpisode = $episodes[$i - 1] ?? null;
            $nextEpisode = $episodes[$i + 1] ?? null;
            break;
        }
    }
    $seasons = collect($series['seasons'] ?? [])->filter(fn ($x) => (int) ($x['seasonNumber'] ?? 0) > 0)->sortBy('seasonNumber')->values();
    $poster = $tmdb($activeEpisode['stillPath'] ?? null, 'w780') ?: $tmdb($series['backdropPath'] ?? null, 'w1280');
    $logo = $tmdb($series['logoPath'] ?? null, 'w500');
    $rating = (float) (($activeEpisode['voteAverage'] ?? 0) ?: ($series['voteAverage'] ?? 0));
    $year = substr((string) ($activeEpisode['airDate'] ?? $series['firstAirDate'] ?? ''), 0, 4);
    $genres = array_map(fn($g) => html_entity_decode((string)$g, ENT_QUOTES | ENT_HTML5, 'UTF-8'), array_slice(array_column($series['genres'] ?? [], 'name'), 0, 3));
    $code = 'S' . str_pad($seasonNumber, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($epNo, 2, '0', STR_PAD_LEFT);
    $hasEnSub = !empty($streamData['subtitles']) && count($streamData['subtitles']) > 1;
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 lg:pb-16">
    {{-- Ambient backdrop --}}
    @if($poster)
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[70vh] overflow-hidden" aria-hidden="true">
            <img src="{{ $poster }}" alt="" class="w-full h-full object-cover opacity-20 blur-3xl scale-125">
            <div class="absolute inset-0 bg-gradient-to-b from-[#050505]/30 via-[#050505]/80 to-[#050505]"></div>
        </div>
    @endif

    <div class="relative w-full max-w-[1400px] mx-auto lg:px-8 lg:pt-24" x-data="watchPlayerInit()">

        {{-- Top bar --}}
        <div class="flex items-center gap-3 px-4 lg:px-0 h-14 pt-[env(safe-area-inset-top,0px)] box-content lg:h-12 lg:pt-0 lg:mb-3">
            <a href="{{ ShowSeries::getUrl(['slug' => $seriesSlug], panel: 'streaming') }}" aria-label="Kembali ke serial"
               class="w-10 h-10 shrink-0 inline-flex items-center justify-center rounded-full bg-white/5 ring-1 ring-white/10 active:bg-white/10 hover:bg-white/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold leading-tight truncate">{{ $series['title'] ?? 'TV Series' }}</p>
                <p class="text-[11px] text-zinc-400 leading-tight truncate">Season {{ $seasonNumber }} · Episode {{ $epNo }}</p>
            </div>
            <span class="shrink-0 text-[10px] font-black tracking-wider text-red-300 bg-red-600/15 ring-1 ring-red-500/30 px-2 py-1 rounded-md tabular-nums">{{ $code }}</span>
        </div>

        <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-8">
            {{-- ============ LEFT: player + info ============ --}}
            <div class="min-w-0">
                {{-- Player --}}
                <div class="relative isolate z-10 w-full aspect-video bg-black lg:rounded-2xl overflow-hidden lg:ring-1 lg:ring-white/10 shadow-2xl" x-ref="playerShell" data-kz-fs @kz-fs-failed="art && (art.fullscreenWeb = (!window.KzFs || KzFs.allowWebFs()))">
                    <div x-show="isPlaying && !isEmbed" class="absolute inset-0" style="display:none;">
                        <div x-ref="artContainer" id="seriesArtPlayer" class="w-full h-full"></div>
                    </div>

                    <div x-show="isPlaying && isEmbed" class="absolute inset-0 bg-black" style="display:none;">
                        <iframe :src="currentEmbedUrl" class="w-full h-full border-0" allowfullscreen webkitallowfullscreen mozallowfullscreen allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="no-referrer"></iframe>
                    </div>

                    <div x-show="isLoadingStream" x-cloak class="absolute inset-0 z-30 bg-black/90 backdrop-blur-md flex flex-col items-center justify-center p-6 text-center">
                        @include('filament.streaming.partials.server-loading-indicator', [
                            'title' => 'Memilih server terbaik',
                            'subtitle' => 'Membuka resolusi HD & buffer episode',
                            'showCountdown' => true,
                        ])
                    </div>

                    <button type="button" x-show="!isPlaying && !isLoadingStream" @click="playVideo()"
                            aria-label="Putar episode {{ $epNo }}"
                            class="group absolute inset-0 z-20 w-full h-full text-left">
                        @if($poster)
                            <img src="{{ $poster }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-80 group-hover:scale-[1.02] transition duration-700">
                        @endif
                        <span class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-black/10"></span>
                        <span class="absolute inset-0 flex items-center justify-center">
                            <span class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-red-600 shadow-[0_0_0_8px_rgba(229,9,20,0.18)] flex items-center justify-center group-hover:scale-110 group-active:scale-95 transition">
                                <svg class="w-7 h-7 md:w-9 md:h-9 fill-white ml-1" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        </span>
                        <span class="absolute left-4 right-4 bottom-3 md:bottom-5 flex items-end justify-between gap-3">
                            <span class="min-w-0">
                                <span class="block text-[10px] font-black uppercase tracking-[0.2em] text-red-400">Episode {{ $epNo }}</span>
                                <span class="block text-sm md:text-lg font-black leading-tight line-clamp-1">{{ $activeEpisode['name'] ?? '' }}</span>
                            </span>
                            @if(!empty($activeEpisode['runtime']))
                                <span class="shrink-0 text-[11px] font-bold bg-black/60 backdrop-blur px-2 py-1 rounded-md">{{ $activeEpisode['runtime'] }} mnt</span>
                            @endif
                        </span>
                    </button>

                    <div x-show="streamError" x-cloak class="absolute inset-x-3 bottom-3 z-40 rounded-xl bg-red-950/90 ring-1 ring-red-500/40 px-3 py-2 text-xs text-red-100" x-text="streamError"></div>
                </div>

                {{-- Server Switcher --}}
                @if(!empty($servers) && count($servers) > 1)
                    <div class="px-4 lg:px-0 pt-3 flex items-center gap-2 overflow-x-auto kz-noscroll pb-1">
                        <span class="text-xs font-bold text-zinc-400 mr-1 flex items-center gap-1.5 shrink-0">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.7 7.2a3 3 0 012.4-1.2h7.8a3 3 0 012.4 1.2l2.55 4.35A4.5 4.5 0 0121 14.25"/></svg>
                            Pilihan Server:
                        </span>
                        @foreach($servers as $s)
                            <button type="button"
                                    @click="switchServer('{{ $s['id'] }}', '{{ $s['embed_url'] ?? '' }}', {{ $s['type'] === 'embed' ? 'true' : 'false' }})"
                                    :class="activeServer === '{{ $s['id'] }}' ? 'bg-red-600 text-white ring-red-500 shadow-lg shadow-red-600/30' : 'bg-zinc-900 text-zinc-300 ring-white/10 hover:text-white'"
                                    class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold ring-1 transition flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full" :class="activeServer === '{{ $s['id'] }}' ? 'bg-white' : 'bg-zinc-500'"></span>
                                {{ $s['name'] }}
                            </button>
                        @endforeach
                    </div>
                    <div x-show="isEmbed" class="px-4 lg:px-0 flex items-center gap-2 text-[11px] text-zinc-400 bg-amber-500/10 border border-amber-500/20 rounded-xl px-3.5 py-2 mt-2">
                        <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        <span><strong>Tips Bebas Iklan</strong>: Server global gratisan dapat memicu pop-up/redirect ke e-commerce. Disarankan gunakan browser <strong>Brave</strong> atau pasang <strong>AdGuard / uBlock</strong> di HP agar bebas iklan 100%. Jika terganggu, coba ganti Server 2 atau 3.</span>
                    </div>
                @endif

                {{-- Info --}}
                <div class="px-4 lg:px-0 pt-4 space-y-4">
                    <div>
                        @if($logo)
                            <img src="{{ $logo }}" alt="{{ $series['title'] ?? '' }}" class="h-8 md:h-10 w-auto max-w-[220px] object-contain object-left mb-2 opacity-90">
                        @else
                            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-red-400 mb-1">{{ $series['title'] ?? '' }}</p>
                        @endif
                        <h1 class="text-xl md:text-3xl font-black tracking-tight leading-tight">{{ $activeEpisode['name'] ?? ('Episode ' . $epNo) }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-400">
                            @php
                                $meta = [];
                                $meta[] = '<span class="font-semibold text-zinc-200">' . e($code) . '</span>';
                                if (!empty($activeEpisode['runtime'])) $meta[] = e($activeEpisode['runtime']) . ' mnt';
                                if ($year) $meta[] = e($year);
                                $meta[] = '<span class="font-bold text-emerald-300">HD</span>';
                                foreach ($genres as $g) $meta[] = e($g);
                            @endphp
                            @if($rating > 0)
                                <span class="inline-flex items-center gap-1 font-bold text-amber-300"><svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>{{ number_format($rating, 1, ',', '.') }}</span>
                            @endif
                            @foreach($meta as $i => $m)
                                <span class="whitespace-nowrap">@if($i > 0 || $rating > 0)<span class="mr-2 text-zinc-600" aria-hidden="true">·</span>@endif{!! $m !!}</span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Prev / Next / Share --}}
                    <div class="grid grid-cols-[1fr_1fr_auto] gap-2">
                        @if($prevEpisode)
                            <a href="{{ $epUrl($prevEpisode['episodeNumber']) }}" class="inline-flex items-center justify-center gap-1.5 h-11 rounded-xl bg-zinc-900 ring-1 ring-white/10 text-sm font-bold active:bg-zinc-800 hover:bg-zinc-800 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                                Ep {{ $prevEpisode['episodeNumber'] }}
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center h-11 rounded-xl bg-zinc-950 ring-1 ring-white/5 text-sm font-bold text-zinc-500">Episode pertama</span>
                        @endif
                        @if($nextEpisode)
                            <a href="{{ $epUrl($nextEpisode['episodeNumber']) }}"
                               x-on:mouseenter.once="prefetchNextEpisode()"
                               x-on:touchstart.passive.once="prefetchNextEpisode()"
                               class="inline-flex items-center justify-center gap-1.5 h-11 rounded-xl bg-red-600 text-sm font-bold shadow-lg shadow-red-600/25 active:bg-red-700 hover:bg-red-700 transition">
                                Ep {{ $nextEpisode['episodeNumber'] }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center h-11 rounded-xl bg-zinc-950 ring-1 ring-white/5 text-sm font-bold text-zinc-500">Episode terakhir</span>
                        @endif
                        <button type="button" x-data="{ copied: false }" aria-label="Bagikan episode"
                                @click="if (navigator.share) { navigator.share({ title: document.title, url: location.href }).catch(() => {}) } else { navigator.clipboard.writeText(location.href); copied = true; setTimeout(() => copied = false, 1800) }"
                                class="relative w-11 h-11 inline-flex items-center justify-center rounded-xl bg-zinc-900 ring-1 ring-white/10 active:bg-zinc-800 hover:bg-zinc-800 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/></svg>
                            <span x-show="copied" x-cloak class="absolute -top-8 right-0 whitespace-nowrap text-[10px] font-bold bg-white text-black px-2 py-1 rounded-md">Link disalin</span>
                        </button>
                    </div>

                    {{-- Subtitle --}}
                    <div class="flex items-center gap-2 overflow-x-auto kz-noscroll -mx-4 px-4 lg:mx-0 lg:px-0" role="group" aria-label="Pilih subtitle">
                        <span class="shrink-0 text-[11px] font-semibold text-zinc-500 mr-1">Subtitle</span>
                        <button type="button" @click="setSubtitle('id')" :aria-pressed="activeSubtitleLang === 'id'"
                                :class="activeSubtitleLang === 'id' ? 'bg-white text-black ring-white' : 'bg-zinc-900 text-zinc-300 ring-white/10'"
                                class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold ring-1 transition">Indonesia</button>
                        @if($hasEnSub)
                            <button type="button" @click="setSubtitle('en')" :aria-pressed="activeSubtitleLang === 'en'"
                                    :class="activeSubtitleLang === 'en' ? 'bg-white text-black ring-white' : 'bg-zinc-900 text-zinc-300 ring-white/10'"
                                    class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold ring-1 transition">English</button>
                        @endif
                        <button type="button" @click="setSubtitle('off')" :aria-pressed="activeSubtitleLang === 'off'"
                                :class="activeSubtitleLang === 'off' ? 'bg-white text-black ring-white' : 'bg-zinc-900 text-zinc-300 ring-white/10'"
                                class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold ring-1 transition">Mati</button>
                    </div>

                    @if(!empty($activeEpisode['overview']))
                        <div x-data="{ more: false }" class="rounded-2xl bg-white/[0.03] ring-1 ring-white/[0.06] p-4">
                            <h2 class="text-[11px] font-black uppercase tracking-[0.18em] text-zinc-500">Sinopsis</h2>
                            <p class="mt-1.5 text-sm text-zinc-300 leading-relaxed" :class="more ? '' : 'line-clamp-3'">{{ $activeEpisode['overview'] }}</p>
                            @if(mb_strlen($activeEpisode['overview']) > 160)
                                <button type="button" @click="more = !more" class="mt-1 text-xs font-bold text-red-400" x-text="more ? 'Tutup' : 'Selengkapnya'"></button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============ RIGHT: seasons + episodes ============ --}}
            <aside class="px-4 lg:px-0 pt-6 lg:pt-0 min-w-0" aria-label="Daftar episode">
                <div class="lg:sticky lg:top-24 lg:rounded-2xl lg:bg-zinc-900/50 lg:ring-1 lg:ring-white/[0.06] lg:p-4">
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 class="text-base font-black tracking-tight">Episode</h2>
                        <span class="text-[11px] text-zinc-500">{{ count($episodes) }} episode</span>
                    </div>

                    @if($seasons->count() > 1)
                        <div class="mt-3 flex gap-2 overflow-x-auto kz-noscroll kz-fade-x scroll-px-4 -mx-4 px-4 lg:mx-0 lg:px-0" x-init="$nextTick(() => { const a = $el.querySelector('[data-active]'); if (a) $el.scrollLeft = a.offsetLeft - $el.clientWidth / 2 + a.clientWidth / 2 })">
                            @foreach($seasons as $se)
                                @php $isSe = (int) $se['seasonNumber'] === (int) $seasonNumber; @endphp
                                <a href="{{ $epUrl(1, $se['seasonNumber']) }}" @if($isSe) data-active aria-current="true" @endif
                                   class="shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold ring-1 transition {{ $isSe ? 'bg-red-600 ring-red-500 text-white' : 'bg-zinc-900 ring-white/10 text-zinc-300 hover:text-white' }}">
                                    Season {{ $se['seasonNumber'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <ol class="mt-3 space-y-2 lg:max-h-[calc(100vh-16rem)] lg:overflow-y-auto lg:pr-1 kz-thin lg:kz-fade-y"
                        x-on:mouseover.passive="prewarmFromTarget($event)"
                        x-on:touchstart.passive="prewarmFromTarget($event)"
                        x-init="$nextTick(() => { const a = $el.querySelector('[data-active]'); if (a && window.innerWidth >= 1024) a.scrollIntoView({ block: 'center' }) })">
                        @foreach($episodes as $ep)
                            @php
                                $n = (int) $ep['episodeNumber'];
                                $isActive = $n === $epNo;
                                $still = $tmdb($ep['stillPath'] ?? null, 'w300');
                            @endphp
                            <li>
                                <a href="{{ $epUrl($n) }}" @if($isActive) data-active aria-current="true" @endif
                                   @if(!$isActive && !empty($ep['id'])) data-stream-id="{{ $ep['id'] }}" @endif
                                   class="group flex gap-3 p-2 -mx-2 lg:mx-0 rounded-xl transition {{ $isActive ? 'bg-red-600/10 ring-1 ring-red-500/40' : 'hover:bg-white/[0.04] active:bg-white/[0.06]' }}">
                                    <div class="relative w-32 sm:w-36 aspect-video shrink-0 self-start rounded-lg overflow-hidden bg-zinc-800">
                                        @if($still)
                                            <img src="{{ $still }}" alt="" class="w-full h-full object-cover {{ $isActive ? '' : 'opacity-90 group-hover:opacity-100' }}" loading="lazy">
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
                                        @if(!empty($ep['runtime']))
                                            <span class="absolute bottom-1 right-1 text-[9px] font-bold bg-black/75 px-1 py-0.5 rounded">{{ $ep['runtime'] }} mnt</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1 py-0.5">
                                        <p class="text-[10px] font-black uppercase tracking-wider {{ $isActive ? 'text-red-400' : 'text-zinc-500' }}">{{ $isActive ? 'Sedang diputar' : 'Episode ' . $n }}</p>
                                        <p class="mt-0.5 text-sm font-bold leading-snug line-clamp-2 {{ $isActive ? 'text-white' : 'text-zinc-200' }}">{{ $ep['name'] ?? ('Episode ' . $n) }}</p>
                                        @if(!empty($ep['overview']))
                                            <p class="mt-0.5 text-[11px] text-zinc-500 leading-snug line-clamp-1 lg:line-clamp-2">{{ $ep['overview'] }}</p>
                                        @endif
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
    </style>
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/artplayer/5.1.7/artplayer.js"></script>
    <script src="/js/kz-fullscreen.js?v=3"></script>
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

            function initWatchSeriesComponent() {
                const componentDefinition = () => ({
                    isPlaying: false,
                    isLoadingStream: false,
                    art: null,
                    hls: null,
                    videoSrc: {!! json_encode($streamData['video_url'] ?? '') !!},
                    subtitles: @json($streamData['subtitles'] ?? []),
                    episodeId: '{{ $activeEpisode['id'] ?? '' }}',
                    nextEpisodeId: '{{ $nextEpisode['id'] ?? '' }}',
                    activeSubtitleLang: 'id',
                    serversList: @js($servers),
                    activeServer: '{{ $servers[0]["id"] ?? "server1" }}',
                    isEmbed: {{ !empty($servers[0]["type"]) && $servers[0]["type"] === "embed" ? "true" : "false" }},
                    currentEmbedUrl: {!! json_encode($servers[0]["embed_url"] ?? "") !!},
                    savedProgressSeconds: {{ (float) ($savedProgressSeconds ?? 0) }},
                    useProxy: false,
                    streamError: '',
                    _prefetch: null,
                    _hasPrefetchedNext: false,
                    _stallTimer: null,
                    _proxyRestarted: false,
                    loadingSeconds: 0,
                    _loadingTimer: null,

                    init() {
                        // Ambil stream sejak halaman dibuka supaya saat tombol play ditekan video langsung jalan (khusus HLS)
                        if (!this.isEmbed && !this.videoSrc && this.episodeId) this._prefetch = this.fetchStreamAsync(false);
                    },

                    startLoadingCountdown() {
                        this.loadingSeconds = 18;
                        if (this._loadingTimer) clearInterval(this._loadingTimer);
                        this._loadingTimer = setInterval(() => {
                            if (this.loadingSeconds > 1) {
                                this.loadingSeconds--;
                            }
                        }, 1000);
                    },

                    stopLoadingCountdown() {
                        if (this._loadingTimer) {
                            clearInterval(this._loadingTimer);
                            this._loadingTimer = null;
                        }
                        this.loadingSeconds = 0;
                    },

                    prefetchNextEpisode() {
                        if (!this.nextEpisodeId || this._hasPrefetchedNext) return;
                        this._hasPrefetchedNext = true;
                        fetch('/api/streaming/stream?type=episode&id=' + encodeURIComponent(this.nextEpisodeId), { priority: 'low' }).catch(() => {});
                    },

                    prewarmFromTarget(e) {
                        const el = e.target.closest('[data-stream-id]');
                        if (!el || !el.dataset.streamId) return;
                        const id = el.dataset.streamId;
                        el.removeAttribute('data-stream-id');
                        fetch('/api/streaming/stream?type=episode&id=' + encodeURIComponent(id), { priority: 'low' }).catch(() => {});
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
                            const q = new URLSearchParams({ kind, detail: String(detail).slice(0, 300), type: 'episode', id: this.episodeId, proxy: this.useProxy ? '1' : '0' });
                            fetch('/api/player-log?' + q.toString(), { keepalive: true }).catch(() => {});
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
                        if (!this.episodeId) return;
                        if (autoPlayAfter) {
                            this.isLoadingStream = true;
                            this.startLoadingCountdown();
                        }
                        try {
                            const ctrl = new AbortController();
                            const timer = setTimeout(() => ctrl.abort(), 60000);
                            const res = await fetch('/api/streaming/stream?type=episode&id=' + encodeURIComponent(this.episodeId) + (force ? '&force=1' : ''), { signal: ctrl.signal });
                            clearTimeout(timer);
                            if (res.ok) {
                                const data = await res.json();
                                if (data && data.video_url) {
                                    this.videoSrc = data.video_url;
                                    this.subtitles = data.subtitles || [];
                                }
                            } else {
                                this.report('stream-api', res.status);
                            }
                        } catch (e) {
                            this.report('stream-api-exc', e && e.name);
                        } finally {
                            if (autoPlayAfter) {
                                this.stopLoadingCountdown();
                                this.isLoadingStream = false;
                            }
                        }
                        if (autoPlayAfter && this.videoSrc) {
                            this.isPlaying = true;
                            await this.$nextTick();
                            this.initPlayer();
                            this.afterInitFs();
                        }
                    },

                    async playVideo() {
                        this.streamError = '';
                        // Harus dipanggil sebelum await apa pun, selagi masih di dalam gesture tap
                        if (this.useNativeFs()) this.enterNativeFs();

                        // Jika mode embed (Server mirror / Rebahin)
                        if (this.isEmbed) {
                            this.isPlaying = true;
                            return;
                        }

                        if (!this.videoSrc) {
                            this.isLoadingStream = true;
                            this.startLoadingCountdown();
                            if (this._prefetch) await this._prefetch;
                            if (!this.videoSrc) await this.fetchStreamAsync(false);
                            this.stopLoadingCountdown();
                            this.isLoadingStream = false;
                            if (!this.videoSrc) {
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
                    },

                    initPlayer() {
                        const artContainer = this.$refs.artContainer || document.getElementById('seriesArtPlayer');
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

                        this.art = new Artplayer({
                            container: artContainer,
                            url: this.videoSrc,
                            type: 'm3u8',
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
                            customType: {
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
                                            startFragPrefetch: true,
                                            maxBufferLength: 30,
                                            maxMaxBufferLength: 60,
                                            backBufferLength: 15,
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
                            }
                        });

                        // Stamp data-name on setting items for custom styling
                        const stampSettingNames = () => {
                            const items = document.querySelectorAll('.art-setting-item');
                            const names = ['captions', 'subtitle-size', 'subtitle-position', 'resolution', 'playback-speed', 'pip', 'subtitle-style'];
                            items.forEach((item, idx) => {
                                if (names[idx]) item.setAttribute('data-name', names[idx]);
                            });
                        };
                        this.art.on('setting', stampSettingNames);

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
                            window.kzServerLoader.applyToArtplayer(this.art, 'Memilih server terbaik', 'Mengoptimalkan stream episode & buffer');
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

                        // ============ SAVE PLAYBACK PROGRESS (SERIES) ============
                        const progressMeta = {
                            item_id: '{{ $seriesSlug ?? "" }}',
                            item_type: 'series',
                            episode: '{{ $episodeNumber ?? "1" }}',
                            title: {!! json_encode($series['title'] ?? '') !!},
                            thumbnail: {!! json_encode($series['poster'] ?? $series['thumbnail'] ?? '') !!},
                            url: window.location.pathname,
                        };
                        let _lastSavedTime = 0;
                        const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                        const saveProgress = (force = false) => {
                            if (!self.art || !self.art.video) return;
                            const ct = self.art.currentTime || 0;
                            const dur = self.art.duration || 0;
                            if (ct >= 20) self.prefetchNextEpisode();
                            if (dur < 10 || ct < 5) return;
                            if (!force && Math.abs(ct - _lastSavedTime) < 8) return;
                            _lastSavedTime = ct;
                            const body = JSON.stringify({ ...progressMeta, progress: ct, duration: dur });
                            if (navigator.sendBeacon && force) {
                                navigator.sendBeacon('/api/watch-progress', new Blob([body], { type: 'application/json' }));
                            } else {
                                fetch('/api/watch-progress', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _csrfToken },
                                    body, keepalive: true,
                                }).catch(() => {});
                            }
                        };
                        const _progressInterval = setInterval(() => {
                            if (self.art && !self.art.video?.paused) saveProgress();
                        }, 10000);
                        this.art.on('video:pause', () => saveProgress(true));
                        this.art.on('video:ended', () => saveProgress(true));
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
            initWatchSeriesComponent();
        })();
    </script>
@endpush
