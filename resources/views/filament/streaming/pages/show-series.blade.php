@php
    use App\Filament\Streaming\Pages\WatchSeries;

    $tmdbImg = function ($path, $size = 'w500') {
        if (empty($path)) return null;
        if (str_starts_with($path, 'http')) return $path;
        return "https://image.tmdb.org/t/p/{$size}" . $path;
    };

    $backdrop = $tmdbImg($series['backdropPath'] ?? null, 'w1280') ?: $tmdbImg($series['posterPath'] ?? null, 'w780');
    $poster = $tmdbImg($series['posterPath'] ?? null, 'w500');
    $logo = $tmdbImg($series['logoPath'] ?? null, 'w500');
    $rating = (float) ($series['voteAverage'] ?? 0);
    $year = !empty($series['firstAirDate']) ? \Carbon\Carbon::parse($series['firstAirDate'])->format('Y') : null;
    $genreNames = array_column($series['genres'] ?? [], 'name');
    $seasons = $series['seasons'] ?? [];
    $episodes = $currentSeasonData['episodes'] ?? [];
    $cast = array_slice($series['cast'] ?? [], 0, 14);
    $networks = $series['networks'] ?? [];
    $status = $series['status'] ?? null;
    $statusLabel = ['Ended' => 'Tamat', 'Returning Series' => 'Berlanjut', 'Canceled' => 'Dibatalkan', 'In Production' => 'Produksi'][$status] ?? $status;

    $firstEpisode = $episodes[0] ?? null;
    $watchUrl = $firstEpisode
        ? WatchSeries::getUrl(['slug' => $seriesSlug, 'seasonNumber' => $selectedSeasonNumber ?? 1, 'episodeNumber' => $firstEpisode['episodeNumber'] ?? 1], panel: 'streaming')
        : null;
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ================= HERO ================= --}}
    <section class="relative w-full min-h-[640px] md:min-h-[88vh] overflow-hidden bg-black flex flex-col justify-end">
        @if($backdrop)
            <img src="{{ $backdrop }}" alt="" class="absolute inset-0 w-full h-full object-cover object-top">
        @endif
        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/75 to-transparent"></div>
        <div class="absolute inset-x-0 bottom-0 h-[65%] bg-gradient-to-t from-[#050505] via-[#050505]/80 to-transparent"></div>
        <div class="absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-[#050505]/90 to-transparent"></div>

        <div class="relative w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-28 pb-12 md:pb-16 z-10">
            <div class="flex flex-col md:flex-row md:items-end gap-8 md:gap-12">
                @if($poster)
                    <div class="hidden md:block shrink-0 w-56 lg:w-64 aspect-[2/3] rounded-2xl overflow-hidden shadow-2xl shadow-black ring-1 ring-white/10">
                        <img src="{{ $poster }}" alt="{{ $series['title'] ?? '' }}" class="w-full h-full object-cover">
                    </div>
                @endif

                <div class="flex-1 min-w-0 max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="bg-red-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-[0.15em]">TV Series</span>
                        @if($statusLabel)
                            <span class="text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider border {{ $status === 'Returning Series' ? 'text-emerald-400 border-emerald-500/40 bg-emerald-500/10' : 'text-zinc-300 border-white/15 bg-white/5' }}">{{ $statusLabel }}</span>
                        @endif
                    </div>

                    @if($logo)
                        <img src="{{ $logo }}" alt="{{ $series['title'] ?? '' }}" class="max-h-28 md:max-h-36 max-w-[85%] object-contain object-left drop-shadow-2xl"
                             onerror="this.style.display='none';this.nextElementSibling.classList.remove('hidden')">
                        <h1 class="hidden text-4xl md:text-6xl font-black tracking-tight leading-[0.95]">{{ $series['title'] ?? '' }}</h1>
                    @else
                        <h1 class="text-4xl md:text-6xl lg:text-7xl font-black tracking-tight leading-[0.95] drop-shadow-2xl">{{ $series['title'] ?? '' }}</h1>
                    @endif

                    @if(!empty($series['originalTitle']) && $series['originalTitle'] !== ($series['title'] ?? ''))
                        <p class="mt-3 text-sm text-zinc-400">{{ $series['originalTitle'] }}</p>
                    @endif

                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mt-5 text-sm font-semibold text-zinc-300">
                        @if($rating > 0)
                            <span class="inline-flex items-center gap-1 text-yellow-400">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                {{ number_format($rating, 1) }}
                            </span>
                        @endif
                        @if($year)<span class="text-zinc-600">•</span><span>{{ $year }}</span>@endif
                        @if(!empty($series['numberOfSeasons']))<span class="text-zinc-600">•</span><span>{{ $series['numberOfSeasons'] }} Season</span>@endif
                        @if(!empty($series['country']))<span class="text-zinc-600">•</span><span>{{ $series['country'] }}</span>@endif
                        <span class="px-1.5 py-0.5 text-[10px] font-black border border-zinc-500 rounded text-zinc-200">HD</span>
                    </div>

                    @if(count($genreNames))
                        <div class="flex flex-wrap gap-2 mt-4">
                            @foreach($genreNames as $g)
                                <span class="px-3 py-1 text-xs font-semibold text-zinc-200 bg-white/5 border border-white/10 rounded-full">{{ $g }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($series['overview']))
                        <p x-data="{ more: false }" @click="more = !more"
                           :class="more ? '' : 'line-clamp-3'"
                           class="mt-5 text-sm md:text-base text-zinc-300/90 leading-relaxed cursor-pointer max-w-2xl">{{ $series['overview'] }}</p>
                    @endif

                    <div class="flex flex-wrap items-center gap-3 mt-7">
                        @if($watchUrl)
                            <a href="{{ $watchUrl }}" class="inline-flex items-center gap-2 pl-5 pr-6 py-3 bg-white text-black hover:bg-zinc-200 active:scale-95 rounded-lg font-bold text-sm transition">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                Putar S{{ $selectedSeasonNumber }} E{{ $firstEpisode['episodeNumber'] ?? 1 }}
                            </a>
                        @endif
                        <button wire:click="toggleFavorite" type="button"
                                class="inline-flex items-center gap-2 px-5 py-3 rounded-lg font-bold text-sm transition border {{ $isFavorited ? 'bg-red-600/15 border-red-600/50 text-red-400' : 'bg-zinc-500/30 hover:bg-zinc-500/50 backdrop-blur-md border-white/10 text-white' }}">
                            <svg class="w-5 h-5 {{ $isFavorited ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            {{ $isFavorited ? 'Tersimpan' : 'Favorit' }}
                        </button>
                        <button type="button" x-data="{ ok: false }"
                                @click="navigator.clipboard.writeText(window.location.href); ok = true; setTimeout(() => ok = false, 1800)"
                                class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-zinc-500/30 hover:bg-zinc-500/50 backdrop-blur-md border border-white/10 transition" aria-label="Salin link">
                            <svg x-show="!ok" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/></svg>
                            <svg x-show="ok" x-cloak class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= EPISODES ================= --}}
    <section class="w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-6">
        <div class="flex flex-col gap-4 mb-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl md:text-2xl font-black tracking-tight">Episode</h2>
                    <p class="text-xs md:text-sm text-zinc-500 mt-1">
                        {{ $currentSeasonData['name'] ?? ('Season ' . $selectedSeasonNumber) }}
                        @if(count($episodes)) · {{ count($episodes) }} episode @endif
                    </p>
                </div>
            </div>

            @if(count($seasons) > 1)
                <div class="flex gap-2 overflow-x-auto pb-1 kz-noscroll" role="tablist" aria-label="Pilih season"
                     x-data x-init="$nextTick(() => { const a = $el.querySelector('[aria-selected=true]'); if (a) $el.scrollLeft = a.offsetLeft - 24 })">
                    @foreach($seasons as $s)
                        @php $active = (int) $selectedSeasonNumber === (int) $s['seasonNumber']; @endphp
                        <button type="button" role="tab" aria-selected="{{ $active ? 'true' : 'false' }}"
                                wire:click="changeSeason({{ (int) $s['seasonNumber'] }})"
                                class="shrink-0 px-4 py-2 rounded-full text-sm font-bold transition border {{ $active ? 'bg-white text-black border-white' : 'bg-zinc-900 text-zinc-300 border-zinc-800 hover:border-zinc-600 hover:text-white' }}">
                            Season {{ $s['seasonNumber'] }}
                            @if(!empty($s['episodeCount']))<span class="ml-1 text-[11px] font-semibold {{ $active ? 'text-zinc-600' : 'text-zinc-500' }}">{{ $s['episodeCount'] }}</span>@endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div wire:loading.flex wire:target="changeSeason" class="items-center justify-center py-20 gap-3 text-zinc-400">
            <svg class="animate-spin h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-sm font-semibold">Memuat episode...</span>
        </div>

        <div wire:loading.remove wire:target="changeSeason">
            @if(empty($episodes))
                <div class="py-16 text-center text-zinc-500 bg-zinc-900/40 rounded-2xl border border-dashed border-zinc-800">Belum ada episode untuk season ini.</div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4 md:gap-5"
                     x-data="{ prewarm(e) { const el = e.target.closest('[data-stream-id]'); if (!el || !el.dataset.streamId) return; const id = el.dataset.streamId; el.removeAttribute('data-stream-id'); fetch('/api/streaming/stream?type=episode&id=' + encodeURIComponent(id), { priority: 'low' }).catch(()=>{}); } }"
                     x-on:mouseover.passive="prewarm($event)"
                     x-on:touchstart.passive="prewarm($event)">
                    @foreach($episodes as $ep)
                        @php
                            $epUrl = WatchSeries::getUrl(['slug' => $seriesSlug, 'seasonNumber' => $selectedSeasonNumber ?? 1, 'episodeNumber' => $ep['episodeNumber']], panel: 'streaming');
                            $still = $tmdbImg($ep['stillPath'] ?? null, 'w500') ?: $backdrop;
                            $epRating = (float) ($ep['voteAverage'] ?? 0);
                            $epName = $ep['name'] ?? ('Episode ' . $ep['episodeNumber']);
                        @endphp
                        <a href="{{ $epUrl }}" wire:key="ep-{{ $selectedSeasonNumber }}-{{ $ep['episodeNumber'] }}"
                           @if(!empty($ep['id'])) data-stream-id="{{ $ep['id'] }}" @endif
                           class="group flex flex-col bg-zinc-900/50 hover:bg-zinc-900 rounded-xl overflow-hidden ring-1 ring-white/5 hover:ring-red-600/50 transition">
                            <div class="relative aspect-video bg-zinc-950 overflow-hidden">
                                @if($still)
                                    <img src="{{ $still }}" alt="{{ $epName }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500 {{ empty($ep['stillPath']) ? 'opacity-40 blur-[2px]' : '' }}" loading="lazy">
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                                <span class="absolute top-2 left-2 text-[11px] font-black px-2 py-0.5 rounded bg-black/70 backdrop-blur border border-white/10">
                                    S{{ str_pad($selectedSeasonNumber, 2, '0', STR_PAD_LEFT) }} · E{{ str_pad($ep['episodeNumber'], 2, '0', STR_PAD_LEFT) }}
                                </span>
                                @if(!empty($ep['runtime']))
                                    <span class="absolute bottom-2 right-2 text-[11px] font-bold px-2 py-0.5 rounded bg-black/70 backdrop-blur">{{ $ep['runtime'] }}m</span>
                                @endif
                                <span class="absolute inset-0 m-auto w-12 h-12 rounded-full bg-white/90 text-black flex items-center justify-center scale-75 opacity-0 group-hover:opacity-100 group-hover:scale-100 transition duration-300">
                                    <svg class="w-5 h-5 fill-current ml-0.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                </span>
                            </div>
                            <div class="p-3.5 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-bold text-zinc-100 group-hover:text-red-500 transition line-clamp-1">
                                        <span class="text-zinc-500 mr-1">{{ $ep['episodeNumber'] }}.</span>{{ $epName }}
                                    </h3>
                                    @if($epRating > 0)
                                        <span class="shrink-0 text-[11px] font-bold text-yellow-400">★ {{ number_format($epRating, 1) }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-zinc-500 mt-1.5 leading-relaxed line-clamp-2">
                                    @if(!empty($ep['overview'])){{ $ep['overview'] }}@elseif(!empty($ep['airDate']))Tayang {{ \Carbon\Carbon::parse($ep['airDate'])->locale('id')->translatedFormat('j F Y') }}@endif
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ================= CAST ================= --}}
    @if(count($cast))
        <section class="w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-14">
            <h2 class="text-xl md:text-2xl font-black tracking-tight mb-5">Pemeran</h2>
            <div class="flex gap-4 md:gap-5 overflow-x-auto pb-2 kz-noscroll">
                @foreach($cast as $c)
                    @php $photo = $tmdbImg($c['profilePath'] ?? null, 'w185'); @endphp
                    <div class="shrink-0 w-24 md:w-28 text-center">
                        <div class="w-24 h-24 md:w-28 md:h-28 rounded-full overflow-hidden bg-zinc-900 ring-1 ring-white/10 mx-auto">
                            @if($photo)
                                <img src="{{ $photo }}" alt="{{ $c['name'] }}" class="w-full h-full object-cover" loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-2xl font-black text-zinc-700">{{ mb_substr($c['name'] ?? '?', 0, 1) }}</div>
                            @endif
                        </div>
                        <p class="mt-2 text-xs font-bold text-zinc-200 line-clamp-1">{{ $c['name'] }}</p>
                        @if(!empty($c['character']))<p class="text-[11px] text-zinc-500 line-clamp-1">{{ $c['character'] }}</p>@endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ================= INFO ================= --}}
    <section class="w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-14">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-zinc-800/60 rounded-2xl overflow-hidden ring-1 ring-zinc-800">
            @foreach([
                ['Tayang Perdana', !empty($series['firstAirDate']) ? \Carbon\Carbon::parse($series['firstAirDate'])->locale('id')->translatedFormat('j F Y') : '-'],
                ['Status', $statusLabel ?: '-'],
                ['Jumlah Season', $series['numberOfSeasons'] ?? count($seasons) ?: '-'],
                ['Negara', $series['country'] ?? '-'],
            ] as [$k, $v])
                <div class="bg-[#0b0b0c] p-5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-500">{{ $k }}</p>
                    <p class="mt-1.5 text-sm md:text-base font-bold text-zinc-100">{{ $v }}</p>
                </div>
            @endforeach
        </div>
        @if(count($networks))
            <div class="flex flex-wrap items-center gap-3 mt-5">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-500">Jaringan</span>
                @foreach($networks as $n)
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs font-semibold">
                        @if(!empty($n['logo_path']))
                            <img src="{{ $tmdbImg($n['logo_path'], 'w92') }}" alt="" class="h-4 w-auto max-w-[56px] object-contain brightness-0 invert opacity-80" loading="lazy">
                        @endif
                        {{ $n['name'] }}
                    </span>
                @endforeach
            </div>
        @endif
    </section>
</div>

@once
    @push('styles')
        <style>
            .kz-noscroll { scrollbar-width: none; }
            .kz-noscroll::-webkit-scrollbar { display: none; }
        </style>
    @endpush
    @if(!empty($episodes))
        @push('scripts')
            <script>
                (function() {
                    setTimeout(function() {
                        const ep1 = @json($episodes[0]['id'] ?? null);
                        const ep2 = @json($episodes[1]['id'] ?? null);
                        if (ep1 && navigator.onLine) {
                            fetch('/api/streaming/stream?type=episode&id=' + encodeURIComponent(ep1), { priority: 'low' })
                                .then(function() {
                                    if (ep2 && navigator.onLine) {
                                        setTimeout(function() {
                                            fetch('/api/streaming/stream?type=episode&id=' + encodeURIComponent(ep2), { priority: 'low' }).catch(function() {});
                                        }, 1200);
                                    }
                                })
                                .catch(function() {});
                        }
                    }, 800);
                })();
            </script>
        @endpush
    @endif
@endonce
