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
                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($d['items'] as $item)
                            <a href="{{ $item['url'] }}"
                               class="group flex items-center gap-3.5 rounded-xl border border-white/[0.06] bg-white/[0.03] p-2.5 transition hover:border-red-500/40 hover:bg-white/[0.06]">
                                <div class="w-14 shrink-0 text-center">
                                    <span class="block text-lg font-black tabular-nums text-white">{{ $item['time'] ?: '—' }}</span>
                                    <span class="block text-[10px] font-semibold uppercase tracking-wider text-zinc-500">WIB</span>
                                </div>
                                <img src="{{ $item['thumbnail'] }}" alt="" loading="lazy" class="aspect-[2/3] w-[50px] shrink-0 rounded-lg object-cover ring-1 ring-white/10">
                                <div class="min-w-0 flex-1">
                                    <h3 class="line-clamp-2 text-[13.5px] font-semibold leading-snug text-zinc-100 group-hover:text-white">{{ $item['title'] }}</h3>
                                    @if(!empty($item['episode']))
                                        <p class="mt-1 text-[11.5px] font-bold text-red-400">Episode {{ $item['episode'] }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
