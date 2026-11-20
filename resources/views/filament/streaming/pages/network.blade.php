@php $ready = collect($providers)->where('status', 'ready')->count(); @endphp

<div class="min-h-screen bg-[#0b0b0f] pb-24 text-zinc-100">
    <div class="mx-auto max-w-[1400px] px-4 pt-8 md:px-8 md:pt-12">

        <nav class="mb-5 flex items-center gap-2 text-xs text-zinc-500">
            <a href="/" class="hover:text-white">Home</a>
            <span>/</span>
            <span class="text-zinc-300">Network</span>
        </nav>

        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-black tracking-tight md:text-4xl">Network</h1>
                <p class="mt-2 max-w-2xl text-sm text-zinc-400">
                    Kumpulan penyedia yang kami kurasi. {{ $ready }} sudah bisa ditonton sekarang, sisanya segera menyusul.
                </p>
            </div>
            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-zinc-300">{{ count($providers) }} penyedia</span>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($providers as $p)
                @php $isReady = ($p['status'] ?? '') === 'ready'; @endphp
                <a href="{{ $p['route'] ?? ('/network/' . $p['id']) }}"
                   class="group relative overflow-hidden rounded-2xl border border-white/[0.08] bg-white/[0.03] p-4 transition hover:-translate-y-0.5 hover:border-white/20 hover:bg-white/[0.055]">
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br {{ $p['gradient'] }} opacity-70"></div>

                    <div class="relative flex items-start gap-3.5">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-black/40 text-lg font-black text-white ring-1 {{ $p['ring'] }}">{{ $p['initial'] }}</span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h2 class="truncate text-[17px] font-bold text-white">{{ $p['name'] }}</h2>
                                @if($isReady)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-emerald-300 ring-1 ring-emerald-500/30">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Tersedia
                                    </span>
                                @else
                                    <span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-zinc-300">Segera</span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-[11.5px] font-semibold uppercase tracking-wider text-zinc-400">{{ $p['tag'] }}</p>
                            <p class="mt-2 line-clamp-2 text-[13px] leading-snug text-zinc-300">{{ $p['desc'] }}</p>

                            <span class="mt-3 inline-flex items-center gap-1 text-[12.5px] font-semibold text-zinc-300 transition group-hover:text-white">
                                {{ $isReady ? 'Buka katalog' : 'Intip katalog' }}
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <p class="mt-8 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-[12.5px] leading-relaxed text-zinc-400">
            Penyedia bertanda <span class="font-semibold text-zinc-200">Segera</span> katalognya sudah kami tarik, tetapi pemutarannya belum kami buka.
            Kalau kamu menemukan judul yang ingin segera tersedia, kabari kami.
        </p>
    </div>
</div>
