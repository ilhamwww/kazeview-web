{{--
    Progress EXP ke level berikutnya (gaya standar KZSTRM: zinc + merah).
    $kzExp dari ExpService::navSummary(). $variant: 'pill' (topbar desktop), 'compact' (topbar HP), 'card' (dropdown / sheet).
--}}
@php
    $nf = fn ($n) => number_format((int) $n, 0, ',', '.');
    $nextLv = $kzExp['is_max'] ? $kzExp['level'] : $kzExp['level'] + 1;
    $label = $kzExp['is_max']
        ? 'Level maksimal'
        : $nf($kzExp['exp'] - $kzExp['current_min']) . ' / ' . $nf($kzExp['next_min'] - $kzExp['current_min']) . ' EXP ke Lv ' . $nextLv;
    $todayPct = (int) round(min(100, $kzExp['today'] / max(1, \App\Services\ExpService::DAILY_CAP) * 100));
@endphp

@if ($variant === 'pill')
    <a href="/leaderboard" title="Lv {{ $kzExp['level'] }} → Lv {{ $nextLv }} · {{ $label }} · Hari ini {{ $kzExp['today'] }}/{{ \App\Services\ExpService::DAILY_CAP }} EXP"
       class="group hidden xl:flex shrink-0 whitespace-nowrap items-center gap-2 pl-1 pr-3 py-1 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 transition">
        <img src="{{ $kzExp['badge'] }}" alt="" class="w-6 h-6 object-contain" width="24" height="24">
        <span class="text-[11px] font-black text-white tabular-nums">Lv {{ $kzExp['level'] }}</span>
        <span class="w-20 h-1.5 shrink-0 rounded-full bg-white/15 overflow-hidden" role="progressbar" aria-valuenow="{{ $kzExp['progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progres ke level {{ $nextLv }}">
            <span class="block h-full rounded-full bg-gradient-to-r from-red-700 to-red-500" style="width: {{ $kzExp['progress'] > 0 ? max(4, $kzExp['progress']) : 0 }}%"></span>
        </span>
        <span class="text-[11px] font-bold text-zinc-400 tabular-nums">{{ $kzExp['is_max'] ? 'MAX' : 'Lv ' . $nextLv }}</span>
    </a>
@elseif ($variant === 'compact')
    <a href="/leaderboard" aria-label="Lv {{ $kzExp['level'] }}, {{ $label }}"
       class="flex shrink-0 whitespace-nowrap items-center gap-1.5 pl-0.5 pr-2.5 py-0.5 rounded-full bg-black/40 backdrop-blur border border-white/10 active:bg-white/10 transition">
        <img src="{{ $kzExp['badge'] }}" alt="" class="w-6 h-6 object-contain" width="24" height="24">
        <span class="flex flex-col gap-0.5 min-w-0">
            <span class="flex items-center justify-between gap-2 text-[9px] font-black leading-none tabular-nums">
                <span class="text-white">Lv {{ $kzExp['level'] }}</span>
                <span class="text-zinc-500">{{ $kzExp['is_max'] ? 'MAX' : $nextLv }}</span>
            </span>
            <span class="w-12 min-[360px]:w-14 h-1 rounded-full bg-white/15 overflow-hidden" role="progressbar" aria-valuenow="{{ $kzExp['progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progres ke level {{ $nextLv }}">
                <span class="block h-full rounded-full bg-gradient-to-r from-red-700 to-red-500" style="width: {{ $kzExp['progress'] > 0 ? max(4, $kzExp['progress']) : 0 }}%"></span>
            </span>
        </span>
    </a>
@else
    <a href="/leaderboard" class="block p-3 rounded-xl bg-white/[0.04] hover:bg-white/[0.07] border border-white/[0.06] transition">
        <div class="flex items-center gap-2.5">
            <img src="{{ $kzExp['badge'] }}" alt="Badge level {{ $kzExp['level'] }}" class="w-9 h-9 object-contain shrink-0" width="36" height="36">
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-black text-white">Lv {{ $kzExp['level'] }} <span class="font-semibold text-zinc-400">· {{ $kzExp['title'] }}</span></p>
                    <p class="text-[10px] font-bold text-zinc-400 tabular-nums">{{ $kzExp['is_max'] ? 'MAX' : 'Lv ' . $nextLv }}</p>
                </div>
                <div class="mt-1.5 h-1.5 rounded-full bg-white/10 overflow-hidden" role="progressbar" aria-valuenow="{{ $kzExp['progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progres ke level {{ $nextLv }}">
                    <div class="h-full rounded-full bg-gradient-to-r from-red-700 to-red-500" style="width: {{ $kzExp['progress'] > 0 ? max(4, $kzExp['progress']) : 0 }}%"></div>
                </div>
                <p class="mt-1 text-[10px] text-zinc-400 tabular-nums">{{ $label }}</p>
            </div>
        </div>
        <div class="mt-2.5 pt-2.5 border-t border-white/[0.06]">
            <div class="flex items-center justify-between text-[10px] tabular-nums">
                <span class="font-bold text-zinc-300">EXP hari ini</span>
                <span class="font-black {{ $kzExp['today_left'] === 0 ? 'text-red-400' : 'text-white' }}">{{ $kzExp['today'] }} / {{ \App\Services\ExpService::DAILY_CAP }}</span>
            </div>
            <div class="mt-1 h-1 rounded-full bg-white/10 overflow-hidden" role="progressbar" aria-valuenow="{{ $todayPct }}" aria-valuemin="0" aria-valuemax="100" aria-label="EXP harian">
                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $todayPct }}%"></div>
            </div>
            @if ($kzExp['today_left'] === 0)
                <p class="mt-1 text-[10px] text-red-400">Batas harian tercapai, lanjut besok ya.</p>
            @endif
        </div>
    </a>
@endif
