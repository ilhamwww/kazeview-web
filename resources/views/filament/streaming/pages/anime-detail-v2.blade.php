@php
    use App\Filament\Streaming\Pages\ExploreAnimeV2;
    use App\Filament\Streaming\Pages\WatchAnimeV2;

    $epSlug = fn (array $e) => basename(rtrim((string) ($e['url'] ?? ''), '/'));
    $watchUrl = fn (array $e) => ($s = $epSlug($e)) ? WatchAnimeV2::getUrl(['anime_slug' => $animeSlug, 'episode_slug' => $s], panel: 'streaming') : '#';

    // Urutkan episode dari kecil ke besar (sumber mengirim terbaru dulu)
    $eps = $detail['episodes'] ?? [];
    usort($eps, fn ($a, $b) => (float) ($a['episode'] ?? 0) <=> (float) ($b['episode'] ?? 0));
    $firstEp = $eps[0] ?? null;
    $latestEp = $eps ? $eps[count($eps) - 1] : null;

    $score = is_numeric($detail['score'] ?? null) ? (float) $detail['score'] : 0;
    $status = $detail['status'] ?? '';
    $isDone = strtolower($status) === 'completed';
    $info = array_filter([
        'Tipe' => $detail['type'] ?? null,
        'Status' => $status ? ($isDone ? 'Tamat' : 'Ongoing') : null,
        'Total Episode' => ($detail['total_episodes'] ?? '') !== 'Unknown' ? ($detail['total_episodes'] ?? null) : null,
        'Durasi' => $detail['duration'] ?? null,
        'Rilis' => $detail['release_date'] ?? null,
        'Studio' => $detail['studio'] ?? null,
    ]);
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ================= HERO ================= --}}
    <section class="relative w-full overflow-hidden">
        @if(!empty($detail['thumbnail']))
            <div class="absolute inset-0" aria-hidden="true">
                <img src="{{ $detail['thumbnail'] }}" alt="" class="w-full h-full object-cover object-top opacity-40 blur-2xl scale-125">
            </div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-b from-[#050505]/50 via-[#050505]/80 to-[#050505]"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-transparent to-transparent"></div>

        <div class="relative w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-24 md:pt-28 pb-10">
            <a href="{{ ExploreAnimeV2::getUrl(panel: 'streaming') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-3 py-1.5 rounded-full transition mb-8">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Katalog Anime V2
            </a>

            <div class="flex flex-col md:flex-row gap-8 md:gap-12 items-center md:items-end">
                @if(!empty($detail['thumbnail']))
                    <div class="shrink-0 w-44 sm:w-52 md:w-64 aspect-[2/3] rounded-2xl overflow-hidden ring-1 ring-white/15 shadow-[0_30px_60px_-15px_rgba(0,0,0,0.9)]">
                        <img src="{{ $detail['thumbnail'] }}" alt="{{ $detail['title'] ?? '' }}" class="w-full h-full object-cover">
                    </div>
                @endif

                <div class="flex-1 min-w-0 text-center md:text-left">
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2 mb-3">
                        <span class="bg-blue-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider">SERVER 2</span>
                        @if($status)
                            <span class="text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider border {{ $isDone ? 'text-emerald-400 border-emerald-500/40 bg-emerald-500/10' : 'text-blue-300 border-blue-500/40 bg-blue-500/10' }}">{{ $isDone ? 'Tamat' : 'Ongoing' }}</span>
                        @endif
                        @if(!empty($detail['type']))
                            <span class="text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-wider text-zinc-300 border border-white/15 bg-white/5">{{ $detail['type'] }}</span>
                        @endif
                    </div>

                    <h1 class="text-3xl md:text-5xl lg:text-6xl font-black tracking-tight leading-[1.02]">{{ $detail['title'] ?? 'Anime' }}</h1>
                    @if(!empty($detail['japanese_title']))
                        <p class="mt-2 text-sm md:text-base text-zinc-400">{{ $detail['japanese_title'] }}</p>
                    @endif

                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-x-3 gap-y-1 mt-4 text-sm font-semibold text-zinc-300">
                        @if($score > 0)
                            <span class="inline-flex items-center gap-1 text-yellow-400">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                {{ number_format($score, 2) }}
                            </span>
                            <span class="text-zinc-600">•</span>
                        @endif
                        <span>{{ count($eps) }} Episode tersedia</span>
                        @if(!empty($detail['duration']))<span class="text-zinc-600">•</span><span>{{ $detail['duration'] }}</span>@endif
                    </div>

                    @if(!empty($detail['genres']))
                        <div class="flex flex-wrap justify-center md:justify-start gap-2 mt-4">
                            @foreach($detail['genres'] as $g)
                                <span class="px-3 py-1 text-xs font-semibold text-blue-200 bg-blue-500/10 border border-blue-500/25 rounded-full">{{ $g }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mt-7">
                        @if($firstEp)
                            <a href="{{ $watchUrl($firstEp) }}" class="inline-flex items-center gap-2 pl-5 pr-6 py-3 bg-white text-black hover:bg-zinc-200 active:scale-95 rounded-lg font-bold text-sm transition">
                                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                Mulai Episode {{ $firstEp['episode'] }}
                            </a>
                        @endif
                        @if($latestEp && $latestEp !== $firstEp)
                            <a href="{{ $watchUrl($latestEp) }}" class="inline-flex items-center gap-2 px-5 py-3 bg-blue-600 hover:bg-blue-700 active:scale-95 rounded-lg font-bold text-sm transition shadow-lg shadow-blue-900/40">
                                Terbaru: Ep {{ $latestEp['episode'] }}
                            </a>
                        @endif
                        <button wire:click="toggleFavorite" type="button"
                                class="inline-flex items-center gap-2 px-5 py-3 rounded-lg font-bold text-sm transition border {{ $isFavorited ? 'bg-red-600/15 border-red-600/50 text-red-400' : 'bg-white/10 hover:bg-white/15 border-white/10 text-white' }}">
                            <svg class="w-5 h-5 {{ $isFavorited ? 'fill-current' : 'fill-none stroke-current' }}" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            {{ $isFavorited ? 'Tersimpan' : 'Favorit' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= BODY ================= --}}
    <div class="w-full max-w-[1800px] mx-auto px-6 md:px-12 pt-4 grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-8">

        {{-- Episodes --}}
        <section x-data="{ q: '', desc: false }">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                <h2 class="text-xl md:text-2xl font-black tracking-tight">Daftar Episode</h2>
                @if(count($eps) > 8)
                    <div class="flex items-center gap-2">
                        <input type="search" x-model="q" placeholder="Cari nomor..." aria-label="Cari episode"
                               class="w-36 px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500/50">
                        <button type="button" @click="desc = !desc" class="px-3 py-2 bg-zinc-900 border border-zinc-800 hover:border-zinc-600 rounded-lg text-xs font-bold transition"
                                x-text="desc ? 'Terbaru ↓' : 'Terlama ↑'">Terlama ↑</button>
                    </div>
                @endif
            </div>

            @if(count($eps))
                <div class="kz-eps grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 xl:grid-cols-8 gap-2.5" :class="desc ? 'kz-rev' : ''">
                    @foreach($eps as $ep)
                        @php $isNew = $latestEp && $ep === $latestEp && !$isDone; @endphp
                        <a href="{{ $watchUrl($ep) }}"
                           x-show="q === '' || '{{ $ep['episode'] }}'.includes(q)"
                           style="--o: {{ (int) $loop->index }}"
                           class="group relative flex flex-col items-center justify-center py-3.5 rounded-xl bg-zinc-900/70 hover:bg-blue-600 ring-1 ring-white/5 hover:ring-blue-400 transition active:scale-95">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-500 group-hover:text-blue-100">Episode</span>
                            <span class="text-xl font-black leading-none mt-0.5">{{ $ep['episode'] }}</span>
                            @if($isNew)
                                <span class="absolute -top-1.5 -right-1.5 bg-blue-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full ring-2 ring-[#050505]">BARU</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <div class="py-16 text-center text-zinc-500 bg-zinc-900/40 rounded-2xl border border-dashed border-zinc-800">Belum ada episode yang tersedia.</div>
            @endif
        </section>

        {{-- Sidebar --}}
        <aside class="space-y-5">
            @if(!empty($detail['synopsis']))
                <div class="bg-zinc-900/50 rounded-2xl ring-1 ring-white/5 p-5">
                    <h2 class="text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500 mb-2.5">Sinopsis</h2>
                    <p x-data="{ more: false }" @click="more = !more" :class="more ? '' : 'line-clamp-6'"
                       class="text-sm text-zinc-300 leading-relaxed cursor-pointer">{{ $detail['synopsis'] }}</p>
                </div>
            @endif
            @if(count($info))
                <div class="bg-zinc-900/50 rounded-2xl ring-1 ring-white/5 divide-y divide-zinc-800/70">
                    @foreach($info as $k => $v)
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <span class="text-xs font-bold text-zinc-500">{{ $k }}</span>
                            <span class="text-sm font-semibold text-zinc-100 text-right">{{ $v }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </aside>
    </div>
</div>

@once
    @push('styles')
        <style>
            .kz-eps > a { order: var(--o, 0); }
            .kz-eps.kz-rev > a { order: calc(-1 * var(--o, 0)); }
        </style>
    @endpush
@endonce
