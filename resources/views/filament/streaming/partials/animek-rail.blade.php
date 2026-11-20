@if(!empty($items))
<section class="mt-10">
    <div class="mb-3 flex items-end justify-between gap-3">
        <div>
            <h2 class="text-[17px] font-bold tracking-tight text-white md:text-[21px]">{{ $title }}</h2>
            @if(!empty($subtitle))
                <p class="mt-0.5 text-xs text-zinc-500">{{ $subtitle }}</p>
            @endif
        </div>
        @if(!empty($more))
            <a href="{{ $more }}" class="shrink-0 text-xs font-semibold text-zinc-400 transition hover:text-white">Lihat semua →</a>
        @endif
    </div>
    <div class="flex snap-x gap-3 overflow-x-auto overflow-y-hidden pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        @foreach($items as $item)
            <div class="w-[124px] shrink-0 snap-start sm:w-[142px] md:w-[160px]">
                @include('filament.streaming.partials.animek-card', ['item' => $item, 'useEpisode' => $useEpisode ?? false])
            </div>
        @endforeach
    </div>
</section>
@endif
