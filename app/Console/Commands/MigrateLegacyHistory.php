<?php

namespace App\Console\Commands;

use App\Models\WatchHistory;
use App\Services\ChitogenxService;
use Illuminate\Console\Command;

class MigrateLegacyHistory extends Command
{
    protected $signature = 'streaming:migrate-history {--apply : Tulis perubahan ke database}';

    protected $description = 'Pindahkan riwayat tontonan lama (slug provider lama) ke slug Chitogenx';

    public function handle(): int
    {
        $known = [];
        foreach (['movie', 'series'] as $type) {
            foreach (ChitogenxService::pool($type) as $item) {
                $known[$item['slug']] = $item;
            }
        }

        $this->info('Slug Chitogenx tersedia: ' . count($known));

        $rows = WatchHistory::whereIn('item_type', ['movie', 'series'])->get();
        $changed = 0;
        $unmatched = [];

        foreach ($rows as $row) {
            $id = (string) $row->item_id;

            if (isset($known[$id]) || preg_match('/^(rb-|mb-|ix-)/', $id)) {
                continue;
            }

            $target = null;

            // 1) Buang akhiran tahun ala provider lama: "judul-2026" -> "judul"
            if (preg_match('/^(.*)-(\d{4})$/', $id, $m) && isset($known[$m[1]])) {
                $target = $m[1];
            }

            // 2) Cari lewat judul di Chitogenx
            if ($target === null && !empty($row->title)) {
                $results = ChitogenxService::search((string) $row->title, 1, 6)['data'] ?? [];

                foreach ($results as $candidate) {
                    if ($this->sameTitle((string) $row->title, (string) $candidate['title'])) {
                        $target = $candidate['slug'];
                        break;
                    }
                }
            }

            if ($target === null) {
                $unmatched[] = $id . ' | ' . $row->title;
                continue;
            }

            $row->item_id = $target;

            // Kalau user sudah punya baris untuk slug baru, gabungkan agar tidak
            // melanggar unique(user_id, item_id, item_type).
            $existing = WatchHistory::where('user_id', $row->user_id)
                ->where('item_type', $row->item_type)
                ->where('item_id', $target)
                ->where('id', '!=', $row->id)
                ->first();

            if ($existing) {
                $keepRow = $row;
                $dropRow = $existing;

                if (($existing->updated_at?->timestamp ?? 0) > ($row->updated_at?->timestamp ?? 0)) {
                    $keepRow = $existing;
                    $dropRow = $row;
                }

                if ($keepRow->progress_seconds < $dropRow->progress_seconds) {
                    $keepRow->progress_seconds = $dropRow->progress_seconds;
                    $keepRow->duration_seconds = $dropRow->duration_seconds;
                    $keepRow->progress_percent = $dropRow->progress_percent;
                }

                if ($this->option('apply')) {
                    $keepRow->save();
                    $dropRow->delete();
                }

                $changed++;
                $this->line("  {$id} -> {$target} (digabung)");

                continue;
            }

            if (!empty($known[$target]['thumbnail'])) {
                $row->thumbnail = $known[$target]['thumbnail'];
            }

            if ($row->item_type === 'series') {
                $ep = (int) ($row->last_episode ?: 1);
                if (preg_match('#/season/(\d+)/episode/(\d+)#', (string) $row->url, $m)) {
                    $row->url = '/series/' . $target . '/season/' . $m[1] . '/episode/' . $m[2];
                    $row->last_episode = $m[2];
                } else {
                    $row->url = '/series/' . $target . '/season/1/episode/' . $ep;
                }
            } else {
                $row->url = '/movie/' . $target;
            }

            if ($this->option('apply')) {
                $row->save();
            }

            $changed++;
            $this->line("  {$id} -> {$target}");
        }

        $this->info(($this->option('apply') ? 'Diperbarui' : 'Akan diperbarui') . ': ' . $changed);
        $this->info('Tidak ketemu padanan: ' . count($unmatched));

        foreach (array_slice($unmatched, 0, 15) as $line) {
            $this->line('  ! ' . $line);
        }

        return self::SUCCESS;
    }

    private function sameTitle(string $a, string $b): bool
    {
        $norm = function (string $s): string {
            $s = strtolower($s);
            $s = preg_replace('/\[[^\]]*\]|\([^)]*\)/', ' ', $s);
            $s = preg_replace('/\b(s\d+(-s\d+)?|season\s*\d+)\b/', ' ', $s);

            return trim(preg_replace('/[^a-z0-9]+/', ' ', $s));
        };

        return $norm($a) === $norm($b);
    }
}
