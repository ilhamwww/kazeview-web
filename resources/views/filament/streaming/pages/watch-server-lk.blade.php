@php
    use App\Filament\Streaming\Pages\ExploreServerLk;
    use App\Filament\Streaming\Pages\WatchServerLk;

    $poster = $movie['poster'] ?? '';
    $title = $movie['title'] ?? 'Film';
    $rating = $movie['rating'] ?? null;
    $ratingCount = $movie['rating_count'] ?? null;
    $duration = $movie['duration'] ?? null;
    $release = $movie['release'] ?? null;
    $year = $movie['year'] ?? null;
    $country = $movie['country'] ?? null;
    $director = $movie['director'] ?? null;
    $casts = $movie['casts'] ?? [];
    $genres = $movie['genres'] ?? [];
    $synopsis = $movie['synopsis'] ?? '';
    $trailerUrl = $movie['trailer_url'] ?? '';
    $ytId = null;
    if ($trailerUrl && preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $trailerUrl, $mYt)) {
        $ytId = $mYt[1];
    }
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16 relative"
     x-data="{
        activeServer: @entangle('activeServerIndex'),
        currentEmbed: @entangle('currentEmbedUrl'),
        showTrailer: false,
        servers: {{ Js::from($servers) }}
     }">

    {{-- Ambient backdrop --}}
    @if($poster)
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[800px] overflow-hidden" aria-hidden="true">
            <img src="{{ $poster }}" alt="" class="w-full h-full object-cover opacity-20 blur-3xl scale-125">
            <div class="absolute inset-0 bg-gradient-to-b from-[#050505]/40 via-[#050505]/85 to-[#050505]"></div>
        </div>
    @endif

    {{-- Top navigation bar --}}
    <div class="relative w-full max-w-[1600px] mx-auto px-4 md:px-8 pt-20 md:pt-28 pb-4 flex items-center justify-between">
        <a href="{{ ExploreServerLk::getUrl(panel: 'streaming') }}"
           class="inline-flex items-center gap-1.5 text-xs md:text-sm font-semibold text-zinc-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-3.5 py-1.5 rounded-full transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Server LK (18+)
        </a>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-rose-400 bg-rose-500/10 border border-rose-500/30 px-2.5 py-1 rounded-md">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                18+ • SERVER LK
            </span>
            <span class="hidden sm:inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-emerald-400 bg-emerald-400/10 border border-emerald-400/30 px-2.5 py-1 rounded-md">
                HD 1080P
            </span>
        </div>
    </div>

    <div class="relative w-full max-w-[1600px] mx-auto px-0 md:px-8 space-y-6">

        {{-- ================= VIDEO PLAYER ================= --}}
        <div class="w-full bg-black md:rounded-3xl overflow-hidden aspect-video relative ring-1 ring-white/10 shadow-[0_30px_90px_rgba(0,0,0,0.9)]">
            @if(!empty($currentEmbedUrl))
                <iframe :src="currentEmbed"
                        class="w-full h-full border-0"
                        allowfullscreen
                        webkitallowfullscreen
                        mozallowfullscreen
                        allow="autoplay; fullscreen; picture-in-picture"
                        referrerpolicy="no-referrer">
                </iframe>
            @else
                <div class="w-full h-full flex flex-col items-center justify-center p-6 text-center text-zinc-400">
                    <svg class="w-12 h-12 text-zinc-600 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <p class="font-bold text-white text-base">Player Video Belum Tersedia</p>
                    <p class="text-xs text-zinc-500 mt-1 max-w-sm">
                        Server video sedang memproses media. Silakan pilih server mirror lainnya di bawah.
                    </p>
                </div>
            @endif
        </div>

        {{-- ================= SERVER CONTROLS & ACTION BAR ================= --}}
        <div class="px-4 md:px-0 flex flex-wrap items-center justify-between gap-4 py-3 bg-[#0d0d10] border border-white/5 rounded-2xl p-4">
            {{-- Mirror Servers --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-zinc-400 mr-1 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.7 7.4A2.25 2.25 0 017.5 6.75h9a2.25 2.25 0 011.8.65l2.55 4.15c.58.95.9 2.05.9 3.2"/></svg>
                    Ganti Mirror:
                </span>
                @foreach($servers as $idx => $srv)
                    <button type="button"
                            wire:click="switchServer({{ $idx }})"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer {{ $activeServerIndex === $idx ? 'bg-red-600 text-white shadow-md shadow-red-950/60 border border-red-500/50' : 'bg-zinc-900 hover:bg-zinc-800 text-zinc-300 hover:text-white border border-white/5' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $activeServerIndex === $idx ? 'bg-white' : 'bg-zinc-600' }}"></span>
                        {{ $srv['name'] }}
                    </button>
                @endforeach
            </div>

            {{-- Action buttons: Trailer & Watchlist --}}
            <div class="flex items-center gap-2">
                @if($ytId || $trailerUrl)
                    <button type="button"
                            @click="showTrailer = true"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-zinc-900 hover:bg-zinc-800 text-white border border-white/10 transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                        Trailer
                    </button>
                @endif

                <button type="button"
                        wire:click="toggleFavorite"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer {{ $isFavorited ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-zinc-900 hover:bg-zinc-800 text-zinc-300 border border-white/10' }}">
                    <svg class="w-4 h-4 {{ $isFavorited ? 'text-amber-400 fill-current' : 'text-zinc-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z"/>
                    </svg>
                    {{ $isFavorited ? 'Tersimpan' : 'Favorit' }}
                </button>
            </div>
        </div>

        {{-- ================= MOVIE DETAILS & SYNOPSIS ================= --}}
        <div class="px-4 md:px-0 grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6">
            {{-- Poster Column --}}
            <div class="hidden lg:block">
                <div class="rounded-2xl overflow-hidden bg-zinc-900 border border-white/10 shadow-2xl sticky top-24">
                    <img src="{{ $poster }}" alt="{{ $title }}" class="w-full aspect-[2/3] object-cover">
                    <div class="p-4 space-y-2.5 text-xs text-zinc-400 bg-[#0d0d10]">
                        @if(!empty($rating))
                            <div class="flex items-center justify-between pb-2 border-b border-white/5">
                                <span>Rating</span>
                                <span class="text-amber-400 font-extrabold flex items-center gap-1">
                                    ★ {{ $rating }} <span class="text-zinc-500 font-normal">({{ $ratingCount ?: 'LK' }})</span>
                                </span>
                            </div>
                        @endif
                        @if(!empty($year))
                            <div class="flex items-center justify-between pb-2 border-b border-white/5">
                                <span>Tahun Rilis</span>
                                <span class="text-white font-bold">{{ $year }}</span>
                            </div>
                        @endif
                        @if(!empty($duration))
                            <div class="flex items-center justify-between pb-2 border-b border-white/5">
                                <span>Durasi</span>
                                <span class="text-white font-bold">{{ $duration }}</span>
                            </div>
                        @endif
                        @if(!empty($country))
                            <div class="flex items-center justify-between pb-2 border-b border-white/5">
                                <span>Negara</span>
                                <span class="text-white font-bold">{{ $country }}</span>
                            </div>
                        @endif
                        @if(!empty($director))
                            <div class="flex items-center justify-between">
                                <span>Sutradara</span>
                                <span class="text-white font-bold truncate max-w-[130px]" title="{{ $director }}">{{ $director }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Info & Synopsis Column --}}
            <div class="space-y-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-wider">
                            Server LK
                        </span>
                        <span class="bg-amber-400/20 text-amber-300 border border-amber-400/30 text-[10px] font-bold px-2 py-0.5 rounded uppercase">
                            HD Quality
                        </span>
                        @if(!empty($duration))
                            <span class="text-xs text-zinc-400 font-semibold">{{ $duration }}</span>
                        @endif
                        @if(!empty($year))
                            <span class="text-xs text-zinc-400 font-semibold">• {{ $year }}</span>
                        @endif
                    </div>

                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-white tracking-tight leading-tight">
                        {{ $title }}
                    </h1>

                    {{-- Genre pills --}}
                    @if(!empty($genres))
                        <div class="flex flex-wrap items-center gap-1.5 mt-3">
                            @foreach($genres as $g)
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-900 border border-white/5 text-zinc-300">
                                    {{ $g }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Casts --}}
                @if(!empty($casts))
                    <div class="p-4 rounded-2xl bg-[#0d0d10] border border-white/5 space-y-1">
                        <h4 class="text-xs font-black uppercase tracking-wider text-zinc-400">Pemeran Utama</h4>
                        <p class="text-xs sm:text-sm text-zinc-200 font-medium">
                            {{ implode(', ', $casts) }}
                        </p>
                    </div>
                @endif

                {{-- Synopsis --}}
                @if(!empty($synopsis))
                    <div class="space-y-2">
                        <h3 class="text-sm font-black uppercase tracking-wider text-zinc-400">Sinopsis Film</h3>
                        <div class="text-sm sm:text-base text-zinc-300 leading-relaxed bg-[#0d0d10]/60 p-5 rounded-2xl border border-white/5">
                            {{ $synopsis }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ================= RELATED MOVIES SHELF ================= --}}
        @if(!empty($related))
            <div class="px-4 md:px-0 pt-8 border-t border-white/5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg sm:text-xl font-black text-white tracking-tight flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-600"></span>
                        Film Terkait Lainnya
                    </h3>
                    <a href="{{ ExploreServerLk::getUrl(panel: 'streaming') }}" class="text-xs font-bold text-red-400 hover:text-red-300 transition">
                        Lihat Semua →
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                    @foreach($related as $rel)
                        <a href="{{ WatchServerLk::getUrl(['slug' => $rel['slug']], panel: 'streaming') }}"
                           class="group relative flex flex-col rounded-2xl overflow-hidden bg-[#101012] border border-white/5 hover:border-red-500/40 transition">
                            <div class="aspect-[2/3] w-full overflow-hidden bg-zinc-900 relative">
                                <img src="{{ $rel['poster'] }}" alt="{{ $rel['title'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                <span class="absolute top-1.5 left-1.5 bg-black/80 backdrop-blur-md text-[9px] font-black px-1.5 py-0.5 rounded text-amber-300 border border-amber-400/30">HD</span>
                            </div>
                            <div class="p-2.5">
                                <h4 class="text-xs font-bold text-white group-hover:text-red-400 transition line-clamp-2 leading-snug">
                                    {{ $rel['title'] }}
                                </h4>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ================= TRAILER MODAL ================= --}}
    @if($ytId)
        <div x-show="showTrailer"
             x-transition.opacity
             @click.self="showTrailer = false"
             class="fixed inset-0 z-[120] bg-black/85 backdrop-blur-md flex items-center justify-center p-4"
             style="display: none;">
            <div class="w-full max-w-4xl bg-black rounded-3xl overflow-hidden border border-white/10 shadow-2xl relative">
                <div class="flex items-center justify-between p-4 border-b border-white/10 bg-zinc-950">
                    <span class="text-sm font-bold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                        Trailer: {{ $title }}
                    </span>
                    <button type="button" @click="showTrailer = false" class="text-zinc-400 hover:text-white p-1 rounded-lg">
                        ✕
                    </button>
                </div>
                <div class="aspect-video w-full bg-black">
                    <iframe x-if="showTrailer"
                            src="https://www.youtube.com/embed/{{ $ytId }}?autoplay=1"
                            class="w-full h-full border-0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                    </iframe>
                </div>
            </div>
        </div>
    @endif
</div>
