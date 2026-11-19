@php
    $star = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';
@endphp

<div class="min-h-screen bg-[#0b0b0d] text-zinc-100 pb-24">
    <div class="mx-auto max-w-[1600px] px-4 pt-6 md:px-8 md:pt-10">

        <nav class="mb-6 flex items-center gap-2 text-xs text-zinc-400">
            <a href="/" class="hover:text-white">Home</a>
            <span>/</span>
            <span class="text-zinc-300">Drama Asia</span>
        </nav>

        <div class="mb-8 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black tracking-tight md:text-4xl">Drama Asia</h1>
                <p class="mt-2 max-w-2xl text-sm text-zinc-400">
                    Serial drama Asia pilihan, termasuk drama Indonesia. Kualitas sampai 720P dengan subtitle Indonesia.
                </p>
            </div>
            <span class="rounded-full border border-emerald-500/40 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300">
                {{ count($items) }} judul
            </span>
        </div>

        @if(empty($items))
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-6 text-sm text-zinc-400">
                Katalog drama sedang tidak bisa dimuat. Coba lagi beberapa saat.
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7">
                @foreach($items as $item)
                    <a href="{{ $item['url'] }}" class="group">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-lg bg-zinc-900 ring-1 ring-white/5 transition duration-300 group-hover:ring-2 group-hover:ring-red-500/70">
                            @if(!empty($item['thumbnail']))
                                <img src="{{ $item['thumbnail'] }}" alt="{{ $item['title'] }}" loading="lazy"
                                     class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @endif
                            <div class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-black/90 to-transparent"></div>

                            <span class="absolute left-1.5 top-1.5 rounded bg-red-600/90 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-white">ORIGINAL</span>

                            @if(!empty($item['badge']))
                                <span class="absolute right-1.5 top-1.5 rounded bg-black/70 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-300">{{ $item['badge'] }}</span>
                            @endif

                            @if(!empty($item['rating']))
                                <span class="absolute bottom-1.5 left-1.5 flex items-center gap-0.5 text-[10px] font-bold text-white">
                                    <svg class="h-3 w-3 fill-yellow-400" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $star }}"/></svg>{{ number_format($item['rating'], 1) }}
                                </span>
                            @endif
                        </div>
                        <h3 class="mt-1.5 truncate text-[13px] font-semibold text-zinc-200 group-hover:text-white" title="{{ $item['title'] }}">{{ $item['title'] }}</h3>
                        @if(!empty($item['status']))
                            <p class="truncate text-[11px] text-zinc-500">{{ $item['status'] }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
