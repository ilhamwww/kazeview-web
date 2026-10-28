@php
    $s = $stats['summary'] ?? [];
    $daily = $stats['daily'] ?? [];
    $maxDaily = max(1, ...array_column($daily, 'views') ?: [1]);
    $hourly = $stats['hourly_today'] ?? array_fill(0, 24, 0);
    $maxHour = max(1, ...$hourly);
    $img = fn ($u) => $u ? url('/api/proxy-image?url=' . urlencode($u)) : null;
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $pct = fn ($n) => number_format((float) $n, 1, ',', '.') . '%';
    $typeColor = fn ($t) => match ($t) {
        'Dracin' => 'text-pink-300 bg-pink-500/10 border-pink-500/30',
        'Movie' => 'text-red-300 bg-red-500/10 border-red-500/30',
        'Series' => 'text-blue-300 bg-blue-500/10 border-blue-500/30',
        'Anime' => 'text-violet-300 bg-violet-500/10 border-violet-500/30',
        default => 'text-zinc-300 bg-zinc-800 border-zinc-700',
    };
    $kpis = [
        ['label' => 'Aktif sekarang', 'value' => $s['active_now'] ?? 0, 'hint' => 'Sesi browser 15 menit terakhir', 'accent' => 'text-emerald-400', 'live' => true],
        ['label' => 'Pengunjung 24 jam', 'value' => $s['visitors_24h'] ?? 0, 'hint' => 'Sesi browser unik', 'accent' => 'text-white'],
        ['label' => 'Halaman dibuka hari ini', 'value' => $s['today_pageviews'] ?? 0, 'hint' => 'Tanpa bot & API', 'accent' => 'text-white'],
        ['label' => 'Halaman dibuka 7 hari', 'value' => $s['pageviews_7d'] ?? 0, 'hint' => '7 hari terakhir', 'accent' => 'text-white'],
        ['label' => 'Episode ditonton', 'value' => $s['episodes_watched'] ?? 0, 'hint' => 'Sejak awal · ' . ($fmt($s['titles_watched'] ?? 0)) . ' judul', 'accent' => 'text-red-400'],
        ['label' => 'Akun terdaftar', 'value' => $s['registered_users'] ?? 0, 'hint' => 'Total pengguna', 'accent' => 'text-white'],
    ];
@endphp

<div class="min-h-screen bg-[#050505] text-white pt-24 md:pt-28 pb-44 md:pb-20">
    <div class="w-full max-w-[1400px] mx-auto px-4 md:px-10 space-y-6 md:space-y-8">

        {{-- ================= HEADER ================= --}}
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-emerald-300 bg-emerald-500/10 border border-emerald-500/30 px-3 py-1 rounded-full">
                    <span class="relative flex w-2 h-2"><span class="absolute inline-flex w-full h-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span><span class="relative inline-flex w-2 h-2 rounded-full bg-emerald-400"></span></span>
                    Statistik Publik
                </span>
                <h1 class="mt-3 text-3xl md:text-5xl font-black tracking-tight leading-[1.05]">Statistik Traffic</h1>
                <p class="mt-2 max-w-xl text-sm text-zinc-400 leading-relaxed">Aktivitas penonton KZSTRM dalam 7 hari terakhir. Semua angka diambil langsung dari log server dan riwayat tontonan.</p>
            </div>
            <div class="self-start md:self-auto inline-flex items-center gap-2 text-xs text-zinc-400 bg-zinc-900/80 border border-zinc-800 px-3.5 py-2 rounded-xl">
                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Diperbarui <strong class="text-zinc-200 font-semibold">{{ $stats['last_updated'] ?? '-' }}</strong></span>
            </div>
        </header>

        {{-- ================= KUOTA BANDWIDTH STREAM (1.5 TB LIMIT) ================= --}}
        @php
            $bw = $stats['bandwidth'] ?? [
                'quota_tb' => 1.5,
                'quota_gb' => 1500,
                'used_formatted' => '0,00 GB',
                'remaining_formatted' => '1.500,00 GB',
                'percent_used' => 0.0,
                'percent_remaining' => 100.0,
                'movies_est' => 1000,
                'reset_date' => '1 November 2026',
                'speed_cap' => '12 Mbps',
                'max_concurrent' => 15,
                'status' => 'Normal & Aman',
                'status_color' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/30',
            ];
            $barColor = $bw['percent_used'] >= 90 ? 'bg-rose-500' : ($bw['percent_used'] >= 75 ? 'bg-amber-500' : 'bg-emerald-500');
        @endphp

        <section aria-label="Kuota Bandwidth Stream" class="rounded-3xl bg-zinc-900/60 ring-1 ring-white/[0.08] p-6 sm:p-7 md:p-8 relative overflow-hidden">
            {{-- Ambient glow --}}
            <div class="absolute -right-20 -top-20 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

            <div class="relative z-10 space-y-5 sm:space-y-6">
                {{-- Baris 1: Status & Reset --}}
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-[0.18em] px-2.5 py-1 rounded-full border {{ $bw['status_color'] }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            {{ $bw['status'] }}
                        </span>
                        <span class="text-[11px] font-semibold text-zinc-400 hidden xs:inline">Jatah Relay Khusus</span>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-[11px] text-zinc-400 bg-zinc-800/60 px-3 py-1 rounded-full border border-white/[0.06]">
                        <svg class="w-3 h-3 text-zinc-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        <span>Reset: <strong class="text-zinc-200 font-semibold">{{ $bw['reset_date'] }}</strong></span>
                    </span>
                </div>

                {{-- Baris 2: Judul & Angka Kuota --}}
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-5 sm:gap-6 pt-1">
                    <div class="space-y-2 max-w-xl">
                        <h2 class="text-lg sm:text-2xl font-black tracking-tight text-white flex items-center gap-2.5">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                            <span>Sisa Kuota Bandwidth Bulan Ini</span>
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                            Khusus aliran data pemutaran film/relay video agar aman sharing VPS. Website utama & katalog <strong class="text-zinc-200">100% bebas tanpa batas</strong>.
                        </p>
                    </div>

                    <div class="sm:text-right shrink-0 pt-2 sm:pt-0">
                        <div class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500">Sisa Kuota Stream</div>
                        <div class="text-3xl sm:text-4xl font-black text-emerald-400 tabular-nums tracking-tight leading-none mt-1.5">
                            {{ $bw['remaining_formatted'] }}
                        </div>
                        <div class="text-[11px] text-zinc-500 mt-1">
                            dari jatah <strong class="text-zinc-300">1.500 GB (1,5 TB)</strong>
                        </div>
                    </div>
                </div>

                {{-- Baris 3: Progress Bar --}}
                <div class="space-y-2 pt-1">
                    <div class="flex items-center justify-between text-xs font-semibold tabular-nums">
                        <span class="text-zinc-400">
                            Terpakai: <strong class="text-white">{{ $bw['used_formatted'] }}</strong> ({{ $bw['percent_used'] }}%)
                        </span>
                        <span class="text-emerald-400 font-bold">
                            Tersisa: {{ $bw['percent_remaining'] }}%
                        </span>
                    </div>
                    <div class="w-full h-2.5 bg-zinc-800 rounded-full overflow-hidden p-0.5 ring-1 ring-white/[0.05]">
                        <div class="h-full {{ $barColor }} rounded-full transition-all duration-700" style="width: <?php echo max((float)$bw['percent_used'] > 0 ? 1.5 : 0.0, min(100.0, (float)$bw['percent_used'])); ?>%;"></div>
                    </div>
                </div>

                {{-- Baris 4: Rincian Bandwidth per Kategori Konten --}}
                @if(!empty($bw['categories']))
                    <div class="pt-4 border-t border-white/[0.06] space-y-2.5">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 flex items-center justify-between">
                            <span>Rincian Bandwidth per Kategori</span>
                            <span class="text-zinc-500 font-normal lowercase hidden sm:inline">total {{ number_format($bw['total_requests'] ?? 0, 0, ',', '.') }} request tercatat</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 pt-0.5">
                            @foreach($bw['categories'] as $cKey => $cVal)
                                <div class="p-2.5 rounded-xl bg-zinc-800/40 border border-white/[0.04] flex flex-col justify-between">
                                    <div class="flex items-center gap-1.5 text-[11px] font-medium text-zinc-400">
                                        <span>{{ $cVal['icon'] }}</span>
                                        <span class="truncate">{{ $cVal['label'] }}</span>
                                    </div>
                                    <div class="text-sm font-black text-white mt-1 tabular-nums">
                                        {{ $cVal['formatted'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Baris 5: Chips Parameter Operasional --}}
                <div class="pt-3 border-t border-white/[0.06] flex flex-wrap items-center gap-2.5 sm:gap-3 text-xs">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-800/40 border border-white/[0.04] text-zinc-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                        <span>Estimasi: <strong class="text-white">~{{ number_format($bw['movies_est'], 0, ',', '.') }} film</strong></span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-800/40 border border-white/[0.04] text-zinc-300">
                        <span class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></span>
                        <span>Speed: <strong class="text-white">Maks {{ $bw['speed_cap'] }}</strong></span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-800/40 border border-white/[0.04] text-zinc-300">
                        <span class="w-2 h-2 rounded-full bg-purple-400 shrink-0"></span>
                        <span>Maks Barengan: <strong class="text-white">{{ $bw['max_concurrent'] }} user</strong></span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= KPI ================= --}}
        <section aria-label="Ringkasan" class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
            @foreach($kpis as $k)
                <div class="rounded-2xl bg-zinc-900/60 ring-1 ring-white/[0.06] p-4 flex flex-col">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] font-semibold text-zinc-400 leading-tight">{{ $k['label'] }}</span>
                        @if(!empty($k['live']))
                            <span class="relative flex w-2 h-2 shrink-0" aria-hidden="true"><span class="absolute inline-flex w-full h-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span><span class="relative inline-flex w-2 h-2 rounded-full bg-emerald-500"></span></span>
                        @endif
                    </div>
                    <div class="mt-2 text-2xl md:text-3xl font-black tabular-nums tracking-tight {{ $k['accent'] }}">{{ $fmt($k['value']) }}</div>
                    <p class="mt-auto pt-1 text-[10px] text-zinc-400 leading-tight">{{ $k['hint'] }}</p>
                </div>
            @endforeach
        </section>

        @if(empty($stats['log_available']))
            <div class="rounded-2xl bg-amber-500/10 ring-1 ring-amber-500/30 px-4 py-3 text-xs text-amber-200">Log server belum bisa dibaca, jadi data halaman dibuka, kategori, perangkat, dan browser belum tersedia.</div>
        @endif

        {{-- ================= TREN 7 HARI + JAM ================= --}}
        @if(($s['pageviews_7d'] ?? 0) > 0)
            <section class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                <div class="lg:col-span-3 rounded-3xl bg-zinc-900/50 ring-1 ring-white/[0.06] p-5 md:p-6">
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 class="text-base md:text-lg font-black tracking-tight">Halaman dibuka per hari</h2>
                        <span class="text-[11px] text-zinc-500">7 hari terakhir</span>
                    </div>
                    <div class="mt-5 flex gap-2 md:gap-3" role="img" aria-label="Grafik halaman dibuka per hari">
                        @foreach($daily as $i => $d)
                            @php $isToday = $loop->last; $h = max(3, round($d['views'] / $maxDaily * 100)); @endphp
                            <div class="flex-1 min-w-0 flex flex-col items-center">
                                <span class="text-[10px] font-bold tabular-nums mb-1.5 {{ $isToday ? 'text-red-300' : 'text-zinc-300' }}">{{ $fmt($d['views']) }}</span>
                                <div class="w-full h-40 flex items-end justify-center">
                                    <div class="w-full max-w-[56px] rounded-t-lg {{ $isToday ? 'bg-gradient-to-t from-red-700 to-red-500' : 'bg-zinc-600/80' }}" style="height: {{ $h }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex gap-2 md:gap-3">
                        @foreach($daily as $d)
                            <div class="flex-1 text-center min-w-0">
                                <div class="text-[11px] font-bold {{ $loop->last ? 'text-white' : 'text-zinc-400' }}">{{ $loop->last ? 'Hari ini' : $d['label'] }}</div>
                                <div class="text-[9px] text-zinc-500 tabular-nums">{{ $d['day'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="lg:col-span-2 rounded-3xl bg-zinc-900/50 ring-1 ring-white/[0.06] p-5 md:p-6 flex flex-col">
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 class="text-base md:text-lg font-black tracking-tight">Per jam hari ini</h2>
                        <span class="text-[11px] text-zinc-500">WIB</span>
                    </div>
                    @if(array_sum($hourly) > 0)
                        <div class="mt-5 flex-1 min-h-[120px] flex items-end gap-[3px]" role="img" aria-label="Grafik per jam hari ini">
                            @php $curHour = (int) now('Asia/Jakarta')->format('G'); @endphp
                            @foreach($hourly as $hr => $v)
                                <div class="flex-1 rounded-t-sm {{ $v > 0 && $v === max($hourly) ? 'bg-red-500' : ($v > 0 ? 'bg-zinc-500' : ($hr > $curHour ? 'bg-zinc-900' : 'bg-zinc-800')) }}"
                                     style="height: {{ $v > 0 ? max(6, round($v / $maxHour * 100)) : 3 }}%" title="{{ sprintf('%02d:00', $hr) }} · {{ $v }}"></div>
                            @endforeach
                        </div>
                        <div class="mt-1.5 flex justify-between text-[9px] text-zinc-500 tabular-nums"><span>00</span><span>06</span><span>12</span><span>18</span><span>23</span></div>
                        @if($stats['peak_hour'])
                            <p class="mt-4 text-xs text-zinc-400">Paling ramai jam <strong class="text-white">{{ sprintf('%02d:00', $stats['peak_hour']['hour']) }}</strong> dengan {{ $fmt($stats['peak_hour']['views']) }} halaman dibuka.</p>
                        @endif
                    @else
                        <p class="mt-6 text-sm text-zinc-500">Belum ada kunjungan hari ini.</p>
                    @endif
                </div>
            </section>
        @endif

        {{-- ================= KATEGORI ================= --}}
        @if(count($stats['categories'] ?? []))
            <section class="rounded-3xl bg-zinc-900/50 ring-1 ring-white/[0.06] p-5 md:p-6">
                <div class="flex items-baseline justify-between gap-2">
                    <h2 class="text-base md:text-lg font-black tracking-tight">Halaman paling sering dibuka</h2>
                    <span class="text-[11px] text-zinc-500">7 hari</span>
                </div>
                <div class="mt-4 h-3 rounded-full bg-zinc-950 overflow-hidden flex" role="img" aria-label="Porsi kunjungan per kategori">
                    @foreach($stats['categories'] as $c)
                        <div style="width: {{ $c['percent'] }}%; background-color: {{ $c['color'] }}" title="{{ $c['name'] }} {{ $pct($c['percent']) }}"></div>
                    @endforeach
                </div>
                <ul class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-2">
                    @foreach($stats['categories'] as $c)
                        <li class="rounded-xl bg-zinc-950/60 ring-1 ring-white/[0.05] px-3 py-2.5">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $c['color'] }}"></span>
                                <span class="text-[11px] font-semibold text-zinc-300 truncate">{{ $c['name'] }}</span>
                            </div>
                            <div class="mt-1 flex items-baseline gap-1.5">
                                <span class="text-lg font-black tabular-nums">{{ $pct($c['percent']) }}</span>
                                <span class="text-[10px] text-zinc-500 tabular-nums">{{ $fmt($c['hits']) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- ================= KONTEN POPULER + AKTIVITAS ================= --}}
        @if(count($stats['top_content'] ?? []) || count($stats['recent_events'] ?? []))
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @if(count($stats['top_content'] ?? []))
                    <div class="rounded-3xl bg-zinc-900/50 ring-1 ring-white/[0.06] p-5 md:p-6">
                        <div class="flex items-baseline justify-between gap-2">
                            <h2 class="text-base md:text-lg font-black tracking-tight">Paling banyak ditonton</h2>
                            <span class="text-[11px] text-zinc-500">Jumlah penonton</span>
                        </div>
                        <ol class="mt-4 space-y-2">
                            @foreach($stats['top_content'] as $i => $c)
                                <li class="flex items-center gap-3 rounded-2xl bg-zinc-950/50 ring-1 ring-white/[0.04] p-2.5">
                                    <span class="w-6 text-center text-lg font-black tabular-nums {{ $i === 0 ? 'text-amber-400' : 'text-zinc-600' }}">{{ $i + 1 }}</span>
                                    <div class="w-11 aspect-[3/4] shrink-0 rounded-lg overflow-hidden bg-zinc-800 bg-[radial-gradient(circle_at_center,rgba(229,9,20,.25),transparent_70%)]">
                                        @if($img($c['thumbnail']))<img src="{{ $img($c['thumbnail']) }}" alt="" class="w-full h-full object-cover" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()">@endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold leading-snug line-clamp-2" title="{{ $c['title'] }}">{{ $c['title'] }}</p>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded border {{ $typeColor($c['type']) }}">{{ $c['type'] }}</span>
                                            <span class="text-[11px] text-zinc-500 tabular-nums">{{ $fmt($c['episodes']) }} episode</span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-base font-black tabular-nums">{{ $fmt($c['viewers']) }}</div>
                                        <div class="text-[9px] text-zinc-400">penonton</div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if(count($stats['recent_events'] ?? []))
                    <div class="rounded-3xl bg-zinc-900/50 ring-1 ring-white/[0.06] p-5 md:p-6">
                        <div class="flex items-baseline justify-between gap-2">
                            <h2 class="text-base md:text-lg font-black tracking-tight">Baru saja ditonton</h2>
                            <span class="text-[11px] text-zinc-500">Tanpa identitas penonton</span>
                        </div>
                        <ul class="mt-4 space-y-2">
                            @foreach($stats['recent_events'] as $e)
                                <li class="flex items-center gap-3 rounded-2xl bg-zinc-950/50 ring-1 ring-white/[0.04] p-2.5">
                                    <div class="w-9 aspect-[3/4] shrink-0 rounded-md overflow-hidden bg-zinc-800 bg-[radial-gradient(circle_at_center,rgba(229,9,20,.25),transparent_70%)]">
                                        @if($img($e['thumbnail']))<img src="{{ $img($e['thumbnail']) }}" alt="" class="w-full h-full object-cover" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()">@endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold leading-snug line-clamp-2 break-words">{{ $e['title'] }}</p>
                                        <p class="mt-0.5 text-[11px] text-zinc-500">
                                            <span class="{{ str_contains($typeColor($e['type']), 'pink') ? 'text-pink-300' : 'text-zinc-400' }}">{{ $e['type'] }}</span>
                                            @if(filled($e['episode'])) · Episode {{ $e['episode'] }} @endif
                                        </p>
                                    </div>
                                    <span class="text-[10px] text-zinc-500 shrink-0 text-right">{{ $e['time'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        @endif

        {{-- ================= PERANGKAT + BROWSER ================= --}}
        @if(count($stats['devices'] ?? []) || count($stats['browsers'] ?? []))
            <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if(count($stats['devices'] ?? []))
                    <div class="rounded-3xl bg-zinc-900/50 ring-1 ring-white/[0.06] p-5 md:p-6">
                        <h2 class="text-base md:text-lg font-black tracking-tight">Perangkat</h2>
                        <div class="mt-4 space-y-3">
                            @foreach($stats['devices'] as $d)
                                <div>
                                    <div class="flex justify-between text-xs mb-1.5"><span class="text-zinc-300 font-semibold">{{ $d['name'] }}</span><span class="font-black tabular-nums">{{ $pct($d['percent']) }}</span></div>
                                    <div class="h-2 rounded-full bg-zinc-950 overflow-hidden"><div class="h-full rounded-full" style="width: max(6px, {{ $d['percent'] }}%); background-color: {{ $d['color'] }}"></div></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if(count($stats['browsers'] ?? []))
                    <div class="rounded-3xl bg-zinc-900/50 ring-1 ring-white/[0.06] p-5 md:p-6">
                        <h2 class="text-base md:text-lg font-black tracking-tight">Browser</h2>
                        <div class="mt-4 space-y-3">
                            @foreach(array_slice($stats['browsers'], 0, 5) as $b)
                                <div>
                                    <div class="flex justify-between text-xs mb-1.5"><span class="text-zinc-300 font-semibold">{{ $b['name'] }}</span><span class="font-black tabular-nums">{{ $pct($b['percent']) }}</span></div>
                                    <div class="h-2 rounded-full bg-zinc-950 overflow-hidden"><div class="h-full rounded-full bg-sky-500" style="width: max(6px, {{ $b['percent'] }}%)"></div></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif

        {{-- ================= LEADERBOARD (login only) ================= --}}
        <section class="rounded-3xl bg-gradient-to-b from-amber-500/[0.06] to-zinc-900/50 ring-1 ring-amber-500/20 p-5 md:p-6">
            <div class="flex items-baseline justify-between gap-2">
                <h2 class="text-base md:text-lg font-black tracking-tight">Penonton paling aktif</h2>
                <span class="text-[11px] text-zinc-500">Khusus member</span>
            </div>

            @if(is_null($leaderboard ?? null))
                <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-2xl bg-zinc-950/60 ring-1 ring-white/[0.06] p-4">
                    <p class="text-sm text-zinc-400">Masuk dulu untuk melihat peringkat penonton dan posisi kamu.</p>
                    <a href="{{ route('filament.streaming.auth.login') }}" class="shrink-0 inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-sm font-bold transition">Masuk</a>
                </div>
            @elseif(empty($leaderboard))
                <p class="mt-4 text-sm text-zinc-500">Belum ada riwayat tontonan.</p>
            @else
                <ol class="mt-4 space-y-2">
                    @foreach($leaderboard as $u)
                        @php $me = ($meId ?? null) === $u['user_id']; @endphp
                        <li class="flex items-center gap-3 rounded-2xl p-2.5 {{ $me ? 'bg-red-600/10 ring-1 ring-red-500/40' : 'bg-zinc-950/50 ring-1 ring-white/[0.04]' }}">
                            <span class="w-8 h-8 shrink-0 rounded-xl flex items-center justify-center text-xs font-black tabular-nums
                                {{ $u['rank'] === 1 ? 'bg-amber-400 text-black' : ($u['rank'] === 2 ? 'bg-zinc-300 text-black' : ($u['rank'] === 3 ? 'bg-amber-700 text-white' : 'bg-zinc-900 text-zinc-400 ring-1 ring-zinc-800')) }}">{{ $u['rank'] }}</span>
                            <span class="w-9 h-9 shrink-0 rounded-full bg-gradient-to-br from-zinc-700 to-zinc-900 flex items-center justify-center text-[11px] font-black">{{ $u['initial'] }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold truncate">{{ $u['name'] }} @if($me)<span class="ml-1 text-[9px] font-black uppercase tracking-wider text-red-300">Kamu</span>@endif</p>
                                <p class="text-[11px] text-zinc-400 truncate">{{ $fmt($u['titles']) }} judul · {{ $u['fav'] }}</p>
                                <p class="text-[10px] text-zinc-500 truncate">Aktif {{ $u['last_active'] }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-base font-black tabular-nums text-emerald-400">{{ $fmt($u['episodes']) }}</div>
                                <div class="text-[9px] text-zinc-500">episode</div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <p class="text-center text-[11px] text-zinc-500 leading-relaxed max-w-2xl mx-auto">
            Halaman dibuka dihitung dari log server untuk halaman streaming saja, tanpa bot, API, dan file gambar/video. Pengunjung dihitung dari sesi browser. Data diperbarui tiap 5 menit.
        </p>
    </div>
</div>
