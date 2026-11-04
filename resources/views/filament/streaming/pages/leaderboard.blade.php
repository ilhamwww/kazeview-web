@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $royals = $board['royals'] ?? [];
    $ranked = $board['ranked'] ?? [];
    $top3 = array_slice($ranked, 0, 3);
    $rest = array_slice($ranked, 3);
    $titles = [1 => 'Squire', 2 => 'Footman', 3 => 'Sentinel', 4 => 'Knight', 5 => 'Mystic Knight', 6 => 'Paladin', 7 => 'Dragon Rider', 8 => 'Warlord', 9 => 'Archon', 10 => 'Legend'];
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Cinzel+Decorative:wght@700;900&display=swap" rel="stylesheet">

<style>
    .md-title { font-family: 'Cinzel Decorative', 'Cinzel', serif; }
    .md-serif { font-family: 'Cinzel', serif; }
    .md-gold { background: linear-gradient(180deg, #fde9a8 0%, #e2b04a 45%, #9a6a1c 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .md-stone { background-color: #0b0907; background-image: radial-gradient(ellipse at top, rgba(180, 120, 40, .16), transparent 60%), repeating-linear-gradient(45deg, rgba(255,255,255,.012) 0 2px, transparent 2px 6px); }
    .md-frame { position: relative; background: linear-gradient(180deg, rgba(40, 28, 14, .92), rgba(16, 11, 6, .96)); border: 1px solid rgba(214, 167, 78, .35); box-shadow: inset 0 0 0 1px rgba(0,0,0,.6), inset 0 0 40px rgba(0,0,0,.55), 0 10px 40px rgba(0,0,0,.45); }
    .md-frame::before, .md-frame::after { content: ''; position: absolute; width: 18px; height: 18px; border-color: rgba(232, 187, 96, .75); pointer-events: none; }
    .md-frame::before { top: 6px; left: 6px; border-top: 2px solid; border-left: 2px solid; }
    .md-frame::after { bottom: 6px; right: 6px; border-bottom: 2px solid; border-right: 2px solid; }
    .md-divider { height: 1px; background: linear-gradient(90deg, transparent, rgba(226, 176, 74, .7), transparent); }
    .md-bar { background: linear-gradient(90deg, #7a4f12, #e2b04a, #fde9a8); }
    .md-badge { filter: drop-shadow(0 6px 14px rgba(0,0,0,.6)); }
</style>

<div class="md-stone min-h-screen text-amber-50 selection:bg-amber-700 selection:text-white pb-28 md:pb-20">

    {{-- ================= HERO ================= --}}
    <section class="relative overflow-hidden border-b border-amber-900/40">
        <div class="relative w-full max-w-5xl mx-auto px-5 md:px-8 pt-24 md:pt-32 pb-10 text-center">
            <p class="md-serif text-[11px] font-bold uppercase tracking-[0.35em] text-amber-500/80">Hall of Champions</p>
            <h1 class="md-title md-gold mt-3 text-4xl md:text-6xl font-black leading-tight">Leaderboard</h1>
            <div class="md-divider max-w-xs mx-auto mt-4"></div>
            <p class="mt-4 max-w-xl mx-auto text-sm md:text-base text-amber-100/60 leading-relaxed">
                Kumpulkan EXP dari menonton Movie, TV Series, Indonesia, Anime, Dracin, dan membaca Komik. Naikkan levelmu dan rebut tempat di podium.
            </p>
        </div>
    </section>

    <main class="w-full max-w-5xl mx-auto px-5 md:px-8 pt-8 space-y-10">

        @if(is_null($board))
            {{-- ================= BELUM LOGIN ================= --}}
            <div class="md-frame rounded-2xl p-8 md:p-12 text-center max-w-lg mx-auto">
                <img src="/images/badges/level-1.webp" alt="" class="md-badge w-24 h-24 mx-auto" loading="lazy">
                <h2 class="md-serif mt-4 text-xl font-bold text-amber-200">Gerbang Kastil Tertutup</h2>
                <p class="mt-2 text-sm text-amber-100/60 leading-relaxed">Masuk ke akun KZSTRM untuk melihat para juara, level, dan EXP kamu.</p>
                <a href="{{ route('filament.streaming.auth.login') }}" class="md-serif mt-6 inline-flex items-center justify-center px-6 py-3 rounded-lg bg-gradient-to-b from-amber-500 to-amber-700 hover:from-amber-400 hover:to-amber-600 text-black font-bold text-sm shadow-lg shadow-black/40 transition active:scale-95">
                    Masuk ke Akun
                </a>
            </div>
        @else

            {{-- ================= ROYAL ================= --}}
            @if(count($royals))
                <section aria-labelledby="royal-title">
                    <div class="text-center mb-5">
                        <h2 id="royal-title" class="md-title md-gold text-2xl md:text-3xl font-black">The Royal Court</h2>
                        <p class="text-xs text-amber-100/70 mt-1">Penguasa kerajaan KZSTRM · tidak ikut peringkat</p>
                    </div>
                    {{-- Selalu 2 kolom (HP juga): King kiri, Queen kanan. EXP & level sengaja tidak ditampilkan. --}}
                    <div class="grid grid-cols-2 gap-3 md:gap-6 max-w-3xl mx-auto">
                        @foreach($royals as $r)
                            @php $isKing = $r['special'] === 'king'; @endphp
                            <article class="md-frame rounded-2xl p-3 sm:p-5 md:p-6 text-center overflow-hidden min-w-0 {{ $isKing ? 'ring-1 ring-amber-500/30' : 'ring-1 ring-fuchsia-400/25' }}">
                                <div class="absolute inset-0 pointer-events-none {{ $isKing ? 'bg-[radial-gradient(circle_at_50%_25%,rgba(245,180,60,.22),transparent_60%)]' : 'bg-[radial-gradient(circle_at_50%_25%,rgba(217,120,220,.2),transparent_60%)]' }}" aria-hidden="true"></div>
                                <div class="relative">
                                    <img src="{{ $r['badge'] }}" alt="Badge {{ $r['title'] }}" class="md-badge w-24 h-24 sm:w-32 sm:h-32 md:w-40 md:h-40 mx-auto" loading="lazy">
                                    <p class="md-serif mt-2 text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.25em] sm:tracking-[0.3em] {{ $isKing ? 'text-amber-400' : 'text-fuchsia-300' }}">{{ $r['title'] }}</p>
                                    <h3 class="md-serif mt-1 text-base sm:text-xl md:text-2xl font-black text-amber-50 truncate">{{ $r['name'] }}</h3>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ================= LEVEL KAMU ================= --}}
            @if($me && !in_array($me['special'], ['king', 'queen'], true))
                <section class="md-frame rounded-2xl p-5 md:p-6 flex flex-col sm:flex-row items-center gap-5" aria-label="Level kamu">
                    <img src="{{ $me['badge'] }}" alt="Badge level {{ $me['level'] }}" class="md-badge w-24 h-24 shrink-0" loading="lazy">
                    <div class="flex-1 w-full min-w-0 text-center sm:text-left">
                        <p class="md-serif text-[11px] font-bold uppercase tracking-[0.3em] text-amber-500">Level Kamu</p>
                        <h2 class="md-serif text-xl md:text-2xl font-black text-amber-50">Lv {{ $me['level'] }} · {{ $me['title'] }}</h2>
                        <p class="text-sm text-amber-100/70 mt-0.5">{{ $fmt($me['exp']) }} EXP
                            @if(!$me['is_max'])
                                <span class="text-amber-100/60">· {{ $fmt($me['to_next']) }} EXP lagi ke Lv {{ $me['level'] + 1 }}</span>
                            @endif
                        </p>
                        <div class="mt-3 h-2 rounded-full bg-black/50 overflow-hidden" role="progressbar" aria-valuenow="{{ $me['progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progres ke level berikutnya">
                            <div class="h-full md-bar" style="width: {{ $me['progress'] }}%"></div>
                        </div>
                    </div>
                    <div class="shrink-0 text-center px-4 py-3 rounded-xl bg-black/40 border border-amber-900/50">
                        <p class="text-[10px] uppercase tracking-wider text-amber-100/70">EXP hari ini</p>
                        <p class="md-serif text-lg font-black text-amber-300">{{ $me['today'] }} <span class="text-xs text-amber-100/60">/ {{ $dailyCap }}</span></p>
                    </div>
                </section>
            @endif

            {{-- ================= PODIUM ================= --}}
            @if(count($top3))
                <section aria-labelledby="podium-title">
                    <div class="text-center mb-6">
                        <h2 id="podium-title" class="md-title md-gold text-2xl md:text-3xl font-black">Champions</h2>
                        <div class="md-divider max-w-[180px] mx-auto mt-3"></div>
                    </div>

                    @php
                        $order = array_filter([1 => $top3[1] ?? null, 0 => $top3[0] ?? null, 2 => $top3[2] ?? null]);
                        $style = [
                            0 => ['h' => 'h-40 md:h-52', 'img' => 'w-24 h-24 md:w-32 md:h-32', 'ring' => 'border-amber-400/70', 'num' => 'text-amber-300', 'name' => 'text-amber-200', 'lift' => '-mt-6'],
                            1 => ['h' => 'h-28 md:h-36', 'img' => 'w-20 h-20 md:w-24 md:h-24', 'ring' => 'border-zinc-300/50', 'num' => 'text-zinc-200', 'name' => 'text-amber-50', 'lift' => ''],
                            2 => ['h' => 'h-20 md:h-28', 'img' => 'w-20 h-20 md:w-24 md:h-24', 'ring' => 'border-orange-700/60', 'num' => 'text-orange-400', 'name' => 'text-amber-50', 'lift' => ''],
                        ];
                    @endphp

                    <div class="grid grid-cols-3 gap-2 md:gap-5 items-end max-w-3xl mx-auto">
                        @foreach($order as $idx => $p)
                            @php $s = $style[$idx]; $isMe = $meId === $p['user_id']; @endphp
                            <div class="flex flex-col items-center min-w-0 {{ $s['lift'] }}">
                                <img src="{{ $p['badge'] }}" alt="Badge level {{ $p['level'] }}" class="md-badge {{ $s['img'] }}" loading="lazy">
                                <p class="md-serif mt-2 text-xs md:text-base font-black {{ $s['name'] }} text-center truncate max-w-full px-1">{{ $p['name'] }}</p>
                                @if($isMe)
                                    <span class="mt-0.5 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-red-700 text-white">Kamu</span>
                                @endif
                                <p class="text-[10px] md:text-xs text-amber-100/60 mt-0.5">Lv {{ $p['level'] }} · {{ $p['title'] }}</p>
                                <p class="text-[11px] md:text-sm font-black text-amber-300">{{ $fmt($p['exp']) }} EXP</p>
                                <div class="md-frame w-full {{ $s['h'] }} rounded-t-xl mt-3 flex items-start justify-center pt-3 md:pt-4 border-t-2 {{ $s['ring'] }}">
                                    <span class="md-serif text-2xl md:text-4xl font-black tracking-widest {{ $s['num'] }}">{{ ['I', 'II', 'III'][$idx] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ================= DAFTAR PERINGKAT ================= --}}
            @if(count($rest))
                <section class="md-frame rounded-2xl p-4 md:p-6" aria-labelledby="ranks-title">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-amber-900/50">
                        <h2 id="ranks-title" class="md-serif text-base md:text-lg font-black text-amber-200">Barisan Ksatria</h2>
                        <span class="text-xs text-amber-100/60">Top {{ count($ranked) }}</span>
                    </div>
                    <ol class="space-y-2">
                        @foreach($rest as $u)
                            @php $isMe = $meId === $u['user_id']; @endphp
                            <li class="flex items-center gap-3 md:gap-4 rounded-xl p-2.5 md:p-3 {{ $isMe ? 'bg-red-900/25 ring-1 ring-red-600/60' : 'bg-black/30 ring-1 ring-amber-900/30' }}">
                                <span class="w-8 shrink-0 text-center text-sm md:text-base font-black text-amber-100/70 tabular-nums">{{ $u['rank'] }}</span>
                                <img src="{{ $u['badge'] }}" alt="Badge level {{ $u['level'] }}" class="md-badge w-11 h-11 md:w-12 md:h-12 shrink-0" loading="lazy">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm md:text-base font-bold text-amber-50 truncate">{{ $u['name'] }}</p>
                                        @if($isMe)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-red-700 text-white shrink-0">Kamu</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] md:text-xs text-amber-100/70 truncate">Lv {{ $u['level'] }} · {{ $u['title'] }}<span class="hidden sm:inline"> · Paling banyak: {{ $u['top_source'] }}</span></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="md-serif text-sm md:text-lg font-black text-amber-300 tabular-nums">{{ $fmt($u['exp']) }}</p>
                                    <p class="text-[10px] text-amber-100/60">EXP</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @elseif(!count($top3))
                <div class="md-frame rounded-2xl p-10 text-center text-sm text-amber-100/60">Belum ada ksatria yang mengumpulkan EXP.</div>
            @endif

            {{-- ================= TANGGA LEVEL & ATURAN EXP ================= --}}
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
                <div class="md-frame rounded-2xl p-5 md:p-6 lg:col-span-2">
                    <h2 class="md-serif text-base md:text-lg font-black text-amber-200 mb-4">Tangga Level</h2>
                    <ul class="grid grid-cols-5 gap-2 md:gap-3">
                        @foreach($levels as $lv => $min)
                            @php $reached = $me && $me['level'] >= $lv; @endphp
                            <li class="text-center rounded-xl p-2 bg-black/30 ring-1 {{ $reached ? 'ring-amber-500/50' : 'ring-amber-900/30' }}">
                                <img src="/images/badges/level-{{ $lv }}.webp" alt="Badge level {{ $lv }}" class="md-badge w-full max-w-[64px] mx-auto aspect-square {{ $reached ? '' : 'opacity-50 grayscale' }}">
                                <p class="md-serif text-[11px] md:text-xs font-black text-amber-100 mt-1">Lv {{ $lv }}</p>
                                <p class="text-[9px] md:text-[10px] leading-tight text-amber-100/70 break-words">{{ $titles[$lv] }}</p>
                                <p class="text-[9px] md:text-[10px] text-amber-400/80 tabular-nums">{{ $fmt($min) }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="md-frame rounded-2xl p-5 md:p-6">
                    <h2 class="md-serif text-base md:text-lg font-black text-amber-200 mb-4">Cara Dapat EXP</h2>
                    <ul class="space-y-2 text-sm">
                        @foreach([
                            ['Movie (ditonton ≥ 80%)', $values['movie']],
                            ['Episode TV Series / Indonesia', $values['series']],
                            ['Episode Anime', $values['anime']],
                            ['Episode Dracin', $values['dracin']],
                            ['Chapter Komik (dibaca ≥ 90%)', $values['komik']],
                        ] as [$label, $val])
                            <li class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 bg-black/30">
                                <span class="text-amber-100/70">{{ $label }}</span>
                                <span class="md-serif font-black text-amber-300 shrink-0">+{{ $val }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-4 text-[11px] text-amber-100/60 leading-relaxed">
                        Maksimal {{ $dailyCap }} EXP per hari (WIB). Episode atau chapter yang sama hanya dihitung sekali.
                    </p>
                </div>
            </section>
        @endif
    </main>
</div>
