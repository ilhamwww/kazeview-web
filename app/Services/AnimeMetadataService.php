<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnimeMetadataService
{
    protected static string $endpoint = 'https://graphql.anilist.co';

    /**
     * Bersihkan judul dari embel-embel lokal seperti "Sub Indo", "Season 2", dll.
     */
    public static function cleanTitle(string $raw): string
    {
        $c = preg_replace('/(sub\s*indo|subtitle\s*indonesia|tamat|end|batch|\bep\s*\d+\b|\bepisode\s*\d+\b)/i', '', $raw);
        $c = preg_replace('/\[[^\]]*\]|\([^\)]*\)/', '', $c);
        $c = preg_replace('/\s+/', ' ', $c);
        return trim($c);
    }

    /**
     * Ambil metadata resmi dari AniList / MyAnimeList
     */
    public static function getMetadata(string $title, ?int $malId = null): ?array
    {
        $clean = self::cleanTitle($title);
        if (empty($clean)) {
            return null;
        }

        $cacheKey = 'anime_meta_' . md5(strtolower($clean) . '_' . ($malId ?? ''));

        return Cache::remember($cacheKey, 86400, function () use ($clean, $malId) {
            try {
                $query = <<<'GRAPHQL'
query ($search: String, $idMal: Int) {
    Media (search: $search, idMal: $idMal, type: ANIME) {
        id
        idMal
        title {
            romaji
            english
            native
        }
        status
        description(asHtml: false)
        season
        seasonYear
        episodes
        duration
        format
        averageScore
        genres
        studios(isMain: true) {
            nodes {
                name
            }
        }
        nextAiringEpisode {
            episode
            airingAt
            timeUntilAiring
        }
        startDate {
            year
            month
            day
        }
        endDate {
            year
            month
            day
        }
        coverImage {
            extraLarge
            large
            color
        }
        bannerImage
    }
}
GRAPHQL;

                $variables = [];
                if ($malId) {
                    $variables['idMal'] = $malId;
                } else {
                    $variables['search'] = $clean;
                }

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'KZSTRM-AnimeHub/1.0',
                ])->timeout(8)->post(self::$endpoint, [
                    'query' => $query,
                    'variables' => $variables,
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $media = $response->json('data.Media');
                if (!$media) {
                    return null;
                }

                // Format Tanggal Rilis
                $releaseDate = null;
                if (!empty($media['startDate']['year'])) {
                    try {
                        $y = (int) $media['startDate']['year'];
                        $m = (int) ($media['startDate']['month'] ?? 1);
                        $d = (int) ($media['startDate']['day'] ?? 1);
                        $dt = Carbon::create($y, $m, $d);
                        if ($dt) {
                            $releaseDate = $dt->locale('id')->isoFormat('D MMMM Y');
                        }
                    } catch (\Throwable $e) {}
                }

                // Format Hari Tayang & Jadwal Berikutnya
                $broadcastDay = null;
                $nextEpText = null;
                $isAiring = ($media['status'] ?? '') === 'RELEASING';

                if (!empty($media['nextAiringEpisode'])) {
                    $next = $media['nextAiringEpisode'];
                    try {
                        $airingDt = Carbon::createFromTimestamp($next['airingAt'])
                            ->setTimezone('Asia/Jakarta')
                            ->locale('id');
                        $broadcastDay = 'Setiap Hari ' . $airingDt->isoFormat('dddd');
                        $nextEpText = 'Episode ' . $next['episode'] . ' tayang ' . $airingDt->isoFormat('dddd, D MMMM Y • HH:mm') . ' WIB';
                    } catch (\Throwable $e) {}
                }

                // Format Skor
                $score = !empty($media['averageScore']) ? number_format($media['averageScore'] / 10, 1) : null;

                // Status Bahasa Indonesia
                $statusIndo = match ($media['status'] ?? '') {
                    'RELEASING' => 'Sedang Tayang (Ongoing)',
                    'FINISHED' => 'Tamat (Completed)',
                    'NOT_YET_RELEASED' => 'Segera Tayang',
                    'CANCELLED' => 'Dibatalkan',
                    default => 'N/A'
                };

                return [
                    'mal_id' => $media['idMal'] ?? null,
                    'anilist_id' => $media['id'] ?? null,
                    'title' => $media['title']['romaji'] ?? $media['title']['english'] ?? $clean,
                    'english_title' => $media['title']['english'] ?? null,
                    'native_title' => $media['title']['native'] ?? null,
                    'score' => $score,
                    'status' => $statusIndo,
                    'is_airing' => $isAiring,
                    'release_date' => $releaseDate,
                    'broadcast_day' => $broadcastDay,
                    'next_episode_schedule' => $nextEpText,
                    'total_episodes' => $media['episodes'] ?? null,
                    'duration' => !empty($media['duration']) ? $media['duration'] . ' Menit / Eps' : null,
                    'format' => $media['format'] ?? 'TV',
                    'studio' => $media['studios']['nodes'][0]['name'] ?? 'N/A',
                    'genres' => $media['genres'] ?? [],
                    'synopsis' => strip_tags($media['description'] ?? ''),
                    'poster' => $media['coverImage']['extraLarge'] ?? ($media['coverImage']['large'] ?? null),
                    'banner' => $media['bannerImage'] ?? null,
                    'accent_color' => $media['coverImage']['color'] ?? '#10B981',
                ];
            } catch (\Throwable $e) {
                Log::warning('AnimeMetadataService error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Ambil jadwal tayang mingguan anime (Senin - Minggu) dari AniList GraphQL
     */
    public static function getWeeklySchedule(): array
    {
        return Cache::remember('anime_weekly_schedule_v1', 21600, function () {
            try {
                $now = time();
                $monday = strtotime('monday this week 00:00:00 UTC', $now);
                $sunday = $monday + (7 * 86400) - 1;

                $query = <<<'GRAPHQL'
query ($start: Int, $end: Int, $page: Int) {
  Page(page: $page, perPage: 50) {
    pageInfo { hasNextPage }
    airingSchedules(airingAt_greater: $start, airingAt_lesser: $end, sort: TIME) {
      id
      airingAt
      episode
      media {
        id
        idMal
        title { romaji english userPreferred }
        coverImage { extraLarge large }
        format
        genres
        averageScore
        popularity
      }
    }
  }
}
GRAPHQL;

                $allSchedules = [];
                for ($page = 1; $page <= 3; $page++) {
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'User-Agent' => 'KZSTRM-AnimeHub/1.0',
                    ])->timeout(8)->post(self::$endpoint, [
                        'query' => $query,
                        'variables' => [
                            'start' => $monday,
                            'end' => $sunday,
                            'page' => $page,
                        ],
                    ]);

                    if (!$response->successful()) break;
                    $data = $response->json();
                    $items = $data['data']['Page']['airingSchedules'] ?? [];
                    $allSchedules = array_merge($allSchedules, $items);
                    if (!($data['data']['Page']['pageInfo']['hasNextPage'] ?? false)) break;
                }

                $dayMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                $byDay = [];
                foreach ($dayMap as $name) {
                    $byDay[$name] = [];
                }

                foreach ($allSchedules as $s) {
                    $media = $s['media'] ?? null;
                    if (!$media) continue;
                    $fmt = $media['format'] ?? '';
                    if (!in_array($fmt, ['TV', 'ONA', 'TV_SHORT'])) continue;

                    $dt = (new \DateTime())->setTimestamp($s['airingAt'])->setTimezone(new \DateTimeZone('Asia/Jakarta'));
                    $dIndex = (int)$dt->format('N');
                    $dName = $dayMap[$dIndex] ?? 'Senin';
                    $time = $dt->format('H:i') . ' WIB';

                    $title = $media['title']['userPreferred'] ?? ($media['title']['romaji'] ?? ($media['title']['english'] ?? 'Anime'));
                    $score = !empty($media['averageScore']) ? number_format($media['averageScore'] / 10, 1) : null;

                    $byDay[$dName][] = [
                        'title' => $title,
                        'time' => $time,
                        'episode' => $s['episode'],
                        'poster' => $media['coverImage']['large'] ?? ($media['coverImage']['extraLarge'] ?? ''),
                        'score' => $score,
                        'popularity' => $media['popularity'] ?? 0,
                        'genres' => array_slice($media['genres'] ?? [], 0, 2),
                    ];
                }

                foreach ($byDay as $d => &$list) {
                    usort($list, fn($a, $b) => $b['popularity'] <=> $a['popularity']);
                }

                return $byDay;
            } catch (\Throwable $e) {
                Log::warning('AnimeMetadataService getWeeklySchedule error: ' . $e->getMessage());
                return [];
            }
        });
    }
}
