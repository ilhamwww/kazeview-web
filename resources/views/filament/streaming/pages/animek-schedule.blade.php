<div class="min-h-screen bg-[#0b0b0f] pb-28 text-zinc-100"
     x-data="{ day: @js($today) }">
    @include('filament.streaming.partials.animek-subnav', ['active' => 'jadwal'])

    <div class="mx-auto max-w-[1600px] px-4 md:px-8">
        <div class="mt-2">
            <h1 class="text-2xl font-black tracking-tight md:text-3xl">Jadwal Tayang</h1>
            <p class="mt-1 text-sm text-zinc-400">Rilis episode baru tiap hari, jam dalam WIB.</p>
        </div>

        <div class="-mx-4 mt-5 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] md:-mx-8 md:px-8 [&::-webkit-scrollbar]:hidden" role="tablist">
            @foreach($days as $d)
                <button type="button" role="tab" @click="day = @js($d['day'])"
                        :aria-selected="day === @js($d['day'])"
                        :class="day === @js($d['day']) ? 'bg-white text-black' : 'bg-white/[0.05] text-zinc-300 hover:bg-white/10'"
                        class="flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-[13px] font-bold transition">
                    {{ $d['day'] }}
                    @if($d['day'] === $today)
                        <span class="rounded-full bg-red-600 px-1.5 py-px text-[9.5px] font-black uppercase text-white">Hari ini</span>
                    @endif
                    <span class="text-[11px] font-semibold opacity-60">{{ count($d['items']) }}</span>
                </button>
            @endforeach
        </div>

        @foreach($days as $d)
            <section x-show="day === @js($d['day'])" x-cloak class="mt-6">
                @if(empty($d['items']))
                    <p class="py-16 text-center text-sm text-zinc-400">Tidak ada jadwal untuk hari {{ $d['day'] }}.</p>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 md:gap-4">
                        @foreach($d['items'] as $item)
                            <a href="{{ $item['url'] }}" class="group block">
                                <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/10 transition group-hover:ring-red-500/50">
                                    @if(!empty($item['thumbnail']))
                                        <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}" loading="lazy"
                                             class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    @endif

                                    <span class="absolute left-2 top-2 rounded-md bg-black/80 px-1.5 py-0.5 text-[10px] font-black tabular-nums text-white backdrop-blur-sm">
                                        {{ $item['time'] ?: '—' }} WIB
                                    </span>

                                    @if(!empty($item['episode']))
                                        <span class="absolute inset-x-2 bottom-2 rounded-md bg-red-600/90 px-1.5 py-0.5 text-center text-[10px] font-bold text-white">
                                            Episode {{ $item['episode'] }}
                                        </span>
                                    @endif
                                </div>
                                <h3 class="mt-2 line-clamp-2 text-xs font-semibold leading-snug text-zinc-200 transition group-hover:text-white md:text-[13px]">{{ $item['title'] }}</h3>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
