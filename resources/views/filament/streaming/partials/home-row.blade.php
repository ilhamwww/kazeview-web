{{--
    Rak horizontal ala Netflix.
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

    <div class="mb-2.5 md:mb-3 flex items-end justify-between gap-3 px-4 md:px-12">
        <h2 class="text-[17px] md:text-[22px] font-bold tracking-tight text-zinc-100">
            @if($variant === 'top10')
                <span class="mr-2 inline-flex items-center rounded bg-[#E50914] px-1.5 py-0.5 align-middle text-[11px] font-black tracking-wider text-white">TOP 10</span>
            @endif
            {{ $title }}
        </h2>
        @if(!empty($moreUrl))
            <a href="{{ $moreUrl }}" class="inline-flex shrink-0 items-center gap-0.5 text-xs md:text-sm font-semibold text-zinc-400 transition hover:text-white">
                Lihat semua
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        @endif
    </div>

    <div class="group/row relative">
        {{-- Tombol geser (desktop) --}}
        <button type="button" @click="move(-1)" x-show="canLeft" x-transition.opacity aria-label="Geser ke kiri"
                class="absolute left-0 top-0 bottom-0 z-20 hidden md:flex w-12 items-center justify-center bg-gradient-to-r from-[#141414] via-[#141414]/80 to-transparent text-white opacity-0 transition group-hover/row:opacity-100 focus-visible:opacity-100">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" @click="move(1)" x-show="canRight" x-transition.opacity aria-label="Geser ke kanan"
                class="absolute right-0 top-0 bottom-0 z-20 hidden md:flex w-12 items-center justify-center bg-gradient-to-l from-[#141414] via-[#141414]/80 to-transparent text-white opacity-0 transition group-hover/row:opacity-100 focus-visible:opacity-100">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>

        <div x-ref="track" @scroll.passive.debounce.80ms="update()"
             class="flex gap-2 md:gap-2.5 overflow-x-auto overflow-y-visible px-4 md:px-12 pb-3 pt-1 scroll-px-4 md:scroll-px-12 snap-x [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">

            @foreach($items as $rank => $item)
                @if($variant === 'top10')
                    <a href="{{ $item['url'] }}" class="group relative flex shrink-0 snap-start items-end pl-1">
                        {{-- Slot angka lebar tetap, rata kanan: jarak ke poster sama untuk semua peringkat --}}
                        <span class="pointer-events-none select-none -mr-1 md:-mr-2 w-[62px] md:w-[92px] shrink-0 text-right whitespace-nowrap text-[112px] md:text-[168px] font-black leading-[0.8] text-[#141414] {{ $rank === 9 ? 'tracking-[-0.14em]' : 'tracking-[-0.06em]' }}"
                              style="-webkit-text-stroke: 3px #a3a3a3;">{{ $rank + 1 }}</span>
                        <div class="relative z-10 w-[94px] md:w-[134px] aspect-[2/3] overflow-hidden rounded-[4px] bg-zinc-900 shadow-lg shadow-black/70 transition duration-300 group-hover:scale-[1.04]">
                            @if(!empty($item['thumbnail']))
                                <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] ?? '' }}" loading="lazy" class="h-full w-full object-cover">
                            @endif
                        </div>
                    </a>
                @else
                    <a href="{{ $item['url'] }}" class="group w-[112px] sm:w-[136px] md:w-[166px] xl:w-[188px] shrink-0 snap-start">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-[4px] bg-zinc-900 transition duration-300 group-hover:scale-[1.04] group-hover:ring-2 group-hover:ring-white/70">
                            @if(!empty($item['thumbnail']))
                                <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] ?? '' }}" loading="lazy" class="h-full w-full object-cover">
                            @endif
                            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/85 to-transparent opacity-0 transition group-hover:opacity-100"></div>
                            @if(!empty($item['rating']))
                                <span class="absolute bottom-1.5 left-1.5 flex items-center gap-0.5 text-[10px] font-bold text-white opacity-0 transition group-hover:opacity-100">
                                    <svg class="w-3 h-3 fill-yellow-400" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $star }}"/></svg>{{ $item['rating'] }}
                                </span>
                            @endif
                        </div>
                        <h3 class="mt-1.5 truncate text-[12px] md:text-[13px] font-medium text-zinc-300 group-hover:text-white" title="{{ $item['title'] ?? '' }}">{{ $item['title'] ?? '' }}</h3>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</section>
