{{--
    Katalog Movie / TV Series (dipakai movies.blade.php & tv-series.blade.php)
    Params: $kind ('movie'|'series'), $trending (array), $items (array), $hasMorePages (bool)
--}}
@php
    use App\Filament\Streaming\Pages\ShowSeries;
    use App\Filament\Streaming\Pages\WatchMovie;

    $isSeries = $kind === 'series';
    $label = $customLabel ?? ($isSeries ? 'TV Series' : ($kind === 'indonesia' ? 'Indonesia' : 'Movies'));
    $urlFor = function (array $it) use ($isSeries) {
        if (empty($it['slug'])) {
            return '#';
        }
        $series = $isSeries || in_array($it['type'] ?? '', ['tv_series', 'tv', 'series'], true);
        return $series
            ? ShowSeries::getUrl(['slug' => $it['slug']], panel: 'streaming')
            : WatchMovie::getUrl(['slug' => $it['slug']], panel: 'streaming');
    };
    $cleanImg = function ($u) {
        if (empty($u)) return '';
        return preg_replace('#^https?://[^/]+(?=/api/proxy-image)#', '', (string) $u);
    };
    $banner = fn (array $it) => $cleanImg(str_replace('/t/p/original/', '/t/p/w1280/', $it['banner'] ?? '') ?: ($it['thumbnail'] ?? ''));
    $runtime = function ($d) {
        if (!preg_match('/^(\d+)m$/', (string) $d, $m)) {
            return $d ?: null;
        }
        $min = (int) $m[1];
        return $min >= 60 ? intdiv($min, 60) . 'j ' . ($min % 60) . 'm' : $min . 'm';
    };
    $hasRating = fn ($r) => is_numeric($r) && (float) $r > 0;
    $genres = fn ($g) => array_values(array_filter(array_map('trim', explode(',', (string) $g))));
    $hero = array_slice($trending, 0, 6);
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-28 md:pb-16">

    {{-- ================= HERO ================= --}}
    @if(count($hero) && empty($search) && ($category ?? 'all') === 'all')
        <section wire:ignore
                 x-data="{ active: 0, total: {{ count($hero) }}, t: null, sx: 0,
                     start() { this.stop(); this.t = setInterval(() => this.next(), 7000) },
                     stop() { if (this.t) clearInterval(this.t) },
                     next() { this.active = (this.active + 1) % this.total },
                     prev() { this.active = (this.active - 1 + this.total) % this.total },
                     go(i) { this.active = i; this.start() } }"
                 x-init="start()"
                 @touchstart.passive="sx = $event.touches[0].clientX"
                 @touchend.passive="let dx = $event.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) { dx < 0 ? next() : prev(); start() }"
                 class="relative w-full h-[86vh] min-h-[600px] max-h-[920px] overflow-hidden bg-black">

            @foreach($hero as $i => $h)
                @php $g = array_slice($genres($h['genre'] ?? ''), 0, 3); @endphp
                <div x-show="active === {{ $i }}"
                     x-transition:enter="transition ease-out duration-1000"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @if($i > 0) style="display:none" @endif
                     class="absolute inset-0">
                    <img src="{{ $banner($h) }}" alt="" class="absolute inset-0 w-full h-full object-cover kz-kenburns" @if($i > 0) loading="lazy" @endif>
                    <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/70 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 h-[55%] bg-gradient-to-t from-[#050505] via-[#050505]/70 to-transparent"></div>
                    <div class="absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-[#050505]/90 to-transparent"></div>

                    <div class="relative h-full w-full max-w-[1800px] mx-auto px-6 md:px-12 pb-36 md:pb-40 flex items-end">
                        <div class="max-w-2xl">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="inline-flex items-center gap-1.5 bg-red-600 text-white text-[10px] font-black px-2.5 py-1 rounded uppercase tracking-[0.15em]">
                                    <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67z"/></svg>
                                    Trending #{{ $i + 1 }}
                                </span>
                                <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-400">{{ $label }}</span>
                            </div>

                            @if(!empty($h['logo']))
                                <h1>
                                    <img src="{{ $h['logo'] }}" alt="{{ $h['title'] ?? '' }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                                         class="block h-auto w-auto max-w-[75%] sm:max-w-[460px] max-h-[100px] sm:max-h-[150px] lg:max-h-[180px] object-contain object-left drop-shadow-[0_4px_24px_rgba(0,0,0,0.85)]"
                                         onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'block text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black tracking-tight leading-[0.95] text-white drop-shadow-2xl line-clamp-2',textContent:this.alt}))">
                                </h1>
                            @else
                                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black tracking-tight leading-[0.95] text-white drop-shadow-2xl line-clamp-2">{{ $h['title'] ?? '' }}</h1>
                            @endif

                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mt-5 text-sm font-semibold text-zinc-300">
                                @if($hasRating($h['rating'] ?? null))
                                    <span class="inline-flex items-center gap-1 text-yellow-400">
                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                        {{ number_format((float) $h['rating'], 1) }}
                                    </span>
                                @endif
                                @if(!empty($h['year']))<span class="text-zinc-500">•</span><span>{{ $h['year'] }}</span>@endif
                                @if($runtime($h['duration'] ?? null))<span class="text-zinc-500">•</span><span>{{ $runtime($h['duration']) }}</span>@endif
                                @if(!empty($h['quality']))<span class="px-1.5 py-0.5 text-[10px] font-black border border-zinc-500 rounded text-zinc-200">{{ $h['quality'] }}</span>@endif
                            </div>

                            @if(count($g))
                                <p class="mt-3 text-sm text-zinc-400">{{ implode('  ·  ', $g) }}</p>
                            @endif

                            @if(!empty($h['description']))
                                <p class="hidden sm:block mt-4 text-sm md:text-base text-zinc-300/90 leading-relaxed line-clamp-3">{{ $h['description'] }}</p>
                            @endif

                            <div class="flex flex-wrap items-center gap-3 mt-7">
                                <a href="{{ $urlFor($h) }}" class="inline-flex items-center gap-2 pl-5 pr-6 py-3 bg-white text-black hover:bg-zinc-200 active:scale-95 rounded-lg font-bold text-sm transition">
                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                                    {{ $isSeries ? 'Tonton Sekarang' : 'Putar' }}
                                </a>
                                <a href="{{ $urlFor($h) }}" class="inline-flex items-center gap-2 px-5 py-3 bg-zinc-500/30 hover:bg-zinc-500/50 backdrop-blur-md text-white rounded-lg font-bold text-sm transition border border-white/10">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                                    Info Lengkap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Thumbnail selector (desktop) & dots (mobile) --}}
            <div class="absolute bottom-8 md:bottom-10 inset-x-0 z-20">
                <div class="w-full max-w-[1800px] mx-auto px-6 md:px-12 flex items-end justify-between gap-6">
                    <div class="flex md:hidden items-center gap-2 mx-auto">
                        @foreach($hero as $i => $h)
                            <button type="button" @click="go({{ $i }})" aria-label="Slide {{ $i + 1 }}"
                                    :class="active === {{ $i }} ? 'w-7 bg-red-600' : 'w-2 bg-white/30'"
                                    class="h-2 rounded-full transition-all duration-300"></button>
                        @endforeach
                    </div>
                    <div class="hidden md:flex items-center gap-3 ml-auto">
                        @foreach($hero as $i => $h)
                            <button type="button" @click="go({{ $i }})" aria-label="{{ $h['title'] ?? 'Slide' }}"
                                    :class="active === {{ $i }} ? 'ring-2 ring-red-600 opacity-100 scale-105' : 'opacity-50 hover:opacity-90'"
                                    class="relative w-36 lg:w-44 aspect-video rounded-lg overflow-hidden bg-zinc-900 transition-all duration-300 shadow-xl">
                                <img src="{{ $banner($h) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                                <span class="absolute inset-x-0 bottom-0 px-2 py-1 bg-gradient-to-t from-black/90 to-transparent text-[10px] font-bold text-left line-clamp-1">{{ $h['title'] ?? '' }}</span>
                                <span x-show="active === {{ $i }}" class="absolute top-0 left-0 h-0.5 bg-red-600 kz-progress"></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ================= TOP 10 ================= --}}
    @if(count($trending) >= 5 && empty($search) && ($category ?? 'all') === 'all')
        <section class="relative z-10 w-full max-w-[1800px] mx-auto px-6 md:px-12 {{ count($hero) ? '-mt-4' : 'pt-28' }}">
            <div class="flex items-center gap-3 mb-5">
                <h2 class="text-xl md:text-2xl font-black tracking-tight">Top 10 {{ $label }} Minggu Ini</h2>
                <span class="hidden sm:inline text-[10px] font-black uppercase tracking-[0.2em] text-red-500 border border-red-600/40 bg-red-600/10 px-2 py-1 rounded">Trending</span>
            </div>
            {{-- Disamakan dengan Top 10 di Beranda: angka absolute di dalam kartu, scroll horizontal saja --}}
            <div class="flex gap-2 md:gap-3 overflow-x-auto overflow-y-hidden pb-2 -mx-6 px-6 md:-mx-12 md:px-12 scroll-px-6 md:scroll-px-12 snap-x kz-noscroll">
                @foreach(array_slice($trending, 0, 10) as $i => $t)
                    <a href="{{ $urlFor($t) }}" class="group relative block w-[150px] md:w-[214px] shrink-0 snap-start">
                        <div class="relative flex items-end">
                            <span class="pointer-events-none select-none absolute left-0 bottom-[-6px] md:bottom-[-10px] text-[104px] md:text-[150px] font-black leading-none tracking-tighter text-[#050505]"
                                  style="-webkit-text-stroke: 3px rgba(255,255,255,0.55);" aria-hidden="true">{{ $i + 1 }}</span>
                            <div class="relative z-10 ml-auto w-[96px] md:w-[136px] aspect-[2/3] overflow-hidden rounded-lg bg-zinc-900 ring-1 ring-white/10 shadow-xl shadow-black/70">
                                @if(!empty($t['thumbnail']))
                                    <img src="{{ $cleanImg($t['thumbnail']) }}" alt="{{ $t['title'] ?? '' }}" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" referrerpolicy="no-referrer">
                                @endif
                            </div>
                        </div>
                        <div class="ml-auto mt-2 w-[96px] md:w-[136px]">
                            <h3 class="truncate text-xs md:text-[13px] font-semibold text-zinc-200 group-hover:text-white" title="{{ $t['title'] ?? '' }}">{{ $t['title'] ?? '' }}</h3>
                            <p class="text-[11px] text-zinc-500">{{ $isSeries ? 'Series' : 'Film' }}@if(!empty($t['year'])) · {{ $t['year'] }}@endif</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ================= KATEGORI & PENCARIAN (JIKA ADA $categories) ================= --}}
    @if(!empty($categories))
        <section class="relative z-10 w-full max-w-[1800px] mx-auto px-6 md:px-12 {{ (count($hero) && empty($search) && ($category ?? 'all') === 'all') ? 'pt-8' : 'pt-24 md:pt-28' }}">
            <div class="bg-gradient-to-b from-zinc-900/60 to-zinc-900/30 border border-zinc-800/90 rounded-2xl p-4 md:p-5 backdrop-blur-xl shadow-2xl space-y-4">
                {{-- Search Bar (KZSTRM Signature Dark Redesign) --}}
                <div class="relative w-full group">
                    <div class="flex items-center gap-3 w-full px-4 py-3 bg-[#080808]/90 border border-zinc-800/90 rounded-xl focus-within:border-red-600 focus-within:ring-1 focus-within:ring-red-600/40 transition-all duration-200 shadow-inner">
                        <svg class="w-4 h-4 text-zinc-500 group-focus-within:text-red-500 flex-shrink-0 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                        <input type="text"
                               wire:model.live.debounce.400ms="search"
                               placeholder="Cari film atau serial Indonesia (misal: Siksa Kubur, Dilan, Agak Laen)..."
                               class="flex-1 bg-transparent text-xs sm:text-sm text-zinc-100 placeholder:text-zinc-500 focus:outline-none">
                        @if(!empty($search))
                            <button type="button" wire:click="clearSearch" aria-label="Hapus pencarian"
                                    class="p-1 text-zinc-400 hover:text-white hover:bg-white/10 rounded-full transition flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Category Filter Pills (Clean Text Only) --}}
                <div class="flex items-center gap-2 overflow-x-auto kz-noscroll pt-0.5 -mx-1 px-1">
                    @foreach($categories as $cat)
                        @php
                            $isActive = ($category ?? 'all') === $cat['id'];
                        @endphp
                        <button type="button"
                                wire:click="setCategory('{{ $cat['id'] }}')"
                                class="px-4 py-1.5 rounded-full text-xs font-semibold tracking-wide transition-all duration-200 shrink-0 border
                                {{ $isActive
                                    ? 'bg-red-600 text-white border-red-600 shadow-lg shadow-red-600/30 scale-[1.02]'
                                    : 'bg-zinc-900/80 hover:bg-zinc-800 text-zinc-400 hover:text-white border-zinc-800/80 hover:border-zinc-700' }}">
                            {{ $cat['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= GRID ================= --}}
    <main class="w-full max-w-[1800px] mx-auto px-6 md:px-12 {{ !empty($categories) ? 'pt-6' : 'pt-10' }}">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <h2 class="text-xl md:text-2xl font-black tracking-tight">
                        @if(!empty($search))
                            Hasil Pencarian: "{{ $search }}"
                        @elseif(($category ?? 'all') !== 'all')
                            Kategori: {{ $activeCategoryLabel ?? ucfirst($category) }}
                        @else
                            Baru Ditambahkan
                        @endif
                    </h2>
                    @if(!empty($search) || ($category ?? 'all') !== 'all')
                        <button type="button" wire:click="resetFilter"
                                class="text-xs font-bold text-red-500 hover:text-red-400 bg-red-600/10 border border-red-600/20 px-2.5 py-0.5 rounded-md transition flex items-center gap-1">
                            <span>Reset</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endif
                </div>
                <p class="text-xs md:text-sm text-zinc-500 mt-1">{{ $kind === 'indonesia' ? 'Koleksi film & serial Indonesia terlengkap' : ($isSeries ? 'Serial terbaru yang masuk ke katalog' : 'Film terbaru yang masuk ke katalog') }}</p>
            </div>
            <span class="text-xs font-bold text-zinc-400 bg-zinc-900 border border-zinc-800 px-3 py-1.5 rounded-full shrink-0 self-start sm:self-auto">{{ count($items) }} judul</span>
        </div>

        @if(empty($items))
            <div class="py-20 text-center text-zinc-500 bg-zinc-900/40 rounded-2xl border border-dashed border-zinc-800 p-6">
                @if(!empty($search))
                    <p class="text-sm">Tidak ditemukan judul Indonesia dengan kata kunci "<span class="text-zinc-300 font-semibold">{{ $search }}</span>".</p>
                    <div class="mt-4">
                        <button type="button" wire:click="resetFilter" class="px-5 py-2 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-red-600/20">
                            Lihat Semua Koleksi Indonesia
                        </button>
                    </div>
                @else
                    <p class="text-sm">Belum ada {{ strtolower($label) }} yang tersedia untuk kategori ini.</p>
                @endif
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-x-4 gap-y-7">
                @foreach($items as $item)
                    @php $g = array_slice($genres($item['genre'] ?? ''), 0, 2); @endphp
                    <a href="{{ $urlFor($item) }}" wire:key="it-{{ $item['id'] ?? $loop->index }}" class="group block">
                        <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-zinc-900 ring-1 ring-white/5 group-hover:ring-red-600/60 shadow-lg shadow-black/40 transition">
                            @if(!empty($item['thumbnail']))
                                <img src="{{ $cleanImg($item['thumbnail']) }}" alt="{{ $item['title'] ?? '' }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy" referrerpolicy="no-referrer">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-xs text-zinc-600">No Image</div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/0 to-black/0 opacity-70 group-hover:opacity-100 transition"></div>

                            @if(!empty($item['quality']))
                                <span class="absolute top-2 left-2 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider rounded bg-black/60 backdrop-blur text-white border border-white/10">{{ $item['quality'] }}</span>
                            @endif
                            @if($hasRating($item['rating'] ?? null))
                                <span class="absolute top-2 right-2 inline-flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] font-black rounded bg-black/60 backdrop-blur text-yellow-400 border border-white/10">★ {{ number_format((float) $item['rating'], 1) }}</span>
                            @endif

                            <span class="absolute inset-0 m-auto w-12 h-12 rounded-full bg-red-600/90 flex items-center justify-center scale-75 opacity-0 group-hover:opacity-100 group-hover:scale-100 transition duration-300 shadow-xl shadow-red-900/50">
                                <svg class="w-5 h-5 fill-white ml-0.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                            </span>

                            @if(count($g))
                                <span class="absolute bottom-2 inset-x-2 text-[10px] font-semibold text-zinc-300 line-clamp-1">{{ implode(' · ', $g) }}</span>
                            @endif
                        </div>
                        <h3 class="mt-2.5 text-sm font-bold text-zinc-100 group-hover:text-red-500 transition line-clamp-1">{{ $item['title'] ?? '' }}</h3>
                        <p class="text-[11px] text-zinc-500 mt-0.5">
                            {{ $item['year'] ?? '' }}@if($runtime($item['duration'] ?? null)) · {{ $runtime($item['duration']) }}@endif
                        </p>
                    </a>
                @endforeach
            </div>

            @if($hasMorePages)
                <div x-intersect.margin.400px="$wire.loadMore()" class="flex justify-center pt-12">
                    <div wire:loading.flex wire:target="loadMore" class="items-center gap-3 text-zinc-400 bg-zinc-900/60 px-5 py-2.5 rounded-full border border-zinc-800">
                        <svg class="animate-spin h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-sm font-semibold">Memuat lebih banyak...</span>
                    </div>
                    <button type="button" wire:click="loadMore" wire:loading.remove wire:target="loadMore"
                            class="px-6 py-2.5 rounded-full bg-zinc-900 hover:bg-zinc-800 border border-zinc-700 text-sm font-bold text-zinc-200 transition">
                        Muat Lebih Banyak
                    </button>
                </div>
            @endif
        @endif
    </main>
</div>

@once
    @push('styles')
        <style>
            .kz-noscroll { scrollbar-width: none; }
            .kz-noscroll::-webkit-scrollbar { display: none; }
            .kz-rank { color: #050505; -webkit-text-stroke: 3px #71717a; font-family: 'Arial Black', Impact, sans-serif; }
            a:hover .kz-rank { -webkit-text-stroke-color: #dc2626; }
            @keyframes kzKen { from { transform: scale(1.04); } to { transform: scale(1.14); } }
            .kz-kenburns { animation: kzKen 14s ease-out forwards; }
            @keyframes kzProg { from { width: 0; } to { width: 100%; } }
            .kz-progress { animation: kzProg 7s linear forwards; }
            @media (prefers-reduced-motion: reduce) { .kz-kenburns, .kz-progress { animation: none; } }
        </style>
    @endpush
@endonce
