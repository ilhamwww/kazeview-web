<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Rekomendasi sederhana berbasis riwayat tontonan.
 *
 * Tidak memanggil API tambahan: judul yang pernah ditonton dicocokkan ke
 * katalog Filmbox (judul/slug), lalu genre yang sering ditonton dipakai untuk
 * memberi skor judul lain yang belum ditonton.
 */
class RecommendationService
{
    protected const BLOCKED_GENRES = ['hentai', 'erotica', 'adult', 'yaoi', 'yuri'];

    protected const PREFIXES = ['fb:', 'v2:', 'v3:', 'v4:', 'animek:', 'comic:', 'doujin:', 'vidio-', 'bstation:'];

    public static function forUser(?User $user, int $limit = 14): array
    {
        if (! $user) {
            return [];
        }

        return Cache::remember('kz_recommend_' . $user->id, 600, function () use ($user, $limit) {
            return self::build($user, $limit);
        });
    }

    public static function forget(int $userId): void
    {
        Cache::forget('kz_recommend_' . $userId);
    }

    protected static function build(User $user, int $limit): array
    {
        $histories = $user->watchHistories()
            ->whereIn('item_type', ['movie', 'series', 'indonesia_movie', 'indonesia_series', 'anime', 'dracin', 'bstation'])
            ->latest('updated_at')
            ->limit(60)
            ->get();

        if ($histories->isEmpty()) {
            return [];
        }

        $pool = array_merge(FilmboxService::pool('movie'), FilmboxService::pool('series'));

        if (empty($pool)) {
            return [];
        }

        $bySlug = [];
        $byTitle = [];
        foreach ($pool as $item) {
            $slug = self::slugify((string) ($item['slug'] ?? ''));
            if ($slug !== '') {
                $bySlug[$slug] = $item;
            }
            $key = self::titleKey((string) ($item['title'] ?? ''));
            if ($key !== '') {
                $byTitle[$key] ??= $item;
            }
        }

        $watchedSlugs = [];
        $watchedTitles = [];
        foreach ($histories as $h) {
            $watchedSlugs[self::slugify(self::slugFromHistory($h))] = true;
            $watchedTitles[self::titleKey((string) $h->title)] = true;
        }

        $genreWeight = [];
        $typeWeight = [];
        $anchor = null;

        foreach ($histories as $rank => $h) {
            $w = 1 / ($rank + 1);

            $match = null;
            $slug = self::slugify(self::slugFromHistory($h));
            if ($slug !== '' && isset($bySlug[$slug])) {
                $match = $bySlug[$slug];
            } else {
                $key = self::titleKey((string) $h->title);
                if ($key !== '' && isset($byTitle[$key])) {
                    $match = $byTitle[$key];
                }
            }

            if (! $match) {
                continue;
            }

            $anchor ??= $match;
            $typeWeight[$match['type']] = ($typeWeight[$match['type']] ?? 0) + $w;

            foreach (self::genresOf($match) as $g) {
                $genreWeight[$g] = ($genreWeight[$g] ?? 0) + $w;
            }
        }

        if (empty($genreWeight) || ! $anchor) {
            return [];
        }

        arsort($genreWeight);
        $topGenres = array_slice(array_keys($genreWeight), 0, 4);
        $anchorType = $anchor['type'] ?? 'movie';
        $anchorTitle = (string) ($anchor['title'] ?? '');

        $scored = [];
        foreach ($pool as $item) {
            $slug = self::slugify((string) ($item['slug'] ?? ''));
            if ($slug === '' || isset($watchedSlugs[$slug])) {
                continue;
            }
            if (isset($watchedTitles[self::titleKey((string) ($item['title'] ?? ''))])) {
                continue;
            }

            $genres = self::genresOf($item);
            if (array_intersect($genres, self::BLOCKED_GENRES)) {
                continue;
            }

            $score = 0.0;
            foreach ($genres as $g) {
                if (isset($genreWeight[$g])) {
                    $score += $genreWeight[$g] * 10;
                }
            }

            if ($score <= 0) {
                continue;
            }

            if (($item['type'] ?? 'movie') === $anchorType) {
                $score += 1.5;
            }

            $score += ((float) ($item['rating'] ?? 0)) * 0.15;

            $year = (int) ($item['year'] ?? 0);
            if ($year >= 2024) {
                $score += 0.6;
            }

            $scored[] = ['score' => $score, 'item' => $item];
        }

        if (empty($scored)) {
            return [];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $items = array_map(fn ($row) => $row['item'], array_slice($scored, 0, $limit));

        return [
            'title' => $anchorTitle !== '' ? 'Karena kamu menonton ' . $anchorTitle : 'Pilihan untukmu',
            'genres' => $topGenres,
            'items' => array_values($items),
        ];
    }

    protected static function genresOf(array $item): array
    {
        $raw = (string) ($item['genre'] ?? '');
        $out = [];

        foreach (preg_split('/\s*[,|\/]\s*/', $raw) as $g) {
            $g = strtolower(trim($g));
            if ($g !== '') {
                $out[] = $g;
            }
        }

        return array_values(array_unique($out));
    }

    protected static function slugFromHistory($history): string
    {
        $url = (string) ($history->url ?? '');
        if ($url !== '') {
            $path = parse_url($url, PHP_URL_PATH) ?: $url;
            $segments = array_values(array_filter(explode('/', trim($path, '/'))));

            if (! empty($segments)) {
                $last = end($segments);
                if ($last !== '' && ! ctype_digit($last)) {
                    return $last;
                }
            }
        }

        $id = (string) ($history->item_id ?? '');
        foreach (self::PREFIXES as $prefix) {
            if (str_starts_with($id, $prefix)) {
                $id = substr($id, strlen($prefix));
                break;
            }
        }

        if (str_contains($id, ':')) {
            $parts = explode(':', $id);
            $id = end($parts) ?: $id;
        }

        return $id;
    }

    protected static function titleKey(string $title): string
    {
        $title = strtolower(trim($title));
        $title = preg_replace('/[^a-z0-9]+/', ' ', $title) ?? '';

        return trim(preg_replace('/\s+/', ' ', $title) ?? '');
    }

    protected static function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
