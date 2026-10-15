@php
    $star = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';
    $typeLabel = fn ($item) => ($item['is_series'] ?? false) ? 'Series' : 'Movie';
@endphp

<div class="min-h-screen bg-[#050505] text-white pb-32">

    {{-- ============ HERO ============ --}}
    @if(!empty($heroes))
    <section x-data="{
            active: 0,
            count: {{ count($heroes) }},
            timer: null,
            startX: null,
            init() { this.play(); },
            play() { this.stop(); this.timer = setInterval(() => this.next(false), 7000); },
            stop() { if (this.timer) { clearInterval(this.timer); this.timer = null; } },
            next(reset = true) { this.active = (this.active + 1) % this.count; if (reset) this.play(); },
            prev() { this.active = (this.active - 1 + this.count) % this.count; this.play(); },
            goTo(i) { this.active = i; this.play(); },
            touchStart(e) { this.startX = e.touches[0].clientX; },
            touchEnd(e) {
                if (this.startX === null) return;
                const dx = e.changedTouches[0].clientX - this.startX;
                if (Math.abs(dx) > 50) { dx < 0 ? this.next() : this.prev(); }
                this.startX = null;
            }
        }"
        @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)"
        class="relative w-full h-[78vh] md:h-[88vh] min-h-[520px] overflow-hidden bg-black">

        @foreach($heroes as $index => $hero)
        <div x-show="active === {{ $index }}"
             x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0" @if($index !== 0) style="display:none;" @endif>

            {{-- Background Picture: Poster vertikal di HP agar objek utama presisi di tengah, Banner landscape di Desktop --}}
            <picture class="absolute inset-0 w-full h-full">
                <source media="(max-width: 767px)" srcset="{{ $hero['thumbnail'] ?: $hero['banner'] }}">
                <img src="{{ $hero['banner'] ?: ($hero['thumbnail'] ?? '') }}" alt="{{ $hero['title'] ?? '' }}"
                     class="w-full h-full object-cover object-center"
                     @if($index === 0) fetchpriority="high" @else loading="lazy" @endif>
            </picture>

            {{-- Overlay: Gradien kiri hanya di desktop (agar di HP gambar tidak tertutup hitam di sisi kiri/tengah) --}}
            <div class="hidden md:block absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/80 to-transparent md:w-[75%]"></div>
            <div class="absolute top-0 inset-x-0 h-32 md:h-40 bg-gradient-to-b from-[#050505]/85 via-[#050505]/40 to-transparent"></div>
            <div class="absolute bottom-0 inset-x-0 h-[65%] md:h-[60%] bg-gradient-to-t from-[#050505] via-[#050505]/85 via-40% to-transparent"></div>

            <div class="relative z-10 h-full w-full max-w-[1800px] mx-auto px-5 md:px-10 flex flex-col justify-end pb-24 md:pb-32">
                <div class="max-w-2xl">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E50914] px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-widest">
                            <span class="h-1.5 w-1.5 rounded-full bg-white"></span> Unggulan #{{ $index + 1 }}
                        </span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest backdrop-blur">{{ $typeLabel($hero) }}</span>
                        @if(!empty($hero['quality']))
                        <span class="rounded-full border border-white/15 bg-black/40 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-zinc-200">{{ $hero['quality'] }}</span>
                        @endif
                    </div>

                    <h1 class="mt-4 text-3xl sm:text-5xl lg:text-6xl font-black leading-[1.05] tracking-tight line-clamp-2 drop-shadow-lg">{{ $hero['title'] ?? '' }}</h1>

                    <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs md:text-sm font-semibold text-zinc-300">
                        @if(!empty($hero['rating']))
                        <span class="flex items-center gap-1 text-yellow-400"><svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="{{ $star }}"/></svg>{{ $hero['rating'] }}</span>
                        @endif
                        @if(!empty($hero['year']))<span>{{ $hero['year'] }}</span>@endif
                        @if(!empty($hero['duration']))<span>{{ $hero['duration'] }}</span>@endif
                    </div>

                    @if(!empty($hero['genre']))
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach(array_slice(array_map('trim', explode(',', $hero['genre'])), 0, 4) as $g)
                        <span class="rounded-md border border-white/15 bg-black/50 px-2 py-0.5 text-[11px] font-semibold text-white">{{ $g }}</span>
                        @endforeach
                    </div>
                    @endif

                    <p class="mt-4 hidden sm:block max-w-xl text-sm md:text-base leading-relaxed text-zinc-200 line-clamp-3 drop-shadow">{{ $hero['description'] ?? '' }}</p>

                    <div class="mt-6 flex items-center gap-3">
                        <a href="{{ $hero['url'] }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-extrabold text-black shadow-lg transition active:scale-95 hover:bg-zinc-200">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            {{ ($hero['is_series'] ?? false) ? 'Lihat Episode' : 'Tonton Sekarang' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        {{-- Kontrol --}}
        <div class="absolute bottom-8 md:bottom-12 inset-x-0 z-20">
            <div class="max-w-[1800px] mx-auto px-5 md:px-10 flex items-center justify-between gap-4">
                <div class="flex items-center gap-1.5">
                    @foreach($heroes as $index => $hero)
                    <button type="button" @click="goTo({{ $index }})" aria-label="Slide {{ $index + 1 }}"
                            :class="active === {{ $index }} ? 'w-8 bg-[#E50914]' : 'w-2 bg-white/35'"
                            class="h-2 rounded-full transition-all duration-300"></button>
                    @endforeach
                </div>
                <div class="hidden md:flex items-center gap-2">
                    <button type="button" @click="prev()" aria-label="Sebelumnya" class="grid h-11 w-11 place-items-center rounded-full border border-white/15 bg-black/40 backdrop-blur transition hover:bg-white/15 active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" @click="next()" aria-label="Berikutnya" class="grid h-11 w-11 place-items-center rounded-full border border-white/15 bg-black/40 backdrop-blur transition hover:bg-white/15 active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </section>
    @else
    <div class="pt-24 md:pt-28"></div>
    @endif

    <div class="relative z-10 -mt-6 space-y-10 md:space-y-14">

        {{-- ============ KATEGORI CEPAT ============ --}}
        <section class="max-w-[1800px] mx-auto px-5 md:px-10">
            <div class="flex gap-2.5 overflow-x-auto pb-1 -mx-5 px-5 md:-mx-10 md:px-10 scroll-px-5 md:scroll-px-10 [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                @foreach($quickLinks as $link)
                <a href="{{ $link['url'] }}" class="group flex shrink-0 items-center gap-2 rounded-full border border-white/10 bg-zinc-900/80 px-4 py-2.5 text-sm font-semibold text-zinc-200 backdrop-blur transition hover:border-[#E50914]/60 hover:text-white active:scale-95">
                    <svg class="w-4 h-4 text-[#E50914]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/></svg>
                    {{ $link['label'] }}
                </a>
                @endforeach
            </div>
        </section>

        {{-- ============ LANJUTKAN MENONTON ============ --}}
        @if(!empty($continueWatching))
        <section class="max-w-[1800px] mx-auto px-5 md:px-10">
            <div class="mb-4 flex items-end justify-between">
                <div>
                    <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Lanjutkan Menonton</h2>
                    <p class="mt-0.5 text-xs text-zinc-400">Lanjut dari posisi terakhir kamu berhenti</p>
                </div>
            </div>
            <div class="flex gap-3 md:gap-4 overflow-x-auto pb-2 -mx-5 px-5 md:-mx-10 md:px-10 scroll-px-5 md:scroll-px-10 snap-x [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                @foreach($continueWatching as $cw)
                <a href="{{ $cw['url'] }}" class="group relative w-[124px] sm:w-[140px] md:w-[156px] shrink-0 snap-start">
                    <div class="relative aspect-[3/4] overflow-hidden rounded-xl bg-gradient-to-br from-zinc-800 to-zinc-950 ring-1 ring-white/5">
                        @if(!empty($cw['thumbnail']))
                        <img src="{{ $cw['thumbnail'] }}" alt="" loading="lazy" onerror="this.remove()" class="relative h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                        <div class="absolute inset-0 flex items-center justify-center text-zinc-700">
                            <svg class="w-10 h-10 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                        </div>
                        @endif
                        <div class="absolute inset-x-0 bottom-0 h-3/4 bg-gradient-to-t from-black via-black/80 to-transparent"></div>
                        <span class="absolute left-2 top-2 rounded-md bg-black/70 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-zinc-200">{{ $cw['label'] }}</span>
                        <div class="absolute inset-0 grid place-items-center">
                            <span class="grid h-10 w-10 place-items-center rounded-full bg-[#E50914]/90 shadow-lg shadow-black/50">
                                <svg class="ml-0.5 w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        </div>
                        <div class="absolute inset-x-2 bottom-2">
                            @if(!empty($cw['episode']) && $cw['label'] !== 'Film')
                                <p class="text-[11px] font-extrabold text-white drop-shadow">Episode {{ $cw['episode'] }}</p>
                            @endif
                            @if($cw['ago'])<p class="text-[10px] font-medium text-zinc-300 drop-shadow">{{ $cw['ago'] }}</p>@endif
                        </div>
                        {{-- Progress bar merah ala Netflix di bawah poster --}}
                        @if(!empty($cw['has_progress']))
                        <div class="absolute inset-x-0 bottom-0 h-[3px] bg-zinc-700/60 rounded-b-xl overflow-hidden">
                            <div class="h-full bg-[#E50914] rounded-b-xl" style="width: {{ min(100, $cw['progress_percent']) }}%"></div>
                        </div>
                        @endif
                    </div>
                    <h3 class="mt-2 truncate text-xs md:text-[13px] font-semibold text-zinc-200" title="{{ $cw['title'] }}">{{ $cw['title'] }}</h3>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- ============ TOP 10 ============ --}}
        @if(!empty($topTen))
        <section class="max-w-[1800px] mx-auto px-5 md:px-10">
            <div class="mb-4 flex items-center gap-3">
                <span class="rounded-md bg-[#E50914] px-2 py-1 text-xs font-black tracking-wider">TOP 10</span>
                <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Lagi Trending Hari Ini</h2>
            </div>
            <div class="flex gap-2 md:gap-3 overflow-x-auto pb-2 -mx-5 px-5 md:-mx-10 md:px-10 scroll-px-5 md:scroll-px-10 snap-x [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                @foreach($topTen as $rank => $item)
                <a href="{{ $item['url'] }}" class="group relative block w-[150px] md:w-[214px] shrink-0 snap-start">
                    <div class="relative flex items-end">
                        <span class="pointer-events-none select-none absolute left-0 bottom-[-6px] md:bottom-[-10px] text-[104px] md:text-[150px] font-black leading-none tracking-tighter text-[#050505]"
                              style="-webkit-text-stroke: 3px rgba(255,255,255,0.55);">{{ $rank + 1 }}</span>
                        <div class="relative z-10 ml-auto w-[96px] md:w-[136px] aspect-[2/3] overflow-hidden rounded-lg bg-zinc-900 ring-1 ring-white/10 shadow-xl shadow-black/70">
                            @if(!empty($item['thumbnail']))
                            <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] ?? '' }}" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @endif
                        </div>
                    </div>
                    <div class="ml-auto mt-2 w-[96px] md:w-[136px]">
                        <h3 class="truncate text-xs md:text-[13px] font-semibold text-zinc-200 group-hover:text-white" title="{{ $item['title'] ?? '' }}">{{ $item['title'] ?? '' }}</h3>
                        <p class="text-[11px] text-zinc-500">{{ $typeLabel($item) }}@if(!empty($item['year'])) · {{ $item['year'] }}@endif</p>
                    </div>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- ============ SECTIONS ============ --}}
        @foreach($sections as $sIndex => $section)

            @if($section['style'] === 'spotlight')
            <section class="max-w-[1800px] mx-auto px-5 md:px-10">
                <h2 class="mb-4 text-xl md:text-2xl font-extrabold tracking-tight">{{ $section['title'] }}</h2>
                <div class="flex gap-4 overflow-x-auto pb-2 -mx-5 px-5 md:-mx-10 md:px-10 scroll-px-5 md:scroll-px-10 snap-x snap-mandatory [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                    @foreach($section['items'] as $item)
                    <a href="{{ $item['url'] }}" class="group relative flex w-[86vw] sm:w-[440px] lg:w-[520px] shrink-0 snap-start gap-4 overflow-hidden rounded-2xl border border-white/5 bg-zinc-950 p-4">
                        <img src="{{ $item['banner'] ?: $item['thumbnail'] }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-40 transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-r from-[#050505] via-[#050505]/90 to-[#050505]/30"></div>
                        <div class="relative w-24 sm:w-28 aspect-[2/3] shrink-0 overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/10">
                            @if(!empty($item['thumbnail']))<img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] ?? '' }}" loading="lazy" class="h-full w-full object-cover">@endif
                        </div>
                        <div class="relative flex min-w-0 flex-1 flex-col justify-between py-0.5">
                            <div>
                                <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider">
                                    <span class="rounded bg-yellow-500/15 px-1.5 py-0.5 text-yellow-400">Sorotan</span>
                                    <span class="rounded bg-white/10 px-1.5 py-0.5 text-zinc-200">{{ $typeLabel($item) }}</span>
                                    @if(!empty($item['rating']))
                                    <span class="flex items-center gap-0.5 text-yellow-400"><svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="{{ $star }}"/></svg>{{ $item['rating'] }}</span>
                                    @endif
                                </div>
                                <h3 class="mt-2 text-base sm:text-lg font-extrabold leading-tight line-clamp-2 group-hover:text-[#ff4d55] transition">{{ $item['title'] ?? '' }}</h3>
                                <p class="mt-2 text-xs sm:text-[13px] leading-relaxed text-zinc-400 line-clamp-3">{{ $item['description'] ?: 'Belum ada sinopsis.' }}</p>
                            </div>
                            <span class="mt-3 inline-flex w-max items-center gap-1.5 rounded-lg bg-[#E50914] px-3 py-1.5 text-[11px] font-bold">
                                <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Tonton
                            </span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </section>
            @else
            <section class="max-w-[1800px] mx-auto px-5 md:px-10">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">{{ $section['title'] }}</h2>
                    @if($section['more_url'])
                    <a href="{{ $section['more_url'] }}" class="inline-flex shrink-0 items-center gap-1 text-xs md:text-sm font-semibold text-zinc-400 transition hover:text-white">
                        Lihat Semua
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    @endif
                </div>
                <div class="flex gap-3 md:gap-4 overflow-x-auto pb-2 -mx-5 px-5 md:-mx-10 md:px-10 scroll-px-5 md:scroll-px-10 snap-x [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                    @foreach($section['items'] as $item)
                    <a href="{{ $item['url'] }}" class="group w-[124px] sm:w-[150px] md:w-[170px] xl:w-[185px] shrink-0 snap-start">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/5">
                            @if(!empty($item['thumbnail']))
                            <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] ?? '' }}" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @endif
                            <div class="absolute inset-x-0 top-0 h-12 bg-gradient-to-b from-black/60 to-transparent"></div>
                            <div class="absolute inset-x-0 top-0 flex justify-between p-2">
                                <span class="rounded bg-black/70 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-zinc-200">{{ $typeLabel($item) }}</span>
                                @if(!empty($item['quality']))
                                <span class="rounded bg-black/70 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-sky-300">{{ $item['quality'] }}</span>
                                @endif
                            </div>
                            @if(!empty($item['rating']))
                            <span class="absolute bottom-2 left-2 flex items-center gap-0.5 rounded bg-black/75 px-1.5 py-0.5 text-[10px] font-extrabold text-yellow-400">
                                <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="{{ $star }}"/></svg>{{ $item['rating'] }}
                            </span>
                            @endif
                        </div>
                        <h3 class="mt-2 truncate text-xs md:text-[13px] font-semibold text-zinc-200 group-hover:text-white" title="{{ $item['title'] ?? '' }}">{{ $item['title'] ?? '' }}</h3>
                        <p class="text-[11px] text-zinc-500">{{ $item['year'] ?? '' }}@if(!empty($item['genre'])) · {{ trim(explode(',', $item['genre'])[0]) }}@endif</p>
                    </a>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- Strip Network setelah section ketiga --}}
            @if($sIndex === 2 && !empty($networks))
            <section class="max-w-[1800px] mx-auto px-5 md:px-10">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-xl md:text-2xl font-extrabold tracking-tight">Jelajahi per Network</h2>
                    <a href="{{ \App\Filament\Streaming\Pages\Network::getUrl(panel: 'streaming') }}" class="inline-flex shrink-0 items-center gap-1 text-xs md:text-sm font-semibold text-zinc-400 transition hover:text-white">
                        Lihat Semua
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="flex gap-3 md:grid md:grid-cols-5 md:gap-4 overflow-x-auto pb-2 -mx-5 px-5 md:mx-0 md:px-0 [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                    @foreach($networks as $net)
                    <a href="{{ $net['url'] }}" class="group relative flex h-24 md:h-28 w-[170px] md:w-auto shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br {{ $net['accent'] }} transition active:scale-95 hover:border-white/25">
                        <div class="absolute inset-x-0 bottom-0 h-1" style="background: {{ $net['color'] }};"></div>
                        <div class="h-9 md:h-10 w-[70%] flex items-center justify-center [&>svg]:max-h-full [&>svg]:max-w-full [&>svg]:w-auto [&>svg]:h-full">{!! $net['logo_svg'] !!}</div>
                        <span class="absolute right-2 top-2 rounded-full bg-black/50 px-2 py-0.5 text-[9px] font-bold text-zinc-300">{{ $net['badge'] }}</span>
                    </a>
                    @endforeach
                </div>
            </section>
            @endif

        @endforeach
    </div>
</div>
