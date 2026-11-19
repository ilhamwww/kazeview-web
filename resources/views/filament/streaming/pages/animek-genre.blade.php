@php
    $palette = ['from-red-600/30', 'from-fuchsia-600/30', 'from-violet-600/30', 'from-sky-600/30', 'from-emerald-600/30', 'from-amber-500/30', 'from-rose-600/30', 'from-indigo-600/30'];
@endphp

<div class="min-h-screen bg-[#0b0b0f] pb-28 text-zinc-100">
    @include('filament.streaming.partials.animek-subnav', ['active' => 'genre'])

    <div class="mx-auto max-w-[1600px] px-4 md:px-8">
        @if($genre === '')
            {{-- ================= SEMUA GENRE ================= --}}
            <div class="mt-2" x-data="{ q: '' }">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-black tracking-tight md:text-3xl">Genre</h1>
                        <p class="mt-1 text-sm text-zinc-400">{{ count($genres) }} genre, pilih yang sesuai mood kamu.</p>
                    </div>
                    <input x-model="q" type="search" placeholder="Cari genre…" aria-label="Cari genre"
                           class="w-full rounded-full border border-white/10 bg-white/[0.05] px-4 py-2 text-sm text-white placeholder-zinc-500 outline-none focus:border-red-500/60 sm:w-64">
                </div>

                <div class="mt-6 grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    @foreach($genres as $n => $g)
                        <a href="/animek/genre?g={{ $g['id'] }}"
                           x-show="q === '' || @js(mb_strtolower($g['name'])).includes(q.toLowerCase().trim())"
                           class="group relative overflow-hidden rounded-xl border border-white/[0.07] bg-gradient-to-br {{ $palette[$n % count($palette)] }} to-transparent px-4 py-4 transition hover:-translate-y-0.5 hover:border-white/25">
                            <span class="block truncate text-[15px] font-bold text-white">{{ $g['name'] }}</span>
                            <span class="mt-0.5 block text-[11.5px] text-zinc-400">{{ number_format($g['count'], 0, ',', '.') }} judul</span>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 transition group-hover:translate-x-0.5 group-hover:text-white" aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            {{-- ================= SATU GENRE ================= --}}
            <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <a href="/animek/genre" class="text-xs font-semibold text-zinc-400 hover:text-white">← Semua genre</a>
                    <h1 class="mt-1 text-2xl font-black tracking-tight md:text-3xl">{{ $result['name'] }}</h1>
                    @php $meta = collect($genres)->firstWhere('id', $genre); @endphp
                    @if($meta)
                        <p class="mt-1 text-sm text-zinc-400">{{ number_format($meta['count'], 0, ',', '.') }} judul · halaman {{ $result['page'] }}</p>
                    @endif
                </div>
            </div>

            <div class="-mx-4 mt-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] md:-mx-8 md:px-8 [&::-webkit-scrollbar]:hidden">
                @foreach(array_slice($genres, 0, 24) as $g)
                    <a href="/animek/genre?g={{ $g['id'] }}"
                       class="shrink-0 rounded-full px-3.5 py-1.5 text-[12.5px] font-semibold transition {{ $g['id'] === $genre ? 'bg-white text-black' : 'border border-white/10 bg-white/[0.04] text-zinc-300 hover:bg-white/10 hover:text-white' }}">{{ $g['name'] }}</a>
                @endforeach
            </div>

            @if(empty($result['items']))
                <p class="mt-16 text-center text-sm text-zinc-400">Tidak ada judul lagi di halaman ini.</p>
            @else
                <div class="mt-6 grid grid-cols-3 gap-x-3 gap-y-5 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 2xl:grid-cols-8">
                    @foreach($result['items'] as $item)
                        @include('filament.streaming.partials.animek-card', ['item' => $item])
                    @endforeach
                </div>
            @endif

            <nav class="mt-10 flex items-center justify-center gap-2" aria-label="Halaman">
                @if($result['page'] > 1)
                    <a href="/animek/genre?g={{ $genre }}{{ $result['page'] - 1 > 1 ? '&page=' . ($result['page'] - 1) : '' }}" class="rounded-lg bg-white/[0.06] px-4 py-2 text-sm font-semibold text-zinc-300 hover:bg-white/10">‹ Sebelumnya</a>
                @endif
                <span class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-black">{{ $result['page'] }}</span>
                @if($result['has_next'])
                    <a href="/animek/genre?g={{ $genre }}&page={{ $result['page'] + 1 }}" class="rounded-lg bg-white/[0.06] px-4 py-2 text-sm font-semibold text-zinc-300 hover:bg-white/10">Berikutnya ›</a>
                @endif
            </nav>
        @endif
    </div>
</div>
