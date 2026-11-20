@php
    $fmtDate = function ($d) {
        try {
            return $d ? \Illuminate\Support\Carbon::parse($d)->locale('id')->translatedFormat('j M Y') : '';
        } catch (\Throwable $e) {
            return '';
        }
    };
    $m = $media;
    $eps = $m['episodes'];
    $first = $eps[0] ?? null;
    $last = $eps ? end($eps) : null;
    $facts = array_filter([
        'Tipe' => $m['kind'],
        'Status' => $m['status_label'],
        'Rilis' => $fmtDate($m['release_date']),
        'Musim' => $m['season'],
        'Studio' => $m['studio'],
        'Durasi' => $m['duration'],
        'Episode' => $m['total_episodes'] ? $m['total_episodes'] . ' episode' : '',
    ]);
@endphp

<div class="min-h-screen bg-[#0b0b0f] pb-28 text-zinc-100">
    @include('filament.streaming.partials.animek-subnav', ['active' => ''])

    {{-- ================= HEADER ================= --}}
    <section class="relative">
        <div class="absolute inset-x-0 top-0 h-[520px] overflow-hidden" aria-hidden="true">
            <img src="{{ $m['poster'] }}" alt="" class="h-full w-full scale-110 object-cover opacity-35 blur-2xl">
            <div class="absolute inset-0 bg-gradient-to-b from-[#0b0b0f]/30 via-[#0b0b0f]/80 to-[#0b0b0f]"></div>
        </div>

        <div class="relative mx-auto max-w-[1400px] px-4 pt-2 md:px-8">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/animek' }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-[12.5px] font-semibold text-zinc-200 ring-1 ring-white/10 transition hover:bg-white/20">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg> Kembali
            </a>

            <div class="mt-6 grid gap-6 md:grid-cols-[240px_minmax(0,1fr)] md:gap-10 lg:grid-cols-[280px_minmax(0,1fr)]">
                <div class="mx-auto w-[190px] md:mx-0 md:w-full">
                    <div class="overflow-hidden rounded-2xl shadow-2xl shadow-black/70 ring-1 ring-white/15">
                        <img src="{{ $m['poster'] }}" alt="{{ $m['title'] }}" class="aspect-[2/3] w-full object-cover">
                    </div>
                </div>

                <div class="min-w-0 text-center md:text-left">
                    <div class="flex flex-wrap items-center justify-center gap-2 md:justify-start">
                        @if($m['kind'])
                            <span class="rounded-md bg-white/10 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-zinc-200">{{ $m['kind'] }}</span>
                        @endif
                        @if($m['status'] === 'ongoing')
                            <span class="flex items-center gap-1 rounded-md bg-red-600 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>Sedang Tayang</span>
                        @elseif($m['status_label'])
                            <span class="rounded-md bg-emerald-500/15 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-emerald-300 ring-1 ring-emerald-500/30">{{ $m['status_label'] }}</span>
                        @endif
                    </div>

                    <h1 class="mt-3 text-3xl font-black leading-tight tracking-tight text-white md:text-5xl">{{ $m['title'] }}</h1>

                    <div class="mt-3 flex flex-wrap items-center justify-center gap-x-4 gap-y-1.5 text-sm text-zinc-300 md:justify-start">
                        @if($m['rating'])
                            <span class="flex items-center gap-1.5">
                                <span class="flex">
                                    @for($s = 1; $s <= 5; $s++)
                                        @php $fill = max(0, min(1, $m['rating'] / 2 - ($s - 1))); @endphp
                                        <span class="relative h-4 w-4">
                                            <svg class="absolute inset-0 h-4 w-4 fill-zinc-700" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                            <span class="absolute inset-0 overflow-hidden" style="width: {{ round($fill * 100) }}%">
                                                <svg class="h-4 w-4 fill-yellow-400" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                            </span>
                                        </span>
                                    @endfor
                                </span>
                                <span class="font-bold text-white">{{ number_format($m['rating'], 1) }}</span><span class="text-zinc-500">/10</span>
                            </span>
                        @endif
                        @if($m['year'])<span>{{ $m['year'] }}</span>@endif
                        @if($m['total_episodes'])<span>{{ $m['total_episodes'] }} episode</span>@endif
                        @if($m['studio'])<span>{{ $m['studio'] }}</span>@endif
                    </div>

                    @if(!empty($m['genre_links']))
                        <div class="mt-4 flex flex-wrap justify-center gap-2 md:justify-start">
                            @foreach($m['genre_links'] as $g)
                                <a href="/animek/genre?g={{ $g['id'] }}" class="rounded-full border border-white/15 bg-white/[0.05] px-3 py-1 text-[12px] font-semibold text-zinc-200 transition hover:border-red-500/60 hover:bg-red-600/15 hover:text-white">{{ $g['name'] }}</a>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center justify-center gap-2.5 md:justify-start">
                        @if($resume && $resume['url'])
                            <a href="{{ $resume['url'] }}" class="inline-flex items-center gap-2 rounded-full bg-red-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-red-600/25 transition hover:bg-red-500">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg> Lanjutkan Episode {{ $resume['number'] }}
                            </a>
                        @endif
                        @if($first && (!$resume || (int) $resume['number'] !== (int) $first['number']))
                            <a href="{{ $first['url'] }}" class="inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-bold transition {{ $resume ? 'bg-white/10 text-white ring-1 ring-white/15 hover:bg-white/15' : 'bg-white text-black hover:bg-zinc-200' }}">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg> Mulai dari Episode {{ $first['number'] }}
                            </a>
                        @endif
                        @if($last && $last !== $first)
                            <a href="{{ $last['url'] }}" class="inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-bold transition {{ $resume ? 'bg-white/10 text-white ring-1 ring-white/15 hover:bg-white/15' : 'bg-red-600 text-white hover:bg-red-500' }}">Episode Terbaru · {{ $last['number'] }}</a>
                        @endif
                        @if($resume && $resume['ago'])
                            <span class="basis-full text-center text-[12px] text-zinc-500 md:basis-auto md:text-left">Terakhir kamu tonton {{ $resume['ago'] }}</span>
                        @endif
                    </div>

                    @if($m['synopsis'])
                        <div class="mx-auto mt-7 max-w-3xl text-left md:mx-0" x-data="{ more: false }">
                            <h2 class="text-[11px] font-black uppercase tracking-[0.18em] text-zinc-500">Sinopsis</h2>
                            <p :class="more ? '' : 'line-clamp-4'" class="mt-2 text-[14.5px] leading-relaxed text-zinc-300">{{ $m['synopsis'] }}</p>
                            @if(mb_strlen($m['synopsis']) > 320)
                                <button type="button" @click="more = !more" class="mt-1.5 text-xs font-bold text-red-400 hover:text-red-300" x-text="more ? 'Tutup' : 'Baca selengkapnya'"></button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto mt-12 grid max-w-[1400px] gap-10 px-4 md:px-8 lg:grid-cols-[minmax(0,1fr)_300px]">

        {{-- ================= EPISODE ================= --}}
        <section x-data="{ q: '', desc: false }" class="min-w-0">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold tracking-tight">Episode <span class="text-sm font-semibold text-zinc-500">({{ count($eps) }})</span></h2>
                @if(count($eps) > 1)
                    <div class="flex items-center gap-2">
                        @if(count($eps) > 12)
                            <input x-model="q" type="search" inputmode="numeric" placeholder="Cari nomor…" aria-label="Cari nomor episode"
                                   class="w-32 rounded-full border border-white/10 bg-white/[0.05] px-3 py-1.5 text-[13px] text-white placeholder-zinc-500 outline-none focus:border-red-500/60">
                        @endif
                        <button type="button" @click="desc = !desc" class="rounded-full border border-white/10 bg-white/[0.05] px-3 py-1.5 text-[12.5px] font-semibold text-zinc-300 transition hover:bg-white/10"
                                x-text="desc ? 'Terbaru dulu' : 'Terlama dulu'"></button>
                    </div>
                @endif
            </div>

            @if(empty($eps))
                <p class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-6 text-center text-sm text-zinc-400">Belum ada episode yang bisa diputar.</p>
            @else
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 md:grid-cols-6 xl:grid-cols-8" :class="desc ? 'kz-ep-desc' : ''">
                    @foreach($eps as $n => $ep)
                        @php $isResume = $resume && (int) $resume['number'] === (int) $ep['number']; @endphp
                        <a href="{{ $ep['url'] }}"
                           x-show="q === '' || '{{ $ep['number'] }}'.includes(q.trim())"
                           :style="desc ? 'order: {{ count($eps) - $n }}' : 'order: {{ $n }}'"
                           title="{{ $ep['title'] }}"
                           class="group relative flex min-h-[78px] flex-col items-center justify-start rounded-xl border px-2 pb-2.5 pt-3 text-center transition {{ $isResume ? 'border-red-500/70 bg-red-600/15' : 'border-white/[0.07] bg-white/[0.03] hover:border-red-500/50 hover:bg-red-600/10' }}">
                            @if($isResume)
                                <span class="absolute -top-1.5 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-red-600 px-2 py-[2px] text-[9px] font-black uppercase tracking-wide text-white">Terakhir ditonton</span>
                            @endif
                            <span class="text-[10px] font-semibold uppercase tracking-wider {{ $isResume ? 'text-red-300' : 'text-zinc-500' }} group-hover:text-red-300">Episode</span>
                            <span class="mt-0.5 text-lg font-black text-white">{{ $ep['number'] }}</span>
                            <span class="mt-0.5 min-h-[15px] text-[10px] text-zinc-500">{{ $fmtDate($ep['date']) }}</span>
                            @if($isResume && $resume['percent'] > 0)
                                <span class="mt-auto block h-[3px] w-full overflow-hidden rounded-full bg-white/15">
                                    <span class="block h-full rounded-full bg-red-500" style="width: {{ min(100, $resume['percent']) }}%"></span>
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ================= INFO ================= --}}
        <aside class="lg:pt-[52px]">
            <div class="rounded-2xl border border-white/[0.07] bg-white/[0.03] p-5">
                <h2 class="text-[11px] font-black uppercase tracking-[0.18em] text-zinc-500">Informasi</h2>
                <dl class="mt-3 divide-y divide-white/[0.06] text-sm">
                    @foreach($facts as $label => $value)
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-zinc-500">{{ $label }}</dt>
                            <dd class="text-right font-semibold text-zinc-200">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </aside>
    </div>

    {{-- ================= MIRIP ================= --}}
    @if(!empty($similar))
        <div class="mx-auto max-w-[1400px] px-4 md:px-8">
            @include('filament.streaming.partials.animek-rail', [
                'title' => 'Mungkin Kamu Suka',
                'subtitle' => 'Dari genre ' . implode(' & ', array_column(array_slice($m['genre_links'], 0, 2), 'name')),
                'items' => $similar,
                'more' => !empty($m['genre_links']) ? '/animek/genre?g=' . $m['genre_links'][0]['id'] : null,
            ])
        </div>
    @endif
</div>
