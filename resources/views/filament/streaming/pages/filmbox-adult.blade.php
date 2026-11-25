@php
    $hero = $hero[0] ?? null;
@endphp

<div class="min-h-screen bg-[#070707] text-white pb-24">
    <div class="mx-auto w-full max-w-[1500px] px-4 sm:px-6 pt-24 md:pt-28">

        <div class="mb-6 flex items-center gap-3">
            <span class="inline-flex items-center rounded-md bg-red-600 px-2 py-1 text-[11px] font-black uppercase tracking-wider">18+</span>
            <h1 class="text-2xl md:text-3xl font-black tracking-tight">Filmbox 18+</h1>
            <span class="hidden sm:inline text-xs text-zinc-500">Konten dewasa · hanya untuk akun dengan mode 18+ aktif</span>
        </div>

        @if($hero)
            <a href="{{ $hero['url'] }}" class="group relative block overflow-hidden rounded-3xl ring-1 ring-white/10 bg-zinc-900">
                <div class="aspect-[16/7] w-full">
                    <img src="{{ $hero['banner'] ?: $hero['thumbnail'] }}" alt="{{ $hero['title'] }}" class="h-full w-full object-cover opacity-80 transition group-hover:opacity-95" loading="eager">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
                <div class="absolute bottom-0 left-0 right-0 p-5 sm:p-8">
                    <div class="mb-2 flex flex-wrap items-center gap-2 text-[11px] font-bold">
                        <span class="rounded bg-red-600 px-2 py-0.5">18+</span>
                        @if($hero['year'])<span class="rounded bg-white/10 px-2 py-0.5">{{ $hero['year'] }}</span>@endif
                        @if($hero['rating'])<span class="rounded bg-amber-500/20 px-2 py-0.5 text-amber-300">★ {{ $hero['rating'] }}</span>@endif
                    </div>
                    <h2 class="text-xl sm:text-3xl font-black">{{ $hero['title'] }}</h2>
                    @if(!empty($hero['description']))
                        <p class="mt-2 max-w-2xl text-xs sm:text-sm text-zinc-300 line-clamp-2">{{ $hero['description'] }}</p>
                    @endif
                </div>
            </a>
        @endif

        @if($trending)
            <section class="mt-8">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg md:text-xl font-black">Trending 18+</h2>
                </div>
                <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach($trending as $item)
                        @include('filament.streaming.partials.adult-card', ['item' => $item])
                    @endforeach
                </div>
            </section>
        @endif

        @foreach($sections as $section)
            <section class="mt-8">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg md:text-xl font-black">{{ $section['title'] }}</h2>
                    <span class="text-[11px] text-zinc-500">{{ count($section['items']) }} judul</span>
                </div>
                <div class="flex gap-3 overflow-x-auto pb-2 snap-x">
                    @foreach($section['items'] as $item)
                        <div class="w-[132px] sm:w-[150px] shrink-0 snap-start">
                            @include('filament.streaming.partials.adult-card', ['item' => $item])
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
