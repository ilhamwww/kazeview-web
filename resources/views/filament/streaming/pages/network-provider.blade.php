@php
    $p = $providerData;
    $star = 'M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';
@endphp

<div class="min-h-screen bg-[#0b0b0f] pb-24 text-zinc-100">
    <div class="mx-auto max-w-[1500px] px-4 pt-8 md:px-8 md:pt-12">

        <nav class="mb-5 flex items-center gap-2 text-xs text-zinc-500">
            <a href="/" class="hover:text-white">Home</a>
            <span>/</span>
            <a href="/network" class="hover:text-white">Network</a>
            <span>/</span>
            <span class="text-zinc-300">{{ $p['name'] }}</span>
        </nav>

        <div class="relative overflow-hidden rounded-2xl border border-white/[0.08] bg-white/[0.03] p-5 md:p-7">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br {{ $p['gradient'] }} opacity-60"></div>
            <div class="relative flex flex-wrap items-center gap-4">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-black/45 text-xl font-black text-white ring-1 {{ $p['ring'] }}">{{ $p['initial'] }}</span>
                <div class="min-w-0">
                    <h1 class="text-2xl font-black tracking-tight md:text-3xl">{{ $p['name'] }}</h1>
                    <p class="mt-1 text-[13px] text-zinc-300">{{ $p['desc'] }}</p>
                </div>
            </div>
        </div>

        @if(!$playable)
            <div class="mt-4 flex items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-[13px] leading-relaxed text-amber-200">
                <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <span>Pemutaran untuk {{ $p['name'] }} belum kami buka. Katalog di bawah ini adalah pratinjau isi penyedianya.</span>
            </div>
        @endif

        @if(empty($items))
            <p class="mt-8 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-8 text-center text-sm text-zinc-400">
                Katalog {{ $p['name'] }} belum bisa kami tarik saat ini. Coba lagi nanti.
            </p>
        @else
            <div class="mt-7 flex items-end justify-between gap-3">
                <h2 class="text-[17px] font-bold tracking-tight md:text-[21px]">Katalog</h2>
                <span class="text-xs text-zinc-500">{{ count($items) }} judul</span>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7">
                @foreach($items as $item)
                    <div class="group">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-white/10 transition group-hover:ring-2 group-hover:ring-red-500/50">
                            @if(!empty($item['cover']))
                                <img src="{{ $item['cover'] }}" alt="{{ $item['title'] }}" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <div class="grid h-full w-full place-items-center text-2xl font-black text-zinc-700">{{ mb_substr($item['title'], 0, 1) }}</div>
                            @endif
                            @if(!empty($item['year']))
                                <span class="absolute bottom-1.5 left-1.5 rounded bg-black/75 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $item['year'] }}</span>
                            @endif
                            @if(!$playable)
                                <span class="absolute right-1.5 top-1.5 rounded bg-black/75 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wide text-zinc-300">Segera</span>
                            @endif
                        </div>
                        <h3 class="mt-1.5 truncate text-[12px] font-semibold text-zinc-300" title="{{ $item['title'] }}">{{ $item['title'] }}</h3>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
