@php
    use App\Filament\Streaming\Pages\ExploreAnimeV5;
    use App\Filament\Streaming\Pages\WatchAnimeV5;

    $epSlug = fn (array $e) => !empty($e['slug']) ? $e['slug'] : basename(rtrim((string) ($e['url'] ?? ''), '/'));
    $watchUrl = fn (array $e) => ($s = $epSlug($e)) ? WatchAnimeV5::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $s], panel: 'streaming') : '#';

    // Urutkan episode dari kecil ke besar jika numerik
    $eps = $detail['episodes'] ?? [];
    usort($eps, fn ($a, $b) => (float) ($a['number'] ?? 0) <=> (float) ($b['number'] ?? 0));
    $firstEp = $eps[0] ?? null;
    $latestEp = $eps ? $eps[count($eps) - 1] : null;
    $resumeEp = !empty($lastWatchedEpisode)
        ? (collect($eps)->first(fn ($e) => (string) ($e['number'] ?? $e['episode'] ?? '') === (string) $lastWatchedEpisode) ?: ['number' => $lastWatchedEpisode, 'slug' => (string) $lastWatchedEpisode])
        : null;

    $score = is_numeric($detail['score'] ?? null) ? (float) $detail['score'] : 0;
    $status = $detail['status'] ?? '';
    $isDone = strtolower($status) === 'completed' || strtolower($status) === 'tamat';
    $info = array_filter([
        'Tipe' => $detail['type'] ?? null,
        'Status' => $status ? ($isDone ? 'Tamat' : 'Ongoing') : null,
        'Total Episode' => ($detail['total_episodes'] ?? '') !== 'Unknown' ? ($detail['total_episodes'] ?? null) : null,
        'Durasi' => $detail['duration'] ?? null,
        'Season' => $detail['season'] ?? null,
        'Studio' => $detail['studio'] ?? null,
    ]);
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ================= HERO ================= --}}
    <section class="relative w-full overflow-hidden">
        @if(!empty($detail['thumbnail']))
            <div class="absolute inset-0" aria-hidden="true">
                <img src="{{ $detail['thumbnail'] }}" alt="" class="w-full h-full object-cover object-top opacity-35 blur-2xl scale-125">
            </div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-b from-[#050505]/50 via-[#050505]/80 to-[#050505]"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-transparent to-transparent"></div>

        <div class="relative w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-24 md:pt-28 pb-10">
            <a href="{{ ExploreAnimeV5::getUrl(panel: 'streaming') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-3 py-1.5 rounded-full transition mb-8">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Katalog Anime V5
            </a>

            <div class="flex flex-col md:flex-row gap-8 md:gap-12 items-center md:items-end">
                @if(!empty($detail['thumbnail']))
                    <div class="shrink-0 w-44 sm:w-52 md:w-64 aspect-[2/3] rounded-2xl overflow-hidden ring-1 ring-white/15 shadow-[0_30px_60px_-15px_rgba(0,0,0,0.9)] bg-zinc-900">
                        <img src="{{ $detail['thumbnail'] }}" alt="{{ $detail['title'] ?? '' }}" class="w-full h-full object-cover">
                    </div>
                @endif

                <div class="flex-1 min-w-0 text-center md:text-left">
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 mb-3">
                        <span class="bg-rose-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider">SERVER 5</span>
                        <span class="bg-white/10 text-rose-300 text-[10px] font-extrabold px-2.5 py-1 rounded uppercase tracking-wider border border-rose-500/30">1080P FHD & RELAY</span>
                        @if($status)
                            <span class="text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider border {{ $isDone ? 'text-emerald-400 border-emerald-500/40 bg-emerald-500/10' : 'text-rose-300 border-rose-500/40 bg-rose-500/10' }}">{{ $isDone ? 'Tamat' : 'Ongoing' }}</span>
                        @endif
                        @if(!empty($detail['type']))
                            <span class="text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider border text-zinc-300 border-white/15 bg-white/5">{{ $detail['type'] }}</span>
                        @endif
                        @if($score > 0)
                            <span class="inline-flex items-center gap-1 text-[11px] font-extrabold text-amber-300 bg-amber-400/10 border border-amber-400/30 px-2.5 py-0.5 rounded">
                                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                {{ number_format($score, 2) }}
                            </span>
                        @endif
                    </div>

                    <h1 class="text-3xl sm:text-4xl md:text-6xl font-black tracking-tight leading-[1.05] drop-shadow-xl">
                        {{ $detail['title'] ?? 'Anime Detail' }}
                    </h1>

                    @if(!empty($detail['genres']))
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-1.5 mt-4">
                            @foreach($detail['genres'] as $genre)
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-zinc-900/80 border border-zinc-800 text-zinc-300">
                                    {{ $genre }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- CTA Buttons --}}
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mt-6">
                        @if(!empty($watchedEpisodes) && $lastWatchedEpisode !== null && $resumeEp)
                            <a href="{{ $watchUrl($resumeEp) }}" class="inline-flex items-center gap-2 px-5 py-3.5 bg-emerald-700 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm border border-emerald-500/30 transition">Lanjutkan Episode {{ $lastWatchedEpisode }}</a>
                            <span class="px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-800 text-xs font-semibold text-zinc-400">{{ count($watchedEpisodes) }} episode sudah dilihat</span>
                        @endif
                        @if($latestEp)
                            <a href="{{ $watchUrl($latestEp) }}"
                               class="inline-flex items-center gap-2 px-6 py-3.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl font-bold text-sm shadow-xl shadow-rose-600/30 transition transform hover:-translate-y-0.5">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                <span>Tonton Episode Terakhir (Eps {{ $latestEp['number'] ?? '1' }})</span>
                            </a>
                        @endif

                        @if($firstEp && $firstEp !== $latestEp)
                            <a href="{{ $watchUrl($firstEp) }}"
                               class="inline-flex items-center gap-2 px-5 py-3.5 bg-zinc-900 hover:bg-zinc-800 text-zinc-200 hover:text-white rounded-xl font-bold text-sm border border-zinc-800 transition">
                                <span>Dari Eps 1</span>
                            </a>
                        @endif

                        <button wire:click="toggleFavorite"
                                class="inline-flex items-center gap-2 px-4 py-3.5 rounded-xl border {{ $isFavorited ? 'bg-red-600 border-red-600 text-white shadow-lg shadow-red-600/30' : 'bg-zinc-900 border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800' }} text-sm font-bold transition">
                            <svg class="w-5 h-5 {{ $isFavorited ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            <span>{{ $isFavorited ? 'Tersimpan' : 'Favorit' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= MAIN BODY ================= --}}
    <div class="w-full max-w-[1800px] mx-auto px-6 md:px-12 mt-6 grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-8">
        {{-- Left: Synopsis & Episodes --}}
        <div class="space-y-8">
            {{-- Synopsis --}}
            @if(!empty($detail['synopsis']))
                <section class="bg-zinc-900/60 rounded-2xl p-6 border border-zinc-800/80">
                    <h2 class="text-sm font-black uppercase tracking-wider text-rose-400 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/></svg>
                        Sinopsis
                    </h2>
                    <p class="text-sm md:text-base text-zinc-300 leading-relaxed whitespace-pre-line">
                        {{ $detail['synopsis'] }}
                    </p>
                </section>
            @endif

            {{-- Episode List --}}
            <section class="bg-zinc-900/60 rounded-2xl p-6 border border-zinc-800/80">
                <div class="flex items-center justify-between pb-4 border-b border-zinc-800 mb-4">
                    <h2 class="text-sm font-black uppercase tracking-wider text-rose-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Daftar Episode ({{ count($eps) }})
                    </h2>
                    <span class="text-xs text-zinc-400 font-mono">1080p FHD Ready</span>
                </div>

                @if(empty($eps))
                    <div class="text-center py-10 text-zinc-500">
                        <p class="text-sm">Belum ada episode yang tersedia untuk anime ini.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-[600px] overflow-y-auto pr-1">
                        @foreach($eps as $ep)
                            @php
                                $epNumber = (string) ($ep['number'] ?? $ep['episode'] ?? '');
                                $epSlug = !empty($ep['slug']) ? (string) $ep['slug'] : '';
                                $isActiveEp = ($epNumber !== '' && $epNumber === (string) $lastWatchedEpisode);
                                $isWatchedEp = in_array($epNumber, $watchedEpisodes ?? [], true);
                            @endphp
                            <a href="{{ $watchUrl($ep) }}"
                               class="group flex items-center justify-between p-3 rounded-xl border transition {{ $isActiveEp ? 'bg-rose-600/15 border-rose-500/60' : ($isWatchedEp ? 'bg-emerald-500/[0.06] border-emerald-500/30' : 'bg-zinc-950/70 border-zinc-800/80 hover:border-rose-500/60 hover:bg-zinc-900') }}">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-8 h-8 rounded-lg bg-zinc-900 group-hover:bg-rose-600 text-zinc-300 group-hover:text-white flex items-center justify-center text-xs font-mono font-black shrink-0 transition">
                                        {{ $ep['number'] ?? '•' }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-zinc-200 group-hover:text-rose-300 transition truncate">
                                            {{ $ep['title'] }}
                                        </p>
                                        @if($isActiveEp || $isWatchedEp)
                                            <p class="text-[10px] font-black mt-0.5 {{ $isActiveEp ? 'text-rose-400' : 'text-emerald-400' }}">{{ $isActiveEp ? 'Terakhir dilihat' : 'Sudah dilihat ✓' }}</p>
                                        @elseif(!empty($ep['date']))
                                            <p class="text-[10px] text-zinc-500 mt-0.5">{{ $ep['date'] }}</p>
                                        @endif
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-zinc-600 group-hover:text-rose-400 shrink-0 transition ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- Right: Anime Information Card --}}
        <div class="space-y-6">
            <section class="bg-zinc-900/60 rounded-2xl p-6 border border-zinc-800/80 space-y-4">
                <h3 class="text-xs font-black uppercase tracking-wider text-rose-400 pb-2 border-b border-zinc-800">
                    Informasi Anime
                </h3>

                <dl class="space-y-3 text-xs">
                    @foreach($info as $label => $val)
                        <div class="flex items-center justify-between py-1 border-b border-zinc-800/50">
                            <dt class="text-zinc-400 font-medium">{{ $label }}</dt>
                            <dd class="text-zinc-100 font-bold text-right">{{ $val }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>
    </div>
</div>
