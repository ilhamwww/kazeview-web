{{--
    Rak horizontal dengan gaya yang sama seperti /movies dan /tv-series
    (kartu rounded-xl, ring tipis, badge tipe + rating kuning, judul merah saat hover).
    Param: $title, $items, $moreUrl (opsional), $variant ('poster' | 'top10')
--}}
@php
    $variant = $variant ?? 'poster';
    $star = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';
@endphp

<section class="kz-row relative" x-data="{
        canLeft: false,
        canRight: true,
        update() {
            const t = this.$refs.track;
            this.canLeft = t.scrollLeft > 8;
            this.canRight = t.scrollLeft + t.clientWidth < t.scrollWidth - 8;
        },
        move(dir) {
            const t = this.$refs.track;
            t.scrollBy({ left: dir * t.clientWidth * 0.86, behavior: 'smooth' });
        }
    }" x-init="$nextTick(() => update())">

    <div class="mb-3 flex items-end justify-between gap-3 px-6 md:px-12">
        <h2 class="text-xl md:text-2xl font-black tracking-tight text-white">{{ $title }}</h2>
        @if(!empty($moreUrl))
            <a href="{{ $moreUrl }}" class="inline-flex shrink-0 items-center gap-1 text-xs md:text-sm font-semibold text-zinc-400 transition hover:text-white">
                Lihat semua
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        @endif
    </div>

    <div class="group/row relative">
        <button type="button" @click="move(-1)" x-show="canLeft" x-transition.opacity aria-label="Geser ke kiri"
                class="absolute left-0 top-0 bottom-0 z-20 hidden md:flex w-12 items-center justify-center bg-gradient-to-r from-[#050505] via-[#050505]/80 to-transparent text-white opacity-0 transition group-hover/row:opacity-100 focus-visible:opacity-100">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" @click="move(1)" x-show="canRight" x-transition.opacity aria-label="Geser ke kanan"
                class="absolute right-0 top-0 bottom-0 z-20 hidden md:flex w-12 items-center justify-center bg-gradient-to-l from-[#050505] via-[#050505]/80 to-transparent text-white opacity-0 transition group-hover/row:opacity-100 focus-visible:opacity-100">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>

        <div x-ref="track" @scroll.passive.debounce.80ms="update()"
             class="flex gap-x-4 overflow-x-auto overflow-y-hidden px-6 md:px-12 pb-6 pt-1 scroll-px-6 md:scroll-px-12 snap-x [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">

            @foreach($items as $rank => $item)
                @php
                    $isSeries = !empty($item['is_series']);
                    $kindLabel = $isSeries ? 'Series' : 'Film';
                    $year = trim((string) ($item['year'] ?? ''));
                @endphp

                @if($variant === 'top10')
                    <a href="{{ $item['url'] }}" class="group relative block w-[130px] sm:w-[156px] md:w-[186px] shrink-0 snap-start">
                        <div class="relative flex items-end">
                            <span class="pointer-events-none absolute bottom-[-6px] left-0 select-none text-[96px] font-black leading-none tracking-tighter text-[#050505] md:bottom-[-10px] md:text-[136px]"
                                  style="-webkit-text-stroke: 3px rgba(255,255,255,0.55);" aria-hidden="true">{{ $rank + 1 }}</span>
                            <div class="relative z-10 ml-auto w-[92px] md:w-[124px] aspect-[2/3] overflow-hidden rounded-lg bg-zinc-900 ring-1 ring-white/10 shadow-xl shadow-black/70">
                                @if(!empty($item['thumbnail']))
                                    <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] ?? '' }}" loading="lazy"
                                         class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @endif
                            </div>
                        </div>
                        <div class="ml-auto mt-2 w-[92px] md:w-[124px]">
                            <h3 class="truncate text-xs md:text-[13px] font-semibold text-zinc-200 group-hover:text-white" title="{{ $item['title'] ?? '' }}">{{ $item['title'] ?? '' }}</h3>
                            <p class="text-[11px] text-zinc-500">{{ $kindLabel }}@if($year !== '') · {{ $year }}@endif</p>
                        </div>
                    </a>
                @else
                    <a href="{{ $item['url'] }}" class="group block w-[136px] sm:w-[158px] md:w-[182px] xl:w-[200px] shrink-0 snap-start">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/5 shadow-lg shadow-black/40 transition group-hover:ring-red-600/60">
                            @if(!empty($item['thumbnail']))
                                <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] ?? '' }}" loading="lazy"
                                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-xs text-zinc-600">No Image</div>
                            @endif

                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/90 via-black/0 to-black/0 opacity-70 transition group-hover:opacity-100"></div>

                            <div class="absolute left-2 top-2 flex flex-wrap items-center gap-1">
                                <span class="rounded bg-black/70 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-zinc-200">{{ $kindLabel }}</span>
                                @if(!empty($item['episode']))
                                    <span class="rounded bg-black/70 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-sky-400">Ep {{ $item['episode'] }}</span>
                                @endif
                            </div>

                            @if(!empty($item['rating']))
                                <span class="absolute bottom-2 left-2 flex items-center gap-0.5 rounded bg-black/75 px-1.5 py-0.5 text-[10px] font-black text-yellow-400">
                                    <svg class="h-2.5 w-2.5 fill-current" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $star }}"/></svg> {{ number_format((float) $item['rating'], 1) }}
                                </span>
                            @endif
                        </div>

                        <h3 class="mt-2 truncate text-xs md:text-[13px] font-semibold text-zinc-200 transition group-hover:text-red-500" title="{{ $item['title'] ?? '' }}">{{ $item['title'] ?? '' }}</h3>
                        <p class="text-[11px] text-zinc-500">{{ $year !== '' ? $year : $kindLabel }}@if($year !== '') · {{ $kindLabel }}@endif</p>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</section>
