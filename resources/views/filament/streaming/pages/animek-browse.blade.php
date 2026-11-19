@php
    use App\Services\AnimekompiService;

    $f = $filters;
    $r = $result;
    $link = function (array $changes) use ($f) {
        $q = array_filter(array_merge($f, $changes, ['page' => $changes['page'] ?? null]), fn ($v) => $v !== '' && $v !== null);
        if (($q['sort'] ?? '') === 'terbaru') unset($q['sort']);
        return '/animek/jelajah' . ($q ? '?' . http_build_query($q) : '');
    };
    $active = array_filter(['kind' => $f['kind'], 'status' => $f['status'], 'year' => $f['year'], 'q' => $f['q']]);
@endphp

<div class="min-h-screen bg-[#0b0b0f] pb-28 text-zinc-100">
    @include('filament.streaming.partials.animek-subnav', ['active' => 'jelajah'])

    <div class="mx-auto max-w-[1600px] px-4 md:px-8">
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black tracking-tight md:text-3xl">
                    @if($f['q'] !== '') Hasil untuk “{{ $f['q'] }}” @else Jelajah Anime @endif
                </h1>
                <p class="mt-1 text-sm text-zinc-400">{{ number_format($r['total'], 0, ',', '.') }} judul ditemukan</p>
            </div>
        </div>

        {{-- ================= FILTER ================= --}}
        <form method="get" action="/animek/jelajah" class="mt-5 rounded-2xl border border-white/[0.07] bg-white/[0.03] p-3 md:p-4">
            <div class="grid grid-cols-2 gap-2.5 md:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))_auto]">
                <div class="relative col-span-2 md:col-span-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ $f['q'] }}" placeholder="Cari judul…" aria-label="Cari judul"
                           class="w-full rounded-xl border border-white/10 bg-black/30 py-2.5 pl-9 pr-3 text-sm text-white placeholder-zinc-500 outline-none focus:border-red-500/60">
                </div>
                @foreach([
                    'kind' => ['Semua tipe', array_combine(AnimekompiService::KINDS, AnimekompiService::KINDS)],
                    'status' => ['Semua status', AnimekompiService::STATUSES],
                    'year' => ['Semua tahun', array_combine($years, $years)],
                    'sort' => [null, AnimekompiService::SORTS],
                ] as $name => [$placeholder, $options])
                    <select name="{{ $name }}" onchange="this.form.submit()" aria-label="{{ $placeholder ?? 'Urutkan' }}"
                            class="w-full appearance-none rounded-xl border border-white/10 bg-black/30 bg-[url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%23a1a1aa%22 stroke-width=%222%22><path d=%22M6 9l6 6 6-6%22/></svg>')] bg-[length:16px] bg-[right_10px_center] bg-no-repeat py-2.5 pl-3 pr-8 text-sm text-white outline-none focus:border-red-500/60 [&>option]:bg-zinc-900">
                        @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
                        @foreach($options as $value => $label)
                            <option value="{{ $value }}" @selected((string) $f[$name] === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endforeach
                <button type="submit" class="col-span-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-black transition hover:bg-zinc-200 md:col-span-1">Terapkan</button>
            </div>

            @if($active)
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @foreach($active as $key => $value)
                        <a href="{{ $link([$key => '']) }}" class="inline-flex items-center gap-1.5 rounded-full bg-red-600/15 px-3 py-1 text-[12px] font-semibold text-red-200 ring-1 ring-red-500/30 transition hover:bg-red-600/25">
                            {{ $key === 'status' ? (AnimekompiService::STATUSES[$value] ?? $value) : $value }}
                            <span aria-hidden="true">✕</span><span class="sr-only">hapus filter</span>
                        </a>
                    @endforeach
                    <a href="/animek/jelajah" class="text-[12px] font-semibold text-zinc-400 hover:text-white">Bersihkan semua</a>
                </div>
            @endif
        </form>

        {{-- ================= HASIL ================= --}}
        @if(empty($r['items']))
            <div class="mt-16 text-center">
                <p class="text-lg font-bold">Tidak ada judul yang cocok</p>
                <p class="mt-1 text-sm text-zinc-400">Coba kata kunci lain atau longgarkan filternya.</p>
                <a href="/animek/jelajah" class="mt-5 inline-flex rounded-full bg-white px-5 py-2 text-sm font-bold text-black">Lihat semua anime</a>
            </div>
        @else
            <div class="mt-6 grid grid-cols-3 gap-x-3 gap-y-5 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 2xl:grid-cols-8">
                @foreach($r['items'] as $item)
                    @include('filament.streaming.partials.animek-card', ['item' => $item])
                @endforeach
            </div>

            @if($r['pages'] > 1)
                @php
                    $p = $r['page'];
                    $window = array_unique(array_filter([1, $p - 2, $p - 1, $p, $p + 1, $p + 2, $r['pages']], fn ($n) => $n >= 1 && $n <= $r['pages']));
                    sort($window);
                @endphp
                <nav class="mt-10 flex flex-wrap items-center justify-center gap-1.5" aria-label="Halaman">
                    @if($p > 1)
                        <a href="{{ $link(['page' => $p - 1]) }}" class="rounded-lg bg-white/[0.06] px-3 py-2 text-sm font-semibold text-zinc-300 hover:bg-white/10">‹ Sebelumnya</a>
                    @endif
                    @php $prevN = 0; @endphp
                    @foreach($window as $n)
                        @if($n - $prevN > 1)<span class="px-1 text-zinc-600">…</span>@endif
                        <a href="{{ $link(['page' => $n > 1 ? $n : null]) }}"
                           class="min-w-[38px] rounded-lg px-3 py-2 text-center text-sm font-bold {{ $n === $p ? 'bg-white text-black' : 'bg-white/[0.06] text-zinc-300 hover:bg-white/10' }}">{{ $n }}</a>
                        @php $prevN = $n; @endphp
                    @endforeach
                    @if($p < $r['pages'])
                        <a href="{{ $link(['page' => $p + 1]) }}" class="rounded-lg bg-white/[0.06] px-3 py-2 text-sm font-semibold text-zinc-300 hover:bg-white/10">Berikutnya ›</a>
                    @endif
                </nav>
            @endif
        @endif
    </div>
</div>
