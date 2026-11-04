@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $top3 = array_slice($leaderboard ?? [], 0, 3);
@endphp

<div class="min-h-screen bg-[#050505] text-white selection:bg-red-600 selection:text-white pb-28 md:pb-20">

    {{-- ================= HERO SECTION ================= --}}
    <section class="relative overflow-hidden border-b border-white/[0.06] bg-gradient-to-b from-amber-500/[0.08] via-zinc-950/60 to-[#050505]">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(245,158,11,0.18),transparent_60%)]" aria-hidden="true"></div>
        <div class="relative w-full max-w-5xl mx-auto px-5 md:px-8 pt-24 md:pt-32 pb-10 text-center">
            <span class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-amber-400 bg-amber-500/10 border border-amber-500/30 px-3.5 py-1.5 rounded-full">
                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.004 0V9A3.75 3.75 0 009.75 5.25H8.25m5.25 0h1.5A3.75 3.75 0 0118.75 9v5.25m-9 0h6" />
                </svg>
                Peringkat Komunitas
            </span>
            <h1 class="mt-4 text-3xl md:text-5xl font-black tracking-tight leading-tight">Leaderboard Penonton</h1>
            <p class="mt-3 max-w-xl mx-auto text-sm md:text-base text-zinc-400 leading-relaxed">
                Peringkat penonton paling aktif di KZSTRM berdasarkan total episode yang telah diselesaikan.
            </p>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3 text-xs font-semibold">
                <a href="/traffic" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white/5 border border-white/10 text-zinc-300 hover:text-white hover:bg-white/10 transition">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75z"/></svg>
                    Lihat Traffic Server
                </a>
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white/5 border border-white/10 text-zinc-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Update Realtime
                </span>
            </div>
        </div>
    </section>

    <main class="w-full max-w-5xl mx-auto px-5 md:px-8 pt-8 space-y-8">

        {{-- JIKA BELUM LOGIN --}}
        @if(is_null($leaderboard ?? null))
            <div class="rounded-3xl bg-zinc-900/60 ring-1 ring-white/10 p-8 md:p-12 text-center max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h2 class="text-xl font-black text-white">Khusus Anggota Terdaftar</h2>
                <p class="mt-2 text-sm text-zinc-400 leading-relaxed">
                    Masuk ke akun KZSTRM kamu untuk melihat peringkat lengkap komunitas penonton dan posisi kamu di leaderboard.
                </p>
                <div class="mt-6">
                    <a href="{{ route('filament.streaming.auth.login') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm shadow-lg shadow-red-900/30 transition active:scale-95">
                        Masuk ke Akun
                    </a>
                </div>
            </div>
        @elseif(empty($leaderboard))
            <div class="rounded-3xl bg-zinc-900/60 ring-1 ring-white/10 p-12 text-center">
                <p class="text-zinc-400 text-sm">Belum ada riwayat tontonan yang tercatat di sistem.</p>
            </div>
        @else
            {{-- PODIUM TOP 3 (DESKTOP / TABLET) --}}
            @if(count($top3) >= 3)
                <div class="grid grid-cols-3 gap-3 md:gap-5 pt-6 pb-2 items-end max-w-2xl mx-auto">
                    {{-- Juara 2 --}}
                    @php $p2 = $top3[1]; $me2 = ($meId ?? null) === $p2['user_id']; @endphp
                    <div class="flex flex-col items-center order-1">
                        <div class="relative mb-2">
                            <span class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-zinc-800 ring-2 ring-zinc-400 flex items-center justify-center text-sm md:text-base font-black text-white shadow-xl">
                                {{ $p2['initial'] }}
                            </span>
                            <span class="absolute -top-2 -right-1 w-6 h-6 rounded-full bg-zinc-300 text-black text-xs font-black flex items-center justify-center shadow">2</span>
                        </div>
                        <p class="text-xs md:text-sm font-bold text-white text-center truncate max-w-[100px] md:max-w-[130px]">{{ $p2['name'] }}</p>
                        <p class="text-[10px] md:text-xs font-black text-emerald-400 mt-0.5">{{ $fmt($p2['episodes']) }} ep</p>
                        <div class="w-full h-24 md:h-32 bg-zinc-900/80 border border-zinc-700/50 rounded-t-2xl mt-3 flex items-center justify-center">
                            <span class="text-xl md:text-2xl font-black text-zinc-400">#2</span>
                        </div>
                    </div>

                    {{-- Juara 1 --}}
                    @php $p1 = $top3[0]; $me1 = ($meId ?? null) === $p1['user_id']; @endphp
                    <div class="flex flex-col items-center order-2 -mt-4">
                        <div class="relative mb-2">
                            <span class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-gradient-to-tr from-amber-600 to-amber-300 ring-4 ring-amber-400/60 flex items-center justify-center text-base md:text-xl font-black text-black shadow-2xl">
                                {{ $p1['initial'] }}
                            </span>
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 text-2xl">👑</span>
                            <span class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-amber-400 text-black text-xs font-black flex items-center justify-center shadow-lg ring-2 ring-black">1</span>
                        </div>
                        <p class="text-sm md:text-base font-black text-amber-300 text-center truncate max-w-[110px] md:max-w-[150px]">{{ $p1['name'] }}</p>
                        <p class="text-xs md:text-sm font-black text-emerald-400 mt-0.5">{{ $fmt($p1['episodes']) }} ep</p>
                        <div class="w-full h-32 md:h-44 bg-gradient-to-b from-amber-500/20 to-zinc-900/90 border border-amber-500/40 rounded-t-2xl mt-3 flex items-center justify-center shadow-[0_0_30px_rgba(245,158,11,0.15)]">
                            <span class="text-2xl md:text-3xl font-black text-amber-400">#1</span>
                        </div>
                    </div>

                    {{-- Juara 3 --}}
                    @php $p3 = $top3[2]; $me3 = ($meId ?? null) === $p3['user_id']; @endphp
                    <div class="flex flex-col items-center order-3">
                        <div class="relative mb-2">
                            <span class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-zinc-800 ring-2 ring-amber-700 flex items-center justify-center text-sm md:text-base font-black text-white shadow-xl">
                                {{ $p3['initial'] }}
                            </span>
                            <span class="absolute -top-2 -right-1 w-6 h-6 rounded-full bg-amber-700 text-white text-xs font-black flex items-center justify-center shadow">3</span>
                        </div>
                        <p class="text-xs md:text-sm font-bold text-white text-center truncate max-w-[100px] md:max-w-[130px]">{{ $p3['name'] }}</p>
                        <p class="text-[10px] md:text-xs font-black text-emerald-400 mt-0.5">{{ $fmt($p3['episodes']) }} ep</p>
                        <div class="w-full h-20 md:h-24 bg-zinc-900/80 border border-zinc-700/50 rounded-t-2xl mt-3 flex items-center justify-center">
                            <span class="text-xl md:text-2xl font-black text-amber-700">#3</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- DAFTAR LENGKAP LEADERBOARD --}}
            <div class="rounded-3xl bg-zinc-900/60 ring-1 ring-white/10 p-4 md:p-6">
                <div class="flex items-center justify-between pb-4 border-b border-white/5 mb-4">
                    <h2 class="text-base md:text-lg font-black tracking-tight">Daftar Peringkat Lengkap</h2>
                    <span class="text-xs text-zinc-500 font-semibold">Top 20 Pengguna</span>
                </div>

                <ol class="space-y-2.5">
                    @foreach($leaderboard as $u)
                        @php $me = ($meId ?? null) === $u['user_id']; @endphp
                        <li class="flex items-center gap-3.5 rounded-2xl p-3 md:p-3.5 transition {{ $me ? 'bg-red-600/15 ring-2 ring-red-500/60 shadow-lg shadow-red-950/40' : 'bg-zinc-950/60 ring-1 ring-white/[0.04] hover:bg-zinc-950/90' }}">
                            {{-- Badge Rank --}}
                            <span class="w-8 h-8 md:w-9 md:h-9 shrink-0 rounded-xl flex items-center justify-center text-xs md:text-sm font-black tabular-nums
                                {{ $u['rank'] === 1 ? 'bg-amber-400 text-black shadow-md shadow-amber-500/30' : ($u['rank'] === 2 ? 'bg-zinc-300 text-black' : ($u['rank'] === 3 ? 'bg-amber-700 text-white' : 'bg-zinc-900 text-zinc-400 ring-1 ring-zinc-800')) }}">
                                {{ $u['rank'] }}
                            </span>

                            {{-- Avatar --}}
                            <span class="w-10 h-10 shrink-0 rounded-full bg-gradient-to-br from-zinc-700 to-zinc-900 ring-1 ring-white/10 flex items-center justify-center text-xs font-black text-white">
                                {{ $u['initial'] }}
                            </span>

                            {{-- User Info --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm md:text-base font-bold text-white truncate">{{ $u['name'] }}</p>
                                    @if($me)
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-red-600 text-white shadow">Kamu</span>
                                    @endif
                                </div>
                                <p class="text-xs text-zinc-400 truncate mt-0.5">
                                    {{ $fmt($u['titles']) }} judul · Favorit: <span class="text-zinc-300 font-semibold">{{ $u['fav'] }}</span>
                                </p>
                                <p class="text-[10px] text-zinc-500 truncate mt-0.5">Aktif {{ $u['last_active'] }}</p>
                            </div>

                            {{-- Episodes Watched --}}
                            <div class="text-right shrink-0">
                                <div class="text-base md:text-xl font-black tabular-nums text-emerald-400">{{ $fmt($u['episodes']) }}</div>
                                <div class="text-[10px] text-zinc-500 font-semibold">episode</div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        <p class="text-center text-xs text-zinc-500 leading-relaxed max-w-xl mx-auto pt-4">
            Peringkat dihitung berdasarkan akumulasi seluruh judul dan episode yang ditonton di platform KZSTRM.
        </p>
    </main>
</div>
