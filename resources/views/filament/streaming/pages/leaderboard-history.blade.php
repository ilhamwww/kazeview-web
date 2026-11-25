@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&display=swap" rel="stylesheet">

<div class="min-h-screen bg-[#0b0907] text-amber-50">
    <section class="border-b border-amber-900/40">
        <div class="mx-auto w-full max-w-4xl px-5 md:px-8 pt-24 md:pt-32 pb-10 text-center">
            <p class="text-[11px] font-bold uppercase tracking-[0.35em] text-amber-500/80" style="font-family:Cinzel,serif">Hall of Champions</p>
            <h1 class="mt-3 text-4xl md:text-5xl font-black" style="font-family:Cinzel,serif;background:linear-gradient(180deg,#fde9a8,#e2b04a 45%,#9a6a1c);-webkit-background-clip:text;background-clip:text;color:transparent">Riwayat Season</h1>
            <p class="mt-4 text-sm md:text-base text-amber-100/60">Catatan level dan poin kamu di setiap season yang sudah selesai.</p>
            <div class="mt-5 flex flex-wrap items-center justify-center gap-2 text-[12px]">
                <a href="/leaderboard" class="rounded-full border border-amber-500/40 bg-amber-500/10 px-4 py-1.5 font-bold text-amber-300 hover:bg-amber-500/20">← Leaderboard</a>
                @if($season)
                    <span class="rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-amber-100/70">Season {{ $season->roman }} berjalan</span>
                @endif
            </div>
        </div>
    </section>

    <main class="mx-auto w-full max-w-4xl px-5 md:px-8 py-8 space-y-6">

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-amber-900/40 bg-gradient-to-b from-amber-950/40 to-black/40 p-5 text-center">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-500/80">Level Tertinggi</p>
                <p class="mt-2 text-3xl font-black text-amber-200">{{ $bestLevel ?: '—' }}</p>
                <p class="text-[11px] text-amber-100/50">dari season yang selesai</p>
            </div>
            <div class="rounded-2xl border border-amber-900/40 bg-gradient-to-b from-amber-950/40 to-black/40 p-5 text-center">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-500/80">Poin Tertinggi</p>
                <p class="mt-2 text-3xl font-black text-amber-200">{{ $bestPoints ? $fmt($bestPoints) : '—' }}</p>
                <p class="text-[11px] text-amber-100/50">poin dalam satu season</p>
            </div>
            <div class="rounded-2xl border border-amber-900/40 bg-gradient-to-b from-amber-950/40 to-black/40 p-5 text-center">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-500/80">Season Aktif</p>
                <p class="mt-2 text-3xl font-black text-amber-200">{{ $season?->roman ?? '—' }}</p>
                <p class="text-[11px] text-amber-100/50">Lv {{ $me['level'] }} · {{ $fmt($me['exp']) }} poin</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-amber-900/40">
            <table class="w-full text-left text-sm">
                <thead class="bg-amber-950/40 text-[11px] uppercase tracking-wider text-amber-100/60">
                    <tr>
                        <th class="px-4 py-3">Season</th>
                        <th class="px-4 py-3 text-right">Poin</th>
                        <th class="px-4 py-3 text-right">Level</th>
                        <th class="px-4 py-3 text-right">Peringkat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($rows as $row)
                        <tr class="bg-white/[0.02]">
                            <td class="px-4 py-4 font-bold text-amber-200">Season {{ $row->season?->roman ?? '?' }}</td>
                            <td class="px-4 py-4 text-right text-amber-100/80">{{ $fmt($row->points) }}</td>
                            <td class="px-4 py-4 text-right text-amber-100/80">Lv {{ $row->level }}</td>
                            <td class="px-4 py-4 text-right text-amber-100/80">{{ $row->rank ? '#' . $row->rank : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-amber-100/50">
                                Belum ada season yang selesai. Season {{ $season?->roman ?? 'I' }} masih berjalan — hasilnya akan muncul di sini setelah reset.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="text-center text-[11px] text-amber-100/40">
            Reset pertama: 1 Januari 2027 00:00 WIB · setiap season berjalan 100 hari
        </p>
    </main>
</div>
