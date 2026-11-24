<?php

namespace App\Console\Commands;

use App\Models\AnimeMap;
use App\Services\AniListService;
use App\Services\AnimeDriveSyncer;
use App\Services\AnimekompiService;
use Illuminate\Console\Command;

class AnimeMapTitles extends Command
{
    protected $signature = 'anime:map
        {--limit=25 : Jumlah judul ongoing yang diproses}
        {--save : Simpan hasil pencocokan ke tabel anime_maps}
        {--all : Proses semua halaman ongoing}';

    protected $description = 'Petakan judul Otakudesu ke Animekompi lewat AniList';

    public function handle(AniListService $aniList): int
    {
        $index = [];
        foreach (AnimekompiService::catalog() as $item) {
            [$base, $season, $part] = AnimeDriveSyncer::parseTitle((string) ($item['title'] ?? ''));
            if ($base !== '') {
                $index[$base][] = ['season' => $season, 'part' => $part, 'item' => $item];
            }
        }

        $limit = (int) $this->option('limit');
        $rows = [];

        foreach ([1, 2, 3] as $page) {
            $rows = array_merge($rows, (new \App\Services\OtakudesuScraperService())->scrapeAnimeList($page, 'ongoing'));
            if (! $this->option('all') && count($rows) >= $limit) {
                break;
            }
        }

        $rows = array_slice($rows, 0, $this->option('all') ? null : $limit);
        $hit = 0;
        $miss = 0;

        foreach ($rows as $row) {
            $title = (string) ($row['title'] ?? '');
            $url = (string) ($row['url'] ?? '');
            $slug = basename(rtrim(parse_url($url, PHP_URL_PATH) ?: '', '/'));

            if ($title === '') {
                continue;
            }

            $media = $aniList->bestMatch($title);

            if (! $media) {
                $miss++;
                $this->line("<fg=yellow>MISS</> {$title}");

                continue;
            }

            $match = null;

            foreach (AniListService::titles($media) as $candidate) {
                [$base, $season, $part] = AnimeDriveSyncer::parseTitle($candidate);

                foreach (($index[$base] ?? []) as $entry) {
                    if ($entry['season'] === $season && $entry['part'] === $part) {
                        $match = $entry['item'];
                        break 2;
                    }
                }
            }

            if (! $match) {
                $miss++;
                $this->line("<fg=yellow>NO-ANIMEK</> {$title} (AniList {$media['id']})");

                continue;
            }

            $hit++;
            $this->line("<fg=green>OK</> {$title} → {$match['title']} (AniList {$media['id']})");

            if ($this->option('save')) {
                AnimeMap::updateOrCreate(['otaku_slug' => $slug], [
                    'otaku_title' => $title,
                    'animek_path' => $match['path'] ?? null,
                    'animek_title' => $match['title'] ?? null,
                    'anilist_id' => $media['id'] ?? null,
                    'source' => 'anilist',
                ]);
            }
        }

        $this->info("cocok={$hit} tidak={$miss} dari " . count($rows));

        return self::SUCCESS;
    }
}
