<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class AnimeDownloadResolverService
{
    /**
     * Dapatkan link download episode dan batch dari Otakudesu
     */
    public static function getDownloads(string $title, ?string $episodeNum = null): array
    {
        $cleanTitle = AnimeMetadataService::cleanTitle($title);
        if (empty($cleanTitle)) {
            return ['episode' => [], 'batch' => []];
        }

        $cacheKey = 'anime_dl_' . md5(strtolower($cleanTitle) . '_' . ($episodeNum ?? 'all'));

        return Cache::remember($cacheKey, 43200, function () use ($cleanTitle, $episodeNum) {
            try {
                $scraper = new OtakudesuScraperService();
                $search = $scraper->searchAnime($cleanTitle);

                if (empty($search)) {
                    // Coba kata pertama jika judul panjang
                    $words = explode(' ', $cleanTitle);
                    if (count($words) > 1) {
                        $search = $scraper->searchAnime($words[0] . ' ' . $words[1]);
                    }
                }

                if (empty($search)) {
                    return ['episode' => [], 'batch' => []];
                }

                // Ambil hasil pencarian yang paling cocok
                $match = $search[0];
                foreach ($search as $s) {
                    $sTitle = strtolower($s['title'] ?? '');
                    if (str_contains($sTitle, strtolower($cleanTitle))) {
                        $match = $s;
                        break;
                    }
                }

                $seriesDetail = $scraper->scrapeAnimeSeriesDetail($match['url']);
                if (!$seriesDetail || empty($seriesDetail['episodes'])) {
                    return ['episode' => [], 'batch' => []];
                }

                $batchDownloads = [];
                $targetEpUrl = null;

                foreach ($seriesDetail['episodes'] as $ep) {
                    $epUrl = $ep['url'] ?? '';

                    // Cek jika ini URL Batch
                    if (str_contains($epUrl, '/batch/')) {
                        if (empty($batchDownloads)) {
                            $batchDownloads = self::scrapeBatchDownloads($epUrl);
                        }
                        continue;
                    }

                    // Cocokkan nomor episode
                    if ($episodeNum !== null) {
                        $currNum = (string) ($ep['episode'] ?? '');
                        if ($currNum === (string) $episodeNum || str_contains($ep['title'] ?? '', 'Episode ' . $episodeNum . ' ') || str_ends_with($ep['title'] ?? '', 'Episode ' . $episodeNum)) {
                            $targetEpUrl = $epUrl;
                            break;
                        }
                    }
                }

                $episodeDownloads = [];
                if ($targetEpUrl) {
                    $epDetail = $scraper->scrapeEpisodeDetail($targetEpUrl);
                    $episodeDownloads = $epDetail['downloads'] ?? [];
                } elseif ($episodeNum === null && !empty($seriesDetail['episodes'])) {
                    // Default ambil episode terakhir atau pertama
                    $first = null;
                    foreach ($seriesDetail['episodes'] as $ep) {
                        if (!str_contains($ep['url'] ?? '', '/batch/')) {
                            $first = $ep;
                            break;
                        }
                    }
                    if ($first) {
                        $epDetail = $scraper->scrapeEpisodeDetail($first['url']);
                        $episodeDownloads = $epDetail['downloads'] ?? [];
                    }
                }

                return [
                    'source' => 'Otakudesu',
                    'otaku_title' => $match['title'] ?? '',
                    'otaku_url' => $match['url'] ?? '',
                    'episode_num' => $episodeNum,
                    'episode' => $episodeDownloads,
                    'batch' => $batchDownloads,
                ];

            } catch (\Throwable $e) {
                Log::warning('AnimeDownloadResolverService failed: ' . $e->getMessage());
                return ['episode' => [], 'batch' => []];
            }
        });
    }

    /**
     * Scrape link download batch Otakudesu
     */
    protected static function scrapeBatchDownloads(string $batchUrl): array
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10, 'verify' => false]);
            $res = $client->get($batchUrl, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0.0.0 Safari/537.36',
                ],
            ]);
            $html = (string) $res->getBody();
            $crawler = new Crawler($html);

            $batches = [];
            $crawler->filter('.batchlink ul li')->each(function (Crawler $node) use (&$batches) {
                $format = trim($node->filter('strong')->text(''));
                $size = trim($node->filter('i')->text(''));
                $links = [];

                $node->filter('a')->each(function (Crawler $a) use (&$links) {
                    $host = trim($a->text(''));
                    $url = $a->attr('href');
                    if ($url && $host) {
                        $links[] = [
                            'host' => $host,
                            'url' => $url,
                        ];
                    }
                });

                if (!empty($links)) {
                    $batches[] = [
                        'format' => $format,
                        'size' => $size,
                        'links' => $links,
                    ];
                }
            });

            return $batches;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
