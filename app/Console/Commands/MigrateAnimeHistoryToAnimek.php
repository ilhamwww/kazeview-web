<?php

namespace App\Console\Commands;

use App\Models\WatchHistory;
use App\Services\AnimekompiService;
use Illuminate\Console\Command;

/**
 * Pindahkan riwayat nonton anime dari Mirainime (arsip /anime-v2)
 * ke Animekompi (/animek) untuk judul yang punya padanan.
 *
 * Riwayat yang tidak punya padanan dibiarkan: URL lamanya tetap hidup
 * lewat redirect /anime/{slug} -> /anime-v2/{slug}.
 */
class MigrateAnimeHistoryToAnimek extends Command
{
    protected $signature = 'kz:migrate-anime-history {--dry-run : Hanya laporkan, tidak menulis}';

    protected $description = 'Petakan riwayat anime Mirainime ke judul Animekompi';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $rows = WatchHistory::where('item_type', 'anime')->get();

        if ($rows->isEmpty()) {
            $this->info('Tidak ada riwayat anime.');

            return self::SUCCESS;
        }

        $catalog = AnimekompiService::catalog();
        $byTitle = [];

        foreach ($catalog as $item) {
            $byTitle[$this->norm($item['title'])][] = $item;
        }

        $details = [];
        $migrated = 0;
        $kept = 0;
        $failed = 0;
        $unmatched = [];

        foreach ($rows as $row) {
            // Sudah pernah dimigrasikan.
            if (str_starts_with((string) $row->item_id, 'animek:')) {
                continue;
            }

            $match = $this->match((string) $row->title, $byTitle);

            if (!$match) {
                $kept++;
                $unmatched[$row->title] = true;
                continue;
            }

            $path = $match['path'];
            $details[$path] ??= AnimekompiService::detail($path);

            if (empty($details[$path]['episodes'])) {
                $failed++;
                continue;
            }

            $episode = $this->pickEpisode($details[$path]['episodes'], (int) $row->last_episode);

            if (!$episode) {
                $failed++;
                continue;
            }

            // Episode bertanda "uncen" tidak dipindahkan ke hub utama.
            if (stripos((string) $episode['id'], 'uncen') !== false) {
                $kept++;
                $unmatched[$row->title] = true;
                continue;
            }

            $url = url('/animek/' . $path . '/' . $episode['id']);

            if (!$dry) {
                $newId = 'animek:' . $path;

                // Satu judul lama bisa punya beberapa baris (mis. season berbeda).
                // Kalau baris tujuan sudah ada, simpan yang paling baru lalu hapus duplikatnya.
                $conflict = WatchHistory::where('user_id', $row->user_id)
                    ->where('item_type', 'anime')
                    ->where('item_id', $newId)
                    ->where('id', '!=', $row->id)
                    ->first();

                $fresh = [
                    'title' => $match['title'],
                    'url' => $url,
                    'thumbnail' => $match['thumbnail'] ?: $row->thumbnail,
                    'last_episode' => $row->last_episode,
                    'progress_seconds' => $row->progress_seconds,
                    'duration_seconds' => $row->duration_seconds,
                    'progress_percent' => $row->progress_percent,
                    'watched_episodes' => $row->watched_episodes,
                ];

                if ($conflict) {
                    if ($row->updated_at >= $conflict->updated_at) {
                        $conflict->forceFill($fresh)->save();
                    }
                    $row->delete();
                } else {
                    $row->forceFill($fresh + ['item_id' => $newId])->save();
                }
            }

            $migrated++;
        }

        $this->table(['Keterangan', 'Jumlah'], [
            ['Dipetakan ke Animekompi', $migrated],
            ['Tanpa padanan (tetap di arsip)', $kept],
            ['Gagal (judul ada, episode tidak)', $failed],
        ]);

        if ($unmatched) {
            $this->line('Judul tanpa padanan: ' . implode(' | ', array_slice(array_keys($unmatched), 0, 10)));
        }

        if ($dry) {
            $this->warn('Mode uji: tidak ada data yang diubah.');
        }

        return self::SUCCESS;
    }

    protected function norm(string $title): string
    {
        $t = mb_strtolower($title);
        $t = preg_replace('/\b(sub\s*indo|subtitle\s*indonesia|sub\s*indonesia)\b/u', ' ', $t) ?? $t;
        $t = preg_replace('/[^a-z0-9\s]/u', ' ', $t) ?? $t;

        return trim(preg_replace('/\s+/', ' ', $t) ?? $t);
    }

    /**
     * Cari padanan: persis dulu, lalu tanpa penanda season, lalu kemiripan tinggi.
     * Season harus sama supaya episode tidak nyasar ke musim lain.
     */
    protected function match(string $title, array $byTitle): ?array
    {
        $t = $this->norm($title);

        if ($t === '') {
            return null;
        }

        if (!empty($byTitle[$t])) {
            return $byTitle[$t][0];
        }

        $season = $this->season($t);
        $base = trim(preg_replace('/\bseason\s*\d+\b/u', '', $t) ?? $t);
        $base = trim(preg_replace('/\s+/', ' ', $base) ?? $base);

        if ($base !== '' && !empty($byTitle[$base])) {
            foreach ($byTitle[$base] as $candidate) {
                if ($this->season($this->norm((string) $candidate['title'])) === $season) {
                    return $candidate;
                }
            }
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($byTitle as $name => $items) {
            if ($this->season($name) !== $season) {
                continue;
            }

            $ratio = 0.0;
            similar_text($base, $name, $ratio);

            $lenRatio = min(mb_strlen($base), mb_strlen($name)) / max(1, max(mb_strlen($base), mb_strlen($name)));

            if ($ratio >= 86 && $lenRatio >= 0.82 && $ratio > $bestScore) {
                $best = $items[0];
                $bestScore = $ratio;
            }
        }

        return $best;
    }

    /**
     * Nomor season dari judul (0 = tidak disebut).
     */
    protected function season(string $normalized): int
    {
        if (preg_match('/\bseason\s*(\d{1,2})\b/u', $normalized, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/\bs(\d{1,2})\b/u', $normalized, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    /**
     * Episode dengan nomor yang sama. Kalau nomornya tidak ada, jangan menebak:
     * riwayat dibiarkan di arsip supaya tidak melanjutkan di episode yang salah.
     */
    protected function pickEpisode(array $episodes, int $number): ?array
    {
        foreach ($episodes as $ep) {
            if ((int) $ep['number'] === $number) {
                return $ep;
            }
        }

        return null;
    }
}
