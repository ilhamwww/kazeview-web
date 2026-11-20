{{--
    Topbar ringkas: tombol kembali bulat, judul + subjudul, badge kanan.
    Param: $backUrl, $title, $subtitle, $badge (opsional), $aria (opsional)
--}}
<div class="flex items-center gap-3 h-14 lg:h-12">
    <a href="{{ $backUrl }}" aria-label="{{ $aria ?? 'Kembali' }}"
       class="w-10 h-10 shrink-0 inline-flex items-center justify-center rounded-full bg-white/5 ring-1 ring-white/10 active:bg-white/10 hover:bg-white/10 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
    </a>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-bold leading-tight truncate">{{ $title ?? '' }}</p>
        @if(!empty($subtitle))
            <p class="text-[11px] text-zinc-400 leading-tight truncate">{{ $subtitle }}</p>
        @endif
    </div>
    @if(!empty($badge))
        <span class="shrink-0 text-[10px] font-black tracking-wider text-red-300 bg-red-600/15 ring-1 ring-red-500/30 px-2 py-1 rounded-md tabular-nums">{{ $badge }}</span>
    @endif
</div>
