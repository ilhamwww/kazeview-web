<?php

namespace App\Console\Commands;

use App\Services\ImageDiskCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bersih-bersih cache: gambar di disk, cache kedaluwarsa, dan sesi lama.
 */
class PruneCache extends Command
{
    protected $signature = 'kz:prune-cache {--days=7 : Umur maksimum cache gambar (hari)}';

    protected $description = 'Hapus cache gambar lama, cache kedaluwarsa, dan sesi yang tidak aktif';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $images = ImageDiskCache::prune($days);
        $legacy = ImageDiskCache::purgeLegacyDbCache();

        $expiredCache = 0;
        try {
            $expiredCache = DB::table('cache')
                ->where('expiration', '<', now()->getTimestamp())
                ->delete();
        } catch (\Throwable $e) {
        }

        $sessions = 0;
        try {
            $sessions = DB::table('sessions')
                ->where('last_activity', '<', now()->getTimestamp() - 604800)
                ->delete();
        } catch (\Throwable $e) {
        }

        $this->table(['Bagian', 'Dibersihkan'], [
            ['Gambar di disk', $images . ' berkas'],
            ['Cache gambar lama (DB)', $legacy . ' baris'],
            ['Cache kedaluwarsa', $expiredCache . ' baris'],
            ['Sesi > 7 hari', $sessions . ' baris'],
        ]);

        return self::SUCCESS;
    }
}
