<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SamehadakuScraperService
{
    protected string $baseUrl;
    protected string $scriptPath;
    protected string $executablePath;

    public function __construct()
    {
        $this->baseUrl = config('services.samehadaku.base_url', 'https://v2.samehadaku.how');
        $this->scriptPath = base_path('puppeteer_samehadaku.js');
        $this->executablePath = env('PUPPETEER_EXECUTABLE_PATH', file_exists('/opt/puppeteer-chrome/chrome') ? '/opt/puppeteer-chrome/chrome' : '/usr/bin/google-chrome');
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Run Puppeteer Samehadaku script to get structured JSON data.
     */
    protected function executePuppeteer(string $mode, string $arg1 = '', string $arg2 = ''): array
    {
        $cmd = 'node --dns-result-order=ipv4first ' . escapeshellarg($this->scriptPath) . ' ' . escapeshellarg($mode);
        if ($arg1 !== '') {
            $cmd .= ' ' . escapeshellarg($arg1);
        }
        if ($arg2 !== '') {
            $cmd .= ' ' . escapeshellarg($arg2);
        }
        $cmd .= ' 2>&1';

        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);

        $rawOutput = implode("\n", $output);

        if ($returnVar !== 0) {
            Log::error("Samehadaku Puppeteer failed ({$mode}): {$rawOutput}");
            return [];
        }

        // Find JSON start
        $jsonStart = strpos($rawOutput, '{');
        $arrStart = strpos($rawOutput, '[');

        $startIdx = false;
        if ($jsonStart !== false && $arrStart !== false) {
            $startIdx = min($jsonStart, $arrStart);
        } elseif ($jsonStart !== false) {
            $startIdx = $jsonStart;
        } elseif ($arrStart !== false) {
            $startIdx = $arrStart;
        }

        if ($startIdx === false) {
            Log::warning("Samehadaku Puppeteer returned no JSON: {$rawOutput}");
            return [];
        }

        $jsonStr = substr($rawOutput, $startIdx);
        $decoded = json_decode($jsonStr, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Scrape genres list.
     */
    public function scrapeGenres(): array
    {
        return Cache::remember('v4_samehada_genres', 86400, function () {
            $data = $this->executePuppeteer('genres');
            return is_array($data) ? $data : [];
        });
    }

    /**
     * Search anime by query.
     */
    public function searchAnime(string $query): array
    {
        if (blank($query)) {
            return [];
        }

        $cacheKey = 'v4_samehada_search_' . md5(trim(strtolower($query)));
        return Cache::remember($cacheKey, 3600, function () use ($query) {
            $res = $this->executePuppeteer('search', $query);
            return $res['items'] ?? [];
        });
    }

    /**
     * Scrape anime by genre.
     */
    public function scrapeAnimeByGenre(string $genreSlug, int $page = 1): array
    {
        $cacheKey = "v4_samehada_genre_{$genreSlug}_{$page}";
        return Cache::remember($cacheKey, 21600, function () use ($genreSlug, $page) {
            $res = $this->executePuppeteer('genre', $genreSlug, (string) $page);
            return $res['items'] ?? [];
        });
    }

    /**
     * Scrape anime catalog list (all, ongoing, completed).
     */
    public function scrapeAnimeList(int $page = 1, string $status = ''): array
    {
        $cacheKey = "v4_samehada_list_{$status}_{$page}";
        return Cache::remember($cacheKey, 14400, function () use ($page, $status) {
            $res = $this->executePuppeteer('catalog', (string) $page, $status);
            return $res['items'] ?? [];
        });
    }

    /**
     * Scrape homepage (Ongoing releases & Top 10).
     */
    public function scrapeHomepage(): array
    {
        return Cache::remember('v4_samehada_home', 900, function () {
            return $this->executePuppeteer('home');
        });
    }

    /**
     * Scrape anime series detail.
     */
    public function scrapeAnimeSeriesDetail(string $urlOrSlug): ?array
    {
        $rawSlug = $this->extractSlug($urlOrSlug);
        if (empty($rawSlug)) {
            return null;
        }

        $cleanSlug = preg_replace('/-episode-\d+.*$/i', '', $rawSlug);
        $cacheKey = 'v4_samehada_detail_' . md5($cleanSlug);

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached['title']) && !str_contains(strtolower($cached['title']), '404')) {
            return $cached;
        }

        $data = $this->executePuppeteer('detail', $cleanSlug);
        if (
            empty($data) ||
            empty($data['title']) ||
            str_contains(strtolower($data['title']), '404') ||
            (!empty($data['synopsis']) && str_contains(strtolower($data['synopsis']), 'not exist'))
        ) {
            return null;
        }

        $data['slug'] = $cleanSlug;
        $data['url'] = rtrim($this->baseUrl, '/') . '/anime/' . $cleanSlug . '/';
        Cache::put($cacheKey, $data, 21600);

        return $data;
    }

    /**
     * Scrape episode detail (players & downloads).
     */
    public function scrapeEpisodeDetail(string $urlOrSlug): ?array
    {
        $slug = $this->extractSlug($urlOrSlug);
        if (empty($slug)) {
            return null;
        }

        $cacheKey = 'v4_samehada_ep_' . md5($slug);
        return Cache::remember($cacheKey, 21600, function () use ($slug) {
            $data = $this->executePuppeteer('episode', $slug);
            if (empty($data['title']) && empty($data['mirrors'])) {
                return null;
            }
            $data['slug'] = $slug;
            $data['episode_url'] = rtrim($this->baseUrl, '/') . '/' . $slug . '/';
            return $data;
        });
    }

    /**
     * Extract slug from URL or bare slug string.
     */
    protected function extractSlug(string $urlOrSlug): string
    {
        if (str_starts_with($urlOrSlug, 'http')) {
            $path = parse_url($urlOrSlug, PHP_URL_PATH);
            $parts = array_values(array_filter(explode('/', trim((string) $path, '/'))));
            return end($parts) ?: '';
        }

        return trim($urlOrSlug, '/');
    }
}
