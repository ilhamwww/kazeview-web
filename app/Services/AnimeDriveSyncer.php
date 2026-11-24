<?php

namespace App\Services;

use App\Models\DriveEpisode;
use Illuminate\Support\Facades\Log;

/**
 * Sinkronisasi episode anime dari Otakudesu ke Google Drive.
 *
 * Alur: cari episode → ambil link 1080p (PixelDrain) → unduh → remux MP4 bila
 * MKV → unggah ke Drive → verifikasi MD5 → hapus file lokal → simpan mapping.
 */
class AnimeDriveSyncer
{
    public function __construct(
        protected ?OtakudesuScraperService $otaku = null,
    ) {
        $this->otaku = $otaku ?: new OtakudesuScraperService();
    }

    /* ------------------------------------------------------------------ */
    /* Pencocokan judul                                                    */
    /* ------------------------------------------------------------------ */

    public static function norm(string $t): string
    {
        $t = mb_strtolower($t);
        $t = preg_replace('/\b(sub\s*indo|subtitle\s*indonesia|batch)\b/u', ' ', $t);
        $t = preg_replace('/[^a-z0-9]+/u', ' ', $t);

        return trim(preg_replace('/\s+/', ' ', $t));
    }

    /** @return array{0:string,1:int,2:int} [base, season, part] */
    public static function parseTitle(string $t): array
    {
        $t = self::norm($t);

        $season = 0;
        if (preg_match('/\bseason\s*(\d+)\b/', $t, $m)) {
            $season = (int) $m[1];
        } elseif (preg_match('/\b(\d+)(st|nd|rd|th)\s*season\b/', $t, $m)) {
            $season = (int) $m[1];
        } elseif (preg_match('/\bs(\d+)\b/', $t, $m)) {
            $season = (int) $m[1];
        }

        $part = 0;
        if (preg_match('/\bpart\s*(\d+)\b/', $t, $m)) {
            $part = (int) $m[1];
        }

        $base = preg_replace('/\b(season\s*\d+|\d+(st|nd|rd|th)\s*season|part\s*\d+|s\d+)\b/', ' ', $t);
        $base = trim(preg_replace('/\s+/', ' ', $base));

        return [$base, $season, $part];
    }

    /**
     * Cari anime di katalog Animekompi untuk judul Otakudesu.
     * Season dan part harus sama supaya tidak salah map.
     */
    public function matchAnimek(string $otakuTitle, ?array $index = null): ?array
    {
        $catalog = $index ?: $this->catalogIndex();
        [$base, $season, $part] = self::parseTitle($otakuTitle);

        $candidates = $catalog[$base] ?? [];

        if (! $candidates) {
            $best = 0;
            $bestKey = null;
            foreach ($catalog as $key => $list) {
                similar_text($base, $key, $pct);
                if ($pct > $best) {
                    $best = $pct;
                    $bestKey = $key;
                }
            }
            if ($best >= 88 && $bestKey !== null) {
                $candidates = $catalog[$bestKey];
            }
        }

        foreach ($candidates as $c) {
            if ($c['season'] === $season && $c['part'] === $part) {
                return $c['item'];
            }
        }

        return null;
    }

    /** @return array<string, array<int, array{season:int,part:int,item:array}>> */
    protected function catalogIndex(): array
    {
        $index = [];

        foreach (AnimekompiService::catalog() as $item) {
            [$base, $season, $part] = self::parseTitle((string) ($item['title'] ?? ''));
            if ($base === '') {
                continue;
            }
            $index[$base][] = ['season' => $season, 'part' => $part, 'item' => $item];
        }

        return $index;
    }

    public function otakuOngoing(int $page = 1): array
    {
        return $this->otaku->scrapeAnimeList($page, 'ongoing');
    }

    public function otakuSearch(string $query): array
    {
        return $this->otaku->searchAnime($query);
    }

    /** @return array<int, string> nomor episode => URL episode Otakudesu */
    public function otakuEpisodes(string $animeUrl): array
    {
        $detail = $this->otaku->scrapeAnimeSeriesDetail($animeUrl);
        $map = [];

        foreach (($detail['episodes'] ?? []) as $ep) {
            $num = (int) preg_replace('/\D+/', '', (string) ($ep['episode'] ?? ''));
            $url = (string) ($ep['url'] ?? '');

            if ($num > 0 && $url !== '') {
                $map[$num] = $url;
            }
        }

        return $map;
    }

    public function catalogIndexPublic(): array
    {
        return $this->catalogIndex();
    }

    /* ------------------------------------------------------------------ */
    /* Episode                                                             */
    /* ------------------------------------------------------------------ */

    /** Daftar episode Animekompi: nomor => episode id. */
    public function animekEpisodes(string $path): array
    {
        $detail = AnimekompiService::detail($path);
        $map = [];

        foreach (($detail['episodes'] ?? []) as $ep) {
            $num = (int) ($ep['number'] ?? 0);
            if ($num > 0 && ! empty($ep['id'])) {
                $map[$num] = (string) $ep['id'];
            }
        }

        return $map;
    }

    /**
     * Cari link 1080p yang bisa dipakai (PixelDrain).
     *
     * @return array{url:string,ext:string,size:?int}|null
     */
    public function best1080(string $episodeUrl): ?array
    {
        $detail = $this->otaku->scrapeEpisodeDetail($episodeUrl);

        if (! $detail) {
            return null;
        }

        $best = null;

        foreach (($detail['downloads'] ?? []) as $d) {
            $format = strtolower((string) ($d['format'] ?? ''));
            if (! str_contains($format, '1080')) {
                continue;
            }

            $isMkv = str_contains($format, 'mkv');
            if ($best === null || (! $isMkv && $best['ext'] === 'mkv')) {
                $best = ['format' => $format, 'is_mkv' => $isMkv, 'links' => $d['links'] ?? []];
            }
        }

        if (! $best) {
            return null;
        }

        foreach ($best['links'] as $link) {
            if (! preg_match('#^https?://#i', (string) ($link['url'] ?? ''))) {
                continue;
            }

            $host = (string) ($link['host'] ?? '');
            $final = $this->followRedirect((string) $link['url']);

            if (! $final || ! str_contains($final, 'pixeldrain.com')) {
                continue;
            }

            $id = basename(parse_url($final, PHP_URL_PATH) ?: '');

            if ($id === '') {
                continue;
            }

            $info = $this->pixeldrainInfo($id);

            return [
                'url' => 'https://pixeldrain.com/api/file/' . $id . '?download',
                'ext' => $best['is_mkv'] ? 'mkv' : 'mp4',
                'size' => $info['size'] ?? null,
                'host' => $host,
                'id' => $id,
            ];
        }

        return null;
    }

    protected function followRedirect(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $out = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno || ! $out) {
            return null;
        }

        if (preg_match('/^location:\s*(\S+)/im', (string) $out, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    protected function pixeldrainInfo(string $id): ?array
    {
        $res = \Illuminate\Support\Facades\Http::timeout(20)
            ->get('https://pixeldrain.com/api/file/' . $id . '/info');

        return $res->successful() ? $res->json() : null;
    }

    /* ------------------------------------------------------------------ */
    /* Sinkronisasi satu episode                                           */
    /* ------------------------------------------------------------------ */

    /**
     * @return array{status:string,message:string,bytes?:int}
     */
    public function syncEpisode(string $animePath, string $animeTitle, int $season, int $epNumber, string $episodeId, string $otakuEpisodeUrl, bool $dryRun = false): array
    {
        $source = $this->best1080($otakuEpisodeUrl);

        if (! $source) {
            return ['status' => 'skip', 'message' => '1080p tidak tersedia'];
        }

        if ($dryRun) {
            return [
                'status' => 'dry',
                'message' => "{$source['ext']} " . ($source['size'] ? round($source['size'] / 1048576, 1) . ' MB' : '?') . " dari {$source['host']}",
            ];
        }

        $staging = storage_path('app/drive-staging');
        if (! is_dir($staging)) {
            mkdir($staging, 0775, true);
        }

        $base = preg_replace('/[^A-Za-z0-9 ._-]+/', '', $animeTitle) ?: $animePath;
        $base = trim(preg_replace('/\s+/', ' ', $base));
        $safe = $base . ' - S' . str_pad((string) $season, 2, '0', STR_PAD_LEFT) . 'E' . str_pad((string) $epNumber, 2, '0', STR_PAD_LEFT) . ' [1080p]';

        $download = $staging . '/' . $safe . '.' . $source['ext'];

        try {
            $this->download($source['url'], $download);

            if ($source['ext'] === 'mkv') {
                $mp4 = $staging . '/' . $safe . '.mp4';
                $this->remux($download, $mp4);
                @unlink($download);
                $download = $mp4;
            }

            $size = filesize($download);
            $md5 = md5_file($download);

            $dest = 'gdrive:KZSTRM/Anime/' . $base . '/Season ' . str_pad((string) $season, 2, '0', STR_PAD_LEFT) . '/' . $safe . '.mp4';
            $this->rcloneCopy($download, $dest);
            $remote = $this->rcloneStat($dest);

            if (! $remote || (int) $remote['Size'] !== (int) $size || strtolower((string) ($remote['Hashes']['md5'] ?? '')) !== $md5) {
                throw new \RuntimeException('Verifikasi Drive gagal (ukuran/MD5 tidak sama)');
            }

            $relPath = 'Anime/' . $base . '/Season ' . str_pad((string) $season, 2, '0', STR_PAD_LEFT) . '/' . $safe . '.mp4';

            DriveEpisode::updateOrCreate(
                ['episode_id' => $episodeId],
                [
                    'anime_path' => $animePath,
                    'title' => $animeTitle . ' Episode ' . $epNumber,
                    'rel_path' => $relPath,
                    'quality' => '1080p',
                    'size_bytes' => $size,
                    'md5' => $md5,
                ]
            );

            @unlink($download);

            return ['status' => 'ok', 'message' => round($size / 1048576, 1) . ' MB → ' . $relPath, 'bytes' => $size];
        } catch (\Throwable $e) {
            @unlink($download);
            Log::warning('Drive sync gagal', ['anime' => $animePath, 'ep' => $epNumber, 'error' => $e->getMessage()]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function download(string $url, string $path): void
    {
        $fp = fopen($path, 'wb');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 900,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_FAILONERROR => true,
        ]);
        $ok = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (! $ok || ! is_file($path) || filesize($path) === 0) {
            throw new \RuntimeException('Unduh gagal: ' . ($err ?: 'file kosong'));
        }
    }

    protected function remux(string $input, string $output): void
    {
        $cmd = sprintf(
            'ffmpeg -hide_banner -loglevel error -y -i %s -map 0:v:0 -map 0:a? -c copy -movflags +faststart %s',
            escapeshellarg($input),
            escapeshellarg($output)
        );
        exec($cmd, $out, $code);

        if ($code !== 0 || ! is_file($output) || filesize($output) === 0) {
            throw new \RuntimeException('Remux MP4 gagal');
        }
    }

    protected function rcloneCopy(string $local, string $dest): void
    {
        $cmd = sprintf('rclone --config /etc/rclone/kz-drive.conf copyto %s %s --drive-chunk-size 64M --stats-one-line 2>&1', escapeshellarg($local), escapeshellarg($dest));
        exec($cmd, $out, $code);

        if ($code !== 0) {
            throw new \RuntimeException('Upload Drive gagal: ' . implode(' ', array_slice($out, -2)));
        }
    }

    protected function rcloneStat(string $dest): ?array
    {
        $cmd = sprintf('rclone --config /etc/rclone/kz-drive.conf lsjson %s --hash 2>/dev/null', escapeshellarg($dest));
        exec($cmd, $out, $code);

        if ($code !== 0 || ! $out) {
            return null;
        }

        $data = json_decode(implode('', $out), true);

        if (is_array($data) && isset($data[0])) {
            return $data[0];
        }

        return is_array($data) ? $data : null;
    }
}
