<?php

namespace App\Console\Commands;

use App\Models\DriveEpisode;
use App\Services\AnimeDriveSyncer;
use App\Services\AnimekompiService;
use Illuminate\Console\Command;

class AnimeDriveSync extends Command
{
    protected $signature = 'anime:drive-sync
        {--anime= : Slug/path anime di Animekompi (mis. overgeared)}
        {--episodes= : Daftar episode, mis. 1-9 atau 8,9}
        {--limit=1 : Jumlah episode terbaru per judul yang diproses (mode ongoing)}
        {--max=0 : Batas total episode yang disinkron dalam satu jalan (0 = tanpa batas)}
        {--dry-run : Hanya tampilkan rencana, tidak mengunduh}';

    protected $description = 'Sinkronkan episode anime 1080p dari Otakudesu ke Google Drive';

    public function handle(AnimeDriveSyncer $syncer): int
    {
        $dry = (bool) $this->option('dry-run');

        if ($anime = $this->option('anime')) {
            return $this->syncOneAnime($syncer, (string) $anime, $dry);
        }

        return $this->syncOngoing($syncer, $dry);
    }

    /* ------------------------------------------------------------------ */

    protected function syncOneAnime(AnimeDriveSyncer $syncer, string $path, bool $dry): int
    {
        $detail = AnimekompiService::detail($path);

        if (! $detail) {
            $this->error("Anime tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $title = (string) ($detail['title'] ?? $path);
        $otakuUrl = $this->findOtakuUrl($syncer, $title);

        if (! $otakuUrl) {
            $this->error("Tidak ada padanan di Otakudesu untuk: {$title}");

            return self::FAILURE;
        }

        $otaku = $syncer->otakuEpisodes($otakuUrl);
        $animek = $syncer->animekEpisodes($path);

        if (! $otaku || ! $animek) {
            $this->error('Daftar episode kosong (Otakudesu atau Animekompi)');

            return self::FAILURE;
        }

        $wanted = $this->parseEpisodes((string) $this->option('episodes'), array_keys($animek));

        if (! $wanted) {
            $wanted = array_slice(array_keys($animek), -1);
        }

        $this->info("{$title} → Otakudesu: {$otakuUrl}");
        $season = (int) (AnimeDriveSyncer::parseTitle($title)[1] ?: 1);
        $animePath = $detail['slug'] ?? $path;
        $ok = 0;

        foreach ($wanted as $num) {
            if (! isset($animek[$num], $otaku[$num])) {
                $this->warn("Ep {$num}: tidak ada di salah satu sisi, dilewati");

                continue;
            }

            $res = $syncer->syncEpisode($animePath, $title, $season, (int) $num, (string) $animek[$num], (string) $otaku[$num], $dry);
            $this->line("Ep {$num}: [{$res['status']}] {$res['message']}");

            if ($res['status'] === 'ok' || $res['status'] === 'dry') {
                $ok++;
            }
        }

        $this->info("Selesai: {$ok} episode");

        return self::SUCCESS;
    }

    /* ------------------------------------------------------------------ */

    protected function syncOngoing(AnimeDriveSyncer $syncer, bool $dry): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $max = max(0, (int) $this->option('max'));
        $total = 0;
        $index = null;

        foreach ([1, 2, 3] as $page) {
            $list = $syncer->otakuOngoing($page);

            foreach ($list as $row) {
                $title = (string) ($row['title'] ?? '');
                $url = (string) ($row['url'] ?? '');

                if ($title === '' || $url === '') {
                    continue;
                }

                $index = $index ?: $syncer->catalogIndexPublic();
                $match = $syncer->matchAnimek($title, $index);

                if (! $match) {
                    continue;
                }

                $path = (string) ($match['path'] ?? '');
                $episodes = $syncer->animekEpisodes($path);
                $otaku = $syncer->otakuEpisodes($url);

                if (! $episodes || ! $otaku) {
                    continue;
                }

                $season = (int) (AnimeDriveSyncer::parseTitle((string) $match['title'])[1] ?: 1);
                $targets = array_slice(array_keys($episodes), -$limit);

                foreach ($targets as $num) {
                    if (! isset($otaku[$num])) {
                        continue;
                    }

                    if (DriveEpisode::where('episode_id', $episodes[$num])->exists()) {
                        continue;
                    }

                    $res = $syncer->syncEpisode($path, (string) $match['title'], $season, (int) $num, (string) $episodes[$num], (string) $otaku[$num], $dry);
                    $this->line("{$match['title']} Ep {$num}: [{$res['status']}] {$res['message']}");

                    if ($res['status'] === 'ok' || $res['status'] === 'dry') {
                        $total++;
                    }

                    if ($max > 0 && $total >= $max) {
                        $this->info("Batas {$max} episode tercapai.");

                        return self::SUCCESS;
                    }
                }
            }
        }

        $this->info("Selesai: {$total} episode diproses");

        return self::SUCCESS;
    }

    protected function findOtakuUrl(AnimeDriveSyncer $syncer, string $title): ?string
    {
        [$base, $season, $part] = AnimeDriveSyncer::parseTitle($title);

        $rows = $syncer->otakuSearch($base);

        if (! $rows) {
            $rows = [];
            foreach ([1, 2, 3] as $page) {
                $rows = array_merge($rows, $syncer->otakuOngoing($page));
            }
        }

        foreach ($rows as $row) {
            $candidate = (string) ($row['title'] ?? '');
            $url = (string) ($row['url'] ?? '');

            if ($url === '') {
                continue;
            }

            [$cb, $cs, $cp] = AnimeDriveSyncer::parseTitle($candidate);

            if ($cb === $base && $cs === $season && $cp === $part) {
                return $url;
            }
        }

        return null;
    }

    /** @return array<int, int> */
    protected function parseEpisodes(string $spec, array $available): array
    {
        $spec = trim($spec);

        if ($spec === '') {
            return [];
        }

        $out = [];

        foreach (explode(',', $spec) as $chunk) {
            $chunk = trim($chunk);

            if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $chunk, $m)) {
                foreach (range((int) $m[1], (int) $m[2]) as $n) {
                    if (in_array($n, $available, true)) {
                        $out[] = $n;
                    }
                }
            } elseif (ctype_digit($chunk) && in_array((int) $chunk, $available, true)) {
                $out[] = (int) $chunk;
            }
        }

        return array_values(array_unique($out));
    }
}
