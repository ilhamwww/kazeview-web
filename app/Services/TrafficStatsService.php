<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\BandwidthDailyStat;

/**
 * Statistik traffic publik KZSTRM.
 *
 * Semua angka berasal dari sumber nyata:
 * - Log akses nginx (kaze_access.log + rotasi .1 / .N.gz) untuk page view, kategori, perangkat, browser.
 * - Tabel sessions untuk pengunjung aktif / 24 jam (IP di log adalah IP Cloudflare, jadi tidak dipakai untuk unik).
 * - Tabel watch_histories untuk konten populer, aktivitas terbaru, dan leaderboard.
 * Tidak ada angka tambahan / data karangan.
 */
class TrafficStatsService
{
    protected const TZ = 'Asia/Jakarta';
    protected const LOG_PATH = '/var/log/nginx/kazestream_access.log';
    protected const DAYS = 7;
    public const STREAM_LOG_PATH = '/var/log/nginx/kazestream_stream.log';
    public const MONTHLY_QUOTA_TB = 1.5;
    public const MONTHLY_QUOTA_GB = 1500.0;

    /** UA yang dianggap bot / otomatisasi dan tidak dihitung. */
    protected const BOT_REGEX = '/bot|crawl|spider|slurp|headless|^node|curl|wget|python|go-http|axios|okhttp|java\/|facebookexternalhit|whatsapp|telegram|preview|lighthouse|uptime|monitor|^-$|^$/i';

    public static function getPublicStats(): array
    {
        return Cache::remember('kaze_public_traffic_stats_v2', 300, fn () => self::computeStats());
    }

    public static function isBot(?string $ua): bool
    {
        return (bool) preg_match(self::BOT_REGEX, trim((string) $ua));
    }

    protected static function computeStats(): array
    {
        $now = Carbon::now(self::TZ);
        $today = $now->format('Y-m-d');

        // Siapkan slot 7 hari terakhir (WIB)
        $daily = [];
        for ($i = self::DAYS - 1; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $daily[$d->format('Y-m-d')] = [
                'date' => $d->format('Y-m-d'),
                'label' => $d->locale('id')->translatedFormat('D'),
                'day' => $d->format('d/m'),
                'views' => 0,
            ];
        }
        $oldestKey = array_key_first($daily);

        $cat = ['movie' => 0, 'series' => 0, 'dracin' => 0, 'anime' => 0, 'collection' => 0, 'network' => 0, 'other' => 0];
        $devices = ['mobile' => 0, 'desktop' => 0, 'tablet' => 0];
        $browsers = [];
        $todayHourly = array_fill(0, 24, 0);
        $logAvailable = false;

        $months = ['Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04', 'May' => '05', 'Jun' => '06',
                   'Jul' => '07', 'Aug' => '08', 'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12'];

        foreach (self::logFiles() as $file) {
            $isGz = str_ends_with($file, '.gz');
            $h = $isGz ? @gzopen($file, 'r') : @fopen($file, 'r');
            if (!$h) {
                continue;
            }
            $logAvailable = true;

            while (($line = $isGz ? gzgets($h) : fgets($h)) !== false) {
                // Format combined: IP - - [13/Oct/2026:06:33:01 +0700] "GET /path HTTP/2.0" 200 123 "ref" "ua"
                if (!preg_match('/\[(\d{2})\/(\w{3})\/(\d{4}):(\d{2}):\d{2}:\d{2} [^\]]+\] "(\S+) (\S+)[^"]*" (\d{3}) \S+ "[^"]*" "([^"]*)"/', $line, $m)) {
                    continue;
                }
                [, $dd, $mon, $yyyy, $hh, $method, $path, $status, $ua] = $m;

                if ($method !== 'GET' || $status !== '200' || !self::isPageView($path) || self::isBot($ua)) {
                    continue;
                }

                // Log nginx memakai +0700 (WIB), jadi tanggal di log = tanggal WIB
                $key = $yyyy . '-' . ($months[$mon] ?? '00') . '-' . $dd;
                if ($key < $oldestKey || !isset($daily[$key])) {
                    continue;
                }

                $daily[$key]['views']++;
                if ($key === $today) {
                    $todayHourly[(int) $hh]++;
                }

                $cat[self::categorize($path)]++;
                $devices[self::deviceOf($ua)]++;
                $b = self::browserOf($ua);
                $browsers[$b] = ($browsers[$b] ?? 0) + 1;
            }

            $isGz ? gzclose($h) : fclose($h);
        }

        $totalViews7d = array_sum(array_column($daily, 'views'));
        $todayViews = $daily[$today]['views'] ?? 0;

        // ---------- Sesi browser (database) ----------
        $activeNow = 0;
        $visitors24h = 0;
        try {
            $activeNow = self::countHumanSessions($now->timestamp - 900);
            $visitors24h = self::countHumanSessions($now->timestamp - 86400);
        } catch (\Throwable $e) {
        }

        // ---------- Watch histories ----------
        $registeredUsers = 0;
        $episodesWatched = 0;
        $titlesWatched = 0;
        $topContent = [];
        $recent = [];
        try {
            $registeredUsers = User::count();

            $rows = DB::table('watch_histories')
                ->select('item_id', 'item_type', 'title', 'thumbnail', 'user_id', 'last_episode', 'watched_episodes', 'updated_at')
                ->get();

            $byItem = [];
            foreach ($rows as $r) {
                $eps = json_decode((string) $r->watched_episodes, true);
                $epCount = is_array($eps) && count($eps) ? count($eps) : 1;
                $episodesWatched += $epCount;

                $k = ($r->item_type ?? 'x') . '|' . $r->item_id;
                if (!isset($byItem[$k])) {
                    $byItem[$k] = [
                        'title' => $r->title,
                        'type' => self::typeLabel($r->item_type),
                        'thumbnail' => $r->thumbnail,
                        'viewers' => 0,
                        'episodes' => 0,
                        'last' => $r->updated_at,
                    ];
                }
                $byItem[$k]['viewers']++;
                $byItem[$k]['episodes'] += $epCount;
                if ($r->updated_at > $byItem[$k]['last']) {
                    $byItem[$k]['last'] = $r->updated_at;
                }
                if (empty($byItem[$k]['thumbnail']) && !empty($r->thumbnail)) {
                    $byItem[$k]['thumbnail'] = $r->thumbnail;
                }
            }
            $titlesWatched = count($byItem);

            $topContent = collect($byItem)
                ->filter(fn ($i) => filled($i['title']) && !in_array($i['title'], ['Detail Drama', 'Drama Detail'], true))
                ->sortBy([['viewers', 'desc'], ['episodes', 'desc']])
                ->take(6)
                ->values()
                ->all();

            // Aktivitas terbaru: hanya judul & waktu, tanpa identitas pengguna
            $recent = $rows->filter(fn ($r) => filled($r->title) && !in_array($r->title, ['Detail Drama', 'Drama Detail'], true))
                ->sortByDesc('updated_at')
                ->take(8)
                ->map(fn ($r) => [
                    'title' => $r->title,
                    'type' => self::typeLabel($r->item_type),
                    'episode' => $r->last_episode,
                    'thumbnail' => $r->thumbnail,
                    'time' => Carbon::parse($r->updated_at, 'UTC')->locale('id')->diffForHumans(),
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
        }

        // ---------- Persentase ----------
        $catMeta = [
            'dracin' => ['Dracin', '#EC4899'],
            'movie' => ['Movies', '#E50914'],
            'series' => ['TV Series', '#3B82F6'],
            'anime' => ['Anime', '#8B5CF6'],
            'collection' => ['Collections', '#F59E0B'],
            'network' => ['Network', '#14B8A6'],
            'other' => ['Beranda & lainnya', '#71717A'],
        ];
        $categories = [];
        foreach ($catMeta as $k => [$name, $color]) {
            if ($cat[$k] > 0) {
                $categories[] = [
                    'name' => $name,
                    'hits' => $cat[$k],
                    'color' => $color,
                    'percent' => $totalViews7d ? round($cat[$k] / $totalViews7d * 100, 1) : 0,
                ];
            }
        }
        usort($categories, fn ($a, $b) => $b['hits'] <=> $a['hits']);

        $deviceMeta = ['mobile' => ['HP', '#E50914'], 'desktop' => ['Desktop', '#3B82F6'], 'tablet' => ['Tablet', '#10B981']];
        $deviceList = [];
        $devTotal = array_sum($devices);
        foreach ($deviceMeta as $k => [$name, $color]) {
            if ($devices[$k] > 0) {
                $deviceList[] = ['name' => $name, 'hits' => $devices[$k], 'color' => $color,
                                 'percent' => round($devices[$k] / $devTotal * 100, 1)];
            }
        }

        arsort($browsers);
        if (isset($browsers['Lainnya'])) {
            $other = $browsers['Lainnya'];
            unset($browsers['Lainnya']);
            $browsers['Lainnya'] = $other;
        }
        $brTotal = array_sum($browsers);
        $browserList = [];
        foreach ($browsers as $name => $hits) {
            $browserList[] = ['name' => $name, 'hits' => $hits, 'percent' => round($hits / $brTotal * 100, 1)];
        }

        $peakHour = null;
        if (array_sum($todayHourly) > 0) {
            $max = max($todayHourly);
            $peakHour = ['hour' => array_search($max, $todayHourly, true), 'views' => $max];
        }

        return [
            'summary' => [
                'active_now' => $activeNow,
                'visitors_24h' => $visitors24h,
                'today_pageviews' => $todayViews,
                'pageviews_7d' => $totalViews7d,
                'registered_users' => $registeredUsers,
                'episodes_watched' => $episodesWatched,
                'titles_watched' => $titlesWatched,
            ],
            'daily' => array_values($daily),
            'hourly_today' => $todayHourly,
            'peak_hour' => $peakHour,
            'categories' => $categories,
            'devices' => $deviceList,
            'browsers' => $browserList,
            'top_content' => $topContent,
            'recent_events' => $recent,
            'bandwidth' => self::computeBandwidthStats(),
            'log_available' => $logAvailable,
            'last_updated' => $now->locale('id')->translatedFormat('d F Y, H:i') . ' WIB',
        ];
    }

    /**
     * Hitung pemakaian bandwidth seluruh konten streaming bulan ini:
     * - Movie & TV Series
     * - Anime
     * - Drama China (Dracin)
     * - Manga & Manhwa (Komik)
     * - Media Streaming Proxy & Assets
     * 
     * Terintegrasi ke database ledger `bandwidth_daily_stats` sehingga:
     * 1. Kebal terhadap rotasi harian log Nginx (tidak akan pernah reset harian).
     * 2. Otomatis reset bulanan setiap tanggal 1 jam 00:00 WIB.
     * Limit jatah bulanan: 1,5 TB (1.500 GB).
     */
    public static function computeBandwidthStats(): array
    {
        $now = Carbon::now(self::TZ);
        $nextMonthDate = $now->copy()->addMonth()->startOfMonth()->locale('id')->translatedFormat('j F Y');

        // 1. Sync traffic hari ini dari log Nginx aktif ke database ledger
        try {
            self::syncActiveBandwidth();
        } catch (\Throwable $e) {}

        // 2. Query akumulasi seluruh hari di bulan berjalan
        $startOfMonth = $now->copy()->startOfMonth()->format('Y-m-d');
        $endOfMonth = $now->copy()->endOfMonth()->format('Y-m-d');

        $stats = BandwidthDailyStat::whereBetween('date', [$startOfMonth, $endOfMonth])->get();

        $usedBytes = (int) $stats->sum('total_bytes');
        $movieBytes = (int) $stats->sum('movie_bytes');
        $seriesBytes = (int) $stats->sum('series_bytes');
        $animeBytes = (int) $stats->sum('anime_bytes');
        $dracinBytes = (int) $stats->sum('dracin_bytes');
        $mangaBytes = (int) $stats->sum('manga_bytes');
        $proxyBytes = (int) $stats->sum('proxy_bytes');
        $otherBytes = (int) $stats->sum('other_bytes');
        $totalRequests = (int) $stats->sum('requests_count');

        $quotaBytes = (float) (self::MONTHLY_QUOTA_TB * 1024 * 1024 * 1024 * 1024);
        $usedGb = round($usedBytes / (1024 * 1024 * 1024), 2);
        $remainingGb = max(0, round(self::MONTHLY_QUOTA_GB - $usedGb, 2));
        $percentUsed = min(100.0, round(($usedBytes / $quotaBytes) * 100, 2));
        $percentRemaining = max(0.0, round(100.0 - $percentUsed, 2));
        $moviesEst = max(0, (int) floor($remainingGb / 1.5));

        return [
            'quota_tb' => self::MONTHLY_QUOTA_TB,
            'quota_gb' => self::MONTHLY_QUOTA_GB,
            'used_bytes' => $usedBytes,
            'used_gb' => $usedGb,
            'used_formatted' => number_format($usedGb, 2, ',', '.') . ' GB',
            'remaining_gb' => $remainingGb,
            'remaining_formatted' => number_format($remainingGb, 2, ',', '.') . ' GB',
            'percent_used' => $percentUsed,
            'percent_remaining' => $percentRemaining,
            'movies_est' => $moviesEst,
            'reset_date' => $nextMonthDate,
            'speed_cap' => '12 Mbps',
            'max_concurrent' => 15,
            'status' => $percentUsed >= 95 ? 'Kritis' : ($percentUsed >= 80 ? 'Waspada' : 'Normal & Aman'),
            'status_color' => $percentUsed >= 95 ? 'text-rose-400 bg-rose-500/10 border-rose-500/30' : ($percentUsed >= 80 ? 'text-amber-400 bg-amber-500/10 border-amber-500/30' : 'text-emerald-400 bg-emerald-500/10 border-emerald-500/30'),
            'categories' => [
                'movie_series' => [
                    'label' => 'Film & Series',
                    'bytes' => $movieBytes + $seriesBytes,
                    'formatted' => self::formatBytes($movieBytes + $seriesBytes),
                    'icon' => '🎬',
                ],
                'anime' => [
                    'label' => 'Anime',
                    'bytes' => $animeBytes,
                    'formatted' => self::formatBytes($animeBytes),
                    'icon' => '⛩️',
                ],
                'dracin' => [
                    'label' => 'Drama China',
                    'bytes' => $dracinBytes,
                    'formatted' => self::formatBytes($dracinBytes),
                    'icon' => '🎭',
                ],
                'manga' => [
                    'label' => 'Manga / Manhwa',
                    'bytes' => $mangaBytes,
                    'formatted' => self::formatBytes($mangaBytes),
                    'icon' => '📖',
                ],
                'proxy' => [
                    'label' => 'Media Relay & Proxy',
                    'bytes' => $proxyBytes,
                    'formatted' => self::formatBytes($proxyBytes),
                    'icon' => '⚡',
                ],
                'other' => [
                    'label' => 'Aset & Halaman Web',
                    'bytes' => $otherBytes,
                    'formatted' => self::formatBytes($otherBytes),
                    'icon' => '🌐',
                ],
            ],
            'total_requests' => $totalRequests,
        ];
    }

    /**
     * Format byte ke string satuan yang mudah dibaca (GB / MB / KB).
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024 * 1024), 2, ',', '.') . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1, ',', '.') . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.') . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Sinkronisasi data log Nginx aktif hari ini ke database ledger.
     */
    public static function syncActiveBandwidth(): void
    {
        $logPath = self::LOG_PATH;
        if (!is_file($logPath) || !is_readable($logPath)) return;

        $now = Carbon::now(self::TZ);
        $todayStr = $now->format('Y-m-d');

        $handle = @fopen($logPath, 'r');
        if (!$handle) return;

        $agg = [
            'total_bytes' => 0,
            'movie_bytes' => 0,
            'series_bytes' => 0,
            'anime_bytes' => 0,
            'dracin_bytes' => 0,
            'manga_bytes' => 0,
            'proxy_bytes' => 0,
            'other_bytes' => 0,
            'requests_count' => 0,
        ];

        while (($line = fgets($handle, 4096)) !== false) {
            if (preg_match('/\[(\d{2})\/([a-zA-Z]{3})\/(\d{4}):[^\]]*\]\s+"(\w+)\s+([^\s"]+)[^"]*"\s+(\d+)\s+(\d+)/', $line, $m)) {
                $monNum = date('m', strtotime("1 {$m[2]} 2000"));
                $lineDate = "{$m[3]}-{$monNum}-{$m[1]}";
                if ($lineDate !== $todayStr) continue;

                $path = $m[5];
                $bytes = (int) $m[7];
                $agg['total_bytes'] += $bytes;
                $agg['requests_count']++;

                $lower = strtolower($path);
                $isAnime = str_contains($lower, 'anime') || str_contains($lower, 'suzune') || str_contains($lower, 'suguha') || str_contains($lower, 'mirainime') || str_contains($lower, 'otakudesu');
                $isDracin = str_contains($lower, 'dracin') || str_contains($lower, 'dramabox') || str_contains($lower, 'melolo') || str_contains($lower, 'goodbos') || str_contains($lower, 'reelbuzz') || str_contains($lower, 'serealplus') || str_contains($lower, 'flextv') || str_contains($lower, 'reelshort') || str_contains($lower, 'dramawave');
                $isSeries = str_contains($lower, '/series');
                $isMovie = str_contains($lower, '/movie') || str_contains($lower, 'idlix') || str_contains($lower, 'rebahin') || str_contains($lower, 'stream-relay');
                $isManga = str_contains($lower, 'manga') || str_contains($lower, 'manhwa') || str_contains($lower, 'comic') || str_contains($lower, 'doujin') || str_contains($lower, 'doujindesu');
                $isProxy = str_contains($lower, 'proxy-') || str_contains($lower, 'stream');

                if ($isAnime) $agg['anime_bytes'] += $bytes;
                elseif ($isDracin) $agg['dracin_bytes'] += $bytes;
                elseif ($isSeries) $agg['series_bytes'] += $bytes;
                elseif ($isMovie) $agg['movie_bytes'] += $bytes;
                elseif ($isManga) $agg['manga_bytes'] += $bytes;
                elseif ($isProxy) $agg['proxy_bytes'] += $bytes;
                else $agg['other_bytes'] += $bytes;
            }
        }
        fclose($handle);

        BandwidthDailyStat::updateOrCreate(
            ['date' => $todayStr],
            $agg
        );
    }

    /** File log 7 hari terakhir: aktif, .1 (plain), .2.gz s/d .7.gz */
    protected static function logFiles(): array
    {
        $files = [self::LOG_PATH, self::LOG_PATH . '.1'];
        for ($i = 2; $i <= self::DAYS; $i++) {
            $files[] = self::LOG_PATH . ".{$i}.gz";
        }

        return array_values(array_filter($files, fn ($f) => is_file($f) && is_readable($f)));
    }

    /** Hanya halaman streaming yang dibuka pengguna (bukan API, Livewire, atau aset). */
    protected static function isPageView(string $path): bool
    {
        $p = strtok($path, '?');
        if (str_contains($p, '/livewire') || str_contains($p, '/api/') || str_contains($p, '/sw-play') || str_contains($p, '/offline.html')) {
            return false;
        }

        return !preg_match('/\.(js|css|png|jpe?g|webp|gif|svg|ico|json|webmanifest|m3u8|ts|mp4|woff2?|map)$/i', $p);
    }

    protected static function categorize(string $path): string
    {
        $p = strtolower(strtok($path, '?'));

        return match (true) {
            str_contains($p, 'dracin') || str_contains($p, 'bstation') => 'dracin',
            str_contains($p, 'anime') => 'anime',
            str_contains($p, 'manga') || str_contains($p, 'manhwa') || str_contains($p, 'comic') || str_contains($p, 'doujin') => 'manga',
            str_contains($p, 'series') => 'series',
            str_contains($p, 'movie') => 'movie',
            str_contains($p, 'collection') => 'collection',
            str_contains($p, 'network') => 'network',
            default => 'other',
        };
    }

    protected static function deviceOf(string $ua): string
    {
        if (preg_match('/iPad|Tablet/i', $ua) || (str_contains($ua, 'Android') && !str_contains($ua, 'Mobile'))) {
            return 'tablet';
        }

        return preg_match('/Mobile|iPhone|Android/i', $ua) ? 'mobile' : 'desktop';
    }

    protected static function browserOf(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox') || str_contains($ua, 'FxiOS') => 'Firefox',
            str_contains($ua, 'CriOS') || str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Lainnya',
        };
    }

    protected static function typeLabel(?string $type): string
    {
        return match (strtolower((string) $type)) {
            'dracin', 'bstation' => 'Dracin',
            'movie', 'movies' => 'Movie',
            'series', 'tv', 'tv-series' => 'Series',
            'anime' => 'Anime',
            default => ucfirst((string) ($type ?: 'Stream')),
        };
    }

    protected static function countHumanSessions(int $since): int
    {
        return DB::table('sessions')
            ->where('last_activity', '>=', $since)
            ->pluck('user_agent')
            ->reject(fn ($ua) => self::isBot($ua))
            ->count();
    }

    /**
     * Leaderboard penonton. Hanya dipanggil untuk pengguna yang sudah login.
     */
    public static function getLeaderboard(): array
    {
        try {
            $rows = DB::table('watch_histories')
                ->join('users', 'watch_histories.user_id', '=', 'users.id')
                ->select('users.id', 'users.name', 'watch_histories.item_type', 'watch_histories.watched_episodes', 'watch_histories.updated_at')
                ->get();

            $map = [];
            foreach ($rows as $row) {
                $uid = $row->id;
                $map[$uid] ??= ['name' => $row->name, 'titles' => 0, 'episodes' => 0, 'types' => [], 'last' => $row->updated_at];
                $eps = json_decode((string) $row->watched_episodes, true);
                $epCount = is_array($eps) && count($eps) ? count($eps) : 1;
                $map[$uid]['titles']++;
                $map[$uid]['episodes'] += $epCount;
                $t = self::typeLabel($row->item_type);
                $map[$uid]['types'][$t] = ($map[$uid]['types'][$t] ?? 0) + $epCount;
                if ($row->updated_at > $map[$uid]['last']) {
                    $map[$uid]['last'] = $row->updated_at;
                }
            }

            uasort($map, fn ($a, $b) => [$b['episodes'], $b['titles']] <=> [$a['episodes'], $a['titles']]);

            $rank = 1;
            $result = [];
            foreach (array_slice($map, 0, 20, true) as $uid => $u) {
                arsort($u['types']);
                $result[] = [
                    'rank' => $rank++,
                    'user_id' => $uid,
                    'name' => $u['name'],
                    'initial' => mb_strtoupper(mb_substr($u['name'], 0, 2)),
                    'episodes' => $u['episodes'],
                    'titles' => $u['titles'],
                    'fav' => array_key_first($u['types']) ?? '-',
                    'last_active' => Carbon::parse($u['last'], 'UTC')->locale('id')->diffForHumans(),
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
