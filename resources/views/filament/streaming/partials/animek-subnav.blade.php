@php
    $tabs = [
        'home' => ['Beranda', '/animek'],
        'jelajah' => ['Jelajah', '/animek/jelajah'],
        'genre' => ['Genre', '/animek/genre'],
        'jadwal' => ['Jadwal', '/animek/jadwal'],
    ];
    $active = $active ?? '';
@endphp
{{-- Header situs fixed: tinggi 56px di HP, top-4 + 56px di desktop. --}}
<div class="pt-[calc(56px+env(safe-area-inset-top,0px))] lg:pt-[88px]">
    <div class="mx-auto flex max-w-[1600px] items-center gap-3 px-4 py-3 md:px-8">
        <nav class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:ml-3" aria-label="Navigasi anime">
            @foreach($tabs as $key => [$label, $href])
                <a href="{{ $href }}"
                   class="shrink-0 rounded-full px-3.5 py-1.5 text-[13px] font-semibold transition {{ $active === $key ? 'bg-white text-black' : 'text-zinc-400 hover:bg-white/10 hover:text-white' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <form action="/animek/jelajah" method="get" class="relative hidden w-56 shrink-0 md:block lg:w-72" role="search">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul anime…" autocomplete="off" aria-label="Cari judul anime"
                   class="w-full rounded-full border border-white/10 bg-white/[0.06] py-1.5 pl-9 pr-3 text-[13px] text-white placeholder-zinc-500 outline-none transition focus:border-red-500/60 focus:bg-white/10">
        </form>
        <a href="/animek/jelajah" class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white/[0.06] text-zinc-300 md:hidden" aria-label="Cari anime">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        </a>
    </div>
</div>
