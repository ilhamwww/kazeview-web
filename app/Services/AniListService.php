<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * AniList GraphQL sebagai sumber judul kanonik (gratis, tanpa API key).
 *
 * Satu anime punya romaji/english/native + synonyms, jadi judul yang berbeda
 * antar sumber (mis. "Tempal: Item no Chikara" vs "Overgeared") tetap bisa
 * dipetakan ke id yang sama.
 */
class AniListService
{
    protected const ENDPOINT = 'https://graphql.anilist.co';

    protected const QUERY = <<<'GQL'
    query ($search: String) {
      Page(perPage: 6) {
        media(search: $search, type: ANIME, sort: SEARCH_MATCH) {
          id
          episodes
          seasonYear
          format
          title { romaji english native }
          synonyms
        }
      }
    }
    GQL;

    /** @return array<int, array> */
    public function search(string $title): array
    {
        $title = trim($title);

        if ($title === '') {
            return [];
        }

        $key = 'anilist_search_' . md5(mb_strtolower($title));

        return Cache::remember($key, 86400, function () use ($title) {
            $res = Http::timeout(20)
                ->acceptJson()
                ->post(self::ENDPOINT, [
                    'query' => self::QUERY,
                    'variables' => ['search' => $title],
                ]);

            if (! $res->successful()) {
                return [];
            }

            return (array) ($res->json('data.Page.media') ?? []);
        });
    }

    /** Semua varian judul dari satu hasil AniList. */
    public static function titles(array $media): array
    {
        $titles = [
            $media['title']['romaji'] ?? null,
            $media['title']['english'] ?? null,
            $media['title']['native'] ?? null,
        ];

        foreach ((array) ($media['synonyms'] ?? []) as $syn) {
            $titles[] = $syn;
        }

        return array_values(array_unique(array_filter(array_map('strval', $titles))));
    }

    /**
     * Cari media yang paling cocok untuk sebuah judul.
     * Cocokkan versi tanpa season/part supaya "Season 2" dan "2nd Season" setara.
     */
    public function bestMatch(string $title): ?array
    {
        [$base, $season, $part] = AnimeDriveSyncer::parseTitle($title);

        $best = null;
        $bestScore = 0;

        $candidates = $this->search($title);

        // Judul panjang dengan embel-embel season sering tidak ketemu; coba
        // juga versi dasar tanpa "Season N"/"Part N".
        if ($base !== '' && $base !== $title) {
            $seen = array_column($candidates, 'id');
            foreach ($this->search($base) as $alt) {
                if (! in_array($alt['id'] ?? null, $seen, true)) {
                    $candidates[] = $alt;
                }
            }
        }

        foreach ($candidates as $media) {
            foreach (self::titles($media) as $candidate) {
                [$cb, $cs, $cp] = AnimeDriveSyncer::parseTitle($candidate);

                if ($cb === '' || $cb !== $base) {
                    continue;
                }

                if ($season > 0 && $cs > 0 && $cs !== $season) {
                    continue;
                }

                if ($part > 0 && $cp > 0 && $cp !== $part) {
                    continue;
                }

                $score = 100 - abs($season - $cs) - abs($part - $cp);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $media;
                }
            }
        }

        return $best;
    }
}
